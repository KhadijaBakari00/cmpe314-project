<?php

// Database configuration and session initialization
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_booking');
define('DB_USER', 'root');
define('DB_PASS', '');          

// Error reporting (turn off display_errors in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start([
    'cookie_lifetime' => 86400,      // Session lasts 1 day
    'cookie_secure'   => false,      // Set true if using HTTPS
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Create PDO database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DATABASE CONNECTION ERROR: " . $e->getMessage() .
        "<br><br>Make sure:<br>
        1. XAMPP MySQL is running<br>
        2. You created the 'hotel_booking' database in phpMyAdmin<br>
        3. You ran the SQL schema file");
}

// CSRF Token — regenerated per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
define('CSRF_TOKEN', $_SESSION['csrf_token']);
?>