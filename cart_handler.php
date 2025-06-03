<?php
// cart_handler.php

// This script handles cart actions like add, update, remove, clear.
// It should not output HTML directly but redirect after processing.

// Start session if not already started (cart functions rely on session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'php/db_connect.php';         // For database connection ($mysqli)
require_once 'php/includes/product_functions.php'; // For get_product_by_id, used by cart functions
require_once 'php/includes/cart_functions.php';  // For all cart logic

// Define BASE_URL if not already defined (e.g. by header.php if this was a full page)
// Needed if we want to construct absolute redirect URLs. For relative, it's not strictly needed here.
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_path = ($base_path === '.' || $base_path === '/' || $base_path === '\\') ? '' : $base_path;
    define('BASE_URL', rtrim($protocol . $host . $base_path, '/') . '/');
}


// Determine the action
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : null; // Use $_REQUEST to catch GET or POST

// Default redirect location
// If HTTP_REFERER is set and is from the same host, use it. Otherwise, default to cart.php.
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $server_host = $_SERVER['HTTP_HOST'];
    if (strtolower($referer_host ?? '') === strtolower($server_host ?? '')) { // Ensure hostnames are actually comparable
        $redirect_url = $_SERVER['HTTP_REFERER'];
    } else {
        // Referer is from a different host or parse_url failed, redirect to a safe default.
        $redirect_url = BASE_URL . 'cart.php';
    }
} else {
    // No referer, default to cart.php for most actions, or index.php for 'add' if cart is empty.
    $redirect_url = BASE_URL . ( ($action === 'add' && get_cart_unique_item_count() === 0) ? 'index.php' : 'cart.php');
}


$product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : null;
$quantity = isset($_REQUEST['quantity']) ? (int)$_REQUEST['quantity'] : 1; // Default quantity to 1 for 'add'

$message = '';
$message_type = 'error'; // Default to error

switch ($action) {
    case 'add':
        if ($product_id && $quantity > 0) {
            $result = add_to_cart($mysqli, $product_id, $quantity);
            if ($result === true) {
                $message = "Product added to cart successfully!";
                $message_type = 'success';
                // Keep $redirect_url as referer (product page or category page)
            } else {
                $message = $result; // Error message string from function
            }
        } else {
            $message = "Invalid product ID or quantity for adding to cart.";
        }
        break;

    case 'update':
        if ($product_id && isset($_REQUEST['quantity'])) {
             if ($quantity < 0) {
                $message = "Quantity cannot be negative.";
            } else {
                $result = update_cart_item_quantity($mysqli, $product_id, $quantity);
                if ($result === true) {
                    $message = "Cart updated successfully.";
                    $message_type = 'success';
                } else {
                    $message = $result;
                }
            }
        } else {
            $message = "Invalid product ID or quantity for updating cart.";
        }
        $redirect_url = BASE_URL . 'cart.php'; // Always redirect to cart page for updates
        break;

    case 'remove':
        if ($product_id) {
            remove_from_cart($product_id);
            $message = "Product removed from cart.";
            $message_type = 'success';
        } else {
            $message = "Invalid product ID for removing from cart.";
        }
        $redirect_url = BASE_URL . 'cart.php'; // Always redirect to cart page for remove
        break;

    case 'clear':
        clear_cart();
        $message = "Cart cleared successfully.";
        $message_type = 'success';
        $redirect_url = BASE_URL . 'cart.php'; // Always redirect to cart page for clear
        break;

    default:
        $message = "Invalid cart action specified.";
        $redirect_url = BASE_URL . 'index.php'; // Redirect to a safe page
        break;
}

// Store message in session
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $message_type;

// Close the database connection if it was opened
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}

// Redirect
header("Location: " . $redirect_url);
exit;

?>
