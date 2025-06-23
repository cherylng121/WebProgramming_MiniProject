<?php
require_once '../includes/functions.php';
requireLevel('2'); // Event Organizer only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get statistics for this organizer
$stats = [];

// Total events created by this organizer
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE created_by = ?");
$stmt->execute([$user_id]);
$stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending events
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE created_by = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$stats['pending_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Approved events
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE created_by = ? AND status = 'approved'");
$stmt->execute([$user_id]);
$stats['approved_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total registrations for this organizer's events
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE e.created_by = ?");
$stmt->execute([$user_id]);
$stats['total_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent events by this organizer
$stmt = $pdo->prepare("SELECT e.*, 
                              (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
                       FROM events e 
                       WHERE e.created_by = ? 
                       ORDER BY e.created_at DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent registrations for this organizer's events
$stmt = $pdo->prepare("SELECT er.*, e.title as event_title, u.name as student_name 
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       JOIN users u ON er.user_id = u.user_id 
                       WHERE e.created_by = ? 
                       ORDER BY er.registration_date DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$recent_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/organizer.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Organizer Dashboard - Event Management System</title>
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
        <h1 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_events']; ?></div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved_events']; ?></div>
                <div class="stat-label">Approved Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_events']; ?></div>
                <div class="stat-label">Pending Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Events -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">My Recent Events</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Registrations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo formatDate($event['event_date']); ?></td>
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
                    <div class="card-footer">
                        <a href="events.php" class="btn btn-primary">View All Events</a>
                    </div>
                </div>
            </div>

            <!-- Recent Registrations -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Registrations</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_registrations as $registration): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($registration['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['event_title']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $registration['status'] == 'attended' ? 'success' : 
                                                ($registration['status'] == 'registered' ? 'info' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($registration['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($registration['registration_date']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a href="registrations.php" class="btn btn-primary">View All Registrations</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <a href="create_event.php" class="btn btn-success" style="width: 100%; margin-bottom: 1rem;">
                        Create New Event
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="events.php?status=pending" class="btn btn-warning" style="width: 100%; margin-bottom: 1rem;">
                        View Pending Events
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="analytics.php" class="btn btn-info" style="width: 100%; margin-bottom: 1rem;">
                        View Analytics
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="profile.php" class="btn btn-secondary" style="width: 100%; margin-bottom: 1rem;">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Event Status Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Event Status Summary</h3>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['pending_events']; ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['approved_events']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_events'] - $stats['pending_events'] - $stats['approved_events']; ?></div>
                        <div class="stat-label">Rejected/Cancelled</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                        <div class="stat-label">Total Participants</div>
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