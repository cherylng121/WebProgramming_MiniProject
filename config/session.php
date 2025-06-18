<?php
// Prevent multiple inclusions
if (defined('SESSION_INCLUDED')) {
    return;
}
define('SESSION_INCLUDED', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Function to get current user's profile picture
function getCurrentUserProfilePicture() {
    if (isset($_SESSION["profile_picture"]) && !empty($_SESSION["profile_picture"])) {
        return $_SESSION["profile_picture"];
    }
    return getProfilePicture($_SESSION["role"] ?? '');
}

// Function to get current user's username
function getCurrentUsername() {
    return $_SESSION["username"] ?? null;
}

// Function to get current user's full name
function getCurrentFullName() {
    return $_SESSION["name"] ?? null;
}

// Function to get current user's ID
function getCurrentUserId() {
    return $_SESSION["user_id"] ?? null;
}

// Function to get current user's role
function getCurrentRole() {
    return $_SESSION["role"] ?? null;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("location: /wp_project/login.php");
        exit;
    }
}

// Function to require specific role
function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header("Location: ../login.php");
        exit();
    }
}

// Function to set success message
function setSuccessMessage($message) {
    $_SESSION["success_msg"] = $message;
}

// Function to set error message
function setErrorMessage($message) {
    $_SESSION["error_msg"] = $message;
}

// Function to get and clear success message
function getSuccessMessage() {
    $message = $_SESSION["success_msg"] ?? null;
    unset($_SESSION["success_msg"]);
    return $message;
}

// Function to get and clear error message
function getErrorMessage() {
    $message = $_SESSION["error_msg"] ?? null;
    unset($_SESSION["error_msg"]);
    return $message;
}

// Function to redirect with message
function redirectWithMessage($url, $message, $type = "success") {
    if ($type === "success") {
        setSuccessMessage($message);
    } else {
        setErrorMessage($message);
    }
    header("location: $url");
    exit;
}

// Function to log user activities
function logActivity($conn, $activity) {
    if (!isLoggedIn()) return;
    
    $user_id = $_SESSION['user_id'];
    $sql = "INSERT INTO activity_log (user_id, activity, created_at) VALUES (?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $activity);
    mysqli_stmt_execute($stmt);
}

// Function to get user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Function to get user ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>