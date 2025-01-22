<?php
session_start();
require_once 'config/database.php';
require_once 'includes/sanitizer.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? Sanitizer::sanitizeInput($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';

try {
    $query = "SELECT e.*, u.username as creator_name,
              (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) as registered_attendees,
              e.user_id = :current_user as is_owner
              FROM events e 
              JOIN users u ON e.user_id = u.id
              WHERE 1=1";
    $params = [':current_user' => $_SESSION['user_id']];
    
    if (!empty($search)) {
        $query .= " AND (e.name LIKE :search 
                        OR e.description LIKE :search 
                        OR e.location LIKE :search
                        OR u.username LIKE :search)";
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
    
    header('Content-Type: application/json');
    echo json_encode(['events' => $events]);
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching events']);
} 