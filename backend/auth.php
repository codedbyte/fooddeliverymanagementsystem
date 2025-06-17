<?php
session_start();
header('Content-Type: application/json');

require_once 'database.php'; // Include your database connection file

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'admin_login':
        adminLogin();
        break;
    case 'admin_logout':
        adminLogout();
        break;
    case 'check_admin_session':
        checkAdminSession();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function adminLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
        return;
    }

    $conn = getDbConnection();
    // IMPORTANT: In a real application, passwords should be HASHED (e.g., using password_hash)
    // and verified with password_verify. This is a simplified example.
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // For production, use password_verify($password, $user['password'])
        if ($password === $user['password']) { // Simplified check
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_logged_in'] = true;
            echo json_encode(['success' => true, 'message' => 'Login successful.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
    $stmt->close();
    $conn->close();
}

function adminLogout() {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
}

function checkAdminSession() {
    echo json_encode(['loggedIn' => isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true]);
}

// Helper function to ensure admin is logged in for API calls
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
// backend/auth.php (MODIFIED for driver)
session_start();
header('Content-Type: application/json');

require_once 'database.php';
require_once 'utils.php'; // For password hashing/verification
require_once 'error_handler.php'; // For consistent error handling

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'admin_login':
        adminLogin();
        break;
    case 'admin_logout':
        adminLogout();
        break;
    case 'check_admin_session':
        checkAdminSession();
        break;
    case 'driver_login': // NEW: Driver Login
        driverLogin();
        break;
    case 'driver_logout': // NEW: Driver Logout
        driverLogout();
        break;
    case 'check_driver_session': // NEW: Check Driver Session
        checkDriverSession();
        break;
    // ... (existing customer login/logout if any)
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

// ... (Existing adminLogin, adminLogout, checkAdminSession, isAdminLoggedIn functions) ...

function driverLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'driver'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Use verifyPassword from utils.php for production
        if ($password === $user['password']) { // SIMPLIFIED: Replace with verifyPassword($password, $user['password'])
            $_SESSION['driver_id'] = $user['id'];
            $_SESSION['driver_username'] = $user['username'];
            $_SESSION['driver_logged_in'] = true;
            echo json_encode(['success' => true, 'message' => 'Login successful.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
    $stmt->close();
    $conn->close();
}

function driverLogout() {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
}

function checkDriverSession() {
    if (isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'] === true) {
        echo json_encode(['loggedIn' => true, 'driver_id' => $_SESSION['driver_id'], 'username' => $_SESSION['driver_username']]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
}

// Helper function to ensure driver is logged in for API calls (can be used in order_api.php)
function isDriverLoggedIn() {
    return isset($_SESSION['driver_logged_in']) && $_SESSION['driver_logged_in'] === true;
}