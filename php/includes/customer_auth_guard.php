<?php
// php/includes/customer_auth_guard.php

// Ensure session is started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// customer_functions.php is needed for is_customer_logged_in()
require_once __DIR__ . '/customer_functions.php';

if (!is_customer_logged_in()) {
    // Customer is not logged in.
    $_SESSION['message'] = "Please log in to access your account area.";
    $_SESSION['message_type'] = "error"; // Or 'info'

    // Store the intended destination to redirect back after login (optional but good UX)
    // Ensure not to store AJAX request URIs or POST request URIs if they aren't suitable redirect targets.
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $_SESSION['redirect_to_after_login'] = $_SERVER['REQUEST_URI'];
    }

    // Define BASE_URL if not already defined (fallback for this script's redirect purpose)
    // This helps ensure the redirect path is absolute.
    if (!defined('BASE_URL')) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        // Assuming this guard is in php/includes/ and the site root is two levels up from __DIR__
        // This calculation attempts to find the web root relative to this file's location.
        // For /var/www/html/myproject/php/includes/thisfile.php, __DIR__ is that path.
        // DOCUMENT_ROOT might be /var/www/html.
        // str_replace to get /myproject.
        // This can be error-prone. A globally defined BASE_URL from a central config is best.
        // Fallback for common structures:
        $path_to_root_from_includes = dirname(dirname($_SERVER['PHP_SELF'])); // e.g. /myproject or / if at root
        if ($path_to_root_from_includes === '/' || $path_to_root_from_includes === '\\') {
            $path_to_root_from_includes = ''; // Handle root case
        }
        define('BASE_URL', rtrim($protocol . $host . $path_to_root_from_includes, '/') . '/');
    }

    header("Location: " . BASE_URL . "customer_login.php");
    exit;
}

// Optional: Update 'last activity' timestamp to help with sliding session expiry
// This assumes 'customer_logged_in_at' is the session variable tracking activity.
if (isset($_SESSION['customer_logged_in_at'])) { // Check if it's set (should be by login_customer)
    // Implement session timeout check (e.g., 30 minutes)
    $session_timeout_duration_customer = 30 * 60;
    if ((time() - $_SESSION['customer_logged_in_at']) > $session_timeout_duration_customer) {
        $expired_customer_name = $_SESSION['customer_first_name'] ?? 'Customer';

        logout_customer(); // Use the existing logout function to clear customer session vars

        // Restart session to set a message for the login page
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $_SESSION['message'] = "Hello " . htmlspecialchars($expired_customer_name) . ", your session has expired due to inactivity. Please log in again.";
        $_SESSION['message_type'] = "info";

        if (!defined('BASE_URL')) { // Redefine if somehow lost after logout_customer and session restart
             $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
             $host = $_SERVER['HTTP_HOST'];
             $path_to_root_from_includes = dirname(dirname($_SERVER['PHP_SELF']));
             if ($path_to_root_from_includes === '/' || $path_to_root_from_includes === '\\') $path_to_root_from_includes = '';
             define('BASE_URL', rtrim($protocol . $host . $path_to_root_from_includes, '/') . '/');
        }
        header("Location: " . BASE_URL . "customer_login.php");
        exit;
    }
    // Update last activity time if session is still valid
    $_SESSION['customer_logged_in_at'] = time();
}

?>
