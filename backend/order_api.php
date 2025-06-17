<?php
session_start();
header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'get_all_orders': // Admin only
        if (!isAdminLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit();
        }
        getAllOrders();
        break;
    case 'update_status': // Admin/Driver
        // Add check for isAdminLoggedIn() OR isDriverLoggedIn()
        if (!isAdminLoggedIn() && !(isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'])) {
             echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
             exit();
        }
        updateOrderStatus($data);
        break;
    // ... other order actions like 'place_order' (for customers), 'get_user_orders'
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function getAllOrders() {
    $conn = getDbConnection();
    // Fetch orders, potentially with joins to get customer name, restaurant name, driver name etc.
    // SELECT o.id, u.username as customer_name, r.name as restaurant_name, o.total_amount, o.status, o.payment_status, d.username as driver_name
    // FROM orders o JOIN users u ON o.user_id = u.id JOIN restaurants r ON o.restaurant_id = r.id LEFT JOIN users d ON o.driver_id = d.id;
    $result = $conn->query("SELECT * FROM orders ORDER BY order_date DESC"); // Simplified
    $orders = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $conn->close();
    echo json_encode(['success' => true, 'data' => $orders]);
}

function updateOrderStatus($data) {
    $orderId = $data['order_id'] ?? null;
    $newStatus = $data['status'] ?? ''; // e.g., 'assigned', 'picked_up', 'delivered', 'cancelled'

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
// backend/order_api.php (MODIFIED for driver orders)
session_start();
header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php'; // For isAdminLoggedIn() and isDriverLoggedIn()
require_once 'error_handler.php'; // For consistent error handling

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'get_all_orders': // Admin only
        if (!isAdminLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit();
        }
        getAllOrders();
        break;
    case 'get_driver_assigned_orders': // NEW: Driver specific orders
        if (!isDriverLoggedIn()) { // Only logged-in drivers can see their orders
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit();
        }
        $driverId = $_GET['driver_id'] ?? $_SESSION['driver_id'] ?? null;
        getDriverAssignedOrders($driverId);
        break;
    case 'update_status': // Admin/Driver
        // Existing logic. Ensure either admin or driver is logged in.
        if (!isAdminLoggedIn() && !isDriverLoggedIn()) {
             echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
             exit();
        }
        updateOrderStatus($data);
        break;
    // ... other order actions like 'place_order' (for customers), 'get_user_orders'
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

// ... (Existing getAllOrders, updateOrderStatus functions) ...

function getDriverAssignedOrders($driverId) {
    if (empty($driverId)) {
        echo json_encode(['success' => false, 'message' => 'Driver ID is required.']);
        return;
    }

    $conn = getDbConnection();
    // Fetch orders assigned to this driver that are not yet delivered/cancelled
    $stmt = $conn->prepare("
        SELECT o.id, o.delivery_address, o.total_amount, o.status, o.payment_status,
               u.username AS customer_name, r.name AS restaurant_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN restaurants r ON o.restaurant_id = r.id
        WHERE o.driver_id = ? AND o.status IN ('assigned', 'picked_up', 'on_the_way')
        ORDER BY o.order_date ASC
    ");
    $stmt->bind_param("i", $driverId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($order = $result->fetch_assoc()) {
        // Fetch order items for each order
        $item_stmt = $conn->prepare("SELECT mi.name, oi.quantity, oi.price_at_order FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?");
        $item_stmt->bind_param("i", $order['id']);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $order['items'] = [];
        while ($item = $item_result->fetch_assoc()) {
            $order['items'][] = $item;
        }
        $item_stmt->close();
        $orders[] = $order;
    }
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'data' => $orders]);
}