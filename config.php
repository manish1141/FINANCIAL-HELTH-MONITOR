<?php
// config.php - Dashboard Configuration & Database Connection

session_start();

// Database credentials
$host = 'localhost';
$db   = 'financial_health_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // For development, show error. In production, log it.
     // die("Database connection failed.");
     error_log($e->getMessage());
}

// Authentication Guard
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Ensure session variables for testing if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = "User";
}
?>
