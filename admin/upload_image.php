<?php
/* ============================================================
   GameHub — php/upload_image.php  |  Game Cover Image Upload
   ============================================================
   Handles multipart image upload for game covers.
   Called by manage_games.php Add/Edit Game modal.
   ============================================================ */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

requireAdmin();   // only admins can upload

// ── Config ────────────────────────────────────────────────────
define('UPLOAD_DIR',     __DIR__ . '/../uploads/games/');
define('UPLOAD_URL',     '../uploads/games/');
define('MAX_SIZE_BYTES', 3 * 1024 * 1024);   // 3 MB
define('ALLOWED_TYPES',  ['image/jpeg','image/jpg','image/png','image/webp','image/gif']);
define('ALLOWED_EXTS',   ['jpg','jpeg','png','webp','gif']);

// ── Create folder if missing ──────────────────────────────────
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ── Validate upload ───────────────────────────────────────────
if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
    $phpErr = $_FILES['cover']['error'] ?? 'no file';
    jsonResponse(['success' => false, 'message' => 'Upload error: ' . $phpErr], 400);
}

$file     = $_FILES['cover'];
$origName = basename($file['name']);
$tmpPath  = $file['tmp_name'];
$mimeType = mime_content_type($tmpPath);          // server-side MIME check
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

// Size check
if ($file['size'] > MAX_SIZE_BYTES) {
    jsonResponse(['success' => false, 'message' => 'Image too large. Max size is 3 MB.'], 400);
}

// MIME type check
if (!in_array($mimeType, ALLOWED_TYPES)) {
    jsonResponse(['success' => false, 'message' => 'Only JPG, PNG, WEBP, or GIF images allowed.'], 400);
}

// Extension check
if (!in_array($ext, ALLOWED_EXTS)) {
    jsonResponse(['success' => false, 'message' => 'Invalid file extension.'], 400);
}

// ── Generate unique filename ──────────────────────────────────
$newName  = 'game_' . uniqid() . '_' . time() . '.' . $ext;
$destPath = UPLOAD_DIR . $newName;

if (!move_uploaded_file($tmpPath, $destPath)) {
    jsonResponse(['success' => false, 'message' => 'Failed to save image. Check folder permissions.'], 500);
}

// ── Return the public URL ─────────────────────────────────────
$publicUrl = UPLOAD_URL . $newName;

jsonResponse([
    'success'  => true,
    'message'  => 'Image uploaded successfully!',
    'url'      => $publicUrl,
    'filename' => $newName,
]);