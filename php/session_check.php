<?php
/* ============================================================
   GameHub — session_check.php  |  Session Status & Role Guard
   Called by JS on every page load to validate auth state
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

startSession();

if (isLoggedIn()) {
    // Optionally re-validate against DB to detect deleted/banned users
    $userId = (int)$_SESSION['user_id'];
    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row  = $stmt->fetch();

        if (!$row) {
            // User was deleted — destroy session
            session_destroy();
            jsonResponse(['logged_in' => false, 'reason' => 'user_not_found']);
        }

        // Refresh session role in case it was changed
        $_SESSION['role']     = $row['role'];
        $_SESSION['username'] = $row['username'];

        jsonResponse([
            'logged_in' => true,
            'user_id'   => (int)$row['id'],
            'username'  => $row['username'],
            'email'     => $row['email'],
            'role'      => $row['role'],
        ]);
    } catch (Exception $e) {
        // DB unavailable — trust session cache
        jsonResponse([
            'logged_in' => true,
            'user_id'   => (int)$_SESSION['user_id'],
            'username'  => $_SESSION['username'],
            'email'     => $_SESSION['email'],
            'role'      => $_SESSION['role'] ?? 'user',
        ]);
    }
}

jsonResponse(['logged_in' => false]);