<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buyer_id = $_SESSION['user_id'];
    // Accepts 'recipe_id' or 'id' from the form
    $recipe_id = filter_var($_POST['recipe_id'] ?? $_POST['id'], FILTER_VALIDATE_INT);

    if (!$recipe_id) {
        header("Location: userdashboard.php?status=error&msg=invalid_id");
        exit();
    }

    // 1. Fetch details from 'recipes' table (user_id is the creator)
    $stmt = $pdo->prepare("SELECT user_id, price, title, is_paid FROM recipes WHERE id = ?");
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();

    if ($recipe) {
        $creator_id = $recipe['user_id'];
        $price = $recipe['price'];

        // 2. Security Check: Prevent buying own recipe
        if ($creator_id == $buyer_id) {
            header("Location: view_recipe.php?id=$recipe_id&status=own_recipe");
            exit();
        }

        // 3. Check if already purchased in 'purchases' table
        $check = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND recipe_id = ?");
        $check->execute([$buyer_id, $recipe_id]);
        
        if ($check->fetch()) {
            header("Location: view_recipe.php?id=$recipe_id&status=already_owned");
            exit();
        }

        try {
            $pdo->beginTransaction();
            
            // 4. Record Purchase (matches your purchases schema)
            $insertPurchase = $pdo->prepare("INSERT INTO purchases (user_id, recipe_id, amount_paid) VALUES (?, ?, ?)");
            $insertPurchase->execute([$buyer_id, $recipe_id, $price]);
            
            // 5. Record Earning (matches your earnings schema: id, user_id, recipe_id, amount, earned_at)
            // We use 'earned_at' specifically as seen in your phpMyAdmin screenshot
            $insertEarning = $pdo->prepare("INSERT INTO earnings (user_id, recipe_id, amount, earned_at) VALUES (?, ?, ?, NOW())");
            $insertEarning->execute([$creator_id, $recipe_id, $price]);

            $pdo->commit();
            header("Location: view_recipe.php?id=$recipe_id&status=purchased");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            // Redirect with the actual error message so you can see what's wrong
            header("Location: view_recipe.php?id=$recipe_id&status=error&msg=" . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header("Location: userdashboard.php?status=error&msg=recipe_not_found");
        exit();
    }
}

header("Location: userdashboard.php");
exit();