<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require admin role
requireRole('admin');

$error = '';
$success = '';

// Handle event deletion
if (isset($_POST['delete_event'])) {
    $event_id = (int)$_POST['event_id'];
    
    $sql = "DELETE FROM events WHERE event_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    
    if (mysqli_stmt_execute($stmt)) {
        logActivity($conn, "Event deleted: ID $event_id");
        $success = "Event deleted successfully";
    } else {
        $error = "Error deleting event";
    }
}

// Handle event status update
if (isset($_POST['update_status'])) {
    $event_id = (int)$_POST['event_id'];
    $new_status = $_POST['new_status'];
    
    $sql = "UPDATE events SET status = ? WHERE event_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $event_id);
    
    if (mysqli_stmt_execute($stmt)) {
        logActivity($conn, "Event status updated: ID $event_id to $new_status");
        $success = "Event status updated successfully";
    } else {
        $error = "Error updating event status";
    }
}

// Get all events with organizer details
$sql = "SELECT e.*, o.club_name, u.name as organizer_name,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id) as registration_count
        FROM events e
        JOIN organizers o ON e.organizer_id = o.user_id
        JOIN users u ON o.user_id = u.id
        ORDER BY e.event_date DESC";
$events = mysqli_query($conn, $sql);
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
            <h3>Admin Panel</h3>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users.php">Manage Users</a></li>
                <li><a href="events.php" class="active">Manage Events</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h2>Manage Events</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Events</h3>
                    <a href="add_event.php" class="btn btn-primary">Add New Event</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Organizer</th>
                            <th>Club</th>
                            <th>Date</th>
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
                            <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                            <td><?php echo htmlspecialchars($event['club_name']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($event['event_date'])); ?></td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                            <td>
                                <form method="POST" action="" class="status-form">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                        <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                        <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td><?php echo $event['registration_count']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary">View</a>
                                    <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary">Edit</a>
                                    <form method="POST" action="" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <input type="hidden" name="delete_event" value="1">
                                        <button type="submit" class="btn btn-danger">Delete</button>
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

    <style>
    .status-form {
        display: inline-block;
    }
    
    .status-form select {
        padding: 5px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    .action-buttons .btn {
        padding: 5px 10px;
        font-size: 14px;
    }
    
    .delete-form {
        display: inline-block;
    }
    </style>
</body>
</html> 