<?php
require_once 'config.php';

function getDbConnection() {
    global $host, $user, $password, $food_delivery_db;
    $conn = new mysqli($host, $user, $password, $food_delivery_db);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        global $ENVIRONMENT;
        if ($ENVIRONMENT === 'development') {
            die("Database connection failed: " . $conn->connect_error);
        } else{
            exit("An unexpected error occurred. Please try again later.");
        }
    }

    $conn->set_charset("utf8mb4");

    return $conn;
}

?>