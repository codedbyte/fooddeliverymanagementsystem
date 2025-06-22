<?php
$host = 'localhost';
$user = 'root';   
$password = '';
$food_delivery_db = 'fooddelivery'; 
$ENVIRONMENT = 'development';


date_default_timezone_set( $timezoneId = 'Africa/Nairobi');
if ($ENVIRONMENT === 'development') {
    error_reporting(error_level:E_ALL);
    ini_set('display_error', 1);
} elseif ($ENVIRONMENT === 'production') {
    error_reporting(error_level:0);
    ini_set($display_errors, 0);
    ini_set($log_errors, 1);
    ini_set($error_log, __DIR__ . '/../logs/php_errors.log');
}

$BASE_URL = 'http://localhost/food-delivery-management-system/';
($BACKEND_PATH = dirname("backend") . '/');
($ROOT_PATH = dirname('backend') . '/');
?>
