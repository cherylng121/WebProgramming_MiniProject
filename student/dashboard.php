<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student") {
    header("location: ../login.php");
    exit;
}

// Get student information
$student_id = $_SESSION["user_id"];
$student_name = $_SESSION["name"];

// Handle event registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "register_event") {
    $event_id = (int)$_POST["event_id"];
    
    // Check if already registered
    $sql = "SELECT registration_id FROM registrations WHERE event_id = ? AND student_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $event_id, $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        // Check event capacity
        $sql = "SELECT e.capacity, COUNT(r.registration_id) as registered_count 
                FROM events e 
                LEFT JOIN registrations r ON e.event_id = r.event_id 
                WHERE e.event_id = ? 
                GROUP BY e.event_id";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($result);
        
        if ($event["registered_count"] < $event["capacity"]) {
            // Register for event
            $sql = "INSERT INTO registrations (event_id, student_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $event_id, $student_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["success"] = "Successfully registered for the event!";
            } else {
                $_SESSION["error"] = "Failed to register for the event.";
            }
        } else {
            $_SESSION["error"] = "Event is already full.";
        }
    } else {
        $_SESSION["error"] = "You are already registered for this event.";
    }
}

// Get available events
$available_events = [];
$sql = "SELECT e.*, u.name as organizer_name, 
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as registered_count 
        FROM events e 
        JOIN users u ON e.organizer_id = u.user_id 
        WHERE e.status = 'upcoming' 
        AND e.event_id NOT IN (SELECT event_id FROM registrations WHERE student_id = ?) 
        ORDER BY e.event_date ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $available_events[] = $row;
}

// Get student's registrations
$registrations = [];
$sql = "SELECT r.*, e.title as event_title, e.event_date, e.location, u.name as organizer_name 
        FROM registrations r 
        JOIN events e ON r.event_id = e.event_id 
        JOIN users u ON e.organizer_id = u.user_id 
        WHERE r.student_id = ? 
        ORDER BY r.registration_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $registrations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Student Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#available-events">Available Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#my-registrations">My Registrations</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($student_name); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION["success"])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION["success"];
                unset($_SESSION["success"]);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION["error"])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION["error"];
                unset($_SESSION["error"]);
                ?>
            </div>
        <?php endif; ?>

        <!-- Available Events Section -->
        <section id="available-events" class="mb-5">
            <h2 class="mb-4">Available Events</h2>
            <div class="row">
                <?php foreach ($available_events as $event): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event["title"]); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($event["description"]); ?></p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($event["event_date"]); ?></li>
                                <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event["location"]); ?></li>
                                <li><i class="fas fa-user"></i> Organizer: <?php echo htmlspecialchars($event["organizer_name"]); ?></li>
                                <li><i class="fas fa-users"></i> Available Spots: <?php echo $event["capacity"] - $event["registered_count"]; ?></li>
                            </ul>
                            <form action="" method="POST" class="mt-3">
                                <input type="hidden" name="action" value="register_event">
                                <input type="hidden" name="event_id" value="<?php echo $event["event_id"]; ?>">
                                <button type="submit" class="btn btn-primary w-100">Register for Event</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- My Registrations Section -->
        <section id="my-registrations" class="mb-5">
            <h2 class="mb-4">My Registrations</h2>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Organizer</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $registration): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($registration["event_title"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["event_date"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["location"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["organizer_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["registration_date"]); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $registration["status"] == "approved" ? "success" : 
                                                ($registration["status"] == "pending" ? "warning" : 
                                                ($registration["status"] == "rejected" ? "danger" : "secondary")); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($registration["status"])); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 