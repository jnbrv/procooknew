<?php
require_once 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = $_POST['recipe_id'];
    $user_id = $_SESSION['user_id'];
    
    // Sanitize inputs for security
    $title = filter_var($_POST['recipe_title'], FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_var($_POST['recipe_desc'], FILTER_SANITIZE_SPECIAL_CHARS);
    $ingredients = filter_var($_POST['ingredients'], FILTER_SANITIZE_SPECIAL_CHARS);
    $instructions = filter_var($_POST['procedure'], FILTER_SANITIZE_SPECIAL_CHARS);

    // 1. Security Check: Verify ownership
    $stmt = $pdo->prepare("SELECT image_url FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$recipe_id, $user_id]);
    $current_recipe = $stmt->fetch();

    if (!$current_recipe) {
        header("Location: userdashboard.php?status=error");
        exit();
    }

    $image_url = $current_recipe['image_url']; 

    // 2. Handle Image Upload
    if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] === 0) {
        $upload_dir = 'uploads/';
        $file_ext = pathinfo($_FILES['recipe_image']['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid('recipe_', true) . '.' . $file_ext;
        $target_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['recipe_image']['tmp_name'], $target_path)) {
            if (!empty($current_recipe['image_url']) && file_exists($upload_dir . $current_recipe['image_url'])) {
                unlink($upload_dir . $current_recipe['image_url']);
            }
            $image_url = $new_file_name;
        }
    }

    // 3. Update the Database
    $update_sql = "UPDATE recipes SET 
                   title = ?, 
                   description = ?, 
                   ingredients = ?, 
                   instructions = ?, 
                   image_url = ? 
                   WHERE id = ? AND user_id = ?";
    
    $update_stmt = $pdo->prepare($update_sql);
    $success = $update_stmt->execute([
        $title, 
        $description, 
        $ingredients, 
        $instructions, 
        $image_url, 
        $recipe_id, 
        $user_id
    ]);

    if ($success) {
        // Redirect to view_recipe with a status keyword
        header("Location: view_recipe.php?id=" . $recipe_id . "&status=recipe_updated");
    } else {
        // Redirect back to edit page with error status
        header("Location: update_recipe.php?id=" . $recipe_id . "&status=error");
    }
    exit();
}