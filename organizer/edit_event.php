<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require organizer role
requireRole('organizer');

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify event exists and belongs to organizer
$sql = "SELECT * FROM events WHERE event_id = ? AND organizer_id = ?";
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $location = trim($_POST['location']);
    $capacity = (int)$_POST['capacity'];
    $status = $_POST['status'];
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    
    if (empty($event_date)) {
        $errors[] = "Event date is required.";
    } elseif (strtotime($event_date) < time() && $status === 'upcoming') {
        $errors[] = "Upcoming event date must be in the future.";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required.";
    }
    
    if ($capacity <= 0) {
        $errors[] = "Capacity must be greater than 0.";
    }
    
    if (!in_array($status, ['upcoming', 'ongoing', 'completed'])) {
        $errors[] = "Invalid status.";
    }
    
    if (empty($errors)) {
        // Update event
        $sql = "UPDATE events 
                SET title = ?, description = ?, event_date = ?, location = ?, capacity = ?, status = ? 
                WHERE event_id = ? AND organizer_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssisii", 
            $title,
            $description,
            $event_date,
            $location,
            $capacity,
            $status,
            $event_id,
            $_SESSION['user_id']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Event updated successfully.";
            header("Location: events.php");
            exit();
        } else {
            $errors[] = "Error updating event. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Student Event Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h3>Organizer Panel</h3>
            <ul>
                <li><a href="organizer.php">Home</a></li>
                <li><a href="events.php" class="active">Manage Events</a></li>
                <li><a href="registrations.php">Manage Registrations</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>Edit Event</h2>
                <a href="events.php" class="btn btn-secondary">Back to Events</a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" action="" class="event-form">
                    <div class="form-group">
                        <label for="title">Event Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Event Date & Time *</label>
                            <input type="datetime-local" id="event_date" name="event_date" value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="capacity">Capacity *</label>
                            <input type="number" id="capacity" name="capacity" min="1" value="<?php echo (int)$event['capacity']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location *</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Event</button>
                        <a href="events.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .event-form {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    input[type="text"],
    input[type="datetime-local"],
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1em;
    }
    
    textarea {
        resize: vertical;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    
    .error-list {
        margin: 0;
        padding-left: 20px;
    }
    
    .error-list li {
        margin-bottom: 5px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html> 