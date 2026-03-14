<?php
require_once 'includes/db.php';
session_start();

// 1. Access Control
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    
    // Sanitize inputs
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_SPECIAL_CHARS);
    $phone     = filter_var($_POST['phone'], FILTER_SANITIZE_SPECIAL_CHARS);
    $address   = filter_var($_POST['address'], FILTER_SANITIZE_SPECIAL_CHARS);

    try {
        // Update the users table
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $address, $user_id]);
        
        // UPDATED: Use a specific status for the profile update
        header("Location: userdashboard.php?status=profile_updated");
        exit();
    } catch (PDOException $e) {
        // Redirect back with error status
        header("Location: userdashboard.php?status=error");
        exit();
    }
} else {
    // If someone tries to access this file directly without POST
    header("Location: userdashboard.php");
    exit();
}