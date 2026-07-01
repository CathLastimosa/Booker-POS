<?php
session_start();
include 'dbConnection.php';
include 'auth.php';

// Log the logout activity
if (isLoggedIn()) {
    logActivity('LOGOUT', 'User logged out', getCurrentUser());
}

// Destroy session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

header('Location: login.php?message=logged_out_successfully');
exit();
