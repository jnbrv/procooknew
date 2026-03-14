<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 
session_start();
require_once '../includes/db.php';

$error = "";
$show_modal = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_code'])) {
    $email = $_POST['email'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin) {
        $admin_id = $admin['id'];
        $otp_code = (string)rand(100000, 999999);
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        try {
            // DATABASE SYNC: Your table uses 'token' and 'user_id'
            $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$admin_id]);
            $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")
                ->execute([$admin_id, $otp_code, $expires]);

            $mail = new PHPMailer(true);
            // --- SMTP SETTINGS ---
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ellise.me1234@gmail.com'; 
            $mail->Password   = 'dgourrdqkoulhnna'; // App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('security@procook.com', 'ProCook Admin Security');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Admin Access Code';
            $mail->Body    = "Your security code is: <h2 style='color:#2c3e50;'>$otp_code</h2>Expires in 15 mins.";

            $mail->send();
            
            $_SESSION['reset_admin_id'] = $admin_id;
            $_SESSION['reset_email'] = $email;
            $show_modal = true; 

        } catch (Exception $e) {
            // IMPROVED ERROR DEBUGGING
            $error = "Mailer Error: " . $mail->ErrorInfo;
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Administrator email not found.";
    }
}

// STEP 2: VERIFICATION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'] ?? '';
    $admin_id = $_SESSION['reset_admin_id'] ?? 0;

    // Use 'token' column to match your SQL dump
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE user_id = ? AND token = ? AND expires_at > NOW()");
    $stmt->execute([$admin_id, $entered_otp]);
    $reset = $stmt->fetch();

    if ($reset) {
        $new_pass = "ProCookAdmin2026!";
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

        // Your 'users' table uses 'password_hash' column
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hashed, $admin_id]);
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$admin_id]);

        echo "<script>
            alert('SUCCESS! Your temporary password is: $new_pass\\n\\nPlease login now.');
            window.location='adminlogin.php';
        </script>";
        exit();
    } else {
        $error = "Invalid or expired code. Please try again.";
        $show_modal = true; // Keep modal open if code is wrong
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Recovery | ProCook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .input-group { text-align: left; margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-admin { width: 100%; padding: 14px; background: #2c3e50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: bold; transition: 0.3s; }
        .btn-admin:hover { background: #34495e; }
        .modal-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.8); display: <?php echo $show_modal ? 'flex' : 'none'; ?>; 
            justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-content { background: white; padding: 30px; border-radius: 15px; width: 350px; text-align: center; }
        .otp-box { font-size: 2rem; width: 100%; text-align: center; letter-spacing: 8px; margin: 20px 0; border: 2px solid #2c3e50; border-radius: 8px; padding: 10px; }
        .error-msg { color: #e74c3c; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid #fab1a0; padding: 10px; border-radius: 4px; background: #fff5f5; }
    </style>
</head>
<body>

<div class="card">
    <i class="fas fa-user-lock fa-3x" style="color: #2c3e50; margin-bottom: 20px;"></i>
    <h2>Forgot Password</h2>
    <?php if($error && !$show_modal): ?> <div class="error-msg"><?php echo $error; ?></div> <?php endif; ?>
    
    <form method="POST">
        <div class="input-group">
            <label>Admin Email</label>
            <input type="email" name="email" placeholder="admin@procook.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <button type="submit" name="request_code" class="btn-admin">Send Code</button>
    </form>
    <br><a href="adminlogin.php" style="color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">Back to Login</a>
</div>

<div class="modal-overlay">
    <div class="modal-content">
        <i class="fas fa-envelope-open-text fa-3x" style="color: #27ae60; margin-bottom: 10px;"></i>
        <h3>Enter Code</h3>
        <p style="font-size: 0.9rem; color: #666;">A 6-digit code was sent to:<br><strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></strong></p>
        
        <?php if($error && $show_modal): ?> <div class="error-msg"><?php echo $error; ?></div> <?php endif; ?>

        <form method="POST">
            <input type="text" name="otp" class="otp-box" maxlength="6" pattern="\d{6}" required autocomplete="off">
            <button type="submit" name="verify_otp" class="btn-admin">Verify & Reset</button>
        </form>
        <button onclick="window.location.reload();" style="margin-top: 15px; background: none; border: none; color: #7f8c8d; cursor: pointer;">Cancel</button>
    </div>
</div>

</body>
</html>