<?php
require_once 'vendor/autoload.php';
require_once 'includes/db.php';
session_start();

$client = new Google\Client();
$client->setClientId('283225461554-4dua83rss9b99bjjj8l4t2o1a8gbjf17.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/procook_new/google-callback.php');

if (isset($_GET['code'])) {
    try {
        // 1. Exchange the code for an Access Token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // CHECK: Did we actually get a token or an error?
        if (isset($token['error'])) {
            throw new Exception('Google Access Token Error: ' . $token['error_description']);
        }

        $client->setAccessToken($token);

        // 2. Get profile info
        $google_oauth = new Google\Service\Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;

        // 3. Database Sync
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // New User: Mark as verified immediately since Google verified the email
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, is_verified) VALUES (?, 'GOOGLE_AUTH_USER', 1)");
            $stmt->execute([$email]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $user['id'];
            // OPTIONAL: If they weren't verified before, mark them verified now
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
        }

        // 4. Set Session and Redirect
        $_SESSION['user_id'] = $user_id;
        $_SESSION['logged_in'] = true; // Extra safety check for your dashboard
        
        header("Location: userdashboard.php");
        exit();

    } catch (Exception $e) {
        // Log the real error for yourself
        error_log($e->getMessage());
        // Show a clean error to the user (as discussed for image_a356be.jpg)
        header("Location: sign-up.php?error=social_auth_failed");
        exit();
    }
} else {
    header("Location: sign-up.php");
    exit();
}