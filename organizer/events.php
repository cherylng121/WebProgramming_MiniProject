<?php
require_once '../includes/functions.php';
requireLevel('2'); // Event Organizer only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'delete') {
        $event_id = $_POST['event_id'];
        
        // Verify the event belongs to this organizer
        $stmt = $pdo->prepare("SELECT event_id FROM events WHERE event_id = ? AND created_by = ?");
        $stmt->execute([$event_id, $user_id]);
        if ($stmt->fetch()) {
            try {
                $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ? AND created_by = ?");
                $stmt->execute([$event_id, $user_id]);
                $message = 'Event deleted successfully!';
            } catch (Exception $e) {
                $errors[] = 'Error deleting event: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'You can only delete your own events.';
        }
    }
}

// Get events for this organizer
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$where_conditions = ["e.created_by = ?"];
$params = [$user_id];

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if (!empty($status_filter)) {
    $where_conditions[] = "e.status = ?";
    $params[] = $status_filter;
}

$where_clause = "WHERE " . implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT e.*, 
                              (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
                       FROM events e 
                       $where_clause 
                       ORDER BY $sort $order");
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for this organizer
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM events WHERE created_by = ? GROUP BY status");
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
    <title>My Events - Event Organizer Dashboard</title>
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
            <h1>My Events</h1>
            <a href="create_event.php" class="btn btn-success">Create New Event</a>
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
                <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="stat-label">Approved Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['rejected'] ?? 0; ?></div>
                <div class="stat-label">Rejected Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($events); ?></div>
                <div class="stat-label">Total Events</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-box">
            <form method="GET" action="">
                <div class="search-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="searchInput" class="form-label">Search Events</label>
                        <input type="text" id="searchInput" name="search" class="form-control" 
                               placeholder="Search by title, description, or location" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status" class="form-label">Status Filter</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sort" class="form-label">Sort By</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                            <option value="event_date" <?php echo $sort == 'event_date' ? 'selected' : ''; ?>>Event Date</option>
                            <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>Title</option>
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

        <!-- Events List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Events List (<?php echo count($events); ?> events)</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Registrations</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></small>
                            </td>
                            <td>
                                <?php echo formatDate($event['event_date']); ?>
                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($event['event_time'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                            <td><?php echo $event['capacity'] ? $event['capacity'] : 'Unlimited'; ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $event['registration_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $event['status'] == 'approved' ? 'success' : 
                                        ($event['status'] == 'pending' ? 'warning' : 
                                        ($event['status'] == 'rejected' ? 'danger' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($event['created_at']); ?></td>
                            <td>
                                <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-info btn-sm">View</a>
                                <?php if ($event['status'] == 'pending'): ?>
                                    <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <?php endif; ?>
                                <a href="registrations.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-success btn-sm">Registrations</a>
                                <form method="POST" action="?action=delete" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this event?')">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (empty($events)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <h4>No events found</h4>
                    <p class="text-muted">You haven't created any events yet.</p>
                    <a href="create_event.php" class="btn btn-primary">Create Your First Event</a>
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