<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.html");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';

try {
    $query = "SELECT e.*, u.username as creator_name,
              (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) as registered_attendees
              FROM events e 
              JOIN users u ON e.user_id = u.id
              WHERE 1=1";
    $params = array();
    
    if (!empty($search)) {
        $query .= " AND (e.name LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    switch($date_filter) {
        case 'upcoming':
            $query .= " AND e.event_date >= CURDATE()";
            break;
        case 'past':
            $query .= " AND e.event_date < CURDATE()";
            break;
    }
    
    $query .= " ORDER BY e.event_date DESC";
    
    $stmt = $db->prepare($query);
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching events";
    $events = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="assets/js/search.js" defer></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">Event Manager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="auth/logout.php" 
                                   onclick="return confirm('Are you sure you want to logout?')">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Events Dashboard</h1>
            <a href="events/create_event.html" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Create New Event
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   id="search-input" 
                                   class="form-control search-input" 
                                   placeholder="Search events...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="date-filter" class="form-select">
                            <option value="all">All Events</option>
                            <option value="upcoming">Upcoming Events</option>
                            <option value="past">Past Events</option>
                        </select>
                    </div>
                </div>
                <small class="text-muted" id="search-status"></small>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0" id="events-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="loading-spinner mx-auto"></div>
                                <p class="text-muted mt-2">Loading events...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>