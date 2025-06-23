<?php
require_once '../includes/functions.php';
requireLevel('3'); // Student only

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get filters
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$sort = $_GET['sort'] ?? 'event_date';
$order = $_GET['order'] ?? 'ASC';

// Build query
$where_conditions = ["e.status = 'approved'"];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "ecm.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "e.event_date = CURDATE()";
            break;
        case 'tomorrow':
            $where_conditions[] = "e.event_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'this_week':
            $where_conditions[] = "e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'this_month':
            $where_conditions[] = "e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
            break;
    }
}

$where_clause = "WHERE " . implode(' AND ', $where_conditions);

// Get events
$stmt = $pdo->prepare("SELECT DISTINCT e.*, u.name as organizer_name, 
                              GROUP_CONCAT(ec.category_name) as categories,
                              COUNT(er.registration_id) as registration_count,
                              CASE WHEN er2.registration_id IS NOT NULL THEN 1 ELSE 0 END as is_registered
                       FROM events e 
                       JOIN users u ON e.created_by = u.user_id 
                       LEFT JOIN event_category_mapping ecm ON e.event_id = ecm.event_id 
                       LEFT JOIN event_categories ec ON ecm.category_id = ec.category_id 
                       LEFT JOIN event_registrations er ON e.event_id = er.event_id 
                       LEFT JOIN event_registrations er2 ON e.event_id = er2.event_id AND er2.user_id = ?
                       $where_clause 
                       GROUP BY e.event_id 
                       ORDER BY $sort $order");
$params = array_merge([$user_id], $params);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM event_categories ORDER BY category_name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_event'])) {
    $event_id = $_POST['event_id'];
    
    // Check if already registered
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
        // Check event capacity
        $stmt = $pdo->prepare("SELECT e.capacity, COUNT(er.registration_id) as current_registrations 
                               FROM events e 
                               LEFT JOIN event_registrations er ON e.event_id = er.event_id 
                               WHERE e.event_id = ? 
                               GROUP BY e.event_id");
        $stmt->execute([$event_id]);
        $event_capacity = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event_capacity['capacity'] || $event_capacity['current_registrations'] < $event_capacity['capacity']) {
            try {
                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, registration_date, status) VALUES (?, ?, NOW(), 'registered')");
                $stmt->execute([$event_id, $user_id]);
                $message = 'Successfully registered for the event!';
            } catch (Exception $e) {
                $errors[] = 'Registration failed: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Event is at full capacity.';
        }
    } else {
        $errors[] = 'You are already registered for this event.';
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
            <h1>Browse Events</h1>
        </div>

        <?php if (isset($message)): ?>
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

        <!-- Search and Filter -->
        <div class="search-box">
            <form method="GET" action="">
                <div class="search-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="searchInput" class="form-label">Search Events</label>
                        <input type="text" id="searchInput" name="search" class="form-control" 
                               placeholder="Search by event title, description, or location" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="category" class="form-label">Category</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" 
                                        <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_filter" class="form-label">Date Filter</label>
                        <select id="date_filter" name="date_filter" class="form-select">
                            <option value="">All Dates</option>
                            <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="tomorrow" <?php echo $date_filter == 'tomorrow' ? 'selected' : ''; ?>>Tomorrow</option>
                            <option value="this_week" <?php echo $date_filter == 'this_week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="this_month" <?php echo $date_filter == 'this_month' ? 'selected' : ''; ?>>This Month</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sort" class="form-label">Sort By</label>
                        <select id="sort" name="sort" class="form-select">
                            <option value="event_date" <?php echo $sort == 'event_date' ? 'selected' : ''; ?>>Event Date</option>
                            <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>Event Title</option>
                            <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Created Date</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="order" class="form-label">Order</label>
                        <select id="order" name="order" class="form-select">
                            <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Events Grid -->
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="event-header">
                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <?php if ($event['is_registered']): ?>
                        <span class="badge badge-success">Registered</span>
                    <?php endif; ?>
                </div>
                
                <div class="event-details">
                    <p class="event-description"><?php echo htmlspecialchars(substr($event['description'], 0, 150)) . (strlen($event['description']) > 150 ? '...' : ''); ?></p>
                    
                    <div class="event-info">
                        <div class="info-item">
                            <strong>Date:</strong> <?php echo formatDate($event['event_date']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                        </div>
                        <div class="info-item">
                            <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Registrations:</strong> <?php echo $event['registration_count']; ?>
                            <?php if ($event['capacity']): ?>
                                / <?php echo $event['capacity']; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($event['categories']): ?>
                        <div class="info-item">
                            <strong>Categories:</strong> 
                            <?php 
                            $category_names = explode(',', $event['categories']);
                            foreach ($category_names as $category_name): ?>
                                <span class="badge badge-secondary"><?php echo htmlspecialchars($category_name); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="event-actions">
                    <?php if (!$event['is_registered']): ?>
                        <?php if (!$event['capacity'] || $event['registration_count'] < $event['capacity']): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                <button type="submit" name="register_event" class="btn btn-primary">Register</button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Full Capacity</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-success" disabled>Already Registered</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($events)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <h4>No events found</h4>
                    <p class="text-muted">No events match your search criteria.</p>
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