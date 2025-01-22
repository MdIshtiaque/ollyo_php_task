<?php
require_once '../../config/database.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!APIAuth::validateAPIKey()) {
    exit();
}

if (!isset($_GET['id'])) {
    APIAuth::sendError('Event ID is required');
}

$database = new Database();
$db = $database->getConnection();
$event_id = $_GET['id'];

try {
    $query = "SELECT e.*, u.username as organizer,
              (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) as current_attendees
              FROM events e
              JOIN users u ON e.user_id = u.id
              WHERE e.id = :id";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $event_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get attendees list
        $query = "SELECT u.username, a.registration_date
                 FROM attendees a
                 JOIN users u ON a.user_id = u.id
                 WHERE a.event_id = :event_id
                 ORDER BY a.registration_date";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'id' => $event['id'],
            'name' => $event['name'],
            'description' => $event['description'],
            'date' => $event['event_date'],
            'time' => $event['event_time'],
            'location' => $event['location'],
            'capacity' => [
                'max' => (int)$event['max_capacity'],
                'current' => (int)$event['current_attendees']
            ],
            'organizer' => $event['organizer'],
            'attendees' => $attendees
        ];
        
        APIAuth::sendResponse($response);
    } else {
        APIAuth::sendError('Event not found', 404);
    }
} catch(PDOException $e) {
    APIAuth::sendError('Error fetching event details', 500);
} 