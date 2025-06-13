<?php
require_once 'config/session.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    switch (getUserRole()) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit();
        case 'organizer':
            header('Location: organizer/events.php');
            exit();
        case 'student':
            header('Location: student/dashboard.php');
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .main-landing {
            max-width: 500px;
            margin: 80px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 40px 30px;
            text-align: center;
        }
        .main-landing h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .main-landing p {
            color: #555;
            margin-bottom: 30px;
        }
        .main-landing .btn {
            margin: 0 10px;
            padding: 12px 30px;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="main-landing">
        <h1>Welcome to the Student Event Management System</h1>
        <p>Organize, manage, and participate in student club events with ease.<br>
        Please login or register to get started.</p>
        <a href="login.php" class="btn btn-primary">Login</a>
        <a href="register.php" class="btn btn-secondary">Register as Student</a>
    </div>
</body>
</html> 