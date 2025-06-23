<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php';
require_once 'error_handler.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$action = $_GET['action'] ?? $data['action'] ?? '';

switch ($action) {
    case 'place_order':
        if (!isCustomerLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to place an order.']);
            exit();
        }
        placeOrder($data, $_SESSION['customer_id']);
        break;

    case 'get_user_orders':
        if (!isCustomerLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to view your orders.']);
            exit();
        }
        getUserOrders($_SESSION['customer_id']);
        break;

    case 'get_order_details_for_tracking':
        if (!isCustomerLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to track an order.']);
            exit();
        }
        getOrderDetailsForTracking($_GET['order_id'] ?? null, $_SESSION['customer_id']);
        break;
    
    case 'get_all_orders': // Admin only
        if (!isAdminLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit();
        }
        getAllOrders();
        break;

    case 'update_status': // Admin/Driver
        if (!isAdminLoggedIn() && !isDriverLoggedIn()) {
             echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
             exit();
        }
        updateOrderStatus($data);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action for orders API.']);
        break;
}

function placeOrder($data, $userId) {
    $conn = getDbConnection();
    $conn->begin_transaction();

    try {
        // --- DATA VALIDATION AND SANITIZATION ---
        $restaurantId = (int)($data['restaurant_id'] ?? 0);
        $deliveryAddress = (string)($data['delivery_address'] ?? '');
        $totalAmount = (float)($data['total_amount'] ?? 0.0);
        $deliveryNotes = (string)($data['delivery_notes'] ?? '');
        $items = is_array($data['items']) ? $data['items'] : [];

        if (empty($restaurantId) || empty($deliveryAddress) || empty($items) || $totalAmount <= 0) {
            throw new Exception("Invalid order data provided.");
        }
        // --- END VALIDATION ---

        $stmt = $conn->prepare("INSERT INTO orders (user_id, restaurant_id, delivery_address, total_amount, status, payment_status, notes) VALUES (?, ?, ?, ?, 'pending', 'pending', ?)");
        $stmt->bind_param("iisds", $userId, $restaurantId, $deliveryAddress, $totalAmount, $deliveryNotes);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $stmt->error);
        }
        $orderId = $stmt->insert_id;
        $stmt->close();

        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $itemId = (int)($item['id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            $price = (float)($item['price'] ?? 0.0);

            if ($itemId <= 0 || $quantity <= 0) continue; // Skip invalid items

            $itemStmt->bind_param("iiid", $orderId, $itemId, $quantity, $price);
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to add order item: " . $itemStmt->error);
            }
        }
        $itemStmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order placed successfully.', 'order_id' => $orderId]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to place order due to a server error.']);
    } finally {
        $conn->close();
    }
}

function getUserOrders($userId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT o.id, o.order_date, o.total_amount, o.status, r.name as restaurant_name
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'data' => $orders]);
}

function getOrderDetailsForTracking($orderId, $userId) {
    if (empty($orderId)) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required.']);
        return;
    }
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT o.*, r.name as restaurant_name, d.username as driver_name, d.phone_number as driver_phone
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.id
        LEFT JOIN users d ON o.driver_id = d.id AND d.role = 'driver'
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $order = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $order]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found or you do not have permission to view it.']);
    }
    $stmt->close();
    $conn->close();
}

function getAllOrders() {
    $conn = getDbConnection();
    $result = $conn->query("
        SELECT o.id, u.username as customer_name, r.name as restaurant_name, o.total_amount, o.status, o.payment_status, d.username as driver_name
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        JOIN restaurants r ON o.restaurant_id = r.id 
        LEFT JOIN users d ON o.driver_id = d.id
        ORDER BY order_date DESC
    ");
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    echo json_encode(['success' => true, 'data' => $orders]);
}

function updateOrderStatus($data) {
    $orderId = $data['order_id'] ?? null;
    $newStatus = $data['status'] ?? '';

    if (empty($orderId) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Order ID and new status are required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order status updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}