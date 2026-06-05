<?php
/* ============================================================
   GameHub — login.php  |  User Authentication (Updated)
   Role-based redirect: admin → admin/admin_dashboard.php
                        user  → dashboard.html
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── GET: return session state ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    startSession();
    if (isLoggedIn()) {
        jsonResponse(['success' => true, 'loggedIn' => true, 'user' => currentUser()]);
    }
    jsonResponse(['success' => true, 'loggedIn' => false]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// ── POST: authenticate ────────────────────────────────────────
$body     = getRequestBody();
$login    = trim($body['email'] ?? $body['login'] ?? '');
$password = $body['password'] ?? '';

if (!$login || !$password) {
    jsonResponse(['success' => false, 'message' => 'Please enter your email and password.']);
}

$db = getDB();

$stmt = $db->prepare(
    'SELECT id, username, email, password_hash, role
     FROM users
     WHERE email = ? OR username = ?
     LIMIT 1'
);
$stmt->execute([$login, $login]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid credentials. Please try again.']);
}

// ── Update last_login timestamp ───────────────────────────────
$db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')
   ->execute([$user['id']]);

// ── Start session ─────────────────────────────────────────────
startSession();
session_regenerate_id(true);
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email']    = $user['email'];
$_SESSION['role']     = $user['role'];

// ── Role-based redirect ───────────────────────────────────────
// admin → admin/admin_dashboard.php
// user  → dashboard.html
$redirect = ($user['role'] === 'admin')
    ? 'admin/admin_dashboard.php'
    : 'dashboard.html';

jsonResponse([
    'success'  => true,
    'message'  => 'Welcome back, ' . $user['username'] . '! 🎮',
    'user'     => [
        'id'       => (int)$user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role'],
    ],
    'redirect' => $redirect,
]);