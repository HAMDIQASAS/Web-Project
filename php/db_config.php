<?php
/**
 * Database Configuration
 * Update these values with your MySQL credentials
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'kookie');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 * @return PDO|null Database connection or null on failure
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }

    return $pdo;
}

/**
 * Initialize session with secure settings
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        // Set session lifetime (24 hours)
        ini_set('session.gc_maxlifetime', 86400);
        session_set_cookie_params(86400);

        session_start();

        // Generate session ID if not exists
        if (!isset($_SESSION['session_id'])) {
            $_SESSION['session_id'] = session_id();
        }
    }
}

/**
 * Send JSON response
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Sanitize input
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 * @param string $email Email to validate
 * @return bool True if valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if user is logged in
 * @return bool True if logged in
 */
function isLoggedIn() {
    initSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null User ID or null
 */
function getCurrentUserId() {
    initSession();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get session ID for guest cart
 * @return string Session ID
 */
function getSessionId() {
    initSession();
    return $_SESSION['session_id'] ?? session_id();
}
?>
