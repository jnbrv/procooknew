<?php
require 'vendor/autoload.php';
require_once 'includes/db.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- GOOGLE OAUTH SETUP ---
$client = new Google\Client();
$client->setClientId(''); 
$client->setClientSecret('');
$client->setRedirectUri('http://localhost/procook_new/google-callback.php');
$client->addScope("email");
$client->addScope("profile");
$google_login_url = $client->createAuthUrl();

$error_msg = []; 
$success_msg = [];

// --- NEW: CATCH STATUS FROM OTHER FILES (like forgot-password) ---
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'reset_success':
            $success_msg[] = "Password updated! You can now log in.";
            break;
        case 'verified':
            $success_msg[] = "Email verified successfully! Please log in.";
            break;
        case 'logged_out':
            $info_msg[] = "You have been logged out. See you again!";
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $now = new DateTime();
        $lockout_until = $user['lockout_until'] ? new DateTime($user['lockout_until']) : null;

        if ($lockout_until && $lockout_until > $now) {
            $error_msg[] = "Account temporarily locked. Try again later.";
        } else {
            if (password_verify($password, $user['password_hash'])) {
                if ($user['is_verified'] == 0) {
                    $_SESSION['temp_email'] = $user['email'];
                    header("Location: sign-up.php");
                    exit();
                }
                
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                header("Location: userdashboard.php");
                exit();
            } else {
                $attempts = $user['failed_attempts'] + 1;
                $lockout = null;
                if ($attempts >= 5) {
                    $lockout = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                }
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = ?, lockout_until = ? WHERE id = ?");
                $stmt->execute([$attempts, $lockout, $user['id']]);
                $error_msg[] = "Invalid credentials.";
            }
        }
    } else {
        $error_msg[] = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ProCook | Log In</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="auth-container">
    <div class="auth-image">
        <a href="index.php" class="back-btn"><ion-icon name="arrow-back-outline"></ion-icon></a>
    </div>

    <div class="auth-form-section">
        <h1>Log In Form</h1>
        <p class="subtitle">Log in to your account</p>

        <form action="login.php" method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="login_pass" placeholder="Password" required>
                <ion-icon name="eye-outline" class="password-toggle" onclick="togglePass('login_pass')"></ion-icon>
            </div>
            
            <a href="forgot-password.php" style="display: block; text-align: right; font-size: 0.8rem; color: var(--text-light); text-decoration: none; margin-bottom: 15px;">Forgot Password?</a>
            
            <button type="submit" class="btn-primary">Continue</button>
        </form>

        <div class="divider">or</div>

        <a href="<?php echo $google_login_url; ?>" class="social-btn" style="text-decoration: none;">
            <ion-icon name="logo-google"></ion-icon> Continue with Google
        </a>

        <p class="footer-text">Don't have an account? <a href="sign-up.php">Sign Up Here</a></p>
    </div>
</div>

<script>
    function togglePass(id) {
        const input = document.getElementById(id);
        input.type = input.type === "password" ? "text" : "password";
    }
</script>
    <?php include 'includes/alerts.php'; ?>
</body>
</html>
