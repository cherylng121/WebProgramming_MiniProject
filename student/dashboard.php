<?php
require_once '../includes/functions.php';
requireLevel('3'); // Student only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get statistics for this student
$stats = [];

// Total events registered
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['total_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Events attended
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE user_id = ? AND status = 'attended'");
$stmt->execute([$user_id]);
$stats['events_attended'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Upcoming events
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE er.user_id = ? AND e.event_date >= CURDATE() AND er.status = 'registered'");
$stmt->execute([$user_id]);
$stats['upcoming_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total available events
$stmt = $pdo->query("SELECT COUNT(*) as count FROM events WHERE status = 'approved' AND event_date >= CURDATE()");
$stats['available_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent registrations for this student
$stmt = $pdo->prepare("SELECT er.*, e.title as event_title, e.event_date, e.event_time, e.location 
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE er.user_id = ? 
                       ORDER BY er.registration_date DESC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$recent_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming events for this student
$stmt = $pdo->prepare("SELECT er.*, e.title as event_title, e.event_date, e.event_time, e.location 
                       FROM event_registrations er 
                       JOIN events e ON er.event_id = e.event_id 
                       WHERE er.user_id = ? AND e.event_date >= CURDATE() AND er.status = 'registered'
                       ORDER BY e.event_date ASC 
                       LIMIT 5");
$stmt->execute([$user_id]);
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Featured events (approved events with most registrations)
$stmt = $pdo->query("SELECT e.*, 
                            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
                     FROM events e 
                     WHERE e.status = 'approved' AND e.event_date >= CURDATE()
                     ORDER BY registration_count DESC, e.event_date ASC 
                     LIMIT 3");
$featured_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h1 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['events_attended']; ?></div>
                <div class="stat-label">Events Attended</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['upcoming_events']; ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['available_events']; ?></div>
                <div class="stat-label">Available Events</div>
            </div>
        </div>

        <!-- Featured Events -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Featured Events</h3>
            </div>
            <div class="row">
                <?php foreach ($featured_events as $event): ?>
                <div class="col-md-4">
                    <div class="event-card">
                        <div class="event-header">
                            <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                            <div class="event-meta">
                                <?php echo formatDate($event['event_date']); ?> at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                            </div>
                        </div>
                        <div class="event-body">
                            <div class="event-description">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 150)) . '...'; ?>
                            </div>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                            <p><strong>Registrations:</strong> <?php echo $event['registration_count']; ?></p>
                        </div>
                        <div class="event-footer">
                            <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer">
                <a href="browse_events.php" class="btn btn-primary">Browse All Events</a>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Events -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">My Upcoming Events</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date & Time</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['event_title']); ?></td>
                                    <td>
                                        <?php echo formatDate($event['event_date']); ?>
                                        <br><small class="text-muted"><?php echo date('g:i A', strtotime($event['event_time'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td>
                                        <span class="badge badge-info">Registered</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a href="my_registrations.php" class="btn btn-primary">View All Registrations</a>
                    </div>
                </div>
            </div>

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
                                    <th>Event</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_registrations as $registration): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($registration['event_title']); ?></td>
                                    <td><?php echo formatDate($registration['registration_date']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $registration['status'] == 'attended' ? 'success' : 
                                                ($registration['status'] == 'registered' ? 'info' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($registration['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a href="my_registrations.php" class="btn btn-primary">View All Activity</a>
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