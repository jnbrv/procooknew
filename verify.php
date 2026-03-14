<?php
session_start();
require_once 'includes/db.php';

$error_msg = []; // Initialize the array for SweetAlert

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = $_POST['otp'];
    $email = $_SESSION['verify_email'];

    // Check if code matches AND is not expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ? AND code_expires_at > NOW()");
    $stmt->execute([$email, $entered_code]);
    $user = $stmt->fetch();

    if ($user) {
        // Success! Clear the code and verify user
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $user_id = $user['id'];
        unset($_SESSION['verify_email']);
        $_SESSION['user_id'] = $user_id;
        
        // Redirect to dashboard with a success flag
        header("Location: userdashboard.php?status=verified");
        exit();
    } else {
        $error_msg[] = "Invalid or expired code. Please check your email and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ProCook | Verify Account</title>
    <link rel="stylesheet" href="css/auth.css"> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="auth-container" style="text-align: center; margin-top: 100px;">
    <form method="POST">
        <h2>Enter 6-Digit Code</h2>
        <p>Sent to: <b><?php echo htmlspecialchars($_SESSION['verify_email'] ?? 'your email'); ?></b></p>
        
        <input type="text" name="otp" maxlength="6" pattern="\d{6}" required 
               style="font-size: 32px; text-align: center; width: 200px; letter-spacing: 5px; border-radius: 8px; border: 2px solid #ff6600;">
        <br><br>
        <button type="submit" class="btn-primary">Verify Account</button>
    </form>
</div>

    <?php include 'includes/alerts.php'; ?>
</body>
</html>