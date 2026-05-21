<?php
require_once __DIR__ . '/../config/db.php';

function get_products($filters = [], $per_page = 12) {
    global $pdo;

    $where  = "WHERE is_active = 1";
    $params = [];

    if (!empty($filters['category'])) {
        $where   .= " AND category_id = ?";
        $params[] = (int)$filters['category'];
    }

    if (!empty($filters['search'])) {
        $where   .= " AND (name LIKE ? OR description LIKE ?)";
        $search   = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
    }

    if (!empty($filters['featured'])) {
        $where .= " AND featured = 1";
    }

    // Total count — same params, same WHERE, no string concatenation
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $per_page));

    // Pagination
    $page     = max(1, (int)($filters['page'] ?? 1));
    $offset   = ($page - 1) * $per_page;

    $data_params   = $params;
    $data_params[] = $per_page;
    $data_params[] = $offset;

    $stmt = $pdo->prepare(
        "SELECT * FROM products $where ORDER BY featured DESC, created_at DESC LIMIT ? OFFSET ?"
    );
    $stmt->execute($data_params);
    $products = $stmt->fetchAll();

    return [
        'products'     => $products,
        'total'        => $total,
        'pages'        => $pages,
        'current_page' => $page,
        'per_page'     => $per_page,
    ];
}

function get_product_by_id($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([(int)$product_id]);
    return $stmt->fetch() ?: null;
}

function get_products_by_category($category_id, $limit = 12) {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE category_id = ? AND is_active = 1
         ORDER BY featured DESC, created_at DESC LIMIT ?"
    );
    $stmt->execute([(int)$category_id, (int)$limit]);
    return $stmt->fetchAll();
}

function get_categories() {
    global $pdo;
    return $pdo->query(
        "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC"
    )->fetchAll();
}

function get_featured_products($limit = 8) {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE featured = 1 AND is_active = 1
         ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->execute([(int)$limit]);
    return $stmt->fetchAll();
}
