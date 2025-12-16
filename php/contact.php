<?php
/**
 * Contact Form Handler
 * Saves contact messages to database
 */

require_once 'db_config.php';
initSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$message = sanitize($_POST['message'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($message)) {
    jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
}

if (!isValidEmail($email)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
}

if (strlen($name) < 2 || strlen($name) > 100) {
    jsonResponse(['success' => false, 'message' => 'Name must be between 2 and 100 characters'], 400);
}

if (strlen($message) < 10 || strlen($message) > 1000) {
    jsonResponse(['success' => false, 'message' => 'Message must be between 10 and 1000 characters'], 400);
}

$pdo = getDBConnection();
if (!$pdo) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

$userId = getCurrentUserId();

try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (user_id, name, email, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $name, $email, $message]);

    jsonResponse([
        'success' => true,
        'message' => 'Thank you! Your message has been sent. We\'ll get back to you soon.'
    ]);

} catch (PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to send message'], 500);
}
?>
