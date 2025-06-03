<?php
// admin/product_delete.php
require_once __DIR__ . '/../php/includes/admin_auth_check.php'; // Protect this page
require_once __DIR__ . '/../php/db_connect.php';
require_once __DIR__ . '/../php/includes/product_functions.php';
// file_utils.php is not strictly necessary here as we are constructing path and unlinking directly.
// If we had a generic delete_file utility in file_utils.php that took a web path, we could use it.

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid product ID for deletion.";
    $_SESSION['message_type'] = "error";
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];

// Before deleting from DB, get product details to delete its image if it exists.
$product_to_delete = get_product_by_id($mysqli, $product_id);

if (!$product_to_delete) {
    // This also handles cases where the product might have been deleted by another process
    // after the link was generated on products.php
    $_SESSION['message'] = "Product (ID: {$product_id}) not found. It might have already been deleted.";
    $_SESSION['message_type'] = "error"; // Or 'warning' if preferred
    header("Location: products.php");
    exit;
}

// Attempt to delete the product image file if it exists
$image_deletion_failed_warning = null;
if (!empty($product_to_delete['image_url'])) {
    // Construct the server path to the image.
    // image_url is stored like 'uploads/product_images/filename.jpg'
    // __DIR__ is /app/admin, so __DIR__ . '/../' is /app/
    $image_server_path = rtrim(__DIR__ . '/../', '/') . '/' . ltrim($product_to_delete['image_url'], '/');

    if (file_exists($image_server_path)) {
        if (!unlink($image_server_path)) {
            $error_message = "Failed to delete product image file: " . htmlspecialchars($product_to_delete['image_url']) . ". Check file permissions.";
            error_log($error_message . " Server path: " . $image_server_path);
            $image_deletion_failed_warning = $error_message;
        }
    } else {
        // Optional: Log if image_url is present in DB but file is missing
        error_log("Product image file not found at path: " . $image_server_path . " (referenced by product ID: {$product_id})");
    }
}

// Attempt to delete the product from the database
if (delete_product($mysqli, $product_id)) {
    if ($image_deletion_failed_warning) {
        $_SESSION['message'] = $image_deletion_failed_warning . " However, the product database record was deleted successfully.";
        $_SESSION['message_type'] = "warning"; // Use a 'warning' type if available, else 'error' or 'success' with qualification
    } else {
        $_SESSION['message'] = "Product (ID: {$product_id}) and its associated image (if any) deleted successfully.";
        $_SESSION['message_type'] = "success";
    }
} else {
    // The delete_product function logs specific SQL errors.
    // This might be due to foreign key constraints (e.g., product in an order_item).
    $db_delete_error_message = "Failed to delete product (ID: {$product_id}) from database. It might be part of an existing order or another issue occurred. Check system logs.";
    if ($image_deletion_failed_warning) {
        $_SESSION['message'] = $image_deletion_failed_warning . " Additionally, " . lcfirst($db_delete_error_message);
        $_SESSION['message_type'] = "error";
    } else {
        $_SESSION['message'] = $db_delete_error_message;
        $_SESSION['message_type'] = "error";
    }
}

header("Location: products.php");
exit;
?>
