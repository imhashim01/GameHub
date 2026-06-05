<?php
/* ============================================================
   GameHub — register.php  |  User Registration
   ============================================================
   FIX LOG:
   • INSERT now correctly targets columns that exist in schema
   • favorite_genres column IS in the users table — kept
   • Auto-creates gamer_profile row after registration
   • Auto-logs in user after registration
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$body = getRequestBody();

// ── Validate ──────────────────────────────────────────────────
$username = trim($body['username']         ?? '');
$email    = trim($body['email']            ?? '');
$password = trim($body['password'] ?? '');
$confirm  = trim($body['confirm_password'] ?? '');
$genres   = trim($body['favorite_genres']  ?? '');

$errors = [];

if (strlen($username) < 3 || strlen($username) > 30) {
    $errors[] = 'Username must be 3–30 characters.';
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username may only contain letters, numbers, and underscores.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}
if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

if ($errors) {
    jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
}

$db = getDB();

// ── Check uniqueness ──────────────────────────────────────────
$stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Username or email already in use. Please choose another.']);
}

// ── Insert user ───────────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare(
    'INSERT INTO users (username, email, password_hash, favorite_genres, role, created_at)
     VALUES (?, ?, ?, ?, "user", NOW())'
);
$stmt->execute([$username, $email, $hash, $genres]);
$userId = (int)$db->lastInsertId();

// ── Create gamer profile ──────────────────────────────────────
$db->prepare(
    'INSERT INTO gamer_profiles (user_id, favorite_game, playing_time, play_style)
     VALUES (?, "", "evenings", "casual")'
)->execute([$userId]);

// ── Auto login ────────────────────────────────────────────────
startSession();
session_regenerate_id(true);
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;
$_SESSION['email']    = $email;
$_SESSION['role']     = 'user';

jsonResponse([
    'success'  => true,
    'message'  => 'Account created! Welcome to GameHub, ' . $username . '! 🎮',
    'user'     => ['id' => $userId, 'username' => $username, 'email' => $email, 'role' => 'user'],
    'redirect' => 'login.html',
]);