<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: ../login.php");
    exit;
}

// Get admin information
$admin_id = $_SESSION["user_id"];
$admin_name = $_SESSION["name"];

// Handle user management actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"])) {
        switch ($_POST["action"]) {
            case "add_user":
                $username = trim($_POST["username"]);
                $email = trim($_POST["email"]);
                $name = trim($_POST["name"]);
                $address = trim($_POST["address"]);
                $phone = trim($_POST["phone"]);
                $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
                $role = trim($_POST["role"]);
                
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Insert into users table
                    $sql = "INSERT INTO users (email, name, address, phone) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssss", $email, $name, $address, $phone);
                    mysqli_stmt_execute($stmt);
                    
                    $user_id = mysqli_insert_id($conn);
                    
                    // Insert into login table
                    $sql = "INSERT INTO login (username, password, role, user_id) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssi", $username, $password, $role, $user_id);
                    mysqli_stmt_execute($stmt);
                    
                    mysqli_commit($conn);
                    $_SESSION["success"] = "User added successfully!";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $_SESSION["error"] = "Failed to add user.";
                }
                break;
                
            case "edit_user":
                $user_id = (int)$_POST["user_id"];
                $username = trim($_POST["username"]);
                $email = trim($_POST["email"]);
                $name = trim($_POST["name"]);
                $address = trim($_POST["address"]);
                $phone = trim($_POST["phone"]);
                $role = trim($_POST["role"]);
                
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Update users table
                    $sql = "UPDATE users SET email = ?, name = ?, address = ?, phone = ? WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssssi", $email, $name, $address, $phone, $user_id);
                    mysqli_stmt_execute($stmt);
                    
                    // Update login table
                    $sql = "UPDATE login SET username = ?, role = ? WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssi", $username, $role, $user_id);
                    mysqli_stmt_execute($stmt);
                    
                    mysqli_commit($conn);
                    $_SESSION["success"] = "User updated successfully!";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $_SESSION["error"] = "Failed to update user.";
                }
                break;
                
            case "delete_user":
                $user_id = (int)$_POST["user_id"];
                
                try {
                    // Delete user (cascade will handle related records)
                    $sql = "DELETE FROM users WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    
                    $_SESSION["success"] = "User deleted successfully!";
                } catch (Exception $e) {
                    $_SESSION["error"] = "Failed to delete user.";
                }
                break;
        }
    }
}

// Get all users
$users = [];
$sql = "SELECT u.*, l.username, l.role 
        FROM users u 
        JOIN login l ON u.user_id = l.user_id 
        ORDER BY u.user_id DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// Get all events with registration counts
$events = [];
$sql = "SELECT e.*, u.name as organizer_name,
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as registration_count
        FROM events e 
        JOIN users u ON e.organizer_id = u.user_id 
        ORDER BY e.event_date DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
}

// Get registrations grouped by event
$event_registrations = [];
$sql = "SELECT e.event_id, e.title as event_title,
        COUNT(r.registration_id) as total_registrations,
        SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM events e
        LEFT JOIN registrations r ON e.event_id = r.event_id
        GROUP BY e.event_id, e.title
        ORDER BY e.event_date DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $event_registrations[$row['event_id']] = $row;
    }
}

