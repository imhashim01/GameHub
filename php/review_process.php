<?php
/* ============================================================
   GameHub — review_process.php  |  Reviews & Ratings
   FIXES:
   • Removed backslash typo on myReviews(\$db) → myReviews($db)
   • Added updated_at column to INSERT (some schemas need it)
   • Better error messages returned to frontend
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
    case 'submit':     submitReview($db, $body); break;
    case 'list':       listReviews($db);         break;
    case 'delete':     deleteReview($db, $body); break;
    case 'vote':       voteReview($db, $body);   break;
    case 'my_reviews': myReviews($db);           break;  // ← FIXED: removed backslash
    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}

// ── Submit / Update Review ────────────────────────────────────
function submitReview(PDO $db, array $body): void {
    $userId  = currentUserId();
    $gameId  = (int)($body['game_id'] ?? 0);
    $rating  = (int)($body['rating']  ?? 0);
    $comment = trim($body['comment']  ?? '');

    // Must be logged in
    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'You must be logged in to write a review.', 'auth' => false]);
    }

    // Validate inputs
    if (!$gameId) {
        jsonResponse(['success' => false, 'message' => 'Invalid game ID.'], 400);
    }
    if ($rating < 1 || $rating > 5) {
        jsonResponse(['success' => false, 'message' => 'Please select a star rating (1–5).'], 400);
    }
    if (strlen($comment) < 10) {
        jsonResponse(['success' => false, 'message' => 'Review must be at least 10 characters long.'], 400);
    }
    if (strlen($comment) > 1000) {
        jsonResponse(['success' => false, 'message' => 'Review must be under 1000 characters.'], 400);
    }

    // Check game exists
    $gameCheck = $db->prepare('SELECT id FROM games WHERE id = ? LIMIT 1');
    $gameCheck->execute([$gameId]);
    if (!$gameCheck->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Game not found.'], 404);
    }

    try {
        // Check for existing review by this user for this game
        $existing = $db->prepare('SELECT id FROM reviews WHERE user_id = ? AND game_id = ? LIMIT 1');
        $existing->execute([$userId, $gameId]);

        if ($existing->fetch()) {
            // UPDATE existing review
            $db->prepare(
                'UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW()
                 WHERE user_id = ? AND game_id = ?'
            )->execute([$rating, sanitize($comment), $userId, $gameId]);
            $message = 'Your review has been updated!';
        } else {
            // INSERT new review — try with updated_at first, fallback without
            try {
                $db->prepare(
                    'INSERT INTO reviews (user_id, game_id, rating, comment, created_at, updated_at)
                     VALUES (?, ?, ?, ?, NOW(), NOW())'
                )->execute([$userId, $gameId, $rating, sanitize($comment)]);
            } catch (PDOException $e) {
                // Some schemas don't have updated_at — try without it
                $db->prepare(
                    'INSERT INTO reviews (user_id, game_id, rating, comment, created_at)
                     VALUES (?, ?, ?, ?, NOW())'
                )->execute([$userId, $gameId, $rating, sanitize($comment)]);
            }
            $message = 'Review submitted successfully!';
        }

        // Recalculate and cache average rating on the game row
        $avgStmt = $db->prepare('SELECT ROUND(AVG(rating), 1) FROM reviews WHERE game_id = ?');
        $avgStmt->execute([$gameId]);
        $newRating = (float)$avgStmt->fetchColumn();
        $db->prepare('UPDATE games SET rating = ? WHERE id = ?')->execute([$newRating, $gameId]);

        jsonResponse(['success' => true, 'message' => $message, 'new_rating' => $newRating]);

    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// ── List Reviews for a Game ───────────────────────────────────
function listReviews(PDO $db): void {
    $gameId = (int)($_GET['game_id'] ?? 0);
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 10;
    $offset = ($page - 1) * $limit;

    if (!$gameId) {
        jsonResponse(['success' => false, 'message' => 'Game ID required.'], 400);
    }

    try {
        $stmt = $db->prepare(
            'SELECT r.id, r.rating, r.comment, r.created_at, r.updated_at,
                    r.helpful_votes, r.unhelpful_votes,
                    u.id AS user_id, u.username
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             WHERE r.game_id = ?
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$gameId, $limit, $offset]);

        $totalStmt = $db->prepare('SELECT COUNT(*) FROM reviews WHERE game_id = ?');
        $totalStmt->execute([$gameId]);
        $total = (int)$totalStmt->fetchColumn();

        $avgStmt = $db->prepare('SELECT ROUND(AVG(rating), 1) FROM reviews WHERE game_id = ?');
        $avgStmt->execute([$gameId]);
        $avg = (float)$avgStmt->fetchColumn();

        jsonResponse([
            'success'    => true,
            'reviews'    => $stmt->fetchAll(),
            'total'      => $total,
            'avg_rating' => $avg,
            'page'       => $page,
            'pages'      => (int)ceil($total / $limit),
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Could not load reviews: ' . $e->getMessage()], 500);
    }
}

// ── Delete Review ─────────────────────────────────────────────
function deleteReview(PDO $db, array $body): void {
    $userId   = currentUserId();
    $reviewId = (int)($body['review_id'] ?? 0);

    if (!$userId)   jsonResponse(['success' => false, 'message' => 'Login required.', 'auth' => false]);
    if (!$reviewId) jsonResponse(['success' => false, 'message' => 'Review ID required.'], 400);

    $user = currentUser();
    if ($user['role'] === 'admin') {
        $stmt = $db->prepare('DELETE FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
    } else {
        $stmt = $db->prepare('DELETE FROM reviews WHERE id = ? AND user_id = ?');
        $stmt->execute([$reviewId, $userId]);
    }

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Review not found or permission denied.'], 403);
    }

    jsonResponse(['success' => true, 'message' => 'Review deleted.']);
}

// ── Vote helpful / not helpful ────────────────────────────────
function voteReview(PDO $db, array $body): void {
    $userId   = currentUserId();
    $reviewId = (int)($body['review_id'] ?? 0);
    $voteType = $body['vote'] ?? '';

    if (!$userId)   jsonResponse(['success' => false, 'message' => 'Login required.', 'auth' => false]);
    if (!$reviewId) jsonResponse(['success' => false, 'message' => 'Review ID required.'], 400);

    // Whitelist column to prevent SQL injection
    $column = match($voteType) {
        'up'   => 'helpful_votes',
        'down' => 'unhelpful_votes',
        default => null,
    };

    if (!$column) {
        jsonResponse(['success' => false, 'message' => 'Invalid vote type. Use "up" or "down".'], 400);
    }

    $db->prepare("UPDATE reviews SET $column = $column + 1 WHERE id = ?")
       ->execute([$reviewId]);

    jsonResponse(['success' => true, 'message' => 'Vote recorded.']);
}

// ── My Reviews ────────────────────────────────────────────────
function myReviews(PDO $db): void {
    $userId = currentUserId();
    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'Login required', 'auth' => false]);
    }

    $stmt = $db->prepare(
        'SELECT r.id, r.rating, r.comment, r.created_at,
                g.id AS game_id, g.title AS game_title, g.genre, g.image_url
         FROM reviews r
         JOIN games g ON g.id = r.game_id
         WHERE r.user_id = ?
         ORDER BY r.created_at DESC
         LIMIT 20'
    );
    $stmt->execute([$userId]);
    jsonResponse(['success' => true, 'reviews' => $stmt->fetchAll()]);
}