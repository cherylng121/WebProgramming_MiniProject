<?php
require_once '../includes/functions.php';
requireLevel('2'); // Event Organizer only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'update_status') {
        $registration_id = $_POST['registration_id'];
        $status = $_POST['status'];
        
        // Verify the registration is for an event created by this organizer
        $stmt = $pdo->prepare("SELECT er.registration_id FROM event_registrations er 
                               JOIN events e ON er.event_id = e.event_id 
                               WHERE er.registration_id = ? AND e.created_by = ?");
        $stmt->execute([$registration_id, $user_id]);
        if ($stmt->fetch()) {
            try {
                $stmt = $pdo->prepare("UPDATE event_registrations SET status = ? WHERE registration_id = ?");
                $stmt->execute([$status, $registration_id]);
                $message = 'Registration status updated successfully!';
            } catch (Exception $e) {
                $errors[] = 'Error updating registration: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'You can only update registrations for your own events.';
        }
    }
}

// Get registrations for this organizer's events
$event_filter = $_GET['event_id'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'registration_date';
$order = $_GET['order'] ?? 'DESC';

$where_conditions = ["e.created_by = ?"];
$params = [$user_id];

if (!empty($event_filter)) {
    $where_conditions[] = "e.event_id = ?";
    $params[] = $event_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "er.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR e.title LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$where_clause = "WHERE " . implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT er.*, e.title as event_title, e.event_date, e.event_time, e.location, e.capacity,
                              u.name as student_name, u.email as student_email, u.phone as student_phone
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       JOIN users u ON er.user_id = u.user_id 
                       $where_clause 
                       ORDER BY $sort $order");
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get events for filter dropdown
$stmt = $pdo->prepare("SELECT event_id, title FROM events WHERE created_by = ? ORDER BY title");
$stmt->execute([$user_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT er.status, COUNT(*) as count 
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE e.created_by = ? 
                       GROUP BY er.status");
$stmt->execute([$user_id]);
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats = [];
foreach ($status_stats as $stat) {
    $stats[$stat['status']] = $stat['count'];
}

$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/organizer.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registrations - Event Organizer Dashboard</title>
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
            <h1>Manage Registrations</h1>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['registered'] ?? 0; ?></div>
                <div class="stat-label">Registered</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['attended'] ?? 0; ?></div>
                <div class="stat-label">Attended</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['cancelled'] ?? 0; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($registrations); ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-box">
            <form method="GET" action="">
                <div class="search-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="searchInput" class="form-label">Search Registrations</label>
                        <input type="text" id="searchInput" name="search" class="form-control" 
                               placeholder="Search by student name, email, or event title" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="event_id" class="form-label">Event Filter</label>
                        <select id="event_id" name="event_id" class="form-select">
                            <option value="">All Events</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?php echo $event['event_id']; ?>" 
                                        <?php echo $event_filter == $event['event_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status" class="form-label">Status Filter</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="registered" <?php echo $status_filter == 'registered' ? 'selected' : ''; ?>>Registered</option>
                            <option value="attended" <?php echo $status_filter == 'attended' ? 'selected' : ''; ?>>Attended</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sort" class="form-label">Sort By</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="registration_date" <?php echo $sort == 'registration_date' ? 'selected' : ''; ?>>Registration Date</option>
                            <option value="event_date" <?php echo $sort == 'event_date' ? 'selected' : ''; ?>>Event Date</option>
                            <option value="student_name" <?php echo $sort == 'student_name' ? 'selected' : ''; ?>>Student Name</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="order" class="form-label">Order</label>
                        <select id="order" name="order" class="form-select">
                            <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Registrations List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Registrations List (<?php echo count($registrations); ?> registrations)</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Event</th>
                            <th>Event Date</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($registration['student_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($registration['student_email']); ?></small>
                                <?php if ($registration['student_phone']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($registration['student_phone']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($registration['event_title']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($registration['location']); ?></small>
                            </td>
                            <td>
                                <?php echo formatDate($registration['event_date']); ?>
                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($registration['event_time'])); ?></small>
                            </td>
                            <td><?php echo formatDate($registration['registration_date']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $registration['status'] == 'attended' ? 'success' : 
                                        ($registration['status'] == 'registered' ? 'info' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($registration['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="?action=update_status" style="display: inline;">
                                    <input type="hidden" name="registration_id" value="<?php echo $registration['registration_id']; ?>">
                                    <select name="status" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                        <option value="registered" <?php echo $registration['status'] == 'registered' ? 'selected' : ''; ?>>Registered</option>
                                        <option value="attended" <?php echo $registration['status'] == 'attended' ? 'selected' : ''; ?>>Attended</option>
                                        <option value="cancelled" <?php echo $registration['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-warning btn-sm">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (empty($registrations)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <h4>No registrations found</h4>
                    <p class="text-muted">No students have registered for your events yet.</p>
                </div>
            </div>
        <?php endif; ?>
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