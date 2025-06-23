<?php
require_once '../includes/functions.php';
requireLevel('2'); // Event Organizer only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$errors = [];

// Get event ID
$event_id = $_GET['id'] ?? 0;

// Verify the event belongs to this organizer and is pending
$stmt = $pdo->prepare("SELECT e.*, GROUP_CONCAT(ecm.category_id) as selected_categories 
                       FROM events e 
                       LEFT JOIN event_category_mapping ecm ON e.event_id = ecm.event_id 
                       WHERE e.event_id = ? AND e.created_by = ? AND e.status = 'pending'
                       GROUP BY e.event_id");
$stmt->execute([$event_id, $user_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: events.php');
    exit();
}

// Get event categories
$stmt = $pdo->query("SELECT * FROM event_categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $event_date = sanitizeInput($_POST['event_date']);
    $event_time = sanitizeInput($_POST['event_time']);
    $location = sanitizeInput($_POST['location']);
    $capacity = sanitizeInput($_POST['capacity']);
    $selected_categories = $_POST['categories'] ?? [];
    
    // Validation
    if (empty($title)) $errors[] = 'Event title is required';
    if (empty($description)) $errors[] = 'Event description is required';
    if (empty($event_date)) $errors[] = 'Event date is required';
    if (empty($event_time)) $errors[] = 'Event time is required';
    if (empty($location)) $errors[] = 'Event location is required';
    
    if (!empty($event_date) && !validateFutureDate($event_date)) {
        $errors[] = 'Event date must be today or in the future';
    }
    
    if (!empty($capacity) && !validateCapacity($capacity)) {
        $errors[] = 'Capacity must be a positive number';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update event
            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, capacity = ? WHERE event_id = ? AND created_by = ?");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $capacity, $event_id, $user_id]);
            
            // Remove existing category mappings
            $stmt = $pdo->prepare("DELETE FROM event_category_mapping WHERE event_id = ?");
            $stmt->execute([$event_id]);
            
            // Insert new category mappings
            if (!empty($selected_categories)) {
                $stmt = $pdo->prepare("INSERT INTO event_category_mapping (event_id, category_id) VALUES (?, ?)");
                foreach ($selected_categories as $category_id) {
                    $stmt->execute([$event_id, $category_id]);
                }
            }
            
            $pdo->commit();
            $message = 'Event updated successfully!';
            
            // Refresh event data
            $stmt = $pdo->prepare("SELECT e.*, GROUP_CONCAT(ecm.category_id) as selected_categories 
                                   FROM events e 
                                   LEFT JOIN event_category_mapping ecm ON e.event_id = ecm.event_id 
                                   WHERE e.event_id = ? AND e.created_by = ? AND e.status = 'pending'
                                   GROUP BY e.event_id");
            $stmt->execute([$event_id, $user_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$selected_categories_array = $event['selected_categories'] ? explode(',', $event['selected_categories']) : [];

$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/organizer.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Event Organizer Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="navbar">
            <a href="dashboard.php" class="navbar-brand">Event Organizer Dashboard</a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="events.php" class="nav-link">My Events</a></li>
                <li><a href="create_event.php" class="nav-link">Create Event</a></li>
                <li><a href="registrations.php" class="nav-link">Registrations</a></li>
                <li><a href="analytics.php" class="nav-link">Analytics</a></li>
                <li><a href="profile.php" class="nav-link">Profile</a></li>
                <li><a href="../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Edit Event</h1>
            <a href="events.php" class="btn btn-secondary">Back to Events</a>
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

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit Event Details</h3>
                <p class="text-muted mb-0">You can only edit events that are pending approval.</p>
            </div>
            <form id="eventForm" method="POST" action="">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" id="title" name="title" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['title']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" id="capacity" name="capacity" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['capacity']); ?>" 
                                       placeholder="Leave empty for unlimited">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Event Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_date" class="form-label">Event Date *</label>
                                <input type="date" id="event_date" name="event_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['event_date']); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_time" class="form-label">Event Time *</label>
                                <input type="time" id="event_time" name="event_time" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['event_time']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location" class="form-label">Event Location *</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               value="<?php echo htmlspecialchars($event['location']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Event Categories</label>
                        <div class="row">
                            <?php foreach ($categories as $category): ?>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <input type="checkbox" name="categories[]" value="<?php echo $category['category_id']; ?>" 
                                           <?php echo in_array($category['category_id'], $selected_categories_array) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Event</button>
                    <a href="events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
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
    <script>
        // Set minimum date to today
        document.getElementById('event_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html> 