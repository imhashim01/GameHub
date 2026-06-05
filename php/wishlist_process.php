<?php
/* ============================================================
   GameHub — wishlist_process.php  |  Wishlist CRUD
   ============================================================
   FIX LOG:
   • Added "get" as alias for "list" (called by wishlist.js)
   • Query uses correct column names from schema
   • Non-logged-in users get empty list (not error) for local fallback
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

startSession();

$db     = getDB();
$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'add':    addToWishlist($db, $body);     break;
    case 'remove': removeFromWishlist($db, $body); break;
    case 'toggle': toggleWishlist($db, $body);    break;
    case 'get':                                    // alias used by wishlist.js
    case 'list':   listWishlist($db);             break;
    case 'check':  checkWishlist($db);            break;
    case 'clear':  clearWishlist($db);            break;
    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}

// ── Add ───────────────────────────────────────────────────────
function addToWishlist(PDO $db, array $body): void {
    $userId = currentUserId();
    $gameId = (int)($body['game_id'] ?? 0);

    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'Login required to save games.', 'auth' => false]);
    }
    if (!$gameId) {
        jsonResponse(['success' => false, 'message' => 'Game ID required.'], 400);
    }

    // Verify game exists
    $check = $db->prepare('SELECT id FROM games WHERE id = ? LIMIT 1');
    $check->execute([$gameId]);
    if (!$check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Game not found.'], 404);
    }

    // Duplicate check
    $dup = $db->prepare('SELECT id FROM wishlist WHERE user_id = ? AND game_id = ? LIMIT 1');
    $dup->execute([$userId, $gameId]);
    if ($dup->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Already in your wishlist!', 'already_exists' => true]);
    }

    $db->prepare('INSERT INTO wishlist (user_id, game_id, added_at) VALUES (?, ?, NOW())')
       ->execute([$userId, $gameId]);

    jsonResponse(['success' => true, 'message' => 'Added to wishlist ♥', 'action' => 'added']);
}

// ── Remove ────────────────────────────────────────────────────
function removeFromWishlist(PDO $db, array $body): void {
    $userId = currentUserId();
    $gameId = (int)($body['game_id'] ?? 0);

    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'Login required.', 'auth' => false]);
    }
    if (!$gameId) {
        jsonResponse(['success' => false, 'message' => 'Game ID required.'], 400);
    }

    $db->prepare('DELETE FROM wishlist WHERE user_id = ? AND game_id = ?')
       ->execute([$userId, $gameId]);

    jsonResponse(['success' => true, 'message' => 'Removed from wishlist.', 'action' => 'removed']);
}

// ── Toggle ────────────────────────────────────────────────────
function toggleWishlist(PDO $db, array $body): void {
    $userId = currentUserId();
    $gameId = (int)($body['game_id'] ?? 0);

    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'Login required to use wishlist.', 'auth' => false]);
    }
    if (!$gameId) {
        jsonResponse(['success' => false, 'message' => 'Game ID required.'], 400);
    }

    $dup = $db->prepare('SELECT id FROM wishlist WHERE user_id = ? AND game_id = ? LIMIT 1');
    $dup->execute([$userId, $gameId]);

    if ($dup->fetch()) {
        $db->prepare('DELETE FROM wishlist WHERE user_id = ? AND game_id = ?')
           ->execute([$userId, $gameId]);
        jsonResponse(['success' => true, 'message' => 'Removed from wishlist.', 'action' => 'removed']);
    } else {
        $db->prepare('INSERT INTO wishlist (user_id, game_id, added_at) VALUES (?, ?, NOW())')
           ->execute([$userId, $gameId]);
        jsonResponse(['success' => true, 'message' => 'Added to wishlist ♥', 'action' => 'added']);
    }
}

// ── List (server-side, used for sync) ────────────────────────
function listWishlist(PDO $db): void {
    $userId = currentUserId();

    if (!$userId) {
        // Not logged in — return empty (JS uses localStorage as fallback)
        jsonResponse(['success' => false, 'auth' => false, 'items' => [], 'games' => []]);
    }

    $stmt = $db->prepare(
        'SELECT g.id, g.title, g.genre, g.rating, g.release_date, g.image_url,
                w.added_at,
                COALESCE(AVG(r.rating), g.rating) AS avg_rating
         FROM wishlist w
         JOIN games g    ON w.game_id = g.id
         LEFT JOIN reviews r ON r.game_id = g.id
         WHERE w.user_id = ?
         GROUP BY g.id, w.added_at
         ORDER BY w.added_at DESC'
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $items = array_map(fn($g) => [
        'game_id'      => (int)$g['id'],
        'title'        => $g['title'],
        'genre'        => $g['genre'],
        'rating'       => round((float)($g['avg_rating'] ?? $g['rating'] ?? 0), 1),
        'release_date' => $g['release_date'] ?? '',
        'image_url'    => $g['image_url'] ?? '',
        'added_at'     => $g['added_at'],
    ], $rows);

    jsonResponse(['success' => true, 'items' => $items, 'games' => $items]);
}

// ── Clear all wishlist items for current user ──────────────────
function clearWishlist(PDO $db): void {
    $userId = currentUserId();
    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'Login required.', 'auth' => false]);
    }
    $db->prepare('DELETE FROM wishlist WHERE user_id = ?')->execute([$userId]);
    jsonResponse(['success' => true, 'message' => 'Wishlist cleared.']);
}

// ── Check single game ─────────────────────────────────────────
function checkWishlist(PDO $db): void {
    $userId = currentUserId();
    $gameId = (int)($_GET['game_id'] ?? 0);

    if (!$userId || !$gameId) {
        jsonResponse(['success' => true, 'wishlisted' => false]);
    }

    $stmt = $db->prepare('SELECT id FROM wishlist WHERE user_id = ? AND game_id = ? LIMIT 1');
    $stmt->execute([$userId, $gameId]);
    jsonResponse(['success' => true, 'wishlisted' => (bool)$stmt->fetch()]);
}