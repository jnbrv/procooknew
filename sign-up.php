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

// Initialize SweetAlert Arrays
$error_msg = [];
$success_msg = [];
$show_modal = false;

// --- HANDLE POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CASE A: VERIFY OTP
    if (isset($_POST['otp_code'])) {
        $entered_code = $_POST['otp_code'];
        $email = $_SESSION['temp_email'];

        $current_time = date("Y-m-d H:i:s");
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ? AND code_expires_at > ?");
        $stmt->execute([$email, $entered_code, $current_time]);
        $user = $stmt->fetch();

        if ($user) {
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            unset($_SESSION['temp_email']);
            $_SESSION['user_id'] = $user['id'];
            header("Location: userdashboard.php");
            exit();
        } else {
            $error_msg[] = "Invalid or expired code.";
            $show_modal = true; // Keep modal open on error
        }
    } 
    
    // CASE B: INITIAL SIGN UP
    else if (isset($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error_msg[] = "Passwords do not match.";
        } else {
            // 1. DELETE any old, unverified attempts for this email first
            // This ensures the database stays clean if they try again
            $stmt = $pdo->prepare("DELETE FROM users WHERE email = ? AND is_verified = 0");
            $stmt->execute([$email]);

            // 2. NOW check if a VERIFIED user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_verified = 1");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error_msg[] = "This email is already verified and registered. Please log in.";
            } else {
                    try {
                        $pdo->beginTransaction();
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Generate 6-digit OTP and 15-min expiry
                        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $expiry = date("Y-m-d H:i:s", strtotime('+15 minutes'));

                        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, is_verified, verification_code, code_expires_at) VALUES (?, ?, 0, ?, ?)");
                        $stmt->execute([$email, $hashed_password, $otp, $expiry]);

                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com'; 
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'ellise.me1234@gmail.com'; 
                        $mail->Password   = 'dgourrdqkoulhnna'; // Your App Password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('no-reply@procook.com', 'ProCook Team');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Verify Your ProCook Account';
                        $mail->Body = "<h2>Welcome to ProCook!</h2>
                                    <p>Your 6-digit verification code is:</p>
                                    <h1 style='letter-spacing: 5px; color: #ff6600;'>$otp</h1>
                                    <p>This code expires in 15 minutes.</p>";

                        $mail->send();
                        $pdo->commit();
                        
                        $_SESSION['temp_email'] = $email;
                        $show_modal = true; // Trigger the modal
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error_msg[] = "Mail Error: " . $mail->ErrorInfo;
                    }
                }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook | Sign Up</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; text-align: center;}
        .alert-error { background: #ffebee; color: #c62828; }
        
        /* Modal Styles */
        .otp-modal {
            display: <?php echo $show_modal ? 'flex' : 'none'; ?>;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 1000;
            justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; padding: 40px; border-radius: 15px;
            width: 100%; max-width: 400px; text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .otp-input {
            width: 100%; font-size: 2rem; text-align: center;
            letter-spacing: 10px; margin: 20px 0; border: 2px solid #ddd;
            border-radius: 8px; padding: 10px; outline: none;
        }
        .otp-input:focus { border-color: #ff6600; }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-image">
        <a href="index.php" class="back-btn"><ion-icon name="arrow-back-outline"></ion-icon></a>
    </div>

    <div class="auth-form-section">
        
        <h1>Sign Up Form</h1>
        <p class="subtitle">Create your account</p>

        <form action="sign-up.php" method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            <div class="input-group">
                <input type="password" name="password" id="pass" placeholder="Password" minlength="8" required>
                <ion-icon name="eye-outline" class="password-toggle" onclick="togglePass('pass')"></ion-icon>
            </div>
            <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_pass" placeholder="Confirm Password" minlength="8" required>
                <ion-icon name="eye-outline" class="password-toggle" onclick="togglePass('confirm_pass')"></ion-icon>
            </div>
            <button type="submit" class="btn-primary">Continue</button>
        </form>

        <div class="divider">or</div>

        <a href="<?php echo $google_login_url; ?>" class="social-btn" style="text-decoration: none;">
            <ion-icon name="logo-google"></ion-icon> Continue with Google
        </a>
        <p class="footer-text">Already have an account? <a href="login.php">Login Here</a></p>
    </div>
</div>

<div id="otpModal" class="otp-modal">
    <div class="modal-content">
        <h2>Verify Your Email</h2>
        <p>Enter the 6-digit code sent to your inbox.</p>

        <form action="sign-up.php" method="POST">
            <input type="text" name="otp_code" class="otp-input" placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
            <button type="submit" class="btn-primary">Verify & Login</button>
        </form>
        <p style="margin-top: 20px;"><a href="sign-up.php" style="color: #666; font-size: 0.9rem;">Cancel / Back</a></p>
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
