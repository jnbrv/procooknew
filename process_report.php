<?php
require_once 'includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = (int)$_POST['recipe_id'];
    $reason = $_POST['reason'];
    $user_id = $_SESSION['user_id']; // This is the person reporting

    try {
        // Updated to match your DB column: reporter_id
        $stmt = $pdo->prepare("INSERT INTO reports (recipe_id, reporter_id, reason) VALUES (?, ?, ?)");
        $stmt->execute([$recipe_id, $user_id, $reason]);
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>