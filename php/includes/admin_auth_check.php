<?php
// php/includes/admin_auth_check.php

// Ensure session is started. This might be called already by the script including this,
// but calling it here ensures it's active.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the admin is logged in.
// We'll check for the presence of 'admin_id' and 'admin_username' in the session.
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    // Admin is not logged in.
    // Store a message to display on the login page (optional).
    $_SESSION['login_error'] = "Please log in to access the admin area.";

    // Redirect to login.php.
    // This script (admin_auth_check.php) is located in php/includes/.
    // The admin pages (e.g., categories.php) are in the admin/ directory.
    // The login.php script is also in the admin/ directory.
    // If admin/categories.php includes this file (php/includes/admin_auth_check.php),
    // the current working directory for the header() redirect is effectively admin/.
    // Therefore, a redirect to "login.php" should correctly point to admin/login.php.
    header("Location: login.php");
    exit;
}

// Optional: Session expiry check (simple version)
// Define session timeout duration (e.g., 30 minutes)
$session_timeout_duration = 30 * 60; // 30 minutes in seconds

if (isset($_SESSION['admin_logged_in_at']) && (time() - $_SESSION['admin_logged_in_at'] > $session_timeout_duration)) {
    // Session has expired.
    $expired_username = $_SESSION['admin_username'] ?? 'User'; // Get username before clearing session

    $_SESSION = array(); // Clear session variables
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy(); // Destroy the session

    // Redirect to login page with an expiry message
    // Need to start a new session briefly to pass this message.
    session_start(); // Start a new session just to pass the message
    $_SESSION['login_error'] = "Hello " . htmlspecialchars($expired_username) . ", your session has expired due to inactivity. Please log in again.";
    header("Location: login.php");
    exit;
}

// If session is active, update the 'last activity' time.
// This helps in keeping the session alive if there's activity,
// effectively implementing a sliding session timeout.
$_SESSION['admin_logged_in_at'] = time(); // Reset the timestamp on each authenticated page load

?>
