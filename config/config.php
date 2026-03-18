<?php

// ===== DATABASE CONFIG =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auth_system');

// ===== APP CONFIG =====
define('APP_NAME', 'AuthPro');
define('APP_URL', 'http://localhost/auth-pro');
define('APP_VERSION', '1.0');

// ===== UPLOAD CONFIG =====
define('UPLOAD_PATH', 'C:/xampp/htdocs/auth-pro/uploads/profiles/');
define('UPLOAD_URL', APP_URL . '/uploads/profiles/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ===== SESSION CONFIG =====
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_TIME', 15); // minutes

// ===== DATABASE CONNECTION =====
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ===== START SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== HELPER FUNCTIONS =====

// Generate CSRF token
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect
function redirect($url) {
    header("Location: " . APP_URL . "/" . $url);
    exit();
}

// Log activity
function logActivity($conn, $user_id, $action) {
    $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt   = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $ip);
    mysqli_stmt_execute($stmt);
}

// Update last seen
function updateLastSeen($conn, $user_id) {
    $now  = date('Y-m-d H:i:s');
    $stmt = mysqli_prepare($conn, "UPDATE users SET last_seen = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $now, $user_id);
    mysqli_stmt_execute($stmt);
}

// Get time ago
function timeAgo($datetime) {
    $diff = abs(time() - strtotime($datetime));
    if ($diff < 60)     return $diff . ' seconds ago';
    if ($diff < 3600)   return floor($diff/60) . ' minutes ago';
    if ($diff < 86400)  return floor($diff/3600) . ' hours ago';
    return floor($diff/86400) . ' days ago';
}

// Is online (active in last 5 minutes)
function isOnline($last_seen) {
    if (!$last_seen) return false;
    return (time() - strtotime($last_seen)) < 300;
}
?>