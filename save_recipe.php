<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$recipe_id = $_GET['id'] ?? null;

if (!$recipe_id) {
    header("Location: userdashboard.php");
    exit();
}

// Check if the recipe is already saved
$check = $pdo->prepare("SELECT id FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
$check->execute([$user_id, $recipe_id]);

if ($check->fetch()) {
    // Already saved? Then DELETE it (Unsave)
    $stmt = $pdo->prepare("DELETE FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
    $stmt->execute([$user_id, $recipe_id]);
    
    // Redirect with 'removed' status
    header("Location: userdashboard.php?status=recipe_unsaved");
    exit();
} else {
    // Not saved? Then INSERT it (Save)
    $stmt = $pdo->prepare("INSERT INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $recipe_id]);
    
    // Redirect with 'saved' status
    header("Location: userdashboard.php?status=recipe_saved");
    exit();
}