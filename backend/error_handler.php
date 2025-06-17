<?php
// backend/error_handler.php

require_once 'config.php'; // Ensure ENVIRONMENT constant is available

// Custom error handler function
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Log the error to a file (recommended for production)
    $error_message = "[".date("Y-m-d H:i:s")."] ERROR: [$errno] $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, __DIR__ . '/../logs/php_errors.log'); // Adjust log file path

    // If in development, display the error
    if (ENVIRONMENT === 'development') {
        echo json_encode(['success' => false, 'message' => "An internal server error occurred.", 'debug' => "$errstr in $errfile on line $errline"]);
    } else {
        // In production, just send a generic error message
        echo json_encode(['success' => false, 'message' => "An internal server error occurred. Please try again later."]);
    }
    exit(); // Terminate script execution after handling the error
}

// Set the custom error handler
set_error_handler("customErrorHandler");

// Optional: Set a shutdown function to catch fatal errors (e.g., parse errors)
function shutdownHandler() {
    $last_error = error_get_last();
    if ($last_error && ($last_error['type'] === E_ERROR || $last_error['type'] === E_PARSE || $last_error['type'] === E_CORE_ERROR || $last_error['type'] === E_COMPILE_ERROR)) {
        customErrorHandler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
    }
}
register_shutdown_function("shutdownHandler");

// You might also want to set an exception handler if you use exceptions
// set_exception_handler("customExceptionHandler");
// function customExceptionHandler($exception) {
//     // Log and handle exceptions similarly
// }

?>