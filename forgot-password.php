<?php
require 'vendor/autoload.php';
require_once 'includes/db.php';
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize SweetAlert arrays
$error_msg = [];
$success_msg = [];
$info_msg = [];

// Catch success status from other pages
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'reset_success') {
        $success_msg[] = "Password updated! You can now log in.";
    } elseif ($_GET['status'] === 'verified') {
        $success_msg[] = "Email verified! Please log in.";
    }
}

// Step 1: Request, Step 2: Verify, Step 3: Reset
$step = isset($_SESSION['reset_step']) ? $_SESSION['reset_step'] : 1; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- STEP 1: SEND EMAIL ---
    if (isset($_POST['action']) && $_POST['action'] === 'send_code') {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_verified = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $otp_hash, $expiry]);

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ellise.me1234@gmail.com'; 
                $mail->Password = 'dgourrdqkoulhnna'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@procook.com', 'ProCook Security');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'ProCook Password Reset Code';
                $mail->Body = "<h2>Password Reset</h2><p>Your code is: <b style='font-size:24px; color:#ff6600;'>$otp</b></p><p>Expires in 15 mins.</p>";
                $mail->send();

                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_step'] = 2;
                header("Location: forgot-password.php");
                exit();
            } catch (Exception $e) {
                $error_msg[] = "Mail error. Try again later.";
            }
        } else {
            // Anti-enumeration
            $info_msg[] = "If an account exists, a code has been sent.";
        }
    }

    // --- STEP 2: VERIFY CODE ---
    if (isset($_POST['action']) && $_POST['action'] === 'verify_code') {
        $email = $_SESSION['reset_email'];
        $code = $_POST['otp_code'];
        $current_time = date("Y-m-d H:i:s");

        $stmt = $pdo->prepare("SELECT pr.* FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE u.email = ? AND pr.expires_at > ? AND pr.used = 0");
        $stmt->execute([$email, $current_time]);
        $reset = $stmt->fetch();

        if ($reset && password_verify($code, $reset['token_hash'])) {
            $_SESSION['reset_step'] = 3;
            header("Location: forgot-password.php");
            exit();
        } else {
            $error_msg[] = "Invalid or expired code.";
        }
    }

    // --- STEP 3: NEW PASSWORD ---
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        $email = $_SESSION['reset_email'];

        if ($password !== $confirm) {
            $error_msg[] = "Passwords do not match.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);

            $stmt = $pdo->prepare("UPDATE password_resets pr JOIN users u ON pr.user_id = u.id SET pr.used = 1 WHERE u.email = ?");
            $stmt->execute([$email]);

            session_destroy();
            // Redirect to login with a specific status
            header("Location: login.php?status=reset_success");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ProCook | Reset Password</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #f87171; }
        .otp-box { font-size: 2rem; text-align: center; letter-spacing: 8px; width: 100%; margin: 15px 0; border: 2px solid #ddd; border-radius: 8px; padding: 10px; }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-image">
        <a href="login.php" class="back-btn"><ion-icon name="arrow-back-outline"></ion-icon></a>
    </div>

    <div class="auth-form-section">
        <?php if($step == 1): ?>
            <h1>Forgot Password</h1>
            <p class="subtitle">Enter your email to receive a reset code</p>
            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="action" value="send_code">
                <div class="input-group"><input type="email" name="email" placeholder="Email Address" required></div>
                <button type="submit" class="btn-primary">Send Code</button>
            </form>

        <?php elseif($step == 2): ?>
            <h1>Enter Code</h1>
            <p class="subtitle">Enter the 6-digit code sent to <b><?php echo htmlspecialchars($_SESSION['reset_email']); ?></b></p>
            
            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="action" value="verify_code">
                <input type="text" name="otp_code" class="otp-box" maxlength="6" pattern="\d{6}" required autofocus>
                <button type="submit" class="btn-primary">Verify Code</button>
            </form>

            <div style="margin-top: 25px; font-size: 0.9rem; color: #666;">
                <p id="timer-text">Didn't get the code? Resend in <span id="countdown">60</span>s</p>
                
                <form action="forgot-password.php" method="POST" id="resend-form" style="display: none;">
                    <input type="hidden" name="action" value="send_code">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['reset_email']); ?>">
                    <button type="submit" style="background: none; border: none; color: #ff6600; cursor: pointer; font-weight: bold; text-decoration: underline;">
                        Resend New Code
                    </button>
                </form>
            </div>

        <?php elseif($step == 3): ?>
            <h1>New Password</h1>
            <p class="subtitle">Set a strong password for your account</p>
            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="action" value="update_password">
                <div class="input-group"><input type="password" name="password" placeholder="New Password" minlength="8" required></div>
                <div class="input-group"><input type="password" name="confirm_password" placeholder="Confirm Password" required></div>
                <button type="submit" class="btn-primary">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    // Only run timer if we are on Step 2
    const countdownElement = document.getElementById('countdown');
    if (countdownElement) {
        let timeLeft = 60;
        const timerText = document.getElementById('timer-text');
        const resendForm = document.getElementById('resend-form');

        const timer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(timer);
                timerText.style.display = 'none'; // Hide the "Resend in X" text
                resendForm.style.display = 'block'; // Show the clickable Resend button
            }
        }, 1000);
    }

    function togglePass(id) {
        const input = document.getElementById(id);
        input.type = input.type === "password" ? "text" : "password";
    }
</script>
<?php include 'includes/alerts.php'; ?>
</body>

</html>