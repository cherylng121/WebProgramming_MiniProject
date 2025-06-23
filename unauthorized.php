<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - Event Management System</title>
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
                        <h2 class="card-title text-center text-danger">Unauthorized Access</h2>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-3">You don't have permission to access this page.</p>
                        <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
                        <a href="logout.php" class="btn btn-secondary">Logout</a>
                    </div>
                </div>
            </div>
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