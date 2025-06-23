<?php
require_once 'includes/functions.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $user = getUserByUsername($username);
        
        if ($user && password_verify($password, $user['password']) && $user['is_active']) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_level'] = $user['user_level'];
            $_SESSION['name'] = $user['name'];
            
            // Update last login
            updateLastLogin($user['user_id']);
            
            // Set remember me cookie if requested
            if ($remember) {
                setUserCookie($user['username'], $user['user_id']);
            }
            
            // Redirect based on user level
            switch($user['user_level']) {
                case '1': // Admin
                    header('Location: admin/dashboard.php');
                    break;
                case '2': // Event Organizer
                    header('Location: organizer/dashboard.php');
                    break;
                case '3': // Student
                    header('Location: student/dashboard.php');
                    break;
                default:
                    header('Location: index.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="navbar">
            <a href="index.php" class="navbar-brand">Event Management System</a>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6" style="margin: 0 auto;">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title text-center">Login</h2>
                    </div>
                    
                    
                    <form id="loginForm" method="POST" action="">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="remember" value="1"> Remember me
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php"> Sign Up</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/validation.js"></script>
</body>
</html> 