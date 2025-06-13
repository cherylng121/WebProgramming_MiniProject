<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require organizer role
requireRole('organizer');

// Handle event deletion
if (isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];
    
    // Verify event belongs to organizer
    $sql = "SELECT * FROM events WHERE event_id = ? AND organizer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $event_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = $stmt->get_result();
    
    if (mysqli_num_rows($result) > 0) {
        // Delete event
        $sql = "DELETE FROM events WHERE event_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Event deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting event.";
        }
    } else {
        $_SESSION['error_message'] = "Unauthorized action.";
    }
    
    header("Location: events.php");
    exit();
}

// Get all events for the organizer
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id) as registration_count
        FROM events e
        WHERE e.organizer_id = ?
        ORDER BY e.event_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$events = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Student Event Management System</title>
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
                <h2>Manage Events</h2>
                <a href="create_event.php" class="btn btn-primary">Create New Event</a>
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
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Registrations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($event = mysqli_fetch_assoc($events)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $event['status']; ?>">
                                        <?php echo ucfirst($event['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $event['registration_count']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                            <button type="submit" name="delete_event" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
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
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        padding: 4px 8px;
        font-size: 0.9em;
    }
    
    .delete-form {
        display: inline;
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
    
    .alert {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    </style>
</body>
</html> 