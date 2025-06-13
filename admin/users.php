<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require admin role
requireRole('admin');

$error = '';
$success = '';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Don't allow deleting the last admin
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $result = mysqli_query($conn, $sql);
    $admin_count = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user['role'] === 'admin' && $admin_count <= 1) {
        $error = "Cannot delete the last admin user";
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($conn, "User deleted: ID $user_id");
            $success = "User deleted successfully";
        } else {
            $error = "Error deleting user";
        }
    }
}

// Handle user role update
if (isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    // Don't allow changing the last admin's role
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $result = mysqli_query($conn, $sql);
    $admin_count = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user['role'] === 'admin' && $admin_count <= 1 && $new_role !== 'admin') {
        $error = "Cannot change the last admin's role";
    } else {
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            logActivity($conn, "User role updated: ID $user_id to $new_role");
            $success = "User role updated successfully";
        } else {
            $error = "Error updating user role";
        }
    }
}

// Get all users with their details
$sql = "SELECT u.*, 
        CASE 
            WHEN u.role = 'student' THEN s.matrix_no 
            WHEN u.role = 'organizer' THEN o.club_name 
            ELSE NULL 
        END as additional_info
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN organizers o ON u.id = o.user_id
        ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Student Event Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h3>Admin Panel</h3>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php" class="active">Manage Users</a></li>
                <li><a href="events.php">Manage Events</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h2>Manage Users</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Users</h3>
                    <a href="add_user.php" class="btn btn-primary">Add New User</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Additional Info</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <form method="POST" action="" class="role-form">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="new_role" onchange="this.form.submit()">
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="organizer" <?php echo $user['role'] === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                        <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                    </select>
                                    <input type="hidden" name="update_role" value="1">
                                </form>
                            </td>
                            <td><?php echo htmlspecialchars($user['additional_info'] ?? '-'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form method="POST" action="" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="delete_user" value="1">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
    .role-form {
        display: inline-block;
    }
    
    .role-form select {
        padding: 5px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .delete-form {
        display: inline-block;
    }
    
    .btn-danger {
        padding: 5px 10px;
        font-size: 14px;
    }
    </style>
</body>
</html> 