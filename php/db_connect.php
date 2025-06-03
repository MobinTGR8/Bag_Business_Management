<?php

// Include the database configuration file
require_once 'config.php';

// Attempt to connect to MySQL database
$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($mysqli->connect_error) {
    // Connection failed. Output error and terminate script.
    // In a production environment, you might want to log this error instead of displaying it directly.
    error_log("Database Connection Failed: " . $mysqli->connect_error);
    die("Sorry, we're experiencing some technical difficulties. Please try again later.");
    // For development, you might want more detailed errors:
    // die("Connection Failed: " . $mysqli->connect_error . " (Error Code: " . $mysqli->connect_errno . ")");
}

// Set character set to utf8mb4 (recommended for full Unicode support)
if (!$mysqli->set_charset("utf8mb4")) {
    // In a production environment, log this error.
    error_log("Error loading character set utf8mb4: " . $mysqli->error);
    // For development:
    // printf("Error loading character set utf8mb4: %s
", $mysqli->error);
}

// The $mysqli object is now ready for use by other scripts that include this file.
// Example: require_once 'php/db_connect.php';
// Then use $mysqli to perform queries.

?>
