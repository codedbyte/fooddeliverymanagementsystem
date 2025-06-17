<?php
session_start();
header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php'; // For isAdminLoggedIn()

if (!isAdminLoggedIn()) { // Only admin can assign drivers
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'assign_driver':
        assignDriverToOrder($data);
        break;
    case 'get_available_drivers': // To populate dropdown in admin panel
        getAvailableDrivers();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function assignDriverToOrder($data) {
    $orderId = $data['order_id'] ?? null;
    $driverId = $data['driver_id'] ?? null;

    if (empty($orderId) || empty($driverId)) {
        echo json_encode(['success' => false, 'message' => 'Order ID and Driver ID are required.']);
        return;
    }

    $conn = getDbConnection();
    // Update the 'orders' table to set the driver_id and update status to 'assigned'
    $stmt = $conn->prepare("UPDATE orders SET driver_id = ?, status = 'assigned' WHERE id = ? AND status = 'processing'"); // Only assign if not already assigned/delivered
    $stmt->bind_param("ii", $driverId, $orderId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Driver assigned successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign driver: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

function getAvailableDrivers() {
    $conn = getDbConnection();
    // Fetch users with role 'driver' who are currently available
    // SELECT id, username, contact_number FROM users WHERE role = 'driver' AND status = 'available';
    $result = $conn->query("SELECT id, username FROM users WHERE role = 'driver'"); // Simplified
    $drivers = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }
    }
    $conn->close();
    echo json_encode(['success' => true, 'data' => $drivers]);
}