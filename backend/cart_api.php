<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php';
require_once 'error_handler.php';

// All actions in this API require a customer to be logged in.
if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to manage your cart.', 'data' => []]);
    exit();
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$action = $_GET['action'] ?? $data['action'] ?? '';
$userId = $_SESSION['customer_id'];

switch ($action) {
    case 'get_cart':
        getCart($userId);
        break;
    case 'add_to_cart':
        addToCart($userId, $data);
        break;
    case 'update_quantity':
        updateQuantity($userId, $data);
        break;
    case 'remove_item':
        removeItem($userId, $data);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action for cart API.']);
        break;
}

function getCart($userId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT sc.menu_item_id as id, mi.name, mi.price, sc.quantity, r.id as restaurant_id
        FROM shopping_cart sc
        JOIN menu_items mi ON sc.menu_item_id = mi.id
        JOIN restaurants r ON mi.restaurant_id = r.id
        WHERE sc.user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartItems = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    // Group items by restaurant
    $cart = ['items' => [], 'restaurant_id' => null];
    if (!empty($cartItems)) {
        $cart['items'] = $cartItems;
        $cart['restaurant_id'] = $cartItems[0]['restaurant_id'];
    }

    echo json_encode(['success' => true, 'data' => $cart]);
}

function addToCart($userId, $data) {
    $menuItemId = $data['id'] ?? null;
    $quantity = $data['quantity'] ?? 1;

    if (empty($menuItemId)) {
        echo json_encode(['success' => false, 'message' => 'Menu item ID is required.']);
        return;
    }

    $conn = getDbConnection();
    // Use INSERT ... ON DUPLICATE KEY UPDATE to add or update quantity
    $stmt = $conn->prepare("
        INSERT INTO shopping_cart (user_id, menu_item_id, quantity) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    $stmt->bind_param('iii', $userId, $menuItemId, $quantity);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add item to cart: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

function updateQuantity($userId, $data) {
    $menuItemId = $data['id'] ?? null;
    $quantity = $data['quantity'] ?? 1;

    if (empty($menuItemId) || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Valid menu item ID and quantity are required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE shopping_cart SET quantity = ? WHERE user_id = ? AND menu_item_id = ?");
    $stmt->bind_param('iii', $quantity, $userId, $menuItemId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cart updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

function removeItem($userId, $data) {
    $menuItemId = $data['id'] ?? null;

    if (empty($menuItemId)) {
        echo json_encode(['success' => false, 'message' => 'Menu item ID is required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE user_id = ? AND menu_item_id = ?");
    $stmt->bind_param('ii', $userId, $menuItemId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
} 