<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = trim($_POST['location']);
    $max_capacity = (int)$_POST['max_capacity'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $query = "INSERT INTO events (user_id, name, description, event_date, event_time, location, max_capacity) 
                  VALUES (:user_id, :name, :description, :event_date, :event_time, :location, :max_capacity)";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":event_date", $event_date);
        $stmt->bindParam(":event_time", $event_time);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":max_capacity", $max_capacity);
        
        if($stmt->execute()) {
            $_SESSION['success'] = "Event created successfully!";
            header("Location: ../dashboard.php");
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Failed to create event. Please try again.";
        header("Location: create_event.html");
    }
}
?> 