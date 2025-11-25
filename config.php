<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'earncash_db');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is customer
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

// Redirect based on role
function redirectBasedOnRole() {
    if (isAdmin()) {
        header('Location: admin_dashboard.php');
        exit();
    } elseif (isCustomer()) {
        header('Location: advertisements.php');
        exit();
    }
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Require admin role
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: advertisements.php');
        exit();
    }
}

// Require customer role
function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        header('Location: admin_dashboard.php');
        exit();
    }
}
?>
