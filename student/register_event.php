<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require student role
requireRole('student');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = (int)$_POST['event_id'];
    $student_id = $_SESSION['user_id'];
    
    // Check if event exists and is upcoming
    $sql = "SELECT * FROM events WHERE event_id = ? AND status = 'upcoming'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($event = mysqli_fetch_assoc($result)) {
        // Check if already registered
        $sql = "SELECT * FROM registrations WHERE event_id = ? AND student_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $event_id, $student_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "You have already registered for this event";
        } else {
            // Register for the event
            $sql = "INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'pending')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $student_id, $event_id);
            
            if (mysqli_stmt_execute($stmt)) {
                logActivity($conn, "Student registered for event: ID $event_id");
                $success = "Successfully registered for the event";
            } else {
                $error = "Error registering for the event";
            }
        }
    } else {
        $error = "Event not found or registration is closed";
    }
} else {
    $error = "Invalid request";
}

// Redirect back to dashboard with message
if ($error) {
    $_SESSION['error'] = $error;
} else {
    $_SESSION['success'] = $success;
}

header("Location: dashboard.php");
exit();
?> 