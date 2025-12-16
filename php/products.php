<?php
/**
 * Products API
 * Handles product listing and details
 */

require_once 'db_config.php';
initSession();

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        getProducts();
        break;
    case 'get':
        getProduct();
        break;
    case 'categories':
        getCategories();
        break;
    case 'search':
        searchProducts();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Get all products with optional filtering
 */
function getProducts() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $category = sanitize($_GET['category'] ?? '');
    $sort = sanitize($_GET['sort'] ?? 'name');
    $search = sanitize($_GET['search'] ?? '');

    try {
        $sql = "
            SELECT 
                id,
                name,
                category,
                price,
                description,
                image_url,
                stock_quantity as stock
            FROM products
            WHERE 1=1
        ";

        $params = [];

        // Category filter
        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        // Search filter
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Sorting
        switch ($sort) {
            case 'price-asc':
                $sql .= " ORDER BY price ASC";
                break;
            case 'price-desc':
                $sql .= " ORDER BY price DESC";
                break;
            case 'name':
            default:
                $sql .= " ORDER BY name ASC";
                break;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // Format price as float
        foreach ($products as &$product) {
            $product['price'] = (float) $product['price'];
            $product['stock'] = (int) $product['stock'];
        }

        jsonResponse(['success' => true, 'products' => $products]);

    } catch (PDOException $e) {
        error_log("Products fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch products'], 500);
    }
}

/**
 * Get single product by ID
 */
function getProduct() {
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    try {
        $sql = "
            SELECT 
                id,
                name,
                category,
                price,
                description,
                image_url,
                stock_quantity as stock
            FROM products
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        $product['price'] = (float) $product['price'];
        $product['stock'] = (int) $product['stock'];

        jsonResponse(['success' => true, 'product' => $product]);

    } catch (PDOException $e) {
        error_log("Product fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch product'], 500);
    }
}

/**
 * Get all categories (distinct from products table)
 */
function getCategories() {
    $pdo = getDBConnection();
    if (!$pdo) {
        jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    try {
        $stmt = $pdo->query("SELECT DISTINCT category as name FROM products ORDER BY category");
        $categories = $stmt->fetchAll();

        jsonResponse(['success' => true, 'categories' => $categories]);

    } catch (PDOException $e) {
        error_log("Categories fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch categories'], 500);
    }
}

/**
 * Search products
 */
function searchProducts() {
    $query = sanitize($_GET['q'] ?? '');

    if (strlen($query) < 2) {
        jsonResponse(['success' => false, 'message' => 'Search query too short'], 400);
    }

    $_GET['search'] = $query;
    getProducts();
}
?>
