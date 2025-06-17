<?php
session_start();
header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php';

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'get_by_restaurant': // For customer view and admin edit
        $restaurantId = $_GET['restaurant_id'] ?? null;
        getMenuItemsByRestaurant($restaurantId);
        break;
    case 'add':
        addMenuItem($data);
        break;
    case 'update':
        updateMenuItem($data);
        break;
    case 'delete':
        deleteMenuItem($data);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function getMenuItemsByRestaurant($restaurantId) {
    if (empty($restaurantId)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant ID is required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, name, description, price, is_available FROM menu_items WHERE restaurant_id = ? ORDER BY name");
    $stmt->bind_param("i", $restaurantId);
    $stmt->execute();
    $result = $stmt->get_result();
    $menuItems = [];
    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'data' => $menuItems]);
}

function addMenuItem($data) {
    // Implement logic to add a new menu item
    // Requires restaurant_id, name, description, price, is_available
    // Validate input and insert into 'menu_items' table
    // Example: $name = $data['name'] ?? ''; $price = $data['price'] ?? 0.0; ...
    // Prepare statement, bind params, execute.
}

function updateMenuItem($data) {
    // Implement logic to update an existing menu item
    // Requires menu_item_id, and fields to update (name, description, price, is_available)
    // Validate input and update 'menu_items' table
}

function deleteMenuItem($data) {
    // Implement logic to delete a menu item
    // Requires menu_item_id
    // Validate input and delete from 'menu_items' table
}