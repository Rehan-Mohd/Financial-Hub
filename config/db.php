<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_tracker_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    } else {
        die("<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#991b1b; border-radius:8px;'>
            <h2>Database Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p></div>");
    }
}

// Helper: Check if user is authenticated
function is_authenticated() {
    return !empty($_SESSION['user_id']);
}

// Helper: Get Active User ID
function get_active_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Helper: Require authentication for API endpoints
function require_auth() {
    if (!is_authenticated()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'authenticated' => false, 'message' => 'Authentication required. Please log in.']);
        exit;
    }
    return $_SESSION['user_id'];
}
?>
