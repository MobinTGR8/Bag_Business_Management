<?php
// admin/create_admin_util.php
// Temporary utility to create an admin user.
// Run this script once from your browser, then DELETE IT or secure it.

require_once '../php/db_connect.php'; // Adjust path as needed

$username = 'admin';
$email = 'admin@example.com';
$password = 'password123'; // CHANGE THIS to a strong password in a real scenario
$full_name = 'Site Administrator';
$role = 'superadmin'; // Changed to 'superadmin' to match ENUM in schema.sql

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

if (!$password_hash) {
    die("Password hashing failed. Check PHP version and configuration.");
}

// SQL to insert admin user
// Removed is_active as it's not in the current schema.sql for admins table
// Columns: username, email, password_hash, full_name, role, created_at, updated_at
// last_login will be updated upon actual login.
$sql = "INSERT INTO admins (username, email, password_hash, full_name, role, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $mysqli->error);
}

// Bind parameters: sssss (string for username, email, password_hash, full_name, role)
$stmt->bind_param("sssss", $username, $email, $password_hash, $full_name, $role);

if ($stmt->execute()) {
    echo "Admin user '{$username}' created successfully with password '{$password}'.<br>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "Role: " . htmlspecialchars($role) . "<br>";
    echo "Hashed password for storage: " . htmlspecialchars($password_hash) . "<br>";
    echo "<strong>IMPORTANT: Delete this script (admin/create_admin_util.php) immediately after use!</strong>";
} else {
    echo "Error creating admin user: " . $stmt->error;
    if ($stmt->errno == 1062) { // Error code for Duplicate entry
        echo "<br>This username ('" . htmlspecialchars($username) . "') or email ('" . htmlspecialchars($email) . "') might already exist in the admins table.";
    }
}

$stmt->close();
$mysqli->close();
?>
