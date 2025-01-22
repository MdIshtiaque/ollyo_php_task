<?php
require_once '../../config/database.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!APIAuth::validateAPIKey()) {
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT e.id, e.name, e.description, e.event_date, e.event_time, 
              e.location, e.max_capacity, u.username as organizer,
              (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) as current_attendees
              FROM events e
              JOIN users u ON e.user_id = u.id
              WHERE e.event_date >= CURDATE()
              ORDER BY e.event_date ASC";
              
    $stmt = $db->prepare($query);
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
    
    APIAuth::sendResponse(['events' => $events]);
    
} catch(PDOException $e) {
    APIAuth::sendError('Error fetching events', 500);
} 