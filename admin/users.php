<?php
require_once '../includes/functions.php';
requireLevel('1'); // Admin only

$pdo = getDBConnection();

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $username = sanitizeInput($_POST['username']);
        $user_level = sanitizeInput($_POST['user_level']);
        
        // Validation
        $errors = [];
        if (empty($name)) $errors[] = 'Name is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($username)) $errors[] = 'Username is required';
        
        if ($action == 'add') {
            $password = $_POST['password'];
            if (empty($password)) $errors[] = 'Password is required';
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                if ($action == 'add') {
                    // Insert user
                    $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
                    $stmt->execute([$name, $email]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Insert login credentials
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO login (user_id, username, password, user_level) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $username, $hashed_password, $user_level]);
                    
                    $message = 'User added successfully!';
                } else {
                    $user_id = $_POST['user_id'];
                    
                    // Update user
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
                    $stmt->execute([$name, $email, $user_id]);
                    
                    // Update login credentials
                    $stmt = $pdo->prepare("UPDATE login SET username = ?, user_level = ? WHERE user_id = ?");
                    $stmt->execute([$username, $user_level, $user_id]);
                    
                    $message = 'User updated successfully!';
                }
                
                $pdo->commit();
                $action = 'list';
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action == 'delete') {
        $user_id = $_POST['user_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $message = 'User deleted successfully!';
        } catch (Exception $e) {
            $errors[] = 'Error deleting user: ' . $e->getMessage();
        }
    }
}

// Get user for editing
$edit_user = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT u.*, l.username, l.user_level 
                          FROM users u 
                          LEFT JOIN login l ON u.user_id = l.user_id 
                          WHERE u.user_id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all users for listing
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'ASC';

$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE u.name LIKE ? OR u.email LIKE ? OR l.username LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT u.*, l.username, l.user_level, l.is_active 
                       FROM users u 
                       LEFT JOIN login l ON u.user_id = l.user_id 
                       $where_clause 
                       ORDER BY $sort $order");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/admin.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Event Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 32px;">
            <!-- Left: Profile and Title -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width:56px;height:56px;border-radius:50%;background:#fff;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Admin</span>
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Dashboard</span>
                </div>
            </div>
            <!-- Right: Navigation Links -->
            <ul class="navbar-nav" style="display: flex; gap: 32px; list-style: none; margin: 0; padding: 0;">
                <li><a href="dashboard.php" class="nav-link" style="color: #fff; font-weight: 500;">Dashboard</a></li>
                <li><a href="users.php" class="nav-link" style="color: #fff; font-weight: 500;">User Management</a></li>
                <li><a href="events.php" class="nav-link" style="color: #fff; font-weight: 500;">Event Management</a></li>
                <li><a href="reports.php" class="nav-link" style="color: #fff; font-weight: 500;">Reports</a></li>
                <li><a href="profile.php" class="nav-link" style="color: #fff; font-weight: 500;">Profile</a></li>
                <li><a href="../logout.php" class="nav-link" style="color: #fff; font-weight: 500;">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Manage Users</h1>
            <a href="?action=add" class="btn btn-success">Add New User</a>
        </div>

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

        <?php if ($action == 'add' || $action == 'edit'): ?>
            <!-- Add/Edit User Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $action == 'add' ? 'Add New User' : 'Edit User'; ?></h3>
                </div>
                <form id="userForm" method="POST" action="">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="form-label">Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['name']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="user_level" class="form-label">User Level *</label>
                                <select id="user_level" name="user_level" class="form-select" required>
                                    <option value="">Select Level</option>
                                    <option value="1" <?php echo ($edit_user && $edit_user['user_level'] == '1') ? 'selected' : ''; ?>>Administrator</option>
                                    <option value="2" <?php echo ($edit_user && $edit_user['user_level'] == '2') ? 'selected' : ''; ?>>Event Organizer</option>
                                    <option value="3" <?php echo ($edit_user && $edit_user['user_level'] == '3') ? 'selected' : ''; ?>>Student</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <?php if ($action == 'add'): ?>
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <div id="passwordStrength"></div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $action == 'add' ? 'Add User' : 'Update User'; ?></button>
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Users List -->
            <div class="search-box">
                <form method="GET" action="">
                    <div class="search-row">
                        <div class="form-group" style="flex: 1;">
                            <label for="searchInput" class="form-label">Search Users</label>
                            <input type="text" id="searchInput" name="search" class="form-control" 
                                   placeholder="Search by name, email, or username" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label for="sort" class="form-label">Sort By</label>
                            <select id="sort" name="sort" class="form-select">
                                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name</option>
                                <option value="email" <?php echo $sort == 'email' ? 'selected' : ''; ?>>Email</option>
                                <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Date Joined</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="order" class="form-label">Order</label>
                            <select id="order" name="order" class="form-select">
                                <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                                <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Users List (<?php echo count($users); ?> users)</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo getUserLevelName($user['user_level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form method="POST" action="?action=delete" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
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
    <script>
        // Password strength indicator
        document.getElementById('password')?.addEventListener('input', function() {
            showPasswordStrength(this.value);
        });
    </script>
</body>
</html> 