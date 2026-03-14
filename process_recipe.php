<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) { exit; }

// 1. Check if the image was actually sent
if (!isset($_FILES['recipe_image']) || $_FILES['recipe_image']['error'] === UPLOAD_ERR_NO_FILE) {
    header("Location: userdashboard.php?status=error");
    exit();
}

// 2. Capture Form Data
$user_id     = $_SESSION['user_id'];
// Using htmlspecialchars or FILTER_SANITIZE_FULL_SPECIAL_CHARS is safer for modern PHP
$title       = htmlspecialchars($_POST['recipe_name']);
$description = htmlspecialchars($_POST['description']);
$ingredients = htmlspecialchars($_POST['ingredients']);
$instructions = htmlspecialchars($_POST['procedure']); // Changed variable to match DB column

// Convert price to a float and determine if it's a paid recipe
$price       = isset($_POST['recipe_price']) ? floatval($_POST['recipe_price']) : 0.00;
$is_paid     = ($price > 0) ? 1 : 0;

// 3. Image Handling
$image_name = $_FILES['recipe_image']['name'];
$image_ext  = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
$rename_img = time() . '_' . uniqid() . '.' . $image_ext; 
$targetDir  = 'uploads/';
$targetPath = $targetDir . $rename_img;

if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }

// 4. Save to Database (Status defaults to 'published' in DB)
if (move_uploaded_file($_FILES['recipe_image']['tmp_name'], $targetPath)) {
    
    // REMOVED 'status' from the insert. 
    // Since your DB default is 'published', it goes live immediately.
    $sql = "INSERT INTO recipes (user_id, title, description, ingredients, instructions, image_url, is_paid, price) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$user_id, $title, $description, $ingredients, $instructions, $rename_img, $is_paid, $price])) {
        header("Location: userdashboard.php?status=recipe_posted");
        exit();
    } else {
        header("Location: userdashboard.php?status=error");
        exit();
    }
} else {
    header("Location: userdashboard.php?status=error");
    exit();
}
?>