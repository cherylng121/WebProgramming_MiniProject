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

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if (!mysqli_query($conn, $sql)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
if (!mysqli_select_db($conn, $database)) {
    die("Error selecting database: " . mysqli_error($conn));
}

// Disable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Drop existing tables in correct order
$tables = [
    "DROP TABLE IF EXISTS registrations",
    "DROP TABLE IF EXISTS events",
    "DROP TABLE IF EXISTS users",
    "DROP TABLE IF EXISTS login"
];

foreach ($tables as $sql) {
    mysqli_query($conn, $sql);
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// Create login table
$sql = "CREATE TABLE login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organizer', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Create users table
$sql = "CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    login_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (login_id) REFERENCES login(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create events table
$sql = "CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    organizer_id INT NOT NULL,
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create registrations table
$sql = "CREATE TABLE registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create default admin user
$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_role = "admin";

// Insert into login table
$stmt = mysqli_prepare($conn, "INSERT INTO login (username, password, role) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $admin_username, $admin_password, $admin_role);
mysqli_stmt_execute($stmt);
$admin_login_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Insert into users table
$admin_name = "Admin User";
$admin_email = "admin@example.com";
$stmt = mysqli_prepare($conn, "INSERT INTO users (login_id, name, email) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iss", $admin_login_id, $admin_name, $admin_email);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Function to get profile picture based on role
function getProfilePicture($role) {
    switch ($role) {
        case 'admin':
            return '/wp_project/images/admin.png';
        case 'organizer':
            return '/wp_project/images/eventOrganizer.png';
        case 'student':
            return '/wp_project/images/student.png';
        default:
            return '/wp_project/images/default.png';
    }
}

// Remove status column if it exists
$check_column = "SHOW COLUMNS FROM registrations LIKE 'status'";
$result = mysqli_query($conn, $check_column);
if (mysqli_num_rows($result) > 0) {
    $alter_sql = "ALTER TABLE registrations DROP COLUMN status";
    mysqli_query($conn, $alter_sql);
}

return $conn;
?> 