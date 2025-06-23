<?php
require_once '../includes/functions.php';
requireLevel('2'); // Organizer only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get event ID from URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$event_id) {
    echo "<div class='alert alert-danger'>Invalid event ID.</div>";
    exit;
}

// Fetch event details
$stmt = $pdo->prepare("SELECT e.*, u.name AS organizer_name, u.email AS organizer_email
                       FROM events e
                       JOIN users u ON e.created_by = u.user_id
                       WHERE e.event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='alert alert-danger'>Event not found.</div>";
    exit;
}

// Check if the logged-in organizer is the creator of the event
if ($event['created_by'] != $user_id) {
    echo "<div class='alert alert-danger'>You do not have permission to view this event.</div>";
    exit;
}

// Fetch registrations for this event
$stmt = $pdo->prepare("SELECT er.*, u.name, u.email
                       FROM event_registrations er
                       JOIN users u ON er.user_id = u.user_id
                       WHERE er.event_id = ?
                       ORDER BY er.registration_date DESC");
$stmt->execute([$event_id]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count registrations
$registration_count = count($registrations);

$user = getUserById($_SESSION['user_id']);
$profile_picture = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../images/organizer.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Event - Organizer</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .action-btn {
            display: block;
            width: 140px;    
            margin-bottom: 10px;
            padding: 10px 0;
            border: none;
            border-radius: 8px;
            font-size: 1.05em;
            font-weight: 500;
            text-align: center;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(106,90,249,0.08);
            cursor: pointer;
        }

        .action-btn.view {
            background: linear-gradient(90deg, #6a5af9 0%, #705df2 100%);
        }

        .action-btn.registrations {
            background: linear-gradient(90deg, #17c3b2 0%, #38b6ff 100%);
        }

        .action-btn.delete {
            background: linear-gradient(90deg, #ff5858 0%, #ff884b 100%);
        }

        .action-btn:hover {
            opacity: 0.92;
            box-shadow: 0 4px 16px rgba(106,90,249,0.12);
        }
    </style>
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
        <h1><?php echo htmlspecialchars($event['title']); ?></h1>
        <div class="card mb-4">
            <div class="card-body" >
                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <p><strong>Date:</strong> <?php echo formatDate($event['event_date']); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['event_time'])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>Capacity:</strong> <?php echo $event['capacity']; ?></p>
                <p><strong>Status:</strong>
                    <span class="badge badge-<?php
                        if ($event['status'] == 'approved') echo 'success';
                        elseif ($event['status'] == 'pending') echo 'warning';
                        else echo 'danger';
                    ?>">
                        <?php echo ucfirst($event['status']); ?>
                    </span>
                </p>
                <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?> (<?php echo htmlspecialchars($event['organizer_email']); ?>)</p>
                <p><strong>Registrations:</strong> <?php echo $registration_count; ?></p>
                <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-top: 16px;">
                    <a href="edit_event.php?id=<?php echo $event_id; ?>" class="btn btn-primary">Edit Event</a>
                    <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="action-btn view">View</a>
                    <a href="registrations.php?event_id=<?php echo $event['event_id']; ?>" class="action-btn registrations">Registrations</a>
                    <a href="events.php?action=delete&id=<?php echo $event['event_id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                </div>
            </div>
        </div>

        <h2>Registered Students</h2>
        <div class="card">
            <div class="card-body">
                <?php if ($registration_count == 0): ?>
                    <p>No students have registered for this event yet.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                    <td><?php echo formatDateTime($reg['registration_date']); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo ucfirst($reg['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
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