<?php
/* ============================================================
   GameHub — admin.php  |  Admin REST API
   All admin CRUD operations: games, users, reviews, categories
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// All admin API endpoints require admin role
requireAdmin();

$db     = getDB();
$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    // ── Dashboard stats ─────────────────────────────────────
    case 'stats':
        getDashboardStats($db);
        break;

    // ── Games CRUD ──────────────────────────────────────────
    case 'list_games':     listGames($db);         break;
    case 'get_game':       getGame($db);            break;
    case 'add_game':       addGame($db, $body);     break;
    case 'edit_game':      editGame($db, $body);    break;
    case 'delete_game':    deleteGame($db, $body);  break;
    case 'toggle_trending':toggleTrending($db,$body);break;

    // ── Users management ────────────────────────────────────
    case 'list_users':     listUsers($db);          break;
    case 'get_user':       getUser($db);            break;
    case 'update_user':    updateUser($db, $body);  break;
    case 'delete_user':    deleteUser($db, $body);  break;
    case 'ban_user':       banUser($db, $body);     break;

    // ── Reviews management ───────────────────────────────────
    case 'list_reviews':   listReviews($db);        break;
    case 'delete_review':  deleteReviewAdmin($db, $body); break;
    case 'approve_review': approveReview($db, $body);     break;

    // ── Categories ──────────────────────────────────────────
    case 'list_categories':   listCategories($db);            break;
    case 'add_category':      addCategory($db, $body);        break;
    case 'edit_category':     editCategory($db, $body);       break;
    case 'delete_category':   deleteCategory($db, $body);     break;

    // ── Analytics ───────────────────────────────────────────
    case 'analytics':         getAnalytics($db);              break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action: ' . $action], 400);
}

// ═══════════════════════════════════════════════════════════════
// DASHBOARD STATS
// ═══════════════════════════════════════════════════════════════
function getDashboardStats(PDO $db): void {
    $stats = [
        'total_users'    => (int)$db->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn(),
        'total_games'    => (int)$db->query('SELECT COUNT(*) FROM games')->fetchColumn(),
        'total_reviews'  => (int)$db->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
        'total_wishlist' => (int)$db->query('SELECT COUNT(*) FROM wishlist')->fetchColumn(),
    ];

    // Top wishlisted games
    $topWishlist = $db->query(
        'SELECT g.title, COUNT(w.id) AS count
         FROM wishlist w JOIN games g ON w.game_id = g.id
         GROUP BY g.id ORDER BY count DESC LIMIT 5'
    )->fetchAll();

    // Top genres
    $topGenres = $db->query(
        'SELECT genre, COUNT(*) AS count FROM games GROUP BY genre ORDER BY count DESC LIMIT 6'
    )->fetchAll();

    // Recent users
    $recentUsers = $db->query(
        'SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();

    // Recent reviews
    $recentReviews = $db->query(
        'SELECT r.id, r.rating, r.comment, r.created_at, u.username, g.title AS game_title
         FROM reviews r JOIN users u ON r.user_id=u.id JOIN games g ON r.game_id=g.id
         ORDER BY r.created_at DESC LIMIT 5'
    )->fetchAll();

    jsonResponse([
        'success'        => true,
        'stats'          => $stats,
        'top_wishlist'   => $topWishlist,
        'top_genres'     => $topGenres,
        'recent_users'   => $recentUsers,
        'recent_reviews' => $recentReviews,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// GAMES CRUD
// ═══════════════════════════════════════════════════════════════
function listGames(PDO $db): void {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['q'] ?? '');

    $where  = $search ? 'WHERE title LIKE ?' : '';
    $params = $search ? ["%$search%"] : [];

    $total = (int)$db->prepare("SELECT COUNT(*) FROM games $where")->execute($params) ? (function() use ($db, $where, $params) {
        $s = $db->prepare("SELECT COUNT(*) FROM games $where"); $s->execute($params); return (int)$s->fetchColumn();
    })() : 0;

    $stmt = $db->prepare("SELECT * FROM games $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->execute([...$params, $limit, $offset]);

    jsonResponse([
        'success' => true,
        'games'   => $stmt->fetchAll(),
        'total'   => $total,
        'page'    => $page,
        'pages'   => (int)ceil($total / $limit),
    ]);
}

function getGame(PDO $db): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID required'], 400);
    $stmt = $db->prepare('SELECT * FROM games WHERE id = ?');
    $stmt->execute([$id]);
    $game = $stmt->fetch();
    if (!$game) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
    jsonResponse(['success' => true, 'game' => $game]);
}

function addGame(PDO $db, array $body): void {
    $required = ['title', 'genre'];
    foreach ($required as $f) {
        if (empty($body[$f])) jsonResponse(['success' => false, 'message' => ucfirst($f) . ' is required'], 400);
    }

    $stmt = $db->prepare(
        'INSERT INTO games (title, genre, description, rating, release_date, image_url,
                            cracked_link, min_ram, min_gpu, min_cpu, min_storage, is_trending, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        sanitize($body['title']),
        sanitize($body['genre']),
        sanitize($body['description'] ?? ''),
        (float)($body['rating'] ?? 0),
        !empty($body['release_date']) ? $body['release_date'] : null,
        sanitize($body['image_url']     ?? ''),
        sanitize($body['cracked_link']  ?? ''),   // FIX: was missing, caused column/value mismatch
        (int)($body['min_ram']      ?? 8),
        sanitize($body['min_gpu']   ?? ''),
        sanitize($body['min_cpu']   ?? ''),
        (int)($body['min_storage']  ?? 50),
        (int)($body['is_trending']  ?? 0),
    ]);

    jsonResponse(['success' => true, 'message' => 'Game added successfully! 🎮', 'id' => (int)$db->lastInsertId()]);
}

function editGame(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Game ID required'], 400);

    $stmt = $db->prepare(
        'UPDATE games SET title=?, genre=?, description=?, rating=?, release_date=?,
                          image_url=?, cracked_link=?, min_ram=?, min_gpu=?, min_cpu=?, min_storage=?, is_trending=?
         WHERE id=?'
    );
    $stmt->execute([
        sanitize($body['title'] ?? ''),
        sanitize($body['genre'] ?? ''),
        sanitize($body['description'] ?? ''),
        (float)($body['rating'] ?? 0),
        !empty($body['release_date']) ? $body['release_date'] : null,
        sanitize($body['image_url'] ?? ''),
        sanitize($body['cracked_link'] ?? ''),
        sanitize($body['min_ram'] ?? '8'),
        sanitize($body['min_gpu'] ?? ''),
        sanitize($body['min_cpu'] ?? ''),
        sanitize($body['min_storage'] ?? '50'),
        (int)($body['is_trending'] ?? 0),
        $id,
    ]);

    jsonResponse(['success' => true, 'message' => 'Game updated successfully!']);
}

function deleteGame(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Game ID required'], 400);

    // Remove from wishlist first (FK safety)
    $db->prepare('DELETE FROM wishlist WHERE game_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM reviews  WHERE game_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM games    WHERE id = ?')->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'Game deleted successfully!']);
}

function toggleTrending(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Game ID required'], 400);
    $db->prepare('UPDATE games SET is_trending = NOT is_trending WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Trending status toggled.']);
}

// ═══════════════════════════════════════════════════════════════
// USERS MANAGEMENT
// ═══════════════════════════════════════════════════════════════
function listUsers(PDO $db): void {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['q'] ?? '');

    $where  = $search ? 'WHERE username LIKE ? OR email LIKE ?' : '';
    $params = $search ? ["%$search%", "%$search%"] : [];

    $cStmt = $db->prepare("SELECT COUNT(*) FROM users $where");
    $cStmt->execute($params);
    $total = (int)$cStmt->fetchColumn();

    $stmt = $db->prepare("SELECT id, username, email, role, created_at, last_login FROM users $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->execute([...$params, $limit, $offset]);

    jsonResponse([
        'success' => true,
        'users'   => $stmt->fetchAll(),
        'total'   => $total,
        'page'    => $page,
        'pages'   => (int)ceil($total / $limit),
    ]);
}

function getUser(PDO $db): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID required'], 400);
    $stmt = $db->prepare('SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    jsonResponse(['success' => true, 'user' => $user]);
}

function updateUser(PDO $db, array $body): void {
    $id   = (int)($body['id'] ?? 0);
    $role = in_array($body['role'] ?? '', ['admin','user']) ? $body['role'] : 'user';
    if (!$id) jsonResponse(['success' => false, 'message' => 'User ID required'], 400);

    // Prevent self-demotion
    if ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'You cannot change your own admin role.'], 403);
    }

    $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
    jsonResponse(['success' => true, 'message' => 'User updated successfully.']);
}

function deleteUser(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'User ID required'], 400);

    if ($id === (int)$_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
    }

    $db->prepare('DELETE FROM wishlist       WHERE user_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM reviews        WHERE user_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM gamer_profiles WHERE user_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM users          WHERE id = ?')->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'User deleted successfully.']);
}

function banUser(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'User ID required'], 400);
    // Toggle banned role (set to 'banned' or back to 'user')
    $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    $newRole = ($user['role'] === 'banned') ? 'user' : 'banned';
    $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $id]);
    $action = $newRole === 'banned' ? 'banned' : 'unbanned';
    jsonResponse(['success' => true, 'message' => "User $action successfully.", 'new_role' => $newRole]);
}

// ═══════════════════════════════════════════════════════════════
// REVIEWS MANAGEMENT
// ═══════════════════════════════════════════════════════════════
function listReviews(PDO $db): void {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    $total = (int)$db->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
    $stmt  = $db->prepare(
        'SELECT r.id, r.rating, r.comment, r.created_at, r.is_approved,
                u.username, u.id AS user_id,
                g.title AS game_title, g.id AS game_id
         FROM reviews r
         JOIN users u ON r.user_id = u.id
         JOIN games g ON r.game_id = g.id
         ORDER BY r.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$limit, $offset]);

    jsonResponse([
        'success' => true,
        'reviews' => $stmt->fetchAll(),
        'total'   => $total,
        'page'    => $page,
        'pages'   => (int)ceil($total / $limit),
    ]);
}

function deleteReviewAdmin(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Review ID required'], 400);
    $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Review deleted.']);
}

function approveReview(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Review ID required'], 400);
    $db->prepare('UPDATE reviews SET is_approved = NOT is_approved WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Review approval status toggled.']);
}

// ═══════════════════════════════════════════════════════════════
// CATEGORIES
// ═══════════════════════════════════════════════════════════════
function listCategories(PDO $db): void {
    $stmt = $db->query(
        'SELECT c.*, COUNT(g.id) AS game_count
         FROM categories c
         LEFT JOIN games g ON g.genre = c.name
         GROUP BY c.id ORDER BY c.name ASC'
    );
    jsonResponse(['success' => true, 'categories' => $stmt->fetchAll()]);
}

function addCategory(PDO $db, array $body): void {
    $name = sanitize($body['name'] ?? '');
    if (!$name) jsonResponse(['success' => false, 'message' => 'Category name required'], 400);
    $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)')->execute([$name, sanitize($body['description'] ?? '')]);
    jsonResponse(['success' => true, 'message' => 'Category added!', 'id' => (int)$db->lastInsertId()]);
}

function editCategory(PDO $db, array $body): void {
    $id   = (int)($body['id'] ?? 0);
    $name = sanitize($body['name'] ?? '');
    if (!$id || !$name) jsonResponse(['success' => false, 'message' => 'ID and name required'], 400);
    $db->prepare('UPDATE categories SET name=?, description=? WHERE id=?')->execute([$name, sanitize($body['description'] ?? ''), $id]);
    jsonResponse(['success' => true, 'message' => 'Category updated!']);
}

function deleteCategory(PDO $db, array $body): void {
    $id = (int)($body['id'] ?? $_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Category ID required'], 400);
    $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Category deleted.']);
}

// ═══════════════════════════════════════════════════════════════
// ANALYTICS
// ═══════════════════════════════════════════════════════════════
function getAnalytics(PDO $db): void {
    // Monthly user registrations (last 6 months)
    $monthlyUsers = $db->query(
        'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS count
         FROM users
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
         GROUP BY month ORDER BY month ASC'
    )->fetchAll();

    // Top rated games
    $topRated = $db->query(
        'SELECT title, genre, rating FROM games ORDER BY rating DESC LIMIT 10'
    )->fetchAll();

    // Most wishlisted
    $mostWishlisted = $db->query(
        'SELECT g.title, g.genre, COUNT(w.id) AS wishlist_count
         FROM wishlist w JOIN games g ON w.game_id = g.id
         GROUP BY g.id ORDER BY wishlist_count DESC LIMIT 10'
    )->fetchAll();

    // Genre distribution
    $genreDistrib = $db->query(
        'SELECT genre, COUNT(*) AS count FROM games GROUP BY genre ORDER BY count DESC'
    )->fetchAll();

    // Review activity (last 6 months)
    $reviewActivity = $db->query(
        'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS count, ROUND(AVG(rating),1) AS avg_rating
         FROM reviews
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
         GROUP BY month ORDER BY month ASC'
    )->fetchAll();

    // Most reviewed games
    $mostReviewed = $db->query(
        'SELECT g.title, g.genre, COUNT(r.id) AS review_count, ROUND(AVG(r.rating),1) AS avg_rating
         FROM reviews r JOIN games g ON r.game_id = g.id
         GROUP BY g.id ORDER BY review_count DESC LIMIT 10'
    )->fetchAll();

    jsonResponse([
        'success'          => true,
        'monthly_users'    => $monthlyUsers,
        'top_rated'        => $topRated,
        'most_wishlisted'  => $mostWishlisted,
        'genre_distrib'    => $genreDistrib,
        'review_activity'  => $reviewActivity,
        'most_reviewed'    => $mostReviewed,
    ]);
}