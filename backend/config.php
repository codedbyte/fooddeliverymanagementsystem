<?php
// backend/config.php

// Database Credentials (MySQL
$host('DB_HOST', 'local_instance'); 
$user('DB_USER', 'root');   
$password('DB_PASS', '12345');        
$food_delivery_db('DB_NAME', 'my_food_deliver');

// M-Pesa Daraja API Credentials
// Obtain these from your Safaricom Daraja developer portal
define('MPESA_CONSUMER_KEY', 'YOUR_MPESA_CONSUMER_KEY');
define(constant_name: 'MPESA_CONSUMER_SECRET', 'YOUR_MPESA_CONSUMER_SECRET');
define('MPESA_SHORTCODE', 'YOUR_MPESA_SHORTCODE'); // Paybill or Till number
define('MPESA_PASSKEY', 'YOUR_MPESA_PASSKEY'); // Only for M-Pesa Express (STK Push)
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/backend/mpesa_callback.php'); // Your public URL for M-Pesa callbacks

// Google Maps API Key
// Obtain this from Google Cloud Console (APIs & Services -> Credentials)
// Restrict API Key to specific APIs (Maps JavaScript API, Geocoding API, Places API, etc.)
// and HTTP referrers for security.
define('Maps_API_KEY', 'YOUR_Maps_API_KEY');

// Other settings
define('BASE_URL', 'http://localhost/food-delivery-management-system/'); // Adjust to your project's base URL
define('ENVIRONMENT', 'development'); // 'development' or 'production' for error handling

// Timezone setting (important for date/time consistency)
date_default_timezone_set('Africa/Nairobi'); // Or your local timezone

// Error reporting (for development)
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} elseif (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>