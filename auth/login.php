<?php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $query = "SELECT id, username, password FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("Location: ../dashboard.php");
            } else {
                $_SESSION['error'] = "Invalid password";
                header("Location: login.html");
            }
        } else {
            $_SESSION['error'] = "User not found";
            header("Location: login.html");
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Login failed. Please try again.";
        header("Location: login.html");
    }
}
?> 