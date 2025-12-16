<?php
/**
 * Authentication Handler
 * Handles login, registration, logout, and session management
 */

require_once 'db_config.php';
initSession();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkAuth();
        break;
    case 'user':
        getUserInfo();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Handle user login
 */
function handleLogin() {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
    }

    if (!isValidEmail($email)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;

        // Set client-side cookie indicator (not sensitive - just for JS check)
        setcookie('logged_in', 'true', time() + 86400, '/', '', false, false);

        // Transfer guest cart to user cart
        transferGuestCart($pdo, $user['id']);

        // Regenerate session ID for security
        session_regenerate_id(true);

        jsonResponse([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Login failed'], 500);
    }
}

/**
 * Handle user registration
 */
function handleRegister() {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
    }

    if (!isValidEmail($email)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
    }

    if (strlen($name) < 2 || strlen($name) > 100) {
        jsonResponse(['success' => false, 'message' => 'Name must be between 2 and 100 characters'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Email already registered'], 409);
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);

        $userId = $pdo->lastInsertId();

        // Assign default 'user' role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'user')");
        $stmt->execute([$userId]);

        jsonResponse([
            'success' => true,
            'message' => 'Registration successful'
        ]);

    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Registration failed'], 500);
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    // Clear all session variables
    $_SESSION = [];

    // Clear the logged_in cookie
    setcookie('logged_in', '', time() - 3600, '/', '', false, false);

    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy session
    session_destroy();

    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

/**
 * Check authentication status
 */
function checkAuth() {
    jsonResponse([
        'success' => true,
        'logged_in' => isLoggedIn(),
        'user_id' => getCurrentUserId()
    ]);
}

/**
 * Get current user info
 */
function getUserInfo() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ]
    ]);
}

/**
 * Transfer guest cart to user cart after login
 */
function transferGuestCart($pdo, $userId) {
    $sessionId = getSessionId();

    try {
        // Get guest cart items
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart WHERE session_id = ? AND user_id IS NULL");
        $stmt->execute([$sessionId]);
        $guestItems = $stmt->fetchAll();

        foreach ($guestItems as $item) {
            // Check if user already has this product in cart
            $checkStmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $checkStmt->execute([$userId, $item['product_id']]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Update quantity
                $updateStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
                $updateStmt->execute([$item['quantity'], $existing['id']]);
            } else {
                // Transfer item to user
                $updateStmt = $pdo->prepare("UPDATE cart SET user_id = ?, session_id = NULL WHERE session_id = ? AND product_id = ?");
                $updateStmt->execute([$userId, $sessionId, $item['product_id']]);
            }
        }

        // Remove any remaining guest items for this session
        $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL");
        $deleteStmt->execute([$sessionId]);

    } catch (PDOException $e) {
        error_log("Cart transfer error: " . $e->getMessage());
    }
}
?>
