<?php
// config.php
session_start();

$db_host = 'localhost';
$db_user = 'root'; // default XAMPP user
$db_pass = '';     // default XAMPP password is empty
$db_name = 'finhealth_db';

// OpenAI API Key
// Replace 'YOUR_OPENAI_API_KEY' with your actual API key
$OPENAI_API_KEY = 'YOUR_OPENAI_API_KEY';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper to redirect to login if not authenticated
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
