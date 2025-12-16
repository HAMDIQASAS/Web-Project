<?php
/**
 * Admin API Handler
 * Handles all admin operations - products, orders, users, messages
 */

require_once 'db_config.php';
initSession();

header('Content-Type: application/json');

// Check if user is admin
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }

    $pdo = getDBConnection();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("SELECT role FROM user_roles WHERE user_id = ? AND role = 'admin'");
        $stmt->execute([getCurrentUserId()]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// Require admin access
function requireAdmin() {
    if (!isAdmin()) {
        jsonResponse(['success' => false, 'message' => 'Admin access required'], 403);
    }
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_admin':
        checkAdminAuth();
        break;
    case 'dashboard':
        requireAdmin();
        getDashboard();
        break;
    case 'products':
        requireAdmin();
        getProducts();
        break;
    case 'get_product':
        requireAdmin();
        getProduct();
        break;
    case 'add_product':
        requireAdmin();
        addProduct();
        break;
    case 'update_product':
        requireAdmin();
        updateProduct();
        break;
    case 'delete_product':
        requireAdmin();
        deleteProduct();
        break;
    case 'orders':
        requireAdmin();
        getOrders();
        break;
    case 'update_order':
        requireAdmin();
        updateOrder();
        break;
    case 'users':
        requireAdmin();
        getUsers();
        break;
    case 'update_role':
        requireAdmin();
        updateUserRole();
        break;
    case 'messages':
        requireAdmin();
        getMessages();
        break;
    case 'delete_message':
        requireAdmin();
        deleteMessage();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Check admin authentication
 */
function checkAdminAuth() {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Not logged in', 'is_admin' => false]);
    }

    $isAdminUser = isAdmin();
    jsonResponse([
        'success' => true,
        'is_admin' => $isAdminUser,
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ]
    ]);
}

/**
 * Get dashboard statistics
 */
function getDashboard() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        // Get counts
        $products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

        // Get recent orders
        $stmt = $pdo->query("
            SELECT o.*, u.name as user_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
        $recentOrders = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'stats' => [
                'products' => $products,
                'orders' => $orders,
                'users' => $users,
                'revenue' => $revenue
            ],
            'recent_orders' => $recentOrders
        ]);
    } catch (PDOException $e) {
        error_log("Dashboard error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to load dashboard'], 500);
    }
}

/**
 * Get all products
 */
function getProducts() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        jsonResponse(['success' => true, 'products' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to load products'], 500);
    }
}

/**
 * Get single product
 */
function getProduct() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        jsonResponse(['success' => true, 'product' => $product]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to load product'], 500);
    }
}

/**
 * Add new product
 */
function addProduct() {
    $data = json_decode(file_get_contents('php://input'), true);

    $name = sanitize($data['name'] ?? '');
    $category = sanitize($data['category'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $stock = intval($data['stock_quantity'] ?? 0);
    $description = sanitize($data['description'] ?? '');
    $imageUrl = sanitize($data['image_url'] ?? '');

    if (empty($name) || empty($category) || $price <= 0) {
        jsonResponse(['success' => false, 'message' => 'Name, category and price are required'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (name, category, price, stock_quantity, description, image_url) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category, $price, $stock, $description, $imageUrl]);

        jsonResponse(['success' => true, 'message' => 'Product added', 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        error_log("Add product error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to add product'], 500);
    }
}

/**
 * Update product
 */
function updateProduct() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $name = sanitize($data['name'] ?? '');
    $category = sanitize($data['category'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $stock = intval($data['stock_quantity'] ?? 0);
    $description = sanitize($data['description'] ?? '');
    $imageUrl = sanitize($data['image_url'] ?? '');

    if (empty($name) || empty($category) || $price <= 0) {
        jsonResponse(['success' => false, 'message' => 'Name, category and price are required'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, category = ?, price = ?, stock_quantity = ?, description = ?, image_url = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $category, $price, $stock, $description, $imageUrl, $id]);

        jsonResponse(['success' => true, 'message' => 'Product updated']);
    } catch (PDOException $e) {
        error_log("Update product error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update product'], 500);
    }
}

/**
 * Delete product
 */
function deleteProduct() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'message' => 'Product deleted']);
    } catch (PDOException $e) {
        error_log("Delete product error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to delete product'], 500);
    }
}

/**
 * Get all orders
 */
function getOrders() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->query("
            SELECT o.*, u.name as user_name, 
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC
        ");
        jsonResponse(['success' => true, 'orders' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to load orders'], 500);
    }
}

/**
 * Update order status
 */
function updateOrder() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Order ID required'], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $status = sanitize($data['status'] ?? '');

    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        jsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        jsonResponse(['success' => true, 'message' => 'Order updated']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to update order'], 500);
    }
}

/**
 * Get all users
 */
function getUsers() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email, u.created_at,
                   COALESCE((SELECT role FROM user_roles WHERE user_id = u.id LIMIT 1), 'user') as role
            FROM users u 
            ORDER BY u.created_at DESC
        ");
        jsonResponse(['success' => true, 'users' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to load users'], 500);
    }
}

/**
 * Update user role
 */
function updateUserRole() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'User ID required'], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $role = sanitize($data['role'] ?? '');

    if (!in_array($role, ['user', 'admin'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid role'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        // Delete existing role
        $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $stmt->execute([$id]);

        // Insert new role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmt->execute([$id, $role]);

        jsonResponse(['success' => true, 'message' => 'User role updated']);
    } catch (PDOException $e) {
        error_log("Update role error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update role'], 500);
    }
}

/**
 * Get all contact messages
 */
function getMessages() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
        jsonResponse(['success' => true, 'messages' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to load messages'], 500);
    }
}

/**
 * Delete contact message
 */
function deleteMessage() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Message ID required'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'message' => 'Message deleted']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to delete message'], 500);
    }
}
?>
