<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Require organizer role
requireRole('organizer');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $location = trim($_POST['location']);
    $capacity = (int)$_POST['capacity'];
    $status = 'upcoming';
    
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
    } elseif (strtotime($event_date) < time()) {
        $errors[] = "Event date must be in the future.";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required.";
    }
    
    if ($capacity <= 0) {
        $errors[] = "Capacity must be greater than 0.";
    }
    
    if (empty($errors)) {
        // Insert event
        $sql = "INSERT INTO events (organizer_id, title, description, event_date, location, capacity, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssis", 
            $_SESSION['user_id'],
            $title,
            $description,
            $event_date,
            $location,
            $capacity,
            $status
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Event created successfully.";
            header("Location: events.php");
            exit();
        } else {
            $errors[] = "Error creating event. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - Student Event Management System</title>
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
                <h2>Create New Event</h2>
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
                        <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Event Date & Time *</label>
                            <input type="datetime-local" id="event_date" name="event_date" value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="capacity">Capacity *</label>
                            <input type="number" id="capacity" name="capacity" min="1" value="<?php echo isset($_POST['capacity']) ? (int)$_POST['capacity'] : '50'; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Create Event</button>
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
    textarea {
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