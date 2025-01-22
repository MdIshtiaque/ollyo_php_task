<?php
session_start();
require_once '../config/database.php';
require_once '../includes/sanitizer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $query = "SELECT * FROM events WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $event_id);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() != 1) {
        $_SESSION['error'] = "Unauthorized access";
        header("Location: ../dashboard.php");
        exit();
    }
    
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching event details";
    header("Location: ../dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = trim($_POST['location']);
    $max_capacity = (int)$_POST['max_capacity'];
    
    try {
        $query = "UPDATE events 
                  SET name = :name, description = :description, 
                      event_date = :event_date, event_time = :event_time, 
                      location = :location, max_capacity = :max_capacity 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":event_date", $event_date);
        $stmt->bindParam(":event_time", $event_time);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":max_capacity", $max_capacity);
        $stmt->bindParam(":id", $event_id);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        
        if($stmt->execute()) {
            $_SESSION['success'] = "Event updated successfully!";
            header("Location: ../dashboard.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to update event";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Event Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard.php">Event Manager</a>
        </div>
    </nav>

    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_event.php?id=<?php echo $event_id; ?>"><?php echo htmlspecialchars($event['name']); ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-4">Edit Event</h1>

                        <form action="update_event.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="id" value="<?php echo $event_id; ?>">

                            <div class="mb-3">
                                <label for="name" class="form-label">Event Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-calendar-event"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           value="<?php echo htmlspecialchars($event['name']); ?>" 
                                           required>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter an event name.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-text-paragraph"></i>
                                    </span>
                                    <textarea class="form-control" 
                                              id="description" 
                                              name="description" 
                                              rows="4" 
                                              required><?php echo htmlspecialchars($event['description']); ?></textarea>
                                </div>
                                <div class="invalid-feedback">
                                    Please provide an event description.
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="event_date" class="form-label">Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-calendar3"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control" 
                                               id="event_date" 
                                               name="event_date" 
                                               value="<?php echo $event['event_date']; ?>" 
                                               required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please select a date.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="event_time" class="form-label">Time</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-clock"></i>
                                        </span>
                                        <input type="time" 
                                               class="form-control" 
                                               id="event_time" 
                                               name="event_time" 
                                               value="<?php echo $event['event_time']; ?>" 
                                               required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please select a time.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-geo-alt"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="location" 
                                           name="location" 
                                           value="<?php echo htmlspecialchars($event['location']); ?>" 
                                           required>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter a location.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="max_capacity" class="form-label">Maximum Capacity</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-people"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="max_capacity" 
                                           name="max_capacity" 
                                           value="<?php echo $event['max_capacity']; ?>" 
                                           min="1" 
                                           required>
                                </div>
                                <div class="invalid-feedback">
                                    Please specify the maximum capacity.
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Save Changes
                                </button>
                                <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Form validation
    (function () {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html> 