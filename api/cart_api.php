<?php
/**
 * Cart API
 * JSON endpoint for AJAX cart operations
 * Useful for future frontend improvements (React, Vue, etc.)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../cart/cart_handler.php';
require_once __DIR__ . '/../cart/get_cart.php';

header('Content-Type: application/json');

// Restrict CORS to own origin only
$_parsed_url    = parse_url(SITE_URL);
$_allowed_origin = $_parsed_url['scheme'] . '://' . $_parsed_url['host']
    . (isset($_parsed_url['port']) ? ':' . $_parsed_url['port'] : '');
header('Access-Control-Allow-Origin: ' . $_allowed_origin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
unset($_parsed_url, $_allowed_origin);

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
                $cart_id = (int)($_POST['product_id'] ?? 0);
                $success = remove_from_cart($cart_id);
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Item removed' : 'Error removing item',
                    'cart_count' => get_cart_count()
                ];
            }
            else if ($action === 'update') {
                $cart_id = (int)($_POST['product_id'] ?? 0);
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
                $cart = get_cart_summary(TAX_RATE, STANDARD_SHIPPING);
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
    error_log('Cart API error: ' . $e->getMessage());
    http_response_code(500);
    $response = ['success' => false, 'message' => 'An unexpected error occurred. Please try again.'];
}

echo json_encode($response, JSON_PRETTY_PRINT);

?>