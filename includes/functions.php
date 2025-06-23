<?php
session_start();
require_once dirname(__FILE__) . '/../database.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getUserLevel() {
    return isset($_SESSION['user_level']) ? $_SESSION['user_level'] : null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireLevel($level) {
    requireLogin();
    if (getUserLevel() != $level) {
        header('Location: ../unauthorized.php');
        exit();
    }
}

// Input validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}

function validateAge($age) {
    return is_numeric($age) && $age > 0 && $age < 150;
}

function validateFutureDate($date) {
    return strtotime($date) >= strtotime(date('Y-m-d'));
}

function validateCapacity($capacity) {
    return is_numeric($capacity) && $capacity > 0;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Cookie functions
function setUserCookie($username, $user_id) {
    $cookie_value = base64_encode($username . '|' . $user_id);
    setcookie('user_remember', $cookie_value, time() + (86400 * 30), "/"); // 30 days
}

function getUserFromCookie() {
    if (isset($_COOKIE['user_remember'])) {
        $cookie_value = base64_decode($_COOKIE['user_remember']);
        $parts = explode('|', $cookie_value);
        if (count($parts) == 2) {
            return ['username' => $parts[0], 'user_id' => $parts[1]];
        }
    }
    return null;
}

function clearUserCookie() {
    setcookie('user_remember', '', time() - 3600, "/");
}

// Database helper functions
function getUserById($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.*, l.username, l.user_level, up.matric_number, up.department, up.year_of_study 
                          FROM users u 
                          LEFT JOIN login l ON u.user_id = l.user_id 
                          LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                          WHERE u.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserByUsername($username) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.*, l.username, l.password, l.user_level, l.is_active 
                          FROM users u 
                          JOIN login l ON u.user_id = l.user_id 
                          WHERE l.username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateLastLogin($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE login SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

// Flash message functions
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Date formatting
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('F j, Y g:i A', strtotime($datetime));
}

// User level names
function getUserLevelName($level) {
    switch($level) {
        case '1': return 'Administrator';
        case '2': return 'Event Organizer';
        case '3': return 'Student';
        default: return 'Unknown';
    }
}
?> 