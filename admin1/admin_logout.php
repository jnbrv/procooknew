<?php
session_start();

// 1. Clear the Session Array
$_SESSION = array();

// 2. Kill the session cookie on the user's browser (Cybersecurity Best Practice)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Completely destroy the session on the server
session_destroy();

// 4. Redirect to the login page
header("Location: adminlogin.php?status=logged_out");
exit();
?>