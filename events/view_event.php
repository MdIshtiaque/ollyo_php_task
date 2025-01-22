<?php
session_start();
require_once '../config/database.php';
require_once '../includes/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$event_id = $_GET['id'];

try {
    // Get event details
    $query = "SELECT e.*, u.username as creator_name, 
              (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) as current_attendees 
              FROM events e 
              JOIN users u ON e.user_id = u.id 
              WHERE e.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $event_id);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if current user is registered
        $query = "SELECT id FROM attendees WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        $is_registered = $stmt->rowCount() > 0;
    } else {
        $_SESSION['error'] = "Event not found";
        header("Location: ../dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching event details";
    header("Location: ../dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo CSRF::generateToken(); ?>">
    <title><?php echo htmlspecialchars($event['name']); ?> - Event Details</title>
    <!-- Modern CSS and Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="../assets/js/event.js" defer></script>
</head>

<body>
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($event['name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h1 class="h3 mb-4"><?php echo htmlspecialchars($event['name']); ?></h1>

                        <div class="event-stats">
                            <div class="stat-card">
                                <div class="stat-value">
                                    <i class="bi bi-calendar3"></i>
                                    <?php echo htmlspecialchars($event['event_date']); ?>
                                </div>
                                <div class="stat-label">Date</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">
                                    <i class="bi bi-clock"></i>
                                    <?php echo htmlspecialchars($event['event_time']); ?>
                                </div>
                                <div class="stat-label">Time</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">
                                    <i class="bi bi-people"></i>
                                    <span id="attendee-count-<?php echo $event_id; ?>"
                                        data-count="<?php echo $event['current_attendees']; ?>"
                                        data-capacity="<?php echo $event['max_capacity']; ?>">
                                        <?php echo $event['current_attendees']; ?>/<?php echo $event['max_capacity']; ?>
                                    </span>
                                </div>
                                <div class="stat-label">Attendees</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="text-secondary mb-3">Description</h5>
                            <p class="lead"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        </div>

                        <div class="mb-4">
                            <h5 class="text-secondary mb-3">Location</h5>
                            <p class="d-flex align-items-center">
                                <i class="bi bi-geo-alt me-2"></i>
                                <?php echo htmlspecialchars($event['location']); ?>
                            </p>
                        </div>

                        <div class="mb-4">
                            <h5 class="text-secondary mb-3">Organizer</h5>
                            <p class="d-flex align-items-center">
                                <i class="bi bi-person me-2"></i>
                                <?php echo htmlspecialchars($event['creator_name']); ?>
                            </p>
                        </div>

                        <div class="d-flex gap-2">
                            <?php if (!$is_registered && $event['current_attendees'] < $event['max_capacity']): ?>
                                <div id="registration-buttons-<?php echo $event_id; ?>">
                                    <button onclick="registerForEvent(<?php echo $event_id; ?>)"
                                        class="btn btn-primary">
                                        <i class="bi bi-calendar-plus me-2"></i>Register for Event
                                    </button>
                                </div>
                            <?php elseif ($is_registered): ?>
                                <div id="registration-buttons-<?php echo $event_id; ?>">
                                    <button onclick="unregisterFromEvent(<?php echo $event_id; ?>)"
                                        class="btn btn-danger">
                                        <i class="bi bi-calendar-x me-2"></i>Cancel Registration
                                    </button>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="bi bi-x-circle me-2"></i>Event Full
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <?php if ($event['user_id'] == $_SESSION['user_id']): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Event Management</h5>
                            <div class="d-grid gap-2">
                                <a href="edit_event.php?id=<?php echo $event_id; ?>"
                                    class="btn btn-warning">
                                    <i class="bi bi-pencil me-2"></i>Edit Event
                                </a>
                                <a href="delete_event.php?id=<?php echo $event_id; ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this event?')">
                                    <i class="bi bi-trash me-2"></i>Delete Event
                                </a>
                                <?php
                                // Check if user is admin
                                $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = :user_id");
                                $stmt->bindParam(":user_id", $_SESSION['user_id']);
                                $stmt->execute();
                                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if ($user['is_admin']): ?>
                                    <a href="export_attendees.php?id=<?php echo $event['id']; ?>"
                                        class="btn btn-success">
                                        <i class="bi bi-download me-2"></i>Export Attendees
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($event['user_id'] == $_SESSION['user_id']): ?>
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Attendees</h5>
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text"
                                id="attendee-search"
                                class="form-control search-input"
                                placeholder="Search attendees...">
                        </div>
                        <small class="text-muted" id="search-status"></small>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="attendees-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <div class="loading-spinner mx-auto"></div>
                                        <p class="text-muted mt-2">Loading attendees...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add this script -->
    <script>
        // Add debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Add escapeHtml function
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        const searchAttendees = debounce((searchTerm) => {
            const searchStatus = document.getElementById('search-status');
            searchStatus.textContent = 'Searching...';

            fetch(`search_attendees.php?event_id=<?php echo $event_id; ?>&search=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    updateAttendeesTable(data.attendees);
                    searchStatus.textContent = '';
                })
                .catch(error => {
                    console.error('Error:', error);
                    searchStatus.textContent = 'Error occurred while searching';
                    updateAttendeesTable([]); // Clear table on error
                });
        }, 300);

        function updateAttendeesTable(attendees) {
            const tbody = document.querySelector('#attendees-table tbody');
            tbody.innerHTML = '';

            if (!attendees || attendees.length === 0) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center text-muted">
                        <i>No attendees found</i>
                    </td>
                </tr>`;
                return;
            }

            // Sort attendees by registration date (newest first)
            attendees.sort((a, b) => new Date(b.registration_date) - new Date(a.registration_date));

            attendees.forEach(attendee => {
                const date = new Date(attendee.registration_date);
                const formattedDate = date.toLocaleString();

                tbody.innerHTML += `
                <tr>
                    <td>${escapeHtml(attendee.username)}</td>
                    <td>${escapeHtml(attendee.email)}</td>
                    <td>${formattedDate}</td>
                </tr>`;
            });
        }

        document.getElementById('attendee-search')?.addEventListener('input', (e) => {
            searchAttendees(e.target.value);
        });

        // Initial load of attendees
        searchAttendees('');
    </script>
</body>

</html>