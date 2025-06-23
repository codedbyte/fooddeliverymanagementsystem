<?php
// backend/Maps_api_backend.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'config.php';
require_once 'error_handler.php'; // For consistent error handling
require_once 'auth.php'; // If this API requires admin/driver login

// Example: Restrict access to logged-in users or specific roles if necessary
// if (!isLoggedIn() && !isAdminLoggedIn() && !isDriverLoggedIn()) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
//     exit();
// }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'geocode_address':
        // Example: Convert address to lat/lng
        $address = $data['address'] ?? '';
        geocodeAddress($address);
        break;
    case 'get_distance_matrix':
        // Example: Calculate distance/duration between points for delivery drivers
        $origins = $data['origins'] ?? []; // Array of lat,lng or addresses
        $destinations = $data['destinations'] ?? []; // Array of lat,lng or addresses
        getDistanceMatrix($origins, $destinations);
        break;
    // Add more Google Maps API interactions as needed
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function callGoogleMapsApi($endpoint, $params) {
    $params['key'] = Maps_API_KEY;
    $query_string = http_build_query($params);
    $url = "https://maps.googleapis.com/maps/api/{$endpoint}?" . $query_string;

    // Use cURL to make the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("cURL Error: " . $error);
    }
    if ($http_code !== 200) {
        throw new Exception("Google Maps API returned HTTP $http_code: " . $response);
    }

    return json_decode($response, true);
}

function geocodeAddress($address) {
    if (empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Address is required.']);
        return;
    }

    try {
        $result = callGoogleMapsApi('geocode/json', ['address' => $address]);
        if ($result['status'] === 'OK' && !empty($result['results'])) {
            $location = $result['results'][0]['geometry']['location'];
            echo json_encode(['success' => true, 'data' => $location]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Geocoding failed: ' . ($result['error_message'] ?? 'No results found.')]);
        }
    } catch (Exception $e) {
        error_log("Geocoding API error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to geocode address due to internal error.']);
    }
}

function getDistanceMatrix($origins, $destinations) {
    if (empty($origins) || empty($destinations)) {
        echo json_encode(['success' => false, 'message' => 'Origins and destinations are required.']);
        return;
    }

    try {
        $params = [
            'origins' => implode('|', $origins),
            'destinations' => implode('|', $destinations),
            // 'mode' => 'driving', // optional
            // 'units' => 'metric' // optional
        ];
        $result = callGoogleMapsApi('distancematrix/json', $params);

        if ($result['status'] === 'OK') {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Distance Matrix failed: ' . ($result['error_message'] ?? 'No results found.')]);
        }
    } catch (Exception $e) {
        error_log("Distance Matrix API error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to calculate distance due to internal error.']);
    }
}
?>