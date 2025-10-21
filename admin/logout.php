<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Log activity before logout
if(isset($_SESSION['employee_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    logActivity($db, $_SESSION['employee_id'], 'employee', 'logout', 'Admin logged out');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to admin login page
header('Location: login.php');
exit();
?> 