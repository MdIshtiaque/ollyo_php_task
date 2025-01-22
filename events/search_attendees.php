<?php
session_start();
require_once '../config/database.php';
require_once '../includes/sanitizer.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? Sanitizer::sanitizeInput($_GET['search']) : '';
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

try {
    // First verify if user is event owner
    $query = "SELECT id FROM events WHERE id = :event_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":event_id", $event_id);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception('Unauthorized access');
    }

    // Search attendees
    $query = "SELECT u.username, u.email, a.registration_date
              FROM attendees a
              JOIN users u ON a.user_id = u.id
              WHERE a.event_id = :event_id
              AND (u.username LIKE :search OR u.email LIKE :search)
              ORDER BY a.registration_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":event_id", $event_id);
    $searchParam = "%$search%";
    $stmt->bindParam(":search", $searchParam);
    $stmt->execute();
    
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['attendees' => $attendees]);
    
} catch(Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 