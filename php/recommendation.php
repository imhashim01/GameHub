<?php
/* ============================================================
   GameHub — recommendation.php  |  Smart Game Discovery API
   ============================================================
   FIX LOG:
   • games table uses column "genre" (VARCHAR) — correct
   • Added "views" column to ORDER BY — now in schema
   • formatGame() returns all fields JS expects (rec_gpu, rec_ram etc.)
   • favorite_genres from users table used for personalisation
   • Removed invalid JOIN on category_id when genre col exists
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db     = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? 'get_recommendations';

switch ($action) {
    case 'get_recommendations': getRecommendations($db); break;
    case 'recommendations':     getRecommendations($db); break; // alias for dashboard
    case 'trending':            getTrending($db);         break;
    case 'new_releases':        getNewReleases($db);      break;
    case 'by_genre':            getByGenre($db);          break;
    case 'search':              searchGames($db);          break;
    case 'game_detail':         getGameDetail($db);        break;
    case 'all_games':           getAllGames($db);           break;
    case 'all':                 getAllGames($db);           break; // alias
    case 'genres':              getGenres($db);            break;
    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}

// ── Smart Personalised Recommendations ───────────────────────
function getRecommendations(PDO $db): void {
    startSession();
    $userId = currentUserId();

    if ($userId) {
        // Fetch user's favourite genres
        $uStmt = $db->prepare('SELECT favorite_genres FROM users WHERE id = ? LIMIT 1');
        $uStmt->execute([$userId]);
        $uRow   = $uStmt->fetch();
        $genres = array_filter(array_map('trim', explode(',', $uRow['favorite_genres'] ?? '')));

        // Games already wishlisted (exclude)
        $wStmt = $db->prepare('SELECT game_id FROM wishlist WHERE user_id = ?');
        $wStmt->execute([$userId]);
        $wishlisted = array_column($wStmt->fetchAll(), 'game_id');

        if ($genres) {
            $inClause      = implode(',', array_fill(0, count($genres), '?'));
            $excludeClause = $wishlisted
                ? 'AND g.id NOT IN (' . implode(',', array_fill(0, count($wishlisted), '?')) . ')'
                : '';

            $sql = "SELECT g.*,
                           COALESCE(AVG(r.rating), g.rating) AS avg_rating
                    FROM games g
                    LEFT JOIN reviews r ON r.game_id = g.id
                    WHERE g.genre IN ($inClause)
                    $excludeClause
                    GROUP BY g.id
                    ORDER BY avg_rating DESC, g.release_date DESC
                    LIMIT 12";

            $params = array_merge($genres, $wishlisted);
            $stmt   = $db->prepare($sql);
            $stmt->execute($params);
            $games  = $stmt->fetchAll();

            if (count($games) >= 4) {
                jsonResponse(['success' => true, 'games' => formatGames($games)]);
            }
        }
    }

    // Fallback: top-rated across all games
    $stmt = $db->prepare(
        'SELECT g.*, COALESCE(AVG(r.rating), g.rating) AS avg_rating
         FROM games g
         LEFT JOIN reviews r ON r.game_id = g.id
         GROUP BY g.id
         ORDER BY avg_rating DESC, COALESCE(g.views,0) DESC
         LIMIT 12'
    );
    $stmt->execute();
    jsonResponse(['success' => true, 'games' => formatGames($stmt->fetchAll())]);
}

// ── Trending Games ────────────────────────────────────────────
function getTrending(PDO $db): void {
    $stmt = $db->prepare(
        'SELECT g.*, COALESCE(AVG(r.rating), g.rating) AS avg_rating
         FROM games g
         LEFT JOIN reviews r ON r.game_id = g.id
         WHERE g.is_trending = 1
         GROUP BY g.id
         ORDER BY g.views DESC, avg_rating DESC
         LIMIT 8'
    );
    $stmt->execute();
    jsonResponse(['success' => true, 'games' => formatGames($stmt->fetchAll())]);
}

// ── New Releases ──────────────────────────────────────────────
function getNewReleases(PDO $db): void {
    $stmt = $db->prepare(
        'SELECT g.*, COALESCE(AVG(r.rating), g.rating) AS avg_rating
         FROM games g
         LEFT JOIN reviews r ON r.game_id = g.id
         GROUP BY g.id
         ORDER BY g.release_date DESC
         LIMIT 8'
    );
    $stmt->execute();
    jsonResponse(['success' => true, 'games' => formatGames($stmt->fetchAll())]);
}

// ── Games by Genre ────────────────────────────────────────────
function getByGenre(PDO $db): void {
    $genre = trim($_GET['genre'] ?? '');
    if (!$genre) jsonResponse(['success' => false, 'message' => 'Genre required'], 400);

    $stmt = $db->prepare(
        'SELECT g.*, COALESCE(AVG(r.rating), g.rating) AS avg_rating
         FROM games g
         LEFT JOIN reviews r ON r.game_id = g.id
         WHERE g.genre = ?
         GROUP BY g.id
         ORDER BY avg_rating DESC
         LIMIT 20'
    );
    $stmt->execute([$genre]);
    jsonResponse(['success' => true, 'games' => formatGames($stmt->fetchAll())]);
}

// ── Search & Filter ───────────────────────────────────────────
function searchGames(PDO $db): void {
    $q      = trim($_GET['q']          ?? '');
    $genre  = trim($_GET['genre']      ?? '');
    $rating = (float)($_GET['min_rating'] ?? 0);
    $sort   = $_GET['sort']            ?? 'rating';

    $where  = ['1=1'];
    $params = [];
    $having = [];

    if ($q) {
        $where[]  = '(g.title LIKE ? OR g.description LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($genre) {
        $where[]  = 'g.genre = ?';
        $params[] = $genre;
    }
    if ($rating > 0) {
        $having[] = 'avg_rating >= ?';
        $params[] = $rating;
    }

    $orderMap = [
        'rating'  => 'avg_rating DESC',
        'newest'  => 'g.release_date DESC',
        'oldest'  => 'g.release_date ASC',
        'title'   => 'g.title ASC',
        'popular' => 'g.views DESC',
    ];
    $order = $orderMap[$sort] ?? 'avg_rating DESC';

    $havingClause = $having ? 'HAVING ' . implode(' AND ', $having) : '';

    $sql = "SELECT g.*, COALESCE(AVG(r.rating), g.rating) AS avg_rating
            FROM games g
            LEFT JOIN reviews r ON r.game_id = g.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY g.id
            $havingClause
            ORDER BY $order
            LIMIT 50";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['success' => true, 'games' => formatGames($stmt->fetchAll())]);
}

// ── Game Detail ───────────────────────────────────────────────
function getGameDetail(PDO $db): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Game ID required'], 400);

    // Increment view counter
    try { $db->prepare('UPDATE games SET views = views + 1 WHERE id = ?')->execute([$id]); } catch(\Exception $e) {}

    $stmt = $db->prepare(
        'SELECT g.*,
                COALESCE(AVG(r.rating), g.rating) AS avg_rating,
                COUNT(r.id) AS review_count
         FROM games g
         LEFT JOIN reviews r ON r.game_id = g.id
         WHERE g.id = ?
         GROUP BY g.id'
    );
    $stmt->execute([$id]);
    $game = $stmt->fetch();

    if (!$game) jsonResponse(['success' => false, 'message' => 'Game not found'], 404);

    // Fetch reviews
    $rStmt = $db->prepare(
        'SELECT r.id, r.rating, r.comment, r.created_at, u.username
         FROM reviews r
         JOIN users u ON r.user_id = u.id
         WHERE r.game_id = ?
         ORDER BY r.created_at DESC
         LIMIT 10'
    );
    $rStmt->execute([$id]);

    jsonResponse([
        'success' => true,
        'game'    => formatGame($game),
        'reviews' => $rStmt->fetchAll(),
    ]);
}

// ── All Games paginated ───────────────────────────────────────
function getAllGames(PDO $db): void {
    $page   = max(1, (int)($_GET['page']  ?? 1));
    $limit  = min(200, max(1, (int)($_GET['limit'] ?? 100))); // default 100, max 200
    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare(
        'SELECT g.id, g.title, g.genre, g.description, g.rating, g.release_date,
                g.image_url, g.cracked_link, g.is_trending, g.category_id,
                g.min_ram, g.min_gpu, g.min_cpu, g.min_storage, g.views, g.created_at,
                COALESCE(AVG(r.rating), g.rating) AS avg_rating,
                COUNT(r.id) AS review_count
         FROM games g
         LEFT JOIN reviews r ON r.game_id = g.id
         GROUP BY g.id, g.title, g.genre, g.description, g.rating, g.release_date,
                  g.image_url, g.cracked_link, g.is_trending, g.category_id,
                  g.min_ram, g.min_gpu, g.min_cpu, g.min_storage, g.views, g.created_at
         ORDER BY g.id DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$limit, $offset]);
    $total = (int)$db->query('SELECT COUNT(*) FROM games')->fetchColumn();

    jsonResponse([
        'success' => true,
        'games'   => formatGames($stmt->fetchAll()),
        'total'   => $total,
        'page'    => $page,
        'pages'   => (int)ceil($total / $limit),
    ]);
}

// ── Distinct genres list ──────────────────────────────────────
function getGenres(PDO $db): void {
    $stmt = $db->query('SELECT DISTINCT genre FROM games ORDER BY genre ASC');
    jsonResponse(['success' => true, 'genres' => array_column($stmt->fetchAll(), 'genre')]);
}

// ── Formatters ────────────────────────────────────────────────
function formatGames(array $rows): array {
    return array_map('formatGame', $rows);
}

function formatGame(array $g): array {
    return [
        'id'           => (int)$g['id'],
        'title'        => $g['title'],
        'genre'        => $g['genre'],
        'description'  => $g['description'] ?? '',
        'rating'       => round((float)($g['avg_rating'] ?? $g['rating'] ?? 0), 1),
        'release_date' => $g['release_date'] ?? '',
        'release_year' => !empty($g['release_date']) ? substr($g['release_date'], 0, 4) : '',
        'image_url'    => $g['image_url'] ?? '',
        'cracked_link' => $g['cracked_link'] ?? '',
        'is_trending'  => (bool)($g['is_trending'] ?? false),
        'views'        => (int)($g['views'] ?? 0),
        'review_count' => (int)($g['review_count'] ?? 0),
        // PC requirements (min)
        'min_ram'      => $g['min_ram']     ?? '8',
        'min_gpu'      => $g['min_gpu']     ?? '',
        'min_cpu'      => $g['min_cpu']     ?? '',
        'min_storage'  => $g['min_storage'] ?? '50',
        // PC requirements (recommended)
        'rec_ram'      => $g['rec_ram']     ?? '',
        'rec_gpu'      => $g['rec_gpu']     ?? '',
        'rec_cpu'      => $g['rec_cpu']     ?? '',
        'rec_storage'  => $g['rec_storage'] ?? '',
    ];
}