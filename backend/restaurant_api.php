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
    case 'get_all':
        getAllRestaurants();
        break;
    case 'add':
        addRestaurant($data);
        break;
    case 'update':
        updateRestaurant($data);
        break;
    case 'delete':
        deleteRestaurant($data);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function getAllRestaurants() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT id, name, description FROM restaurants ORDER BY name");
    $restaurants = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $restaurants[] = $row;
        }
    }
    $conn->close();
    echo json_encode(['success' => true, 'data' => $restaurants]);
}

function addRestaurant($data) {
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant name is required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO restaurants (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Restaurant added.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add restaurant: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

function updateRestaurant($data) {
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    if (empty($id) || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'ID and name are required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE restaurants SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Restaurant updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update restaurant: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

function deleteRestaurant($data) {
    $id = $data['id'] ?? null;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant ID is required.']);
        return;
    }

    $conn = getDbConnection();
    // Consider adding checks for associated menu items/orders before deleting
    $stmt = $conn->prepare("DELETE FROM restaurants WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Restaurant deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete restaurant: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}