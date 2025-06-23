<?php
require_once '../includes/functions.php';
requireLevel('3'); // Student only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'cancel_registration') {
        $registration_id = $_POST['registration_id'];
        
        // Verify the registration belongs to this user
        $stmt = $pdo->prepare("SELECT er.*, e.event_date, e.title FROM event_registrations er 
                               JOIN events e ON er.event_id = e.event_id 
                               WHERE er.registration_id = ? AND er.user_id = ?");
        $stmt->execute([$registration_id, $user_id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($registration) {
            // Check if event is in the future
            if (strtotime($registration['event_date']) > time()) {
                try {
                    $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE registration_id = ?");
                    $stmt->execute([$registration_id]);
                    $message = 'Registration cancelled successfully!';
                } catch (Exception $e) {
                    $errors[] = 'Error cancelling registration: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Cannot cancel registration for past events.';
            }
        } else {
            $errors[] = 'Invalid registration.';
        }
    }
}

// Get filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'registration_date';
$order = $_GET['order'] ?? 'DESC';

// Build query
$where_conditions = ["er.user_id = ?"];
$params = [$user_id];

if (!empty($status_filter)) {
    $where_conditions[] = "er.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE ? OR e.location LIKE ? OR u.name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$where_clause = "WHERE " . implode(' AND ', $where_conditions);

// Get registrations
$stmt = $pdo->prepare("SELECT er.*, e.title as event_title, e.event_date, e.event_time, e.location, e.capacity,
                              u.name as organizer_name, u.email as organizer_email
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       JOIN users u ON e.created_by = u.user_id 
                       $where_clause 
                       ORDER BY $sort $order");
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT er.status, COUNT(*) as count 
                       FROM event_registrations er 
                       WHERE er.user_id = ? 
                       GROUP BY er.status");
$stmt->execute([$user_id]);
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats = [];
foreach ($status_stats as $stat) {
    $stats[$stat['status']] = $stat['count'];
}
$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/student.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Event Management System</title>
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>My Registrations</h1>
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
                               placeholder="Search by event title, location, or organizer" 
                               value="<?php echo htmlspecialchars($search); ?>">
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
                            <option value="event_title" <?php echo $sort == 'event_title' ? 'selected' : ''; ?>>Event Title</option>
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
                <h3 class="card-title">Registration History (<?php echo count($registrations); ?> registrations)</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Event Date</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Organizer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                        <tr>
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
                                <?php echo htmlspecialchars($registration['organizer_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($registration['organizer_email']); ?></small>
                            </td>
                            <td>
                                <?php if ($registration['status'] == 'registered' && strtotime($registration['event_date']) > time()): ?>
                                    <form method="POST" action="?action=cancel_registration" style="display: inline;">
                                        <input type="hidden" name="registration_id" value="<?php echo $registration['registration_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Are you sure you want to cancel this registration?')">
                                            Cancel
                                        </button>
                                    </form>
                                <?php elseif ($registration['status'] == 'attended'): ?>
                                    <span class="text-success">✓ Attended</span>
                                <?php elseif ($registration['status'] == 'cancelled'): ?>
                                    <span class="text-danger">✗ Cancelled</span>
                                <?php else: ?>
                                    <span class="text-muted">Event passed</span>
                                <?php endif; ?>
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
                    <p class="text-muted">You haven't registered for any events yet.</p>
                    <a href="browse_events.php" class="btn btn-primary">Browse Events</a>
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