<?php
/* ============================================================
   GameHub — php/partner_requests.php
   Full partner request system: send, accept, reject, list
   
   FIXED BUGS:
   1. File renamed from partner_request.php → partner_requests.php
      to match the URL called in partnerFinder.js
   2. Self-request check uses correct session user_id
   3. All queries use correct column name (u.id not u.user_id)
   4. partner_requests table CREATE added to gamehub.sql
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

startSession();

$db     = getDB();
$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? 'list_received';

switch ($action) {
    case 'send':          sendRequest($db, $body);                  break;
    case 'accept':        respondRequest($db, $body, 'accepted');   break;
    case 'reject':        respondRequest($db, $body, 'rejected');   break;
    case 'cancel':        cancelRequest($db, $body);                break;
    case 'list_received': listReceived($db);                        break;
    case 'list_sent':     listSent($db);                            break;
    case 'pending_count': pendingCount($db);                        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)], 400);
}

/* ── Send a partner request ─────────────────────────────────── */
function sendRequest(PDO $db, array $body): void {
    $senderId   = currentUserId();
    $receiverId = (int)($body['receiver_id'] ?? 0);
    $message    = sanitize($body['message'] ?? 'Hey! Want to play together? 🎮');

    // Not logged in
    if (!$senderId) {
        jsonResponse(['success' => false,
            'message' => 'You need to be logged in to send partner requests.',
            'auth'    => false]);
    }

    // Invalid receiver
    if (!$receiverId) {
        jsonResponse(['success' => false, 'message' => 'Invalid receiver ID.'], 400);
    }

    // Cannot send to yourself
    if ($senderId === $receiverId) {
        jsonResponse(['success' => false, 'message' => 'You cannot send a request to yourself! 😄'], 400);
    }

    // Verify receiver exists and is not banned
    $check = $db->prepare('SELECT id, username FROM users WHERE id = ? AND is_banned = 0 LIMIT 1');
    $check->execute([$receiverId]);
    $receiver = $check->fetch();
    if (!$receiver) {
        jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    }

    // Check for any existing request between these two users (either direction)
    $existing = $db->prepare(
        'SELECT id, status FROM partner_requests
         WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?)
         LIMIT 1'
    );
    $existing->execute([$senderId, $receiverId, $receiverId, $senderId]);
    $row = $existing->fetch();

    if ($row) {
        $statusMessages = [
            'pending'  => 'A request is already pending between you and this user.',
            'accepted' => 'You are already gaming partners with this user! 🎮',
            'rejected' => 'A previous request was rejected. You cannot send another.',
        ];
        jsonResponse(['success' => false,
            'message' => $statusMessages[$row['status']] ?? 'A request already exists.']);
    }

    // Insert the request
    $insert = $db->prepare(
        'INSERT INTO partner_requests (sender_id, receiver_id, message) VALUES (?, ?, ?)'
    );
    $insert->execute([$senderId, $receiverId, $message]);

    jsonResponse([
        'success'  => true,
        'message'  => "Partner request sent to {$receiver['username']}! 🎮 Waiting for their reply.",
        'receiver' => $receiver['username'],
    ]);
}

/* ── Accept or Reject a received request ────────────────────── */
function respondRequest(PDO $db, array $body, string $status): void {
    $userId    = currentUserId();
    $requestId = (int)($body['request_id'] ?? 0);

    if (!$userId)    jsonResponse(['success' => false, 'message' => 'Login required.', 'auth' => false]);
    if (!$requestId) jsonResponse(['success' => false, 'message' => 'request_id is required.'], 400);

    // Only the RECEIVER can accept or reject
    $stmt = $db->prepare(
        'SELECT pr.id, pr.sender_id,
                u.username AS sender_name
         FROM partner_requests pr
         JOIN users u ON pr.sender_id = u.id
         WHERE pr.id = ? AND pr.receiver_id = ? AND pr.status = "pending"
         LIMIT 1'
    );
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if (!$request) {
        jsonResponse(['success' => false,
            'message' => 'Request not found, or it has already been handled.'], 404);
    }

    $db->prepare('UPDATE partner_requests SET status = ? WHERE id = ?')
       ->execute([$status, $requestId]);

    $msg = ($status === 'accepted')
        ? "You are now gaming partners with {$request['sender_name']}! 🎮 Go play together!"
        : "Request from {$request['sender_name']} has been rejected.";

    jsonResponse(['success' => true, 'message' => $msg, 'status' => $status]);
}

/* ── Cancel a sent request ──────────────────────────────────── */
function cancelRequest(PDO $db, array $body): void {
    $userId    = currentUserId();
    $requestId = (int)($body['request_id'] ?? 0);

    if (!$userId)    jsonResponse(['success' => false, 'message' => 'Login required.', 'auth' => false]);
    if (!$requestId) jsonResponse(['success' => false, 'message' => 'request_id is required.'], 400);

    $stmt = $db->prepare(
        'DELETE FROM partner_requests WHERE id = ? AND sender_id = ? AND status = "pending"'
    );
    $stmt->execute([$requestId, $userId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Request not found or already handled.'], 404);
    }
    jsonResponse(['success' => true, 'message' => 'Request cancelled.']);
}

/* ── List requests RECEIVED by current user ─────────────────── */
function listReceived(PDO $db): void {
    $userId = currentUserId();
    if (!$userId) {
        jsonResponse(['success' => false, 'auth' => false, 'requests' => []]);
    }

    $stmt = $db->prepare(
        'SELECT pr.id,
                pr.message,
                pr.status,
                pr.created_at,
                u.id            AS sender_id,
                u.username      AS sender_name,
                gp.favorite_game AS sender_game,
                gp.play_style    AS sender_style,
                gp.playing_time  AS sender_time
         FROM partner_requests pr
         JOIN  users          u  ON pr.sender_id = u.id
         LEFT JOIN gamer_profiles gp ON gp.user_id = u.id
         WHERE pr.receiver_id = ?
         ORDER BY pr.created_at DESC
         LIMIT 30'
    );
    $stmt->execute([$userId]);
    $requests = $stmt->fetchAll();

    $pendingCount = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));

    jsonResponse([
        'success'       => true,
        'requests'      => $requests,
        'pending_count' => $pendingCount,
    ]);
}

/* ── List requests SENT by current user ─────────────────────── */
function listSent(PDO $db): void {
    $userId = currentUserId();
    if (!$userId) {
        jsonResponse(['success' => false, 'auth' => false, 'requests' => []]);
    }

    $stmt = $db->prepare(
        'SELECT pr.id,
                pr.message,
                pr.status,
                pr.created_at,
                u.id            AS receiver_id,
                u.username      AS receiver_name,
                gp.favorite_game AS receiver_game,
                gp.play_style    AS receiver_style
         FROM partner_requests pr
         JOIN  users          u  ON pr.receiver_id = u.id
         LEFT JOIN gamer_profiles gp ON gp.user_id = u.id
         WHERE pr.sender_id = ?
         ORDER BY pr.created_at DESC
         LIMIT 30'
    );
    $stmt->execute([$userId]);

    jsonResponse(['success' => true, 'requests' => $stmt->fetchAll()]);
}

/* ── Count pending requests (for nav badge) ─────────────────── */
function pendingCount(PDO $db): void {
    $userId = currentUserId();
    if (!$userId) {
        jsonResponse(['success' => true, 'count' => 0]);
    }

    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM partner_requests
         WHERE receiver_id = ? AND status = "pending"'
    );
    $stmt->execute([$userId]);
    jsonResponse(['success' => true, 'count' => (int)$stmt->fetchColumn()]);
}
