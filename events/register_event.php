<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['event_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$event_id = $_POST['event_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if event exists and has capacity
    $query = "SELECT max_capacity, 
              (SELECT COUNT(*) FROM attendees WHERE event_id = events.id) as current_attendees 
              FROM events WHERE id = :event_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":event_id", $event_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event['current_attendees'] >= $event['max_capacity']) {
            $_SESSION['error'] = "Event is already full";
            echo json_encode([
                'success' => false,
                'error' => 'Event is already full'
            ]);
            exit();
        }
        
        // Register the user
        $query = "INSERT INTO attendees (event_id, user_id) VALUES (:event_id, :user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Successfully registered for the event'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to register for the event'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Event not found'
        ]);
    }
} catch(PDOException $e) {
    if ($e->getCode() == '23000') { // Duplicate entry error
        $_SESSION['error'] = "You are already registered for this event";
        echo json_encode([
            'success' => false,
            'error' => 'You are already registered for this event'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error processing registration'
        ]);
    }
}

exit(); 