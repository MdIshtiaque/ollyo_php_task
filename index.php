<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/database.php';
include 'includes/session.php';
include 'includes/user.php';
include 'includes/event.php';

// Redirect to dashboard
header('Location: dashboard.php');
exit();
?>