<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an organizer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "organizer") {
    header("location: ../login.php");
    exit;
}

// Get organizer information
$organizer_id = $_SESSION["user_id"];
$organizer_name = $_SESSION["name"];

// Handle event management actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"])) {
        switch ($_POST["action"]) {
            case "create_event":
                $title = trim($_POST["title"]);
                $description = trim($_POST["description"]);
                $event_date = trim($_POST["event_date"]);
                $location = trim($_POST["location"]);
                $capacity = (int)$_POST["capacity"];
                
                $sql = "INSERT INTO events (title, description, event_date, location, capacity, organizer_id) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssii", $title, $description, $event_date, $location, $capacity, $organizer_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION["success"] = "Event created successfully!";
                } else {
                    $_SESSION["error"] = "Failed to create event.";
                }
                break;
                
            case "edit_event":
                $event_id = (int)$_POST["event_id"];
                $title = trim($_POST["title"]);
                $description = trim($_POST["description"]);
                $event_date = trim($_POST["event_date"]);
                $location = trim($_POST["location"]);
                $capacity = (int)$_POST["capacity"];
                $status = trim($_POST["status"]);
                
                $sql = "UPDATE events 
                        SET title = ?, description = ?, event_date = ?, location = ?, capacity = ?, status = ? 
                        WHERE event_id = ? AND organizer_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssissi", $title, $description, $event_date, $location, $capacity, $status, $event_id, $organizer_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION["success"] = "Event updated successfully!";
                } else {
                    $_SESSION["error"] = "Failed to update event.";
                }
                break;
                
            case "delete_event":
                $event_id = (int)$_POST["event_id"];
                
                $sql = "DELETE FROM events WHERE event_id = ? AND organizer_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $event_id, $organizer_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION["success"] = "Event deleted successfully!";
                } else {
                    $_SESSION["error"] = "Failed to delete event.";
                }
                break;
        }
    }
}

// Get organizer's events
$events = [];
$sql = "SELECT * FROM events WHERE organizer_id = ? ORDER BY event_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $organizer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $events[] = $row;
}

// Get event registrations
$registrations = [];
$sql = "SELECT r.*, e.title as event_title, u.name as student_name 
        FROM registrations r 
        JOIN events e ON r.event_id = e.event_id 
        JOIN users u ON r.student_id = u.user_id 
        WHERE e.organizer_id = ? 
        ORDER BY r.registration_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $organizer_id);
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
    <title>Organizer Dashboard - Student Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Organizer Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#events">My Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#registrations">Registrations</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($organizer_name); ?>
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

        <!-- Event Management Section -->
        <section id="events" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Events</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                    <i class="fas fa-plus"></i> Create Event
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo $event["event_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($event["title"]); ?></td>
                                    <td><?php echo htmlspecialchars($event["event_date"]); ?></td>
                                    <td><?php echo htmlspecialchars($event["location"]); ?></td>
                                    <td><?php echo $event["capacity"]; ?></td>
                                    <td><?php echo htmlspecialchars($event["status"]); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?php echo $event["event_id"]; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Registration Management Section -->
        <section id="registrations" class="mb-5">
            <h2 class="mb-4">Event Registrations</h2>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $registration): ?>
                                <tr>
                                    <td><?php echo $registration["registration_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($registration["student_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["event_title"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["registration_date"]); ?></td>
                                    <td><?php echo htmlspecialchars($registration["status"]); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewRegistrationDetails(<?php echo $registration["registration_id"]; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
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

    <!-- Create Event Modal -->
    <div class="modal fade" id="createEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_event">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Date</label>
                            <input type="datetime-local" class="form-control" name="event_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" name="capacity" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_event">
                        <input type="hidden" name="event_id" id="edit_event_id">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Date</label>
                            <input type="datetime-local" class="form-control" name="event_date" id="edit_event_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" id="edit_location" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" name="capacity" id="edit_capacity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="upcoming">Upcoming</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Event Confirmation Modal -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this event?</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="delete_event">
                        <input type="hidden" name="event_id" id="delete_event_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEvent(event) {
            document.getElementById('edit_event_id').value = event.event_id;
            document.getElementById('edit_title').value = event.title;
            document.getElementById('edit_description').value = event.description;
            document.getElementById('edit_event_date').value = event.event_date;
            document.getElementById('edit_location').value = event.location;
            document.getElementById('edit_capacity').value = event.capacity;
            document.getElementById('edit_status').value = event.status;
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }

        function deleteEvent(eventId) {
            document.getElementById('delete_event_id').value = eventId;
            new bootstrap.Modal(document.getElementById('deleteEventModal')).show();
        }

        function viewRegistrationDetails(registrationId) {
            // Implement registration details view functionality
        }
    </script>
</body>
</html> 