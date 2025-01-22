<?php
session_start();
require_once '../config/database.php';
require_once '../includes/sanitizer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get and sanitize input
$event_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? Sanitizer::sanitizeInput($_POST['name']) : '';
$description = isset($_POST['description']) ? Sanitizer::sanitizeInput($_POST['description']) : '';
$event_date = isset($_POST['event_date']) ? $_POST['event_date'] : '';
$event_time = isset($_POST['event_time']) ? $_POST['event_time'] : '';
$location = isset($_POST['location']) ? Sanitizer::sanitizeInput($_POST['location']) : '';
$max_capacity = isset($_POST['max_capacity']) ? (int)$_POST['max_capacity'] : 0;

try {
    // First verify that the user owns this event
    $query = "SELECT id FROM events WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $event_id);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() != 1) {
        $_SESSION['error'] = "Unauthorized access";
        header("Location: ../dashboard.php");
        exit();
    }

    // Verify max capacity is not less than current attendees
    $query = "SELECT COUNT(*) as current_attendees FROM attendees WHERE event_id = :event_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":event_id", $event_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($max_capacity < $result['current_attendees']) {
        $_SESSION['error'] = "Maximum capacity cannot be less than current attendees";
        header("Location: edit_event.php?id=" . $event_id);
        exit();
    }

    // Update the event
    $query = "UPDATE events 
              SET name = :name, 
                  description = :description, 
                  event_date = :event_date, 
                  event_time = :event_time, 
                  location = :location, 
                  max_capacity = :max_capacity 
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
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Event updated successfully!";
        header("Location: view_event.php?id=" . $event_id);
        exit();
    } else {
        throw new Exception("Failed to update event");
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Error updating event: " . $e->getMessage();
    header("Location: edit_event.php?id=" . $event_id);
    exit();
} 