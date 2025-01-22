<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // First check if the user owns this event
        $query = "SELECT user_id FROM events WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $event_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($event['user_id'] == $_SESSION['user_id']) {
                // Delete related attendees first
                $query = "DELETE FROM attendees WHERE event_id = :event_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":event_id", $event_id);
                $stmt->execute();
                
                // Then delete the event
                $query = "DELETE FROM events WHERE id = :id AND user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $event_id);
                $stmt->bindParam(":user_id", $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Event deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete event";
                }
            } else {
                $_SESSION['error'] = "Unauthorized action";
            }
        } else {
            $_SESSION['error'] = "Event not found";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error deleting event";
    }
}

header("Location: ../dashboard.php");
exit(); 