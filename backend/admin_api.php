<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php';

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_dashboard_stats':
        getDashboardStats();
        break;
    case 'manage_drivers':
        
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function getDashboardStats() {
    $conn = getDbConnection();
    $stats = [
        'totalOrders' => 0,
        'pendingOrders' => 0,
        'registeredUsers' => 0
    ];

    // Get total orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($result) {
        $stats['totalOrders'] = $result->fetch_assoc()['count'];
    }

    // Get pending orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'processing', 'assigned')");
    if ($result) {
        $stats['pendingOrders'] = $result->fetch_assoc()['count'];
    }

    // for customer get
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    if ($result) {
        $stats['registeredUsers'] = $result->fetch_assoc()['count'];
    }

    $conn->close();
    echo json_encode(['success' => true, 'data' => $stats]);
}