<?php
// checkout.php

require_once __DIR__ . '/php/includes/customer_auth_guard.php';
require_once __DIR__ . '/php/db_connect.php';
require_once __DIR__ . '/php/includes/product_functions.php';
require_once __DIR__ . '/php/includes/cart_functions.php';
require_once __DIR__ . '/php/includes/order_functions.php'; // Now needed for create_order

// Define BASE_URL if not already defined (e.g. by header.php or customer_auth_guard.php)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_path_direct = dirname($_SERVER['SCRIPT_NAME']);
    $base_path_direct = ($base_path_direct === '.' || $base_path_direct === '/' || $base_path_direct === '\\') ? '' : $base_path_direct;
    define('BASE_URL', rtrim($protocol . $host . $base_path_direct, '/') . '/');
}

$cart_items = get_cart_items($mysqli); // Get cart items early for both display and processing

// If cart is empty, redirect to cart page, unless this is the POST request that just cleared the cart.
// This check is now more robust. If create_order succeeds, cart is cleared, then redirect happens.
// If create_order fails, cart is not cleared, and we re-render the form.
// If user navigates to checkout.php with an empty cart (GET request), then redirect.
if (empty($cart_items) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $_SESSION['message'] = "Your cart is empty. Please add items before proceeding to checkout.";
    $_SESSION['message_type'] = "info";
    header("Location: " . BASE_URL . "cart.php");
    exit;
}

$page_title = "Checkout";
$checkout_errors = [];

// Initialize form data arrays
$shipping_full_name_default = '';
if (isset($_SESSION['customer_first_name'])) $shipping_full_name_default .= $_SESSION['customer_first_name'];
if (isset($_SESSION['customer_last_name'])) $shipping_full_name_default .= ($shipping_full_name_default ? ' ' : '') . $_SESSION['customer_last_name'];

$shipping_address = [
    'full_name' => $shipping_full_name_default, 'address_line1' => '', 'address_line2' => '',
    'city' => '', 'state_province_region' => '', 'postal_code' => '',
    'country_code' => 'US', 'phone_number' => ''
];
$billing_address = $shipping_address;
$billing_same_as_shipping = true;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Repopulate from POST and Trim
    $shipping_address['full_name'] = trim($_POST['shipping_full_name'] ?? $shipping_address['full_name']);
    $shipping_address['address_line1'] = trim($_POST['shipping_address_line1'] ?? '');
    $shipping_address['address_line2'] = trim($_POST['shipping_address_line2'] ?? '');
    $shipping_address['city'] = trim($_POST['shipping_city'] ?? '');
    $shipping_address['state_province_region'] = trim($_POST['shipping_state_province_region'] ?? '');
    $shipping_address['postal_code'] = trim($_POST['shipping_postal_code'] ?? '');
    $shipping_address['country_code'] = strtoupper(trim($_POST['shipping_country_code'] ?? 'US'));
    $shipping_address['phone_number'] = trim($_POST['shipping_phone_number'] ?? '');

    $billing_same_as_shipping = isset($_POST['billing_same_as_shipping']);

    if ($billing_same_as_shipping) {
        $billing_address = $shipping_address;
    } else {
        $billing_address['full_name'] = trim($_POST['billing_full_name'] ?? '');
        $billing_address['address_line1'] = trim($_POST['billing_address_line1'] ?? '');
        $billing_address['address_line2'] = trim($_POST['billing_address_line2'] ?? '');
        $billing_address['city'] = trim($_POST['billing_city'] ?? '');
        $billing_address['state_province_region'] = trim($_POST['billing_state_province_region'] ?? '');
        $billing_address['postal_code'] = trim($_POST['billing_postal_code'] ?? '');
        $billing_address['country_code'] = strtoupper(trim($_POST['billing_country_code'] ?? 'US'));
        $billing_address['phone_number'] = trim($_POST['billing_phone_number'] ?? '');
    }

    // Server-side Validation for required fields
    if (empty($shipping_address['full_name'])) { $checkout_errors['shipping_full_name'] = "Shipping full name is required."; }
    if (empty($shipping_address['address_line1'])) { $checkout_errors['shipping_address_line1'] = "Shipping address line 1 is required."; }
    if (empty($shipping_address['city'])) { $checkout_errors['shipping_city'] = "Shipping city is required."; }
    if (empty($shipping_address['state_province_region'])) { $checkout_errors['shipping_state_province_region'] = "Shipping state/province/region is required."; }
    if (empty($shipping_address['postal_code'])) { $checkout_errors['shipping_postal_code'] = "Shipping postal code is required."; }
    if (empty($shipping_address['country_code']) || strlen($shipping_address['country_code']) !== 2) { $checkout_errors['shipping_country_code'] = "Valid 2-letter shipping country code is required."; }

    if (!$billing_same_as_shipping) {
        if (empty($billing_address['full_name'])) { $checkout_errors['billing_full_name'] = "Billing full name is required."; }
        if (empty($billing_address['address_line1'])) { $checkout_errors['billing_address_line1'] = "Billing address line 1 is required."; }
        if (empty($billing_address['city'])) { $checkout_errors['billing_city'] = "Billing city is required."; }
        if (empty($billing_address['state_province_region'])) { $checkout_errors['billing_state_province_region'] = "Billing state/province/region is required."; }
        if (empty($billing_address['postal_code'])) { $checkout_errors['billing_postal_code'] = "Billing postal code is required."; }
        if (empty($billing_address['country_code']) || strlen($billing_address['country_code']) !== 2) { $checkout_errors['billing_country_code'] = "Valid 2-letter billing country code is required."; }
    }

    if (empty($checkout_errors)) {
        // Re-fetch cart items to ensure they are fresh before creating order (already fetched above, this is a good check if time passed)
        $current_cart_items = get_cart_items($mysqli);
        if (empty($current_cart_items)) {
            $_SESSION['message'] = "Your cart became empty during checkout, or items are no longer available. Please review your cart and try again.";
            $_SESSION['message_type'] = "error";
            header("Location: " . BASE_URL . "cart.php");
            exit;
        }

        $customer_id = $_SESSION['customer_id'];

        $order_id = create_order(
            $mysqli,
            $customer_id,
            $current_cart_items,
            $shipping_address,
            $billing_address,
            "Offline Payment (Simulated)" // Payment method placeholder
        );

        if ($order_id) {
            clear_cart();
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            $_SESSION['message'] = "Thank you! Your order has been placed successfully. Your Order ID is: #" . $order_id;
            $_SESSION['message_type'] = "success";
            header("Location: " . BASE_URL . "order_confirmation.php?order_id=" . $order_id);
            exit;
        } else {
            $checkout_errors['general'] = "Failed to place your order due to an unexpected error (e.g., stock levels changed, or a server issue). Please review your cart or try again. If the problem persists, contact support.";
        }
    }
    // If validation errors, the script will continue and re-render the form.
}

