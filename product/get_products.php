<?php
/**
 * Get Products from Database
 * Handles product listing with filtering and pagination
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

/**
 * Fetch products with optional filters
 * @param array $filters ['category' => int, 'search' => string, 'featured' => bool, 'page' => int]
 * @param int $per_page Items per page
 * @return array ['products' => array, 'total' => int, 'pages' => int]
 */
function get_products($filters = [], $per_page = 12) {
    global $pdo;
    
    $query = "SELECT * FROM products WHERE is_active = 1";
    $params = [];
    
    // Filter by category
    if (!empty($filters['category'])) {
        $query .= " AND category_id = ?";
        $params[] = (int)$filters['category'];
    }
    
    // Filter by search term
    if (!empty($filters['search'])) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
    }
    
    // Filter featured products
    if (isset($filters['featured']) && $filters['featured']) {
        $query .= " AND featured = 1";
    }
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    if (!empty($filters['category'])) {
        $count_query .= " AND category_id = " . (int)$filters['category'];
    }
    if (!empty($filters['search'])) {
        $count_query .= " AND (name LIKE '%" . htmlspecialchars($filters['search']) . "%' OR description LIKE '%" . htmlspecialchars($filters['search']) . "%')";
    }
    if (isset($filters['featured']) && $filters['featured']) {
        $count_query .= " AND featured = 1";
    }
    
    $count_result = $pdo->query($count_query);
    $total = $count_result->fetch()['total'];
    $pages = ceil($total / $per_page);
    
    // Pagination
    $page = max(1, $filters['page'] ?? 1);
    $offset = ($page - 1) * $per_page;
    
    $query .= " ORDER BY featured DESC, created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    return [
        'products' => $products,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page,
        'per_page' => $per_page
    ];
}

/**
 * Fetch a single product by ID
 * @param int $product_id
 * @return array|null Product data or null if not found
 */
function get_product_by_id($product_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    return $stmt->fetch() ?: null;
}

/**
 * Fetch products by category
 * @param int $category_id
 * @param int $limit
 * @return array Products array
 */
function get_products_by_category($category_id, $limit = 12) {
    global $pdo;
    
    $stmt = $pdo->prepare(
        "SELECT * FROM products 
         WHERE category_id = ? AND is_active = 1 
         ORDER BY featured DESC, created_at DESC 
         LIMIT ?"
    );
    $stmt->execute([$category_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get all categories
 * @return array Categories
 */
function get_categories() {
    global $pdo;
    
    $stmt = $pdo->query(
        "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC"
    );
    return $stmt->fetchAll();
}

/**
 * Get featured products
 * @param int $limit
 * @return array Featured products
 */
function get_featured_products($limit = 8) {
    global $pdo;
    
    $stmt = $pdo->prepare(
        "SELECT * FROM products 
         WHERE featured = 1 AND is_active = 1 
         ORDER BY created_at DESC 
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

?>