<?php
// backend/utils.php

/**
 * Sanitizes input data to prevent common vulnerabilities like XSS.
 *
 * @param string $data The input string to sanitize.
 * @return string The sanitized string.
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validates an email address.
 *
 * @param string $email The email address to validate.
 * @return bool True if valid, false otherwise.
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validates a phone number (basic example, adjust for specific country formats).
 *
 * @param string $phone The phone number to validate.
 * @return bool True if valid, false otherwise.
 */
function isValidPhoneNumber($phone) {
    // Basic regex for Kenyan numbers starting with 07 or +2547, and 9 digits long
    return preg_match('/^(0|254)\d{9}$/', $phone);
}

/**
 * Hashes a password for secure storage.
 *
 * @param string $password The plain text password.
 * @return string The hashed password.
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifies a plain text password against a hashed password.
 *
 * @param string $password The plain text password.
 * @param string $hashedPassword The hashed password from the database.
 * @return bool True if passwords match, false otherwise.
 */
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

// You can add other utility functions here, e.g.:
// - generateUniqueId()
// - convertAmountToCents() for payments
// - sendEmailNotification()
// - logActivity()
?>