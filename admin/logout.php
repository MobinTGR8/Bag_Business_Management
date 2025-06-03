<?php
session_start();

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Set a logout message (optional)
// We'll use a query parameter for simplicity here, though session flash messages are also common.
// However, since the session is destroyed, a query param is more direct for this specific case.
header("Location: login.php?status=loggedout");
exit;
?>
