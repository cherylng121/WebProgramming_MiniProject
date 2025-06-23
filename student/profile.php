<?php
require_once '../includes/functions.php';
requireLevel('3'); // Student only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Get user profile
$stmt = $pdo->prepare("SELECT u.*, l.username, l.user_level, up.matric_number, up.department, up.year_of_study 
                       FROM users u 
                       LEFT JOIN login l ON u.user_id = l.user_id 
                       LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                       WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $age = sanitizeInput($_POST['age']);
    $phone = sanitizeInput($_POST['phone']);
    $username = sanitizeInput($_POST['username']);
    $matric_number = sanitizeInput($_POST['matric_number']);
    $department = sanitizeInput($_POST['department']);
    $year_of_study = sanitizeInput($_POST['year_of_study']);
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!validateEmail($email)) $errors[] = 'Invalid email format';
    if (empty($username)) $errors[] = 'Username is required';
    if (!validateUsername($username)) $errors[] = 'Invalid username format';
    if ($age && !validateAge($age)) $errors[] = 'Invalid age';
    if ($phone && !validatePhone($phone)) $errors[] = 'Invalid phone number';
    
    // Check if username is already taken by another user
    if ($username != $user['username']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            $errors[] = 'Username is already taken';
        }
    }
    
    // Check if email is already taken by another user
    if ($email != $user['email']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            $errors[] = 'Email is already taken';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update user
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, address = ?, age = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $address, $age, $phone, $user_id]);
            
            // Update login credentials
            $stmt = $pdo->prepare("UPDATE login SET username = ? WHERE user_id = ?");
            $stmt->execute([$username, $user_id]);
            
            // Update or insert profile
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                $stmt = $pdo->prepare("UPDATE user_profiles SET matric_number = ?, department = ?, year_of_study = ? WHERE user_id = ?");
                $stmt->execute([$matric_number, $department, $year_of_study, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, matric_number, department, year_of_study) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $matric_number, $department, $year_of_study]);
            }
            
            $pdo->commit();
            $message = 'Profile updated successfully!';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT u.*, l.username, l.user_level, up.matric_number, up.department, up.year_of_study 
                                   FROM users u 
                                   LEFT JOIN login l ON u.user_id = l.user_id 
                                   LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                                   WHERE u.user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get student activity
$stmt = $pdo->prepare("SELECT er.*, e.title as event_title, e.event_date, e.location 
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE er.user_id = ? 
                       ORDER BY er.registration_date DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$student_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get registration statistics
$stmt = $pdo->prepare("SELECT er.status, COUNT(*) as count 
                       FROM event_registrations er 
                       WHERE er.user_id = ? 
                       GROUP BY er.status");
$stmt->execute([$user_id]);
$registration_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats = [];
foreach ($registration_stats as $stat) {
    $stats[$stat['status']] = $stat['count'];
}
$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/student.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Event Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 32px;">
            <!-- Left: Profile and Title -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width:56px;height:56px;border-radius:50%;background:#fff;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Student</span>
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Dashboard</span>
                </div>
            </div>
            <!-- Right: Navigation Links -->
            <ul class="navbar-nav" style="display: flex; gap: 32px; list-style: none; margin: 0; padding: 0;">
                <li><a href="dashboard.php" class="nav-link" style="color: #fff; font-weight: 500;">Dashboard</a></li>
                <li><a href="browse_events.php" class="nav-link" style="color: #fff; font-weight: 500;">Browse Events</a></li>
                <li><a href="my_registrations.php" class="nav-link" style="color: #fff; font-weight: 500;">My Registrations</a></li>
                <li><a href="profile.php" class="nav-link" style="color: #fff; font-weight: 500;">Profile</a></li>
                <li><a href="../logout.php" class="nav-link" style="color: #fff; font-weight: 500;">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <h1 class="mb-3">My Profile</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Profile</h3>
                    </div>
                    <form id="profileForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" id="age" name="age" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['age']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" id="username" name="username" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" redaonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_level" class="form-label">User Level</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo getUserLevelName($user['user_level']); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="matric_number" class="form-label">Matric Number</label>
                                    <input type="text" id="matric_number" name="matric_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['matric_number']); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" id="department" name="department" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['department']); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="year_of_study" class="form-label">Year of Study</label>
                                    <input type="number" id="year_of_study" name="year_of_study" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['year_of_study']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>User ID:</strong> <?php echo $user['user_id']; ?></p>
                        <p><strong>Role:</strong> <span class="badge badge-info"><?php echo getUserLevelName($user['user_level']); ?></span></p>
                        <p><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></p>
                        <p><strong>Last Login:</strong> <?php echo isset($user['last_login']) && $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer id="contact" class="footer" style="width:100vw;">
      <div class="footer-content">
        <img src="../images/UniLogo.png" class="university-logo" alt="University Logo">
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

    <script src="../assets/js/validation.js"></script>
</body>
</html> 