// Recalculate cart totals in case cart was modified by another tab/process,
// or if this is a GET request after an empty cart redirect attempt that failed before header()
$cart_items = get_cart_items($mysqli); // Re-fetch cart items for display
$cart_subtotal = calculate_cart_subtotal($cart_items);

require_once 'php/includes/header.php';
?>

<div class="container checkout-container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if (!empty($checkout_errors['general'])): ?>
        <div class="message error"><?php echo htmlspecialchars($checkout_errors['general']); ?></div>
    <?php endif; ?>

    <div class="checkout-layout">
        <section class="checkout-form-section">
            <form action="<?php echo BASE_URL; ?>checkout.php" method="POST" id="checkout-form" novalidate>
                <h2>Shipping Address</h2>
                <div class="form-group <?php echo isset($checkout_errors['shipping_full_name']) ? 'has-error' : ''; ?>">
                    <label for="shipping_full_name">Full Name:</label>
                    <input type="text" id="shipping_full_name" name="shipping_full_name" value="<?php echo htmlspecialchars($shipping_address['full_name']); ?>" required>
                    <?php if (isset($checkout_errors['shipping_full_name'])): ?><span class="error-text"><?php echo $checkout_errors['shipping_full_name']; ?></span><?php endif; ?>
                </div>
                <div class="form-group <?php echo isset($checkout_errors['shipping_address_line1']) ? 'has-error' : ''; ?>">
                    <label for="shipping_address_line1">Address Line 1:</label>
                    <input type="text" id="shipping_address_line1" name="shipping_address_line1" value="<?php echo htmlspecialchars($shipping_address['address_line1']); ?>" required>
                    <?php if (isset($checkout_errors['shipping_address_line1'])): ?><span class="error-text"><?php echo $checkout_errors['shipping_address_line1']; ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="shipping_address_line2">Address Line 2 (Optional):</label>
                    <input type="text" id="shipping_address_line2" name="shipping_address_line2" value="<?php echo htmlspecialchars($shipping_address['address_line2']); ?>">
                </div>
                <div class="form-group <?php echo isset($checkout_errors['shipping_city']) ? 'has-error' : ''; ?>">
                    <label for="shipping_city">City:</label>
                    <input type="text" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($shipping_address['city']); ?>" required>
                    <?php if (isset($checkout_errors['shipping_city'])): ?><span class="error-text"><?php echo $checkout_errors['shipping_city']; ?></span><?php endif; ?>
                </div>
                <div class="form-grid-halves">
                    <div class="form-group <?php echo isset($checkout_errors['shipping_state_province_region']) ? 'has-error' : ''; ?>">
                        <label for="shipping_state_province_region">State/Province/Region:</label>
                        <input type="text" id="shipping_state_province_region" name="shipping_state_province_region" value="<?php echo htmlspecialchars($shipping_address['state_province_region']); ?>" required>
                        <?php if (isset($checkout_errors['shipping_state_province_region'])): ?><span class="error-text"><?php echo $checkout_errors['shipping_state_province_region']; ?></span><?php endif; ?>
                    </div>
                    <div class="form-group <?php echo isset($checkout_errors['shipping_postal_code']) ? 'has-error' : ''; ?>">
                        <label for="shipping_postal_code">Postal Code:</label>
                        <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="<?php echo htmlspecialchars($shipping_address['postal_code']); ?>" required>
                        <?php if (isset($checkout_errors['shipping_postal_code'])): ?><span class="error-text"><?php echo $checkout_errors['shipping_postal_code']; ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="form-group <?php echo isset($checkout_errors['shipping_country_code']) ? 'has-error' : ''; ?>">
                    <label for="shipping_country_code">Country Code (2 letters):</label>
                    <input type="text" id="shipping_country_code" name="shipping_country_code" value="<?php echo htmlspecialchars($shipping_address['country_code']); ?>" required maxlength="2" placeholder="US">
                    <?php if (isset($checkout_errors['shipping_country_code'])): ?><span class="error-text"><?php echo $checkout_errors['shipping_country_code']; ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="shipping_phone_number">Phone Number (Optional for delivery updates):</label>
                    <input type="tel" id="shipping_phone_number" name="shipping_phone_number" value="<?php echo htmlspecialchars($shipping_address['phone_number']); ?>">
                </div>

                <hr class="form-divider">

                <h2>Billing Address</h2>
                <div class="form-group">
                    <label class="checkbox-label" style="font-weight:normal; display:inline-flex; align-items:center;">
                        <input type="checkbox" id="billing_same_as_shipping" name="billing_same_as_shipping" value="1" <?php echo $billing_same_as_shipping ? 'checked' : ''; ?> style="width:auto; margin-right:8px;">
                        Billing address is the same as shipping address.
                    </label>
                </div>

                <div id="billing-address-fields" class="<?php echo $billing_same_as_shipping ? 'hidden-fields' : ''; ?>">
                    <div class="form-group <?php echo isset($checkout_errors['billing_full_name']) ? 'has-error' : ''; ?>">
                        <label for="billing_full_name">Full Name:</label>
                        <input type="text" id="billing_full_name" name="billing_full_name" value="<?php echo htmlspecialchars($billing_address['full_name']); ?>">
                        <?php if (isset($checkout_errors['billing_full_name'])): ?><span class="error-text"><?php echo $checkout_errors['billing_full_name']; ?></span><?php endif; ?>
                    </div>
                    <div class="form-group <?php echo isset($checkout_errors['billing_address_line1']) ? 'has-error' : ''; ?>">
                        <label for="billing_address_line1">Address Line 1:</label>
                        <input type="text" id="billing_address_line1" name="billing_address_line1" value="<?php echo htmlspecialchars($billing_address['address_line1']); ?>">
                        <?php if (isset($checkout_errors['billing_address_line1'])): ?><span class="error-text"><?php echo $checkout_errors['billing_address_line1']; ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="billing_address_line2">Address Line 2 (Optional):</label>
                        <input type="text" id="billing_address_line2" name="billing_address_line2" value="<?php echo htmlspecialchars($billing_address['address_line2']); ?>">
                    </div>
                    <div class="form-group <?php echo isset($checkout_errors['billing_city']) ? 'has-error' : ''; ?>">
                        <label for="billing_city">City:</label>
                        <input type="text" id="billing_city" name="billing_city" value="<?php echo htmlspecialchars($billing_address['city']); ?>">
                        <?php if (isset($checkout_errors['billing_city'])): ?><span class="error-text"><?php echo $checkout_errors['billing_city']; ?></span><?php endif; ?>
                    </div>
                    <div class="form-grid-halves">
                        <div class="form-group <?php echo isset($checkout_errors['billing_state_province_region']) ? 'has-error' : ''; ?>">
                            <label for="billing_state_province_region">State/Province/Region:</label>
                            <input type="text" id="billing_state_province_region" name="billing_state_province_region" value="<?php echo htmlspecialchars($billing_address['state_province_region']); ?>">
                            <?php if (isset($checkout_errors['billing_state_province_region'])): ?><span class="error-text"><?php echo $checkout_errors['billing_state_province_region']; ?></span><?php endif; ?>
                        </div>
                        <div class="form-group <?php echo isset($checkout_errors['billing_postal_code']) ? 'has-error' : ''; ?>">
                            <label for="billing_postal_code">Postal Code:</label>
                            <input type="text" id="billing_postal_code" name="billing_postal_code" value="<?php echo htmlspecialchars($billing_address['postal_code']); ?>">
                            <?php if (isset($checkout_errors['billing_postal_code'])): ?><span class="error-text"><?php echo $checkout_errors['billing_postal_code']; ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group <?php echo isset($checkout_errors['billing_country_code']) ? 'has-error' : ''; ?>">
                        <label for="billing_country_code">Country Code (2 letters):</label>
                        <input type="text" id="billing_country_code" name="billing_country_code" value="<?php echo htmlspecialchars($billing_address['country_code']); ?>" maxlength="2" placeholder="US">
                        <?php if (isset($checkout_errors['billing_country_code'])): ?><span class="error-text"><?php echo $checkout_errors['billing_country_code']; ?></span><?php endif; ?>
                    </div>
                     <div class="form-group">
                        <label for="billing_phone_number">Phone Number (Optional):</label>
                        <input type="tel" id="billing_phone_number" name="billing_phone_number" value="<?php echo htmlspecialchars($billing_address['phone_number']); ?>">
                    </div>
                </div>

                <hr class="form-divider">

                <h2>Payment Method</h2>
                <div class="payment-method-placeholder">
                    <p><strong>Payment Method:</strong> Offline Payment (Simulated)</p>
                    <p><small>This is a demo store. No real payment will be processed. Clicking "Place Order" will simulate order creation with the details provided.</small></p>
                </div>

                <div class="form-actions">
                    <button type="submit" name="place_order" class="btn btn-primary btn-lg btn-block">Place Order</button>
                </div>
            </form>
        </section>

        <aside class="checkout-summary-section">
            <h2>Order Summary</h2>
            <div class="order-summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <img src="<?php echo BASE_URL . htmlspecialchars($item['image_url'] ?? 'images/placeholder_product.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="summary-item-image">
                        <div class="summary-item-details">
                            <span class="summary-item-name"><?php echo htmlspecialchars($item['product_name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span class="summary-item-price">$<?php echo htmlspecialchars(number_format($item['line_total'], 2)); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="order-summary-total">
                <p>Subtotal: <span>$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></span></p>
                <p>Shipping: <span>Calculated at next step (Free for now)</span></p>
                <p class="grand-total"><strong>Total:</strong> <strong>$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></strong></p>
            </div>
        </aside>
    </div> <!-- end .checkout-layout -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('billing_same_as_shipping');
            const billingFieldsDiv = document.getElementById('billing-address-fields');
            // More specific selector for inputs within the billing address fields section
            const billingInputs = billingFieldsDiv.querySelectorAll('input[name^="billing_"]');

            function toggleBillingFieldsRequired(isRequired) {
                billingInputs.forEach(input => {
                    // Only change 'required' for inputs that are not buttons or certain hidden types.
                    // And only if they were originally required or part of a group that implies requirement.
                    if (input.type !== 'hidden' && input.type !== 'button' && input.type !== 'submit' && input.type !== 'reset') {
                        // For this form, if billing is shown, all its core fields are required.
                        if(input.name !== 'billing_address_line2' && input.name !== 'billing_phone_number'){ // These are optional
                           input.required = isRequired;
                        }
                    }
                });
            }

            function toggleBillingFieldsVisibility() {
                if (checkbox.checked) {
                    billingFieldsDiv.classList.add('hidden-fields');
                    toggleBillingFieldsRequired(false);
                } else {
                    billingFieldsDiv.classList.remove('hidden-fields');
                    toggleBillingFieldsRequired(true);
                }
            }

            if (checkbox && billingFieldsDiv) { // Check if elements exist
                checkbox.addEventListener('change', toggleBillingFieldsVisibility);
                toggleBillingFieldsVisibility(); // Initial call
            }
        });
    </script>
</div>

<?php
require_once 'php/includes/footer.php';
?>
