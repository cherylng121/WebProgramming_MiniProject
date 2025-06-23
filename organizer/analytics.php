<?php
require_once '../includes/functions.php';
requireLevel('2'); // Event Organizer only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get comprehensive statistics for this organizer
$stats = [];

// Total events created
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE created_by = ?");
$stmt->execute([$user_id]);
$stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Events by status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM events WHERE created_by = ? GROUP BY status");
$stmt->execute([$user_id]);
$event_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats['event_statuses'] = [];
foreach ($event_statuses as $status) {
    $stats['event_statuses'][$status['status']] = $status['count'];
}

// Total registrations
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE e.created_by = ?");
$stmt->execute([$user_id]);
$stats['total_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Registrations by status
$stmt = $pdo->prepare("SELECT er.status, COUNT(*) as count 
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE e.created_by = ? 
                       GROUP BY er.status");
$stmt->execute([$user_id]);
$registration_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats['registration_statuses'] = [];
foreach ($registration_statuses as $status) {
    $stats['registration_statuses'][$status['status']] = $status['count'];
}

// Top performing events
$stmt = $pdo->prepare("SELECT e.title, e.event_date, e.location,
                              COUNT(er.registration_id) as registration_count,
                              SUM(CASE WHEN er.status = 'attended' THEN 1 ELSE 0 END) as attendance_count
                       FROM events e 
                       LEFT JOIN event_registrations er ON e.event_id = er.event_id 
                       WHERE e.created_by = ? 
                       GROUP BY e.event_id 
                       ORDER BY registration_count DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$top_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly event creation trend
$stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                              COUNT(*) as count 
                       FROM events 
                       WHERE created_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       GROUP BY month 
                       ORDER BY month");
$stmt->execute([$user_id]);
$monthly_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly registration trend
$stmt = $pdo->prepare("SELECT DATE_FORMAT(er.registration_date, '%Y-%m') as month, 
                              COUNT(*) as count 
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE e.created_by = ? AND er.registration_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       GROUP BY month 
                       ORDER BY month");
$stmt->execute([$user_id]);
$monthly_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activity
$stmt = $pdo->prepare("SELECT er.registration_date, e.title as event_title, u.name as student_name, er.status
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       JOIN users u ON er.user_id = u.user_id 
                       WHERE e.created_by = ? 
                       ORDER BY er.registration_date DESC 
                       LIMIT 10");
$stmt->execute([$user_id]);
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/organizer.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Event Organizer Dashboard</title>
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
        <h1 class="mb-3">Event Analytics</h1>
        
        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_events']; ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['registration_statuses']['attended'] ?? 0; ?></div>
                <div class="stat-label">Total Attendance</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    echo $stats['total_registrations'] > 0 ? 
                        round(($stats['registration_statuses']['attended'] ?? 0) / $stats['total_registrations'] * 100, 1) : 0; 
                    ?>%
                </div>
                <div class="stat-label">Attendance Rate</div>
            </div>
        </div>

        <div class="row">
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Registration Status Distribution -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Registration Status Distribution</h3>
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
                                    <td><span class="badge badge-info">Registered</span></td>
                                    <td><?php echo $stats['registration_statuses']['registered'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_registrations'] > 0 ? round(($stats['registration_statuses']['registered'] ?? 0) / $stats['total_registrations'] * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-success">Attended</span></td>
                                    <td><?php echo $stats['registration_statuses']['attended'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_registrations'] > 0 ? round(($stats['registration_statuses']['attended'] ?? 0) / $stats['total_registrations'] * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">Cancelled</span></td>
                                    <td><?php echo $stats['registration_statuses']['cancelled'] ?? 0; ?></td>
                                    <td><?php echo $stats['total_registrations'] > 0 ? round(($stats['registration_statuses']['cancelled'] ?? 0) / $stats['total_registrations'] * 100, 1) : 0; ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Events -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Performing Events</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Registrations</th>
                            <th>Attendance</th>
                            <th>Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                            <td><?php echo formatDate($event['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                            <td><?php echo $event['registration_count']; ?></td>
                            <td><?php echo $event['attendance_count']; ?></td>
                            <td>
                                <?php 
                                echo $event['registration_count'] > 0 ? 
                                    round($event['attendance_count'] / $event['registration_count'] * 100, 1) : 0; 
                                ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Event</th>
                                    <th>Student</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td><?php echo formatDate($activity['registration_date']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['event_title']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['student_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $activity['status'] == 'attended' ? 'success' : 
                                                ($activity['status'] == 'registered' ? 'info' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($activity['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Monthly Trends (Last 6 Months)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Events Created</th>
                                    <th>Registrations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $months = [];
                                foreach ($monthly_events as $event) {
                                    $months[$event['month']]['events'] = $event['count'];
                                }
                                foreach ($monthly_registrations as $registration) {
                                    $months[$registration['month']]['registrations'] = $registration['count'];
                                }
                                foreach ($months as $month => $data): 
                                ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($month . '-01')); ?></td>
                                    <td><?php echo $data['events'] ?? 0; ?></td>
                                    <td><?php echo $data['registrations'] ?? 0; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Performance Insights</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5>Event Success Rate</h5>
                        <p class="text-muted">
                            <?php 
                            $approved_events = $stats['event_statuses']['approved'] ?? 0;
                            $success_rate = $stats['total_events'] > 0 ? round($approved_events / $stats['total_events'] * 100, 1) : 0;
                            echo $success_rate . '% of your events have been approved.';
                            ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h5>Average Attendance</h5>
                        <p class="text-muted">
                            <?php 
                            $avg_attendance = $stats['total_events'] > 0 ? round($stats['total_registrations'] / $stats['total_events'], 1) : 0;
                            echo 'Average of ' . $avg_attendance . ' registrations per event.';
                            ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h5>Student Engagement</h5>
                        <p class="text-muted">
                            <?php 
                            $attendance_rate = $stats['total_registrations'] > 0 ? 
                                round(($stats['registration_statuses']['attended'] ?? 0) / $stats['total_registrations'] * 100, 1) : 0;
                            echo $attendance_rate . '% of registered students attended events.';
                            ?>
                        </p>
                    </div>
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