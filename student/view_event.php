<?php
require_once '../includes/functions.php';
requireLevel('3'); // Student only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$event_id) {
    echo "<div class='alert alert-danger'>Invalid event ID.</div>";
    exit;
}

// Fetch event details (only approved events)
$stmt = $pdo->prepare("SELECT e.*, u.name AS organizer_name, u.email AS organizer_email
                       FROM events e
                       JOIN users u ON e.created_by = u.user_id
                       WHERE e.event_id = ? AND e.status = 'approved'");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='alert alert-danger'>Event not found or not approved.</div>";
    exit;
}

// Fetch event categories
$stmt = $pdo->prepare("SELECT ec.category_name FROM event_category_mapping ecm JOIN event_categories ec ON ecm.category_id = ec.category_id WHERE ecm.event_id = ?");
$stmt->execute([$event_id]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Check registration status
$stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
$stmt->execute([$event_id, $user_id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);
$is_registered = $registration ? true : false;
$registration_status = $registration ? $registration['status'] : null;

// Count registrations
$stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ?");
$stmt->execute([$event_id]);
$registration_count = $stmt->fetchColumn();

// Handle registration
$errors = [];
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_event'])) {
    if ($is_registered) {
        $errors[] = 'You are already registered for this event.';
    } else {
        // Check event capacity
        $capacity = $event['capacity'];
        if (!$capacity || $registration_count < $capacity) {
            try {
                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, registration_date, status) VALUES (?, ?, NOW(), 'registered')");
                $stmt->execute([$event_id, $user_id]);
                $message = 'Successfully registered for the event!';
                $is_registered = true;
                $registration_status = 'registered';
                $registration_count++;
            } catch (Exception $e) {
                $errors[] = 'Registration failed: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Event is at full capacity.';
        }
    }
}

$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/student.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 32px;">
            <!-- Left: Profile and Title -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width:56px;height:56px;border-radius:50%;background:#fff;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Student</span>
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Dashboard</span>
                </div>
            </div>
            <!-- Right: Navigation Links -->
            <ul class="navbar-nav" style="display: flex; gap: 32px; list-style: none; margin: 0; padding: 0;">
                <li><a href="dashboard.php" class="nav-link" style="color: #fff; font-weight: 500;">Dashboard</a></li>
                <li><a href="browse_events.php" class="nav-link" style="color: #fff; font-weight: 500;">Browse Events</a></li>
                <li><a href="my_registrations.php" class="nav-link" style="color: #fff; font-weight: 500;">My Registrations</a></li>
                <li><a href="profile.php" class="nav-link" style="color: #fff; font-weight: 500;">Profile</a></li>
                <li><a href="../logout.php" class="nav-link" style="color: #fff; font-weight: 500;">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <a href="browse_events.php" class="btn btn-secondary mb-2">&larr; Back to Browse Events</a>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h2>
            </div>
            <div class="card-body">
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
                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <p><strong>Date:</strong> <?php echo formatDate($event['event_date']); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['event_time'])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>Capacity:</strong> <?php echo $event['capacity'] ? $event['capacity'] : 'Unlimited'; ?></p>
                <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?> (<?php echo htmlspecialchars($event['organizer_email']); ?>)</p>
                <p><strong>Registrations:</strong> <?php echo $registration_count; ?><?php if ($event['capacity']) { echo ' / ' . $event['capacity']; } ?></p>
                <?php if (!empty($categories)): ?>
                    <p><strong>Categories:</strong> 
                        <?php foreach ($categories as $cat): ?>
                            <span class="badge badge-secondary"><?php echo htmlspecialchars($cat); ?></span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
                <p><strong>Status:</strong> <span class="badge badge-success">Approved</span></p>
                <div class="mt-2">
                    <?php if (!$is_registered): ?>
                        <?php if (!$event['capacity'] || $registration_count < $event['capacity']): ?>
                            <form method="POST" action="" style="display: inline;">
                                <button type="submit" name="register_event" class="btn btn-primary">Register for this Event</button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Full Capacity</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-success" disabled>
                            <?php echo $registration_status == 'attended' ? 'Attended' : ($registration_status == 'registered' ? 'Already Registered' : ucfirst($registration_status)); ?>
                        </button>
                    <?php endif; ?>
                </div>
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
</body>
</html>
