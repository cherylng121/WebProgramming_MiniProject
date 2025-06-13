<?php
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Function to check if user has required role
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['role'] === $required_role;
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit();
    }
}

// Function to require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: /unauthorized.php");
        exit();
    }
}

// Function to log user activity
function logActivity($conn, $action) {
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $sql = "INSERT INTO system_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $ip_address);
        mysqli_stmt_execute($stmt);
    }
}

// Function to set remember me cookie
function setRememberMeCookie($user_id) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
    
    setcookie('remember_token', $token, $expiry, '/', '', true, true);
    
    // Store token in database (you'll need to create a remember_tokens table)
    global $conn;
    $sql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isi", $user_id, $token, $expiry);
    mysqli_stmt_execute($stmt);
}

// Function to check remember me cookie
function checkRememberMeCookie() {
    if (isset($_COOKIE['remember_token'])) {
        global $conn;
        $token = $_COOKIE['remember_token'];
        
        $sql = "SELECT user_id FROM remember_tokens WHERE token = ? AND expires_at > NOW()";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Get user details and set session
            $user_id = $row['user_id'];
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        
        // If token is invalid or expired, remove the cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    return false;
}

// Check remember me cookie on every page load
if (!isLoggedIn()) {
    checkRememberMeCookie();
}
?> 