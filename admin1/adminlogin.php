<?php
session_start();
require_once '../includes/db.php';

// --- SECURITY CHECK: Redirect if already logged in ---
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$email = "";
$password = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? "";
    $password = $_POST['password'] ?? "";

    if (!empty($email) && !empty($password)) {
        // 1. Fetch user by email and ensure they are an admin
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        // 2. Verify password (Using your database column 'password_hash')
        if ($admin && password_verify($password, $admin['password_hash'])) {
            
            // Cybersecurity: Regenerate ID to prevent session hijacking
            session_regenerate_id(true);
            
            // --- SET SESSION DATA ---
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['full_name'] ?? 'Admin';
            $_SESSION['role'] = 'admin';
            
            // --- START THE CLOCK ---
            // This is the "Key" that fixes your loophole. 
            // The dashboard checks this to see if you've been inactive.
            $_SESSION['LAST_ACTIVITY'] = time(); 
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid Admin Credentials.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook | Admin Secure Access</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; margin-bottom: 5px; color: #2c3e50; font-weight: bold; }
        .password-field-container { position: relative; display: flex; align-items: center; }
        .input-group input { width: 100%; padding: 12px; padding-right: 40px; border: 1px solid #dcdde1; border-radius: 6px; box-sizing: border-box; }
        .toggle-password { position: absolute; right: 12px; cursor: pointer; color: #7f8c8d; }
        .btn-admin { background-color: #2c3e50; color: white; border: none; padding: 14px; width: 100%; border-radius: 6px; cursor: pointer; font-size: 1rem; }
        .error-box { background-color: #fab1a0; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; }
        .forgot-pass { display: block; text-align: right; margin-top: 8px; font-size: 0.8rem; color: #3498db; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-card">
    <i class="fas fa-user-shield fa-3x" style="color: #2c3e50; margin-bottom: 20px;"></i>
    <h2>Admin Portal</h2>
    <p>ProCook Management System</p>

    <?php if($error): ?>
        <div class="error-box"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label>Admin Email</label>
            <input type="email" name="email" placeholder="admin@procook.com" required>
        </div>
        
        <div class="input-group">
            <label>Master Password</label>
            <div class="password-field-container">
                <input type="password" name="password" id="adminPass" placeholder="••••••••" required>
                <i class="fas fa-eye-slash toggle-password" id="eyeIcon"></i>
            </div>
            <a href="admin_forgot_password.php" class="forgot-pass">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-admin">Enter Dashboard</button>
    </form>
</div>

<script>
    const eyeIcon = document.querySelector('#eyeIcon');
    const passwordInput = document.querySelector('#adminPass');

    eyeIcon.addEventListener('click', function() {
        // Toggle the type attribute
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle the eye icon classes
        // If type is text (visible), show the open eye. If password (hidden), show the slashed eye.
        if (type === 'password') {
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
</script>

</body>
</html>