<?php
// backend/restaurant_api.php

header('Content-Type: application/json');

require_once 'database.php';
require_once 'auth.php'; // Contains isAdminLoggedIn() and isCustomerLoggedIn()

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$action = $_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? '');

switch ($action) {
    case 'get_all':
        // No login required to see restaurants
        getAllRestaurants();
        break;
    case 'get_single':
        // No login required to see a single restaurant
        getSingleRestaurant($_GET['id'] ?? null);
        break;
    case 'add':
        if (!isAdminLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin only.']);
            exit();
        }
        addRestaurant($data);
        break;
    case 'update':
        if (!isAdminLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin only.']);
            exit();
        }
        updateRestaurant($data);
        break;
    case 'delete':
        if (!isAdminLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin only.']);
            exit();
        }
        deleteRestaurant($data);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action for restaurants.']);
        break;
}

/**
 * Retrieves a single restaurant by its ID.
 */
function getSingleRestaurant($id) {
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant ID is required.']);
        return;
    }
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, name, description, address, phone_number, email, image_url FROM restaurants WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $restaurant = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $restaurant]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Restaurant not found.']);
    }
    $stmt->close();
    $conn->close();
}

/**
 * Retrieves all restaurants from the database.
 * Returns a JSON array of restaurant data.
 */
function getAllRestaurants() {
    $conn = getDbConnection();
    // SELECT all necessary columns including image_url, address, phone_number, email
    $result = $conn->query("SELECT id, name, description, address, phone_number, email, image_url FROM restaurants ORDER BY name");
    $restaurants = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $restaurants[] = $row;
        }
    }
    $conn->close();
    echo json_encode(['success' => true, 'data' => $restaurants]);
}

/**
 * Adds a new restaurant to the database.
 * Requires 'name', 'description', 'address', 'phone_number', 'email', 'image_url' in the $data array.
 */
function addRestaurant($data) {
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $address = $data['address'] ?? '';
    $phone_number = $data['phone_number'] ?? '';
    $email = $data['email'] ?? '';
    $image_url = $data['image_url'] ?? '';

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant name is required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO restaurants (name, description, address, phone_number, email, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $description, $address, $phone_number, $email, $image_url);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Restaurant added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add restaurant: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

/**
 * Updates an existing restaurant in the database.
 * Requires 'id' and at least 'name' in the $data array. Other fields are optional.
 */
function updateRestaurant($data) {
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? null; // Nullable for update, allows not changing
    $address = $data['address'] ?? null;
    $phone_number = $data['phone_number'] ?? null;
    $email = $data['email'] ?? null;
    $image_url = $data['image_url'] ?? null;

    if (empty($id) || empty($name)) { // Name should always be provided for an update
        echo json_encode(['success' => false, 'message' => 'Restaurant ID and name are required for update.']);
        return;
    }

    $conn = getDbConnection();
    $sql = "UPDATE restaurants SET name = ?";
    $types = "s";
    $params = [$name];

    if ($description !== null) {
        $sql .= ", description = ?";
        $types .= "s";
        $params[] = $description;
    }
    if ($address !== null) {
        $sql .= ", address = ?";
        $types .= "s";
        $params[] = $address;
    }
    if ($phone_number !== null) {
        $sql .= ", phone_number = ?";
        $types .= "s";
        $params[] = $phone_number;
    }
    if ($email !== null) {
        $sql .= ", email = ?";
        $types .= "s";
        $params[] = $email;
    }
    if ($image_url !== null) {
        $sql .= ", image_url = ?";
        $types .= "s";
        $params[] = $image_url;
    }

    $sql .= " WHERE id = ?";
    $types .= "i";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
        $conn->close();
        return;
    }

    // Use call_user_func_array for bind_param with dynamic arguments
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Restaurant updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update restaurant: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

/**
 * Deletes a restaurant from the database.
 * Requires 'id' in the $data array.
 */
function deleteRestaurant($data) {
    $id = $data['id'] ?? null;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Restaurant ID is required for deletion.']);
        return;
    }

    $conn = getDbConnection();
    // It's good practice to consider deleting related records (menu items, orders)
    // or setting foreign key constraints to CASCADE DELETE in your schema.
    // For now, this just deletes the restaurant itself.
    $stmt = $conn->prepare("DELETE FROM restaurants WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Restaurant deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete restaurant: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

?>