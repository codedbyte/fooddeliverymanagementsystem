<?php
require_once 'error_handler.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'database.php'; // Include your database connection file

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$action = $_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? '');

// Only run the switch statement if this file is called directly
if (basename($_SERVER['PHP_SELF']) === 'auth.php') {
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
        case 'customer_register':
            customerRegister();
            break;
        case 'customer_login':
            customerLogin();
            break;
        case 'customer_logout':
            customerLogout();
            break;
        case 'check_customer_session':
            checkCustomerSession();
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
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
}

function adminLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['laurachirchir34@gmail.com'] ?? 'laurachirchir34@gmail.com';
    $password = $data['password'] ?? 'laura';
    $username = "username";
    $password = "laura";

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

function customerRegister() {
    require_once 'utils.php';
    $data = json_decode(file_get_contents('php://input'), true);
    $username = sanitizeInput($data['username'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $phone_number = sanitizeInput($data['phone_number'] ?? '');
    $address = sanitizeInput($data['address'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($phone_number) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }
    if (!isValidEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        return;
    }
    if (!isValidPhoneNumber($phone_number)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number.']);
        return;
    }

    $conn = getDbConnection();
    // Check if email or username already exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
    $stmt->bind_param('ss', $email, $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email or username already exists.']);
        $stmt->close();
        $conn->close();
        return;
    }
    $stmt->close();

    $hashedPassword = hashPassword($password);
    $role = 'customer';
    $stmt = $conn->prepare('INSERT INTO users (username, email, password, phone_number, address, role) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $username, $email, $hashedPassword, $phone_number, $address, $role);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registration successful.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}

function customerLogin() {
    require_once 'utils.php';
    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        return;
    }
    $conn = getDbConnection();
    $stmt = $conn->prepare('SELECT id, username, password, role FROM users WHERE email = ? AND role = "customer"');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (verifyPassword($password, $user['password'])) {
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_username'] = $user['username'];
            $_SESSION['customer_logged_in'] = true;
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

function customerLogout() {
    unset($_SESSION['customer_id'], $_SESSION['customer_username'], $_SESSION['customer_logged_in']);
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
}

function checkCustomerSession() {
    echo json_encode([
        'loggedIn' => isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true,
        'user_id' => $_SESSION['customer_id'] ?? null,
        'username' => $_SESSION['customer_username'] ?? null
    ]);
}

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

function isCustomerLoggedIn() {
    return isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true;
}