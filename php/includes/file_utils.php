<?php
// php/includes/file_utils.php

define('PRODUCT_IMAGE_UPLOAD_DIR', __DIR__ . '/../../uploads/product_images/'); // Absolute path on server
define('PRODUCT_IMAGE_WEB_PATH', 'uploads/product_images/'); // Relative path for web access

/**
 * Handles a product image upload.
 *
 * @param array $file_input The file input array from $_FILES (e.g., $_FILES['product_image']).
 * @param string|null $current_image_path Optional. The web-accessible path to the current image if updating, to delete it.
 * @return string|false|null The web-accessible path to the new image on success, false on failure,
 *                           or null if no file was uploaded but no error occurred.
 */
function handle_product_image_upload(array $file_input, ?string $current_image_path = null) {
    // Ensure the upload directory exists and is writable
    if (!is_dir(PRODUCT_IMAGE_UPLOAD_DIR)) {
        // Attempt to create recursively with 0755 permissions
        if (!mkdir(PRODUCT_IMAGE_UPLOAD_DIR, 0755, true)) {
            error_log("Failed to create product image upload directory: " . PRODUCT_IMAGE_UPLOAD_DIR);
            return false; // Cannot create directory
        }
    }
    // Permissions check after creation or if it already exists
    if (!is_writable(PRODUCT_IMAGE_UPLOAD_DIR)) {
        error_log("Product image upload directory is not writable: " . PRODUCT_IMAGE_UPLOAD_DIR . ". Please check server permissions.");
        // Attempt to set permissions (this might fail depending on server configuration/PHP user)
        // if (!@chmod(PRODUCT_IMAGE_UPLOAD_DIR, 0755)) {
        //     error_log("Attempt to chmod " . PRODUCT_IMAGE_UPLOAD_DIR . " failed.");
        // }
        // Re-check writability after chmod attempt could be done, but for now, log and return false if not writable.
        return false; // Directory not writable
    }

    // Check if a file was actually uploaded
    if (!isset($file_input['tmp_name']) || empty($file_input['tmp_name']) || !is_uploaded_file($file_input['tmp_name'])) {
        // UPLOAD_ERR_NO_FILE is a more specific check if available and error code is UPLOAD_ERR_NO_FILE
        if (isset($file_input['error']) && $file_input['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // No file uploaded, this is fine for optional images
        }
        // If tmp_name is not set or empty, but error is not UPLOAD_ERR_NO_FILE, it's an issue or no upload initiated.
        // For robustness, if tmp_name isn't valid, treat as no file or potential issue.
        // Consider it as no file for this function's purpose if tmp_name is not a valid uploaded file.
        return null;
    }

    // Check for other upload errors (covers cases beyond UPLOAD_ERR_NO_FILE)
    if ($file_input['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error code: " . $file_input['error']);
        // You could return specific error messages based on the error code
        return false;
    }

    // Validate file size (e.g., max 2MB)
    $max_file_size = 2 * 1024 * 1024; // 2MB
    if ($file_input['size'] > $max_file_size) {
        error_log("File upload error: File '" . htmlspecialchars($file_input['name']) . "' exceeds maximum size of 2MB.");
        return false;
    }

    // Validate file type (MIME type) using finfo for better security than mime_content_type if available
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_mime_type = finfo_file($finfo, $file_input['tmp_name']);
    finfo_close($finfo);

    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file_mime_type, $allowed_mime_types)) {
        error_log("File upload error: Invalid file type for '" . htmlspecialchars($file_input['name']) . "'. Allowed: JPEG, PNG, GIF. Detected: " . htmlspecialchars($file_mime_type));
        return false;
    }

    // Generate a unique filename to prevent overwriting and sanitize
    // Sanitize original filename's extension part
    $original_filename = basename($file_input['name']);
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        // Fallback or force an extension if pathinfo fails or returns something unexpected
        // Forcing based on MIME type is safer
        $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $file_extension = $ext_map[$file_mime_type] ?? 'jpg'; // default to jpg if mime type somehow passed but ext is weird
    }

    $unique_filename = uniqid('prod_', true) . '.' . $file_extension;
    $destination_path = PRODUCT_IMAGE_UPLOAD_DIR . $unique_filename;

    // Move the uploaded file
    if (move_uploaded_file($file_input['tmp_name'], $destination_path)) {
        // File uploaded successfully. Delete old image if one exists and is different.
        if ($current_image_path && is_string($current_image_path) && $current_image_path !== (PRODUCT_IMAGE_WEB_PATH . $unique_filename)) {
            // Construct server path from web path. This assumes PRODUCT_IMAGE_WEB_PATH is a prefix.
            // A more robust way would be to store only the filename in DB and construct paths.
            // Current logic: $current_image_path = "uploads/product_images/old_image.jpg"
            // Server root is __DIR__ . '/../../' from this file's location (php/includes/file_utils.php)
            $old_image_server_path = rtrim(__DIR__ . '/../../', '/') . '/' . ltrim($current_image_path, '/');

            if (file_exists($old_image_server_path)) {
                if (!unlink($old_image_server_path)) {
                    error_log("Failed to delete old product image: " . $old_image_server_path);
                }
            }
        }
        return PRODUCT_IMAGE_WEB_PATH . $unique_filename; // Return web-accessible path
    } else {
        error_log("File upload error: Failed to move uploaded file to " . $destination_path);
        return false;
    }
}
?>
