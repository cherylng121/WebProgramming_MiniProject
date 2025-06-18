<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "event_management";

// Create connection
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if (mysqli_query($conn, $sql)) {
    mysqli_select_db($conn, $database);
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Create login table
$sql = "CREATE TABLE IF NOT EXISTS login (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organizer', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES login(user_id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create events table
$sql = "CREATE TABLE IF NOT EXISTS events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    organizer_id INT NOT NULL,
    image VARCHAR(255),
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create registrations table
$sql = "CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id)
)";
mysqli_query($conn, $sql);

// Create default admin user if not exists
$sql = "SELECT * FROM login WHERE role = 'admin' LIMIT 1";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) == 0) {
    $admin_username = "admin";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $sql = "INSERT INTO login (username, password, role) VALUES (?, ?, 'admin')";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $admin_username, $admin_password);
        mysqli_stmt_execute($stmt);
        $admin_id = mysqli_insert_id($conn);
        
        // Create admin user profile
        $admin_name = "Administrator";
        $admin_email = "admin@example.com";
        $sql = "INSERT INTO users (user_id, name, email, profile_picture) VALUES (?, ?, ?, 'images/admin.png')";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iss", $admin_id, $admin_name, $admin_email);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Make connection available globally
global $conn;
?>