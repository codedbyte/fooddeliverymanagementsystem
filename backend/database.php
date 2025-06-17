<?php
// backend/database.php

require_once 'config.php';

function getDbConnection() {
    $conn = new mysqli("localhost", "root", "12345", "my_food_database");

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        if (ENVIRONMENT === 'development') {
            die("Database connection failed: " . $conn->connect_error);
        } else{
            exit("An unexpected error occurred. Please try again later.");

        }
    }

    $conn->set_charset("UTF-8");

    return $conn;
}

?>