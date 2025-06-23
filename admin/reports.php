<?php
require_once '../includes/functions.php';
requireLevel('1'); // Admin only

$pdo = getDBConnection();

// Get comprehensive statistics
$stats = [];

// User statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT user_level, COUNT(*) as count FROM login GROUP BY user_level");
$user_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats['user_levels'] = [];
foreach ($user_levels as $level) {
    $stats['user_levels'][$level['user_level']] = $level['count'];
}

// Event statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM events GROUP BY status");
$event_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats['event_statuses'] = [];
foreach ($event_statuses as $status) {
    $stats['event_statuses'][$status['status']] = $status['count'];
}

// Registration statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM event_registrations");
$stats['total_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM event_registrations GROUP BY status");
$registration_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats['registration_statuses'] = [];
foreach ($registration_statuses as $status) {
    $stats['registration_statuses'][$status['status']] = $status['count'];
}

// Recent activity
$stmt = $pdo->query("SELECT e.*, u.name as organizer_name, 
                            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
                     FROM events e 
                     LEFT JOIN users u ON e.created_by = u.user_id 
                     ORDER BY e.created_at DESC 
                     LIMIT 10");
$recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top events by registrations
$stmt = $pdo->query("SELECT e.title, e.event_date, u.name as organizer_name,
                            COUNT(er.registration_id) as registration_count
                     FROM events e 
                     LEFT JOIN users u ON e.created_by = u.user_id 
                     LEFT JOIN event_registrations er ON e.event_id = er.event_id 
                     WHERE e.status = 'approved'
                     GROUP BY e.event_id 
                     ORDER BY registration_count DESC 
                     LIMIT 5");
$top_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly statistics
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                            COUNT(*) as count 
                     FROM events 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY month 
                     ORDER BY month");
$monthly_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/admin.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Event Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 32px;">
            <!-- Left: Profile and Title -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width:56px;height:56px;border-radius:50%;background:#fff;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Admin</span>
                    <span style="font-size: 1.1em; font-weight: bold; color: #fff;">Dashboard</span>
                </div>
            </div>
            <!-- Right: Navigation Links -->
            <ul class="navbar-nav" style="display: flex; gap: 32px; list-style: none; margin: 0; padding: 0;">
                <li><a href="dashboard.php" class="nav-link" style="color: #fff; font-weight: 500;">Dashboard</a></li>
                <li><a href="users.php" class="nav-link" style="color: #fff; font-weight: 500;">User Management</a></li>
                <li><a href="events.php" class="nav-link" style="color: #fff; font-weight: 500;">Event Management</a></li>
                <li><a href="reports.php" class="nav-link" style="color: #fff; font-weight: 500;">Reports</a></li>
                <li><a href="profile.php" class="nav-link" style="color: #fff; font-weight: 500;">Profile</a></li>
                <li><a href="../logout.php" class="nav-link" style="color: #fff; font-weight: 500;">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <h1 class="mb-3">System Reports & Analytics</h1>
        
        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_events']; ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['event_statuses']['approved'] ?? 0; ?></div>
                <div class="stat-label">Active Events</div>
            </div>
        </div>

        <div class="row">
            <!-- User Distribution -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">User Distribution</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User Level</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Administrators</td>
                                    <td><?php echo $stats['user_levels']['1'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_users'] > 0 ? round(($stats['user_levels']['1'] ?? 0) / $stats['total_users'] * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td>Event Organizers</td>
                                    <td><?php echo $stats['user_levels']['2'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_users'] > 0 ? round(($stats['user_levels']['2'] ?? 0) / $stats['total_users'] * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td>Students</td>
                                    <td><?php echo $stats['user_levels']['3'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_users'] > 0 ? round(($stats['user_levels']['3'] ?? 0) / $stats['total_users'] * 100, 1) : 0; ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Event Status Distribution -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Event Status Distribution</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                    <td><?php echo $stats['event_statuses']['pending'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_events'] > 0 ? round(($stats['event_statuses']['pending'] ?? 0) / $stats['total_events'] * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-success">Approved</span></td>
                                    <td><?php echo $stats['event_statuses']['approved'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_events'] > 0 ? round(($stats['event_statuses']['approved'] ?? 0) / $stats['total_events'] * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">Rejected</span></td>
                                    <td><?php echo $stats['event_statuses']['rejected'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_events'] > 0 ? round(($stats['event_statuses']['rejected'] ?? 0) / $stats['total_events'] * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-secondary">Cancelled</span></td>
                                    <td><?php echo $stats['event_statuses']['cancelled'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_events'] > 0 ? round(($stats['event_statuses']['cancelled'] ?? 0) / $stats['total_events'] * 100, 1) : 0; ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Events by Registrations -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Top Events by Registrations</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Organizer</th>
                                    <th>Date</th>
                                    <th>Registrations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                                    <td><?php echo formatDate($event['event_date']); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $event['registration_count']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Event Activity</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Organizer</th>
                                    <th>Status</th>
                                    <th>Registrations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $event['status'] == 'approved' ? 'success' : 
                                                ($event['status'] == 'pending' ? 'warning' : 
                                                ($event['status'] == 'rejected' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $event['registration_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration Statistics -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Registration Statistics</h3>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['registration_statuses']['registered'] ?? 0; ?></div>
                        <div class="stat-label">Registered</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['registration_statuses']['attended'] ?? 0; ?></div>
                        <div class="stat-label">Attended</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['registration_statuses']['cancelled'] ?? 0; ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Export Reports</h3>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <a href="export_users.php" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                        Export Users List
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="export_events.php" class="btn btn-success" style="width: 100%; margin-bottom: 1rem;">
                        Export Events List
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="export_registrations.php" class="btn btn-info" style="width: 100%; margin-bottom: 1rem;">
                        Export Registrations
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="export_summary.php" class="btn btn-warning" style="width: 100%; margin-bottom: 1rem;">
                        Export Summary Report
                    </a>
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
</body>
</html> 