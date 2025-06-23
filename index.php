<?php
require_once 'includes/functions.php';
require_once 'database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            background: url('images/bgImage.png') center center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .landing-container {
            max-width: 540px;
            margin: 48px auto 32px auto;
            position: relative;
            top: unset;
            left: unset;
            transform: none;
            background: rgba(40, 40, 40, 0.7);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 48px 32px 32px 32px;
            text-align: center;
            color: #fff;
            margin-bottom: 200px;
        }
        .landing-title {
            font-size: 2.2em;
            font-weight: bold;
            color: #fff;
            margin-bottom: 12px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        .landing-desc {
            color: #eee;
            font-size: 1.1em;
            margin-bottom: 32px;
            text-shadow: 0 1px 4px rgba(0,0,0,0.18);
        }
        .landing-btn {
            display: inline-block;
            margin: 0 12px;
            padding: 12px 32px;
            font-size: 1.1em;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            background: linear-gradient(90deg, #6a5af9 0%, #705df2 100%);
            color: #fff;
            text-decoration: none;
            transition: background 0.2s;
        }
        .landing-btn:hover {
            background: linear-gradient(90deg, #5a4fcf 0%, #6a5af9 100%);
        }
        .landing-footer {
            margin-top: 48px;
            color: #aaa;
            font-size: 0.95em;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100vw;
            max-width: 100vw;
            padding: 0 48px;
            
            min-height: 64px;
        }
        .navbar-brand {
            font-size: 1.5em;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            letter-spacing: 0.5px;
        }
        .navbar-actions {
            display: flex;
            gap: 16px;
        }
        .navbar-btn {
            display: inline-block;
            padding: 10px 28px;
            font-size: 1.05em;
            font-weight: 500;
            border-radius: 8px;
            border: 2px solid #6a5af9;
            background: #fff;
            color: #6a5af9;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }
        .navbar-btn:hover {
            background: #6a5af9;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="header" style="width:100vw;">
        <div class="navbar">
            <a href="index.php" class="navbar-brand">Event Management System</a>
            <div class="navbar-actions">
                <a href="login.php" class="navbar-btn">Sign In</a>
                <a href="register.php" class="navbar-btn">Sign Up</a>
            </div>
        </div>
    </div>
    <div class="landing-container">
        <div class="landing-title">Welcome to Event Management System</div>
        <div class="landing-desc">
            Manage, organize, and join campus events with ease.
        </div>

    </div>
    <footer id="contact" class="footer" style="width:100vw;">
      <div class="footer-content">
        <img src="images/UniLogo.png" class="university-logo" alt="University Logo">
        <div class="contact">
          <strong>LIM EN DHONG</strong>
          <p>
            A23CS0239<br>
            Year 2 Network & Security<br>
            Faculty of Computing UTM<br>
            limdhong@graduate.utm.my
          </p>
        </div>
        <div class="contact">
          <strong>NG JIN EN</strong>
          <p>
            A23CS0146<br>
            Year 2 Network & Security<br>
            Faculty of Computing UTM<br>
            ngjinen@graduate.utm.my
          </p>
        </div>
        <div class="contact">
          <strong>YEO WERN MIN</strong>
          <p>
            A23CS0285<br>
            Year 2 Network & Security<br>
            Faculty of Computing UTM<br>
            yeomin@graduate.utm.my
          </p>
        </div>
      </div>
        
    <div class="landing-footer" style="text-align:center;">
        &copy; <?php echo date('Y'); ?> Event Management System by Group BlaBlaBla
    </div>
    </footer>
</body>
</html> 