<?php
require_once '../includes/functions.php';
requireLevel('2'); // Event Organizer only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$errors = [];

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
            
            // Insert event
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, capacity, created_by, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $capacity, $user_id]);
            $event_id = $pdo->lastInsertId();
            
            // Insert category mappings
            if (!empty($selected_categories)) {
                $stmt = $pdo->prepare("INSERT INTO event_category_mapping (event_id, category_id) VALUES (?, ?)");
                foreach ($selected_categories as $category_id) {
                    $stmt->execute([$event_id, $category_id]);
                }
            }
            
            $pdo->commit();
            $message = 'Event created successfully! It is now pending admin approval.';
            
            // Clear form data
            $title = $description = $event_date = $event_time = $location = $capacity = '';
            $selected_categories = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/organizer.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - Event Organizer Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="header">
        <div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 32px;">
            <!-- Left: Profile and Title -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width:56px;height:56px;border-radius:50%;background:#fff;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Organizer</span>
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Dashboard</span>
                </div>
            </div>
            <!-- Right: Navigation Links -->
            <ul class="navbar-nav" style="display: flex; gap: 32px; list-style: none; margin: 0; padding: 0;">
                <li><a href="dashboard.php" class="nav-link" style="color: #fff; font-weight: 500;">Dashboard</a></li>
                <li><a href="events.php" class="nav-link" style="color: #fff; font-weight: 500;">My Events</a></li>
                <li><a href="create_event.php" class="nav-link" style="color: #fff; font-weight: 500;">Create Event</a></li>
                <li><a href="registrations.php" class="nav-link" style="color: #fff; font-weight: 500;">Registrations</a></li>
                <li><a href="analytics.php" class="nav-link" style="color: #fff; font-weight: 500;">Analytics</a></li>
                <li><a href="profile.php" class="nav-link" style="color: #fff; font-weight: 500;">Profile</a></li>
                <li><a href="../logout.php" class="nav-link" style="color: #fff; font-weight: 500;">Logout</a></li>
            </ul>
        </div>
    </div>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Create New Event</h1>
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
                <h3 class="card-title">Event Details</h3>
                <p class="text-muted mb-0">Fill in the details below to create a new event. Your event will be reviewed by an administrator before approval.</p>
            </div>
            <form id="eventForm" method="POST" action="">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" id="title" name="title" class="form-control" 
                                       value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" id="capacity" name="capacity" class="form-control" 
                                       value="<?php echo isset($capacity) ? htmlspecialchars($capacity) : ''; ?>" 
                                       placeholder="Leave empty for unlimited">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Event Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_date" class="form-label">Event Date *</label>
                                <input type="date" id="event_date" name="event_date" class="form-control" 
                                       value="<?php echo isset($event_date) ? htmlspecialchars($event_date) : ''; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="event_time" class="form-label">Event Time *</label>
                                <input type="time" id="event_time" name="event_time" class="form-control" 
                                       value="<?php echo isset($event_time) ? htmlspecialchars($event_time) : ''; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location" class="form-label">Event Location *</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Event Categories</label>
                        <div class="row">
                            <?php foreach ($categories as $category): ?>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <input type="checkbox" name="categories[]" value="<?php echo $category['category_id']; ?>" 
                                           <?php echo in_array($category['category_id'], $selected_categories ?? []) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Create Event</button>
                    <a href="events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Event Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h4 class="card-title">Event Creation Guidelines</h4>
            </div>
            <div class="card-body">
                <ul>
                    <li>Provide a clear and descriptive title for your event</li>
                    <li>Include detailed information about what participants can expect</li>
                    <li>Choose appropriate categories to help students find your event</li>
                    <li>Set a realistic capacity based on venue and resources</li>
                    <li>All events require admin approval before being published</li>
                    <li>You can edit pending events before they are approved</li>
                </ul>
            </div>
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