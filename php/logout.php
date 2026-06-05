<?php
/* ============================================================
   GameHub — logout.php  |  End Session
   Handles both admin and user logouts with correct redirect
   ============================================================ */

require_once __DIR__ . '/db.php';

startSession();

// Determine where to redirect based on role before destroying
$wasAdmin = ($_SESSION['role'] ?? '') === 'admin';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirect for browser requests
$acceptsHtml = !isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && isset($_SERVER['HTTP_ACCEPT'])
    && str_contains($_SERVER['HTTP_ACCEPT'], 'text/html');

if ($acceptsHtml || isset($_GET['redirect'])) {
    // Always redirect to index/login after logout
    header('Location: ../index.html');
    exit;
}

// JSON response for AJAX
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success'  => true,
    'message'  => 'Logged out successfully.',
    'redirect' => '../index.html',
]);
exit;