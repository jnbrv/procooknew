<?php
require_once 'includes/db.php';
session_start();

// 1. Security Check: Must be logged in and must be a POST request
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: userdashboard.php");
    exit();
}

if (isset($_POST['recipe_id'])) {
    $recipe_id = $_POST['recipe_id'];
    $user_id = $_SESSION['user_id'];

    // 2. Fetch the image filename (Ensures the user OWNS this recipe before deleting)
    $stmt = $pdo->prepare("SELECT image_url FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$recipe_id, $user_id]);
    $recipe = $stmt->fetch();

    if ($recipe) {
        // 3. Delete physical file
        if (!empty($recipe['image_url'])) {
            $image_path = "uploads/" . $recipe['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path); 
            }
        }

        // 4. Delete the database record
        $delete = $pdo->prepare("DELETE FROM recipes WHERE id = ? AND user_id = ?");
        $delete->execute([$recipe_id, $user_id]);

        // SUCCESS: Redirect using the status keyword we set in userdashboard.php
        header("Location: userdashboard.php?status=recipe_deleted");
        exit();
    }
}

// FAILURE: Redirect with error status
header("Location: userdashboard.php?status=error");
exit();