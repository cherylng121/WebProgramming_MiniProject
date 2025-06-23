<?php
require_once 'includes/functions.php';

$pdo = getDBConnection();
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $age = sanitizeInput($_POST['age']);
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $user_level = $_POST['user_level']; // '2' for organizer, '3' for student

    // Student-specific fields
    $matric_number = isset($_POST['matric_number']) ? sanitizeInput($_POST['matric_number']) : null;
    $department = isset($_POST['department']) ? sanitizeInput($_POST['department']) : null;
    $year_of_study = isset($_POST['year_of_study']) ? sanitizeInput($_POST['year_of_study']) : null;

    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !validateEmail($email)) $errors[] = 'Valid email is required';
    if (empty($username) || !validateUsername($username)) $errors[] = 'Valid username is required';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($age && !validateAge($age)) $errors[] = 'Invalid age';
    if ($phone && !validatePhone($phone)) $errors[] = 'Invalid phone number';
    if ($user_level == '3') { // Student
        if (empty($matric_number)) $errors[] = 'Matric number is required for students';
        if (empty($department)) $errors[] = 'Faculty is required for students';
        if (empty($year_of_study) || !is_numeric($year_of_study)) $errors[] = 'Year of study is required for students';
    }

    // Check for duplicate username/email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) $errors[] = 'Username already exists';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) $errors[] = 'Email already exists';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert into users
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, address, age, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $default_pic = $user_level == '2' ? 'images/organizer.png' : 'images/student.png';
            $stmt->execute([$name, $email, $phone, $address, $age, $default_pic]);
            $user_id = $pdo->lastInsertId();

            // Insert into login
            $stmt = $pdo->prepare("INSERT INTO login (user_id, username, password, user_level, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$user_id, $username, password_hash($password, PASSWORD_DEFAULT), $user_level]);

            // Insert into user_profiles if student
            if ($user_level == '3') {
                $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, matric_number, department, year_of_study) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $matric_number, $department, $year_of_study]);
            }

            $pdo->commit();
            header('Location: login.php?registered=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 48px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 32px 32px 24px 32px;
        }
        .register-title {
            font-size: 2em;
            font-weight: bold;
            color: #5a4fcf;
            margin-bottom: 18px;
            text-align: center;
        }
        .form-group { margin-bottom: 18px; }
        .form-label { font-weight: 500; }
        .btn-primary {
            background: linear-gradient(90deg, #6a5af9 0%, #705df2 100%);
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 500;
            width: 100%;
            margin-top: 8px;
        }
        .btn-primary:hover { background: #5a4fcf; }
        .alert { margin-bottom: 18px; }
        .student-fields { display: none; }
    </style>
    <script>
        function toggleStudentFields() {
            var userLevel = document.getElementById('user_level').value;
            document.getElementById('studentFields').style.display = (userLevel === '3') ? 'block' : 'none';
        }
        window.onload = toggleStudentFields;
    </script>
</head>
<body>
    <div class="register-container">
        <div class="register-title">Create an Account</div>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-bottom:0;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea class="form-control" id="address" name="address"><?= isset($address) ? htmlspecialchars($address) : '' ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="age">Age</label>
                <input type="number" class="form-control" id="age" name="age" value="<?= isset($age) ? htmlspecialchars($age) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="username">Username *</label>
                <input type="text" class="form-control" id="username" name="username" required value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label class="form-label" for="user_level">Register as *</label>
                <select class="form-control" id="user_level" name="user_level" onchange="toggleStudentFields()" required>
                    <option value="2" <?= (isset($user_level) && $user_level == '2') ? 'selected' : '' ?>>Event Organizer</option>
                    <option value="3" <?= (isset($user_level) && $user_level == '3') ? 'selected' : '' ?>>Student</option>
                </select>
            </div>
            <div id="studentFields" class="student-fields">
                <div class="form-group">
                    <label class="form-label" for="matric_number">Matric Number *</label>
                    <input type="text" class="form-control" id="matric_number" name="matric_number" value="<?= isset($matric_number) ? htmlspecialchars($matric_number) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department">Faculty *</label>
                    <input type="text" class="form-control" id="department" name="department" value="<?= isset($department) ? htmlspecialchars($department) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="year_of_study">Year of Study *</label>
                    <input type="number" class="form-control" id="year_of_study" name="year_of_study" value="<?= isset($year_of_study) ? htmlspecialchars($year_of_study) : '' ?>">
                </div>
            </div>
            <button type="submit" class="btn-primary">Register</button>
        </form>
        <div style="text-align:center; margin-top:18px;">
            Already have an account? <a href="login.php" style="color:#5a4fcf; font-weight:500;">Sign In</a> |
            <a href="index.php" style="color:#5a4fcf; font-weight:500;">Back to Home</a>
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