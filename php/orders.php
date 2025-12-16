<?php
/**
 * Orders API
 * Handles order creation and management
 */

require_once 'db_config.php';
initSession();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createOrder();
        break;
    case 'list':
        getOrders();
        break;
    case 'get':
        getOrder();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Create a new order from cart
 */
function createOrder() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    // Get shipping info
    $shippingName = sanitize($_POST['shipping_name'] ?? '');
    $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
    $shippingCity = sanitize($_POST['shipping_city'] ?? '');
    $shippingZip = sanitize($_POST['shipping_zip'] ?? '');

    try {
        // Get cart items
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT c.product_id, c.quantity, p.price, p.name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.product_id, c.quantity, p.price, p.name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.session_id = ? AND c.user_id IS NULL
            ");
            $stmt->execute([$sessionId]);
        }

        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            jsonResponse(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping = $subtotal > 50 ? 0 : 5.99;
        $tax = $subtotal * 0.08;
        $total = $subtotal + $shipping + $tax;

        // Start transaction
        $pdo->beginTransaction();

        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, session_id, total_amount, shipping, tax, shipping_name, shipping_address, shipping_city, shipping_zip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $userId ? null : $sessionId,
            $total,
            $shipping,
            $tax,
            $shippingName,
            $shippingAddress,
            $shippingCity,
            $shippingZip
        ]);

        $orderId = $pdo->lastInsertId();

        // Create order items
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cartItems as $item) {
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Clear cart
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL");
            $stmt->execute([$sessionId]);
        }

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $orderId,
            'total' => $total
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Order creation error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to create order'], 500);
    }
}

/**
 * Get user's orders
 */
function getOrders() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Please log in to view orders'], 401);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();

    try {
        $stmt = $pdo->prepare("
            SELECT id, total_amount AS total, shipping, tax, status, created_at
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        jsonResponse(['success' => true, 'orders' => $orders]);

    } catch (PDOException $e) {
        error_log("Orders fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch orders'], 500);
    }
}

/**
 * Get single order details
 */
function getOrder() {
    $orderId = (int) ($_GET['id'] ?? 0);

    if ($orderId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid order ID'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    try {
        // Get order
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE id = ? AND (user_id = ? OR session_id = ?)
        ");
        $stmt->execute([$orderId, $userId, $sessionId]);
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image_url
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        $order['items'] = $items;

        jsonResponse(['success' => true, 'order' => $order]);

    } catch (PDOException $e) {
        error_log("Order fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch order'], 500);
    }
}
?>
