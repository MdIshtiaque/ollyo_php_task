<?php
session_start();
require_once '../config/database.php';
require_once '../includes/sanitizer.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login first";
    header("Location: ../auth/login.html");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // First check if user is admin
    $query = "SELECT is_admin FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user['is_admin']) {
        $_SESSION['error'] = "Admin access required";
        header("Location: view_event.php?id=" . $event_id);
        exit();
    }

    // Get event details and verify it exists
    $query = "SELECT name FROM events WHERE id = :event_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":event_id", $event_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get attendees list
        $query = "SELECT u.username, u.email, a.registration_date 
                 FROM attendees a 
                 JOIN users u ON a.user_id = u.id 
                 WHERE a.event_id = :event_id 
                 ORDER BY a.registration_date";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $event['name'] . '_attendees.csv"');
        
        // Create CSV file
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel display
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, array('Username', 'Email', 'Registration Date'));
        
        // Add data
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, array(
                $row['username'],
                $row['email'],
                $row['registration_date']
            ));
        }
        
        fclose($output);
        exit();
        
    } else {
        $_SESSION['error'] = "Event not found";
        header("Location: ../dashboard.php");
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Error generating report";
    header("Location: view_event.php?id=" . $event_id);
    exit();
} 