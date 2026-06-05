<?php
/* ============================================================
   GameHub — db.php  |  Database Connection & Helpers
   Updated: Added HTML redirect variants for admin page guards
   ============================================================ */

define('DB_HOST',    'localhost');
define('DB_USER',    'root');       // XAMPP default
define('DB_PASS',    '');           // XAMPP default (empty)
define('DB_NAME',    'gamehub');
define('DB_CHARSET', 'utf8mb4');

// ── PDO singleton ──────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Disable ONLY_FULL_GROUP_BY so SELECT g.* … GROUP BY g.id works
        // (default MySQL 5.7+ strict mode breaks queries that select non-aggregated columns)
        $pdo->exec("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}

// ── Session helpers ────────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400,   // 1 day
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function currentUserId(): ?int {
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function currentUser(): ?array {
    startSession();
    if (!isLoggedIn()) return null;
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'] ?? 'user',
    ];
}

// ── Auth guards (JSON API) ─────────────────────────────────────
function requireLogin(): void {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Authentication required. Please log in.', 'redirect' => '../login.html'], 401);
    }
}

function requireAdmin(): void {
    startSession();
    if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Admin access required.'], 403);
    }
}

// ── Auth guards (HTML page redirect) ──────────────────────────
/**
 * For PHP admin pages (not API endpoints).
 * If not logged in → redirect to login page.
 * If logged in but not admin → redirect to user dashboard.
 */
function requireAdminPage(): void {
    startSession();
    if (!isLoggedIn()) {
        header('Location: ../login.html');
        exit;
    }
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ../dashboard.html');
        exit;
    }
}

/**
 * For PHP user pages (not API endpoints).
 * If not logged in → redirect to login page.
 * If admin → redirect to admin dashboard.
 */
function requireUserPage(): void {
    startSession();
    if (!isLoggedIn()) {
        header('Location: login.html');
        exit;
    }
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin/admin_dashboard.php');
        exit;
    }
}

// ── Response helpers ───────────────────────────────────────────
function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Read POST body (JSON or form-encoded) ─────────────────────
function getRequestBody(): array {
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) return array_merge($_POST, $json);
    return $_POST;
}

// ── XSS sanitiser ─────────────────────────────────────────────
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// ── Pagination helper ──────────────────────────────────────────
function paginate(PDO $db, string $countSql, array $countParams, int $page, int $limit): array {
    $stmt = $db->prepare($countSql);
    $stmt->execute($countParams);
    $total = (int)$stmt->fetchColumn();
    return [
        'total'  => $total,
        'pages'  => (int)ceil($total / $limit),
        'offset' => ($page - 1) * $limit,
    ];
}

// ── Flash message helper (for PHP pages) ──────────────────────
function setFlash(string $type, string $message): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    startSession();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}