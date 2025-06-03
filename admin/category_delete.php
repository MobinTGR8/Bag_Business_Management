<?php
// admin/category_delete.php
require_once __DIR__ . '/../php/includes/admin_auth_check.php';
// session_start(); // session_start() is now handled by admin_auth_check.php if needed

require_once '../php/db_connect.php'; // Provides $mysqli
require_once '../php/includes/category_functions.php';

// Check for admin login status here if implementing authentication // This line is now covered by admin_auth_check.php

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid category ID for deletion.";
    $_SESSION['message_type'] = "error";
    header("Location: categories.php");
    exit;
}

$category_id = (int)$_GET['id'];

// Optional: Add a CSRF token check here for better security

// Attempt to delete the category
// The delete_category function already checks if the category has children and prevents deletion if so.
// It also depends on how products.category_id FOREIGN KEY is set up (ON DELETE SET NULL or ON DELETE RESTRICT)
if (delete_category($mysqli, $category_id)) {
    $_SESSION['message'] = "Category (ID: $category_id) deleted successfully.";
    $_SESSION['message_type'] = "success";
} else {
    // The delete_category function logs specific SQL errors.
    // A general message is usually sufficient here, or you could try to get more specific error info if needed.
    // The function `delete_category` itself returns false if children exist, so this message covers that.
    $_SESSION['message'] = "Failed to delete category (ID: $category_id). It might have child categories, be referenced by products (if ON DELETE RESTRICT is used for products.category_id), or another database error occurred. Please check system logs.";
    $_SESSION['message_type'] = "error";
}

header("Location: categories.php");
exit;
?>
