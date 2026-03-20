<?php
// setup_db.php
$db_host = 'localhost';
$db_user = 'root'; // default XAMPP user
$db_pass = '';     // default XAMPP password is empty

try {
    // 1. Connect without selecting a particular database
    $conn = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Safely create the database if it doesn't already exist
    $sql_db = "CREATE DATABASE IF NOT EXISTS finhealth_db";
    $conn->exec($sql_db);
    echo "<p style='color:green;'>✔️ Database created successfully or already exists.</p>";

    // 3. Switch to the newly created database
    $conn->exec("USE finhealth_db");

    // 4. Create Users table automatically
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        mobile VARCHAR(20) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_verified TINYINT(1) DEFAULT 0,
        otp VARCHAR(10) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql_users);
    echo "<p style='color:green;'>✔️ Users table created successfully.</p>";

    // 5. Create Transactions table automatically
    $sql_transactions = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        category VARCHAR(50) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        type ENUM('income', 'expense', 'debt') NOT NULL,
        transaction_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql_transactions);
    echo "<p style='color:green;'>✔️ Transactions table created successfully.</p>";

    // 6. Create Chat Messages table automatically
    $sql_chat = "CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        sender ENUM('user', 'bot') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql_chat);
    echo "<p style='color:green;'>✔️ Chat Messages table created successfully.</p>";

    echo "<br><h3 style='font-family: sans-serif;'>All setup is complete! Your database is now fully connected and built.</h3>";
    echo "<a href='login.php' style='display:inline-block; padding:10px 20px; background:#6366f1; color:white; text-decoration:none; border-radius:8px; font-family:sans-serif;'>Go to Register/Login</a>";

} catch(PDOException $e) {
    die("<h3 style='color:red;'>Setup failed: " . $e->getMessage() . "</h3><p>Make sure you have started MySQL in your XAMPP Control Panel.</p>");
}
?>
