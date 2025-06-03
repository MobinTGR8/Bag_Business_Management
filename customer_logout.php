<?php
// customer_logout.php

// customer_functions.php ensures session_start() is called if needed,
// and contains logout_customer()
require_once 'php/includes/customer_functions.php';

// Call logout_customer() to clear customer-specific session variables
logout_customer();

// Ensure session is active to set the logout message.
// logout_customer() does not destroy the session, so it should still be active
// if it was active before the call. If it wasn't, customer_functions.php started it.
if (session_status() === PHP_SESSION_NONE) {
    // This case should ideally not be hit if customer_functions.php works as expected
    // or if a page including this had already started a session.
    session_start();
}

$_SESSION['message'] = "You have been successfully logged out.";
$_SESSION['message_type'] = "success";

// Define BASE_URL if not already defined (e.g., by header.php if this was a full page view)
// This is a fallback, ideally BASE_URL is consistently available.
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // SCRIPT_NAME for /customer_logout.php would be /customer_logout.php or /subdir/customer_logout.php
    // dirname() would give / or /subdir
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    // Normalize base_path if it's at the web root
    if ($base_path === '.' || $base_path === '\\' || $base_path === '/') {
        $base_path = '';
    }
    define('BASE_URL', rtrim($protocol . $host . $base_path, '/') . '/');
}

// Redirect to the homepage
header("Location: " . BASE_URL . "index.php");
exit;
?>
