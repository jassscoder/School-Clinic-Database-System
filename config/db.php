<?php
$conn = new mysqli("sql100.infinityfree.com", "if0_41606413", "sfjTodwktf", "if0_41606413_schord_db");

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

/**
 * Sanitize input to prevent SQL injection
 */
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

/**
 * Check if user is logged in, redirect to login if not
 */
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user'])) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Get current logged-in user
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Check if user has admin role
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}
?>