<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require organizer role
requireRole('organizer');

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get event details
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id) as registration_count,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id AND status = 'approved') as approved_count
        FROM events e
        WHERE e.event_id = ? AND e.organizer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $event_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = $stmt->get_result();

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "Event not found or unauthorized access.";
    header("Location: events.php");
    exit();
}

$event = mysqli_fetch_assoc($result);

// Get registrations
$sql = "SELECT r.*, s.matrix_no, u.name as student_name, u.email as student_email
        FROM registrations r
        JOIN students s ON r.student_id = s.user_id
        JOIN users u ON s.user_id = u.id
        WHERE r.event_id = ?
        ORDER BY r.registration_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$registrations = $stmt->get_result();

// Handle registration status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $registration_id = (int)$_POST['registration_id'];
    $new_status = $_POST['new_status'];
    
    if (in_array($new_status, ['pending', 'approved', 'rejected'])) {
        $sql = "UPDATE registrations 
                SET status = ? 
                WHERE registration_id = ? AND event_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $new_status, $registration_id, $event_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Registration status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating registration status.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid status.";
    }
    
    header("Location: view_event.php?id=" . $event_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - Student Event Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h3>Organizer Panel</h3>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="events.php" class="active">Manage Events</a></li>
                <li><a href="registrations.php">Manage Registrations</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>Event Details</h2>
                <div class="header-actions">
                    <a href="edit_event.php?id=<?php echo $event_id; ?>" class="btn btn-primary">Edit Event</a>
                    <a href="events.php" class="btn btn-secondary">Back to Events</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="event-header">
                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                    <span class="status-badge <?php echo $event['status']; ?>">
                        <?php echo ucfirst($event['status']); ?>
                    </span>
                </div>
                
                <div class="event-details">
                    <div class="detail-item">
                        <label>Date & Time:</label>
                        <span><?php echo date('F d, Y H:i', strtotime($event['event_date'])); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <label>Location:</label>
                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <label>Capacity:</label>
                        <span><?php echo $event['capacity']; ?> participants</span>
                    </div>
                    
                    <div class="detail-item">
                        <label>Registrations:</label>
                        <span><?php echo $event['registration_count']; ?> total (<?php echo $event['approved_count']; ?> approved)</span>
                    </div>
                </div>
                
                <div class="event-description">
                    <h4>Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Registrations</h3>
                    <div class="registration-stats">
                        <span class="stat-item">
                            <strong>Total:</strong> <?php echo $event['registration_count']; ?>
                        </span>
                        <span class="stat-item">
                            <strong>Approved:</strong> <?php echo $event['approved_count']; ?>
                        </span>
                        <span class="stat-item">
                            <strong>Available:</strong> <?php echo $event['capacity'] - $event['approved_count']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Matrix No</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($registration = mysqli_fetch_assoc($registrations)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($registration['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($registration['matrix_no']); ?></td>
                                <td><?php echo htmlspecialchars($registration['student_email']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($registration['registration_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $registration['status']; ?>">
                                        <?php echo ucfirst($registration['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="" class="status-form">
                                        <input type="hidden" name="registration_id" value="<?php echo $registration['registration_id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()" <?php echo $event['status'] === 'completed' ? 'disabled' : ''; ?>>
                                            <option value="pending" <?php echo $registration['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $registration['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $registration['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
    .event-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .event-header h3 {
        margin: 0;
        color: #2c3e50;
    }
    
    .event-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .detail-item label {
        display: block;
        color: #666;
        margin-bottom: 5px;
    }
    
    .detail-item span {
        font-weight: 500;
    }
    
    .event-description {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }
    
    .event-description h4 {
        margin: 0 0 10px 0;
        color: #2c3e50;
    }
    
    .registration-stats {
        display: flex;
        gap: 20px;
    }
    
    .stat-item {
        padding: 5px 10px;
        background: #f8f9fa;
        border-radius: 4px;
    }
    
    .header-actions {
        display: flex;
        gap: 10px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9em;
    }
    
    .status-badge.upcoming {
        background-color: #cce5ff;
        color: #004085;
    }
    
    .status-badge.ongoing {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-badge.completed {
        background-color: #e2e3e5;
        color: #383d41;
    }
    
    .status-badge.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-badge.approved {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-badge.rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-form {
        display: inline-block;
    }
    
    .status-form select {
        padding: 5px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .status-form select:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .event-details {
            grid-template-columns: 1fr;
        }
        
        .registration-stats {
            flex-direction: column;
            gap: 10px;
        }
    }
    </style>
</body>
</html>