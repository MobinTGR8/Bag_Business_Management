<?php
// php/includes/cart_functions.php

// Ensure session is started, as cart relies on it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Initializes the cart in the session if it doesn't exist.
 */
function init_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = []; // Cart stores product_id => quantity
    }
}
init_cart(); // Initialize cart when this file is included.

/**
 * Adds a product to the cart or updates its quantity.
 *
 * @param mysqli $mysqli DB connection.
 * @param int $product_id The ID of the product.
 * @param int $quantity The quantity to add.
 * @return bool|string True on success, error message string on failure.
 */
function add_to_cart(mysqli $mysqli, int $product_id, int $quantity = 1): bool|string {
    if ($quantity <= 0) {
        return "Quantity must be a positive number.";
    }

    // product_functions.php should be included by the calling script for get_product_by_id
    if (!function_exists('get_product_by_id')) {
        error_log("FATAL ERROR: get_product_by_id function not found. Ensure product_functions.php is included.");
        return "A system error occurred. Please try again later.";
    }
    $product = get_product_by_id($mysqli, $product_id);

    if (!$product) {
        return "Product not found in the database.";
    }
    if (!$product['is_active']) { // Assumes 'is_active' column exists from updated schema
        return "This product (".htmlspecialchars($product['product_name']).") is currently unavailable.";
    }

    $current_cart_quantity = $_SESSION['cart'][$product_id] ?? 0;
    $new_cart_quantity = $current_cart_quantity + $quantity;

    if ($product['stock_quantity'] < $new_cart_quantity) {
        return "Cannot add more than " . $product['stock_quantity'] . " units of " . htmlspecialchars($product['product_name']) . " to cart (you already have " . $current_cart_quantity . " units). Maximum available stock reached.";
    }

    $_SESSION['cart'][$product_id] = $new_cart_quantity;
    return true;
}

/**
 * Updates the quantity of an item in the cart.
 *
 * @param mysqli $mysqli DB connection (needed for stock check).
 * @param int $product_id The ID of the product.
 * @param int $quantity The new quantity. If 0, removes item.
 * @return bool|string True on success, error message string on failure.
 */
function update_cart_item_quantity(mysqli $mysqli, int $product_id, int $quantity): bool|string {
    if (!isset($_SESSION['cart'][$product_id])) {
        return "Product not found in your cart.";
    }
    if ($quantity < 0) {
        return "Quantity cannot be negative.";
    }
    if ($quantity == 0) {
        remove_from_cart($product_id); // This function is defined below
        return true;
    }

    if (!function_exists('get_product_by_id')) {
        error_log("FATAL ERROR: get_product_by_id function not found. Ensure product_functions.php is included.");
        return "A system error occurred. Please try again later.";
    }
    $product = get_product_by_id($mysqli, $product_id);

    if (!$product) {
        remove_from_cart($product_id);
        return "Product data not found in database, item removed from cart.";
    }
    if (!$product['is_active']) {
        remove_from_cart($product_id);
        return "This product (".htmlspecialchars($product['product_name']).") is currently unavailable and has been removed from your cart.";
    }

    if ($product['stock_quantity'] < $quantity) {
        // Optionally, set to max available stock instead of erroring out.
        // For now, error out as per original logic.
        return "Only " . $product['stock_quantity'] . " units of " . htmlspecialchars($product['product_name']) . " are available in stock.";
    }

    $_SESSION['cart'][$product_id] = $quantity;
    return true;
}

/**
 * Removes an item from the cart.
 *
 * @param int $product_id The ID of the product to remove.
 */
function remove_from_cart(int $product_id): void {
    unset($_SESSION['cart'][$product_id]);
}

/**
 * Retrieves all items from the cart and enriches them with product details from DB.
 *
 * @param mysqli $mysqli DB connection.
 * @return array Array of cart items, each item being an associative array with product details and cart quantity.
 */
function get_cart_items(mysqli $mysqli): array {
    if (empty($_SESSION['cart'])) {
        return [];
    }

    $cart_items = [];
    $product_ids_in_cart = array_keys($_SESSION['cart']);

    if (empty($product_ids_in_cart)) return [];

    // Check if get_product_by_id exists
    if (!function_exists('get_product_by_id')) {
        error_log("FATAL ERROR: get_product_by_id function not found in get_cart_items. Ensure product_functions.php is included.");
        // Potentially clear cart or return error state if products can't be verified
        // For now, return empty to avoid further errors, but this is critical.
        clear_cart(); // Clear cart if we can't get product details, as prices might be wrong.
        return [];
    }

    // Using get_product_by_id in a loop. For many items, an IN clause would be better.
    foreach ($_SESSION['cart'] as $product_id => $quantity_in_cart) {
        $product_details = get_product_by_id($mysqli, (int)$product_id);
        if ($product_details) {
            // Ensure keys match what product_detail.php and other places might expect, or what schema provides.
            // get_product_by_id returns keys like 'product_id', 'product_name', etc.
            $cart_items[] = [
                'product_id'        => $product_details['product_id'],
                'product_name'      => $product_details['product_name'],
                'price'             => (float)$product_details['price'],
                'quantity'          => (int)$quantity_in_cart,
                'line_total'        => (float)$product_details['price'] * (int)$quantity_in_cart,
                'image_url'         => $product_details['image_url'] ?? null,
                'product_sku'       => $product_details['product_sku'] ?? null,
                'stock_quantity'    => $product_details['stock_quantity'], // For display or warnings on cart page
                'category_name'     => $product_details['category_name'] ?? null, // from JOIN in get_product_by_id
                'category_slug'     => $product_details['category_slug'] ?? null  // from JOIN in get_product_by_id
            ];
        } else {
            // Product might have been removed from DB or deactivated while in cart
            remove_from_cart((int)$product_id);
            // Optionally, add a system message here for the user if desired.
        }
    }
    return $cart_items;
}


/**
 * Calculates the subtotal of all items in the cart.
 *
 * @param array $cart_items Array of cart items from get_cart_items().
 * @return float The cart subtotal.
 */
function calculate_cart_subtotal(array $cart_items): float {
    $subtotal = 0.0;
    foreach ($cart_items as $item) {
        $subtotal += $item['line_total'];
    }
    return $subtotal;
}

/**
 * Gets the total number of individual items in the cart (sum of quantities).
 *
 * @return int The total number of items.
 */
function get_cart_total_item_quantity(): int {
    $total_items = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $quantity) {
            $total_items += (int)$quantity;
        }
    }
    return $total_items;
}

/**
 * Gets the number of unique product lines in the cart.
 * @return int
 */
function get_cart_unique_item_count(): int {
    return count($_SESSION['cart'] ?? []);
}


/**
 * Clears all items from the cart.
 */
function clear_cart(): void {
    $_SESSION['cart'] = [];
    // Optionally, unset other cart-related session variables like discount codes, etc.
}

?>
