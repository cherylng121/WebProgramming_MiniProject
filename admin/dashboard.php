<?php
require_once '../includes/functions.php';
requireLevel('1'); // Admin only

$pdo = getDBConnection();

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total events
$stmt = $pdo->query("SELECT COUNT(*) as count FROM events");
$stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending events
$stmt = $pdo->query("SELECT COUNT(*) as count FROM events WHERE status = 'pending'");
$stats['pending_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total registrations
$stmt = $pdo->query("SELECT COUNT(*) as count FROM event_registrations");
$stats['total_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent events
$stmt = $pdo->query("SELECT e.*, u.name as organizer_name 
                     FROM events e 
                     LEFT JOIN users u ON e.created_by = u.user_id 
                     ORDER BY e.created_at DESC 
                     LIMIT 5");
$recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent users
$stmt = $pdo->query("SELECT u.*, l.username, l.user_level 
                     FROM users u 
                     LEFT JOIN login l ON u.user_id = l.user_id 
                     ORDER BY u.created_at DESC 
                     LIMIT 5");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h1 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        
        <!-- Statistics Grid -->
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
                        <h3 class="card-title">Recent Events</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Organizer</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $event['status'] == 'approved' ? 'success' : ($event['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($event['event_date']); ?></td>
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

            <!-- Recent Users -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Users</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo getUserLevelName($user['user_level']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a href="users.php" class="btn btn-primary">View All Users</a>
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