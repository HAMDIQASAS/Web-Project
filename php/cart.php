<?php
/**
 * Cart API
 * Handles shopping cart operations
 */

require_once 'db_config.php';
initSession();

header('Content-Type: application/json');

// Support JSON request bodies (app.js uses fetch with application/json)
$rawBody = file_get_contents('php://input');
$jsonBody = json_decode($rawBody, true);
if (is_array($jsonBody)) {
    $_POST = array_merge($_POST, $jsonBody);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        getCart();
        break;
    case 'add':
        addToCart();
        break;
    case 'update':
        updateCartItem();
        break;
    case 'remove':
        removeFromCart();
        break;
    case 'clear':
        clearCart();
        break;
    case 'count':
        getCartCount();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Get cart items
 */
function getCart() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    try {
        if ($userId) {
            $sql = "
                SELECT
                    c.id as cart_id,
                    c.product_id,
                    c.quantity,
                    p.name,
                    p.category,
                    p.price,
                    p.image_url
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql = "
                SELECT
                    c.id as cart_id,
                    c.product_id,
                    c.quantity,
                    p.name,
                    p.category,
                    p.price,
                    p.image_url
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.session_id = ? AND c.user_id IS NULL
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sessionId]);
        }

        $items = $stmt->fetchAll();

        $total = 0;
        foreach ($items as &$item) {
            $item['price'] = (float) $item['price'];
            $item['quantity'] = (int) $item['quantity'];
            $item['product_id'] = (int) $item['product_id'];
            $total += $item['price'] * $item['quantity'];
        }

        jsonResponse([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'count' => array_sum(array_column($items, 'quantity'))
        ]);

    } catch (PDOException $e) {
        error_log("Cart fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch cart'], 500);
    }
}

/**
 * Add item to cart
 */
function addToCart() {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
    }

    if ($quantity <= 0) {
        $quantity = 1;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    try {
        // Verify product exists
        $stmt = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Check if item already in cart
        if ($userId) {
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND user_id IS NULL");
            $stmt->execute([$sessionId, $productId]);
        }

        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantity
            $newQty = $existing['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQty, $existing['id']]);
        } else {
            // Insert new item
            if ($userId) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $productId, $quantity]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$sessionId, $productId, $quantity]);
            }
        }

        // Get updated count
        $count = getCartCountInternal($pdo, $userId, $sessionId);

        jsonResponse([
            'success' => true,
            'message' => $product['name'] . ' added to cart!',
            'count' => $count
        ]);

    } catch (PDOException $e) {
        error_log("Add to cart error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to add item to cart'], 500);
    }
}

/**
 * Update cart item quantity
 */
function updateCartItem() {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
    }

    if ($quantity <= 0) {
        // Remove item if quantity is 0 or less
        $_POST['product_id'] = $productId;
        removeFromCart();
        return;
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    try {
        if ($userId) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $userId, $productId]);
        } else {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ? AND user_id IS NULL");
            $stmt->execute([$quantity, $sessionId, $productId]);
        }

        jsonResponse(['success' => true, 'message' => 'Cart updated']);

    } catch (PDOException $e) {
        error_log("Update cart error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update cart'], 500);
    }
}

/**
 * Remove item from cart
 */
function removeFromCart() {
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    try {
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ? AND user_id IS NULL");
            $stmt->execute([$sessionId, $productId]);
        }

        jsonResponse(['success' => true, 'message' => 'Item removed from cart']);

    } catch (PDOException $e) {
        error_log("Remove from cart error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to remove item'], 500);
    }
}

/**
 * Clear entire cart
 */
function clearCart() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    try {
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL");
            $stmt->execute([$sessionId]);
        }

        jsonResponse(['success' => true, 'message' => 'Cart cleared']);

    } catch (PDOException $e) {
        error_log("Clear cart error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to clear cart'], 500);
    }
}

/**
 * Get cart item count
 */
function getCartCount() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $userId = getCurrentUserId();
    $sessionId = getSessionId();

    $count = getCartCountInternal($pdo, $userId, $sessionId);

    jsonResponse(['success' => true, 'count' => $count]);
}

/**
 * Internal function to get cart count
 */
function getCartCountInternal($pdo, $userId, $sessionId) {
    try {
        if ($userId) {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE session_id = ? AND user_id IS NULL");
            $stmt->execute([$sessionId]);
        }

        $result = $stmt->fetch();
        return (int) $result['count'];

    } catch (PDOException $e) {
        return 0;
    }
}
?>
