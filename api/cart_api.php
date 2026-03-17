<?php
/**
 * Cart API
 * JSON endpoint for AJAX cart operations
 * Useful for future frontend improvements (React, Vue, etc.)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../cart/cart_handler.php';
require_once __DIR__ . '/../cart/get_cart.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');
$response = ['success' => false, 'message' => 'Invalid request'];

try {
    switch ($method) {
        case 'POST':
            if ($action === 'add') {
                $product_id = (int)($_POST['product_id'] ?? 0);
                $quantity = max(1, (int)($_POST['quantity'] ?? 1));
                
                $result = add_to_cart($product_id, $quantity);
                $response = $result;
                $response['cart_count'] = get_cart_count();
            }
            else if ($action === 'remove') {
                $cart_id = (int)($_POST['cart_id'] ?? 0);
                $success = remove_from_cart($cart_id);
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Item removed' : 'Error removing item',
                    'cart_count' => get_cart_count()
                ];
            }
            else if ($action === 'update') {
                $cart_id = (int)($_POST['cart_id'] ?? 0);
                $quantity = max(0, (int)($_POST['quantity'] ?? 1));
                
                $success = update_cart_quantity($cart_id, $quantity);
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Cart updated' : 'Could not update cart',
                    'cart_count' => get_cart_count()
                ];
            }
            else if ($action === 'clear') {
                $success = clear_cart();
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Cart cleared' : 'Error clearing cart',
                    'cart_count' => 0
                ];
            }
            break;

        case 'GET':
            if ($action === 'cart') {
                $cart = get_cart_summary(0.08, 0);
                $response = [
                    'success' => true,
                    'items' => $cart['items'],
                    'totals' => $cart['totals'],
                    'is_empty' => $cart['is_empty']
                ];
            }
            else if ($action === 'count') {
                $response = [
                    'success' => true,
                    'count' => get_cart_count()
                ];
            }
            break;

        case 'OPTIONS':
            http_response_code(200);
            exit();

        default:
            http_response_code(405);
            $response = ['success' => false, 'message' => 'Method not allowed'];
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);

?>