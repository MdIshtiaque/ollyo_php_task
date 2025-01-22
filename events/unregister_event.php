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
    $query = "DELETE FROM attendees WHERE event_id = :event_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":event_id", $event_id);
    $stmt->bindParam(":user_id", $user_id);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Successfully unregistered from the event'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'You are not registered for this event'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error processing unregistration'
    ]);
}

exit(); 