// Get all registrations
$registrations = [];
$sql = "SELECT r.*, e.title as event_title, u.name as student_name 
        FROM registrations r 
        JOIN events e ON r.event_id = e.event_id 
        JOIN users u ON r.student_id = u.user_id 
        ORDER BY r.registration_date DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $registrations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#events">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#registrations">Registrations</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($admin_name); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION["success"])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION["success"];
                unset($_SESSION["success"]);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION["error"])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION["error"];
                unset($_SESSION["error"]);
                ?>
            </div>
        <?php endif; ?>

        <!-- User Management Section -->
        <section id="users" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user["user_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($user["username"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["name"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($user["role"]); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user["user_id"]; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Event Management Section -->
        <section id="events" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Event Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-plus"></i> Add Event
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Organizer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo $event["event_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($event["title"]); ?></td>
                                    <td><?php echo htmlspecialchars($event["event_date"]); ?></td>
                                    <td><?php echo htmlspecialchars($event["location"]); ?></td>
                                    <td><?php echo htmlspecialchars($event["organizer_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($event["status"]); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?php echo $event["event_id"]; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Registration Management Section -->
        <section id="registrations" class="mb-5">
            <h2 class="mb-4">Registration Management</h2>
            
            <!-- Event Selection -->
            <div class="mb-4">
                <select class="form-select" id="eventFilter" onchange="filterRegistrations(this.value)">
                    <option value="">All Events</option>
                    <?php foreach ($events as $event): ?>
                    <option value="<?php echo $event['event_id']; ?>">
                        <?php echo htmlspecialchars($event['title']); ?> 
                        (<?php echo $event['registration_count']; ?> registrations)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Registration Statistics -->
            <div class="row mb-4">
                <?php foreach ($event_registrations as $event_id => $stats): ?>
                <div class="col-md-6 col-lg-3 mb-3 event-stats" data-event-id="<?php echo $event_id; ?>">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars($stats['event_title']); ?></h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total:</span>
                                <span class="badge bg-primary"><?php echo $stats['total_registrations']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Pending:</span>
                                <span class="badge bg-warning"><?php echo $stats['pending_count']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Approved:</span>
                                <span class="badge bg-success"><?php echo $stats['approved_count']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Rejected:</span>
                                <span class="badge bg-danger"><?php echo $stats['rejected_count']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Cancelled:</span>
                                <span class="badge bg-secondary"><?php echo $stats['cancelled_count']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Registrations Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $registration): ?>
                                <tr class="registration-row" data-event-id="<?php echo $registration['event_id']; ?>">
                                    <td><?php echo $registration["registration_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($registration["student_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["event_title"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["registration_date"]); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($registration["status"]) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'primary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($registration["status"])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewRegistrationModal" onclick="viewRegistrationDetails(<?php echo htmlspecialchars(json_encode($registration)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($registration["status"] == "pending"): ?>
                                            <button class="btn btn-sm btn-success" onclick="updateRegistrationStatus(<?php echo $registration["registration_id"]; ?>, 'approved')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="updateRegistrationStatus(<?php echo $registration["registration_id"]; ?>, 'rejected')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- View Registration Modal -->
        <div class="modal fade" id="viewRegistrationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registration Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Student Name</label>
                            <p id="view_student_name"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Event Title</label>
                            <p id="view_event_title"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Registration Date</label>
                            <p id="view_registration_date"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <p id="view_status"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Registration Status Modal -->
        <div class="modal fade" id="updateRegistrationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Registration Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update_registration">
                            <input type="hidden" name="registration_id" id="update_registration_id">
                            <input type="hidden" name="status" id="update_status">
                            <p>Are you sure you want to <span id="status_action"></span> this registration?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="student">Student</option>
                                <option value="organizer">Event Organizer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" id="edit_address" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="student">Student</option>
                                <option value="organizer">Event Organizer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user?</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_address').value = user.address;
            document.getElementById('edit_phone').value = user.phone;
            document.getElementById('edit_role').value = user.role;
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }

        function editEvent(event) {
            // Implement event editing functionality
        }

        function deleteEvent(eventId) {
            // Implement event deletion functionality
        }

        function viewRegistrationDetails(registration) {
            document.getElementById('view_student_name').textContent = registration.student_name;
            document.getElementById('view_event_title').textContent = registration.event_title;
            document.getElementById('view_registration_date').textContent = registration.registration_date;
            document.getElementById('view_status').textContent = registration.status;
        }

        function updateRegistrationStatus(registrationId, status) {
            document.getElementById('update_registration_id').value = registrationId;
            document.getElementById('update_status').value = status;
            document.getElementById('status_action').textContent = status === 'approved' ? 'approve' : 'reject';
            new bootstrap.Modal(document.getElementById('updateRegistrationModal')).show();
        }

        function filterRegistrations(eventId) {
            const rows = document.querySelectorAll('.registration-row');
            const stats = document.querySelectorAll('.event-stats');
            
            rows.forEach(row => {
                if (!eventId || row.dataset.eventId === eventId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            stats.forEach(stat => {
                if (!eventId || stat.dataset.eventId === eventId) {
                    stat.style.display = '';
                } else {
                    stat.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html> 