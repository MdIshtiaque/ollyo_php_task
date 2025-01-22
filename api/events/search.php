<?php
require_once '../../config/database.php';
require_once '../auth.php';
require_once '../../includes/sanitizer.php';

header('Content-Type: application/json');

if (!APIAuth::validateAPIKey()) {
    exit();
}

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['query']) ? Sanitizer::sanitizeInput($_GET['query']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

try {
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM events e WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $countQuery .= " AND (name LIKE :search OR description LIKE :search OR location LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($filter === 'upcoming') {
        $countQuery .= " AND event_date >= CURDATE()";
    } elseif ($filter === 'past') {
        $countQuery .= " AND event_date < CURDATE()";
    }
    
    $stmt = $db->prepare($countQuery);
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated results
    $query = "SELECT e.*, u.username as organizer,
              (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) as current_attendees
              FROM events e
              JOIN users u ON e.user_id = u.id
              WHERE 1=1";
    
    if (!empty($search)) {
        $query .= " AND (e.name LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
    }
    
    if ($filter === 'upcoming') {
        $query .= " AND e.event_date >= CURDATE()";
    } elseif ($filter === 'past') {
        $query .= " AND e.event_date < CURDATE()";
    }
    
    $query .= " ORDER BY e.event_date DESC LIMIT :offset, :limit";
    
    $stmt = $db->prepare($query);
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'date' => $row['event_date'],
            'time' => $row['event_time'],
            'location' => $row['location'],
            'capacity' => [
                'max' => (int)$row['max_capacity'],
                'current' => (int)$row['current_attendees']
            ],
            'organizer' => $row['organizer']
        ];
    }
    
    APIAuth::sendResponse([
        'events' => $events,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalResults / $limit),
            'total_results' => $totalResults
        ]
    ]);
    
} catch(PDOException $e) {
    APIAuth::sendError('Error searching events', 500);
} 