<?php
// order_confirmation.php

// This guard will redirect to login if not authenticated.
require_once __DIR__ . '/php/includes/customer_auth_guard.php';

require_once __DIR__ . '/php/db_connect.php';
require_once __DIR__ . '/php/includes/order_functions.php';

$order_id_from_get = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$order = null;
$page_error = null;
$page_title = "Order Confirmation"; // Default title

// Ensure customer_id is available from session (set by customer_auth_guard.php)
$customer_id = $_SESSION['customer_id'];

if (empty($order_id_from_get)) {
    $page_error = "No order ID specified. Cannot display confirmation.";
    $page_title = "Order Confirmation Error";
    http_response_code(400); // Bad Request
} else {
    // Pass customer_id to get_order_details to ensure user can only see their own orders
    $order = get_order_details($mysqli, $order_id_from_get, $customer_id);

    if (!$order) {
        http_response_code(404); // Not Found or Forbidden
        $page_error = "Order (ID: " . htmlspecialchars($order_id_from_get) . ") not found, or you do not have permission to view this order.";
        $page_title = "Order Not Found";
    } else {
        // Using order_id from the fetched order data for consistency, as it's confirmed valid
        $page_title = "Order Confirmation - #" . htmlspecialchars($order['order_id']);
    }
}

require_once 'php/includes/header.php'; // BASE_URL is defined here, handles session messages
?>

<div class="container confirmation-container">
    <?php if ($page_error): ?>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <div class="message error"><?php echo htmlspecialchars($page_error); ?></div>
        <p style="text-align:center; margin-top:20px;">
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary">Continue Shopping</a>
            <?php if (is_customer_logged_in()): ?>
                 <a href="<?php echo BASE_URL; ?>account.php" class="btn btn-secondary" style="margin-left:10px;">My Account</a>
            <?php endif; ?>
        </p>
    <?php elseif ($order): ?>
        <h1>Thank You For Your Order!</h1>
        <p class="order-success-intro">
            Your order has been placed successfully.
            <?php if (!empty($order['customer_email'])): ?>
                We've sent a confirmation email to <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong> (simulation only).
            <?php endif; ?>
        </p>

        <div class="order-summary-box">
            <h2>Order Summary - ID: #<?php echo htmlspecialchars($order['order_id']); ?></h2>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['order_date']))); ?></p>
            <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['order_status']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?> (Status: <?php echo htmlspecialchars($order['payment_status']); ?>)</p>
            <p><strong>Currency:</strong> <?php echo htmlspecialchars($order['currency_code']); ?></p>


            <h3>Items Ordered (<?php echo count($order['items']); ?>):</h3>
            <?php if(!empty($order['items'])): ?>
            <table class="confirmation-items-table">
                <thead>
                    <tr>
                        <th colspan="2">Product</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td class="product-image-cell">
                            <?php
                            $item_image_url = BASE_URL . 'images/placeholder_product.jpg'; // Default
                            if (!empty($item['product_image_url'])) {
                                $item_image_url = BASE_URL . htmlspecialchars($item['product_image_url']);
                            }
                            ?>
                            <img src="<?php echo $item_image_url; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="confirmation-item-image">
                        </td>
                        <td>
                            <?php echo htmlspecialchars($item['product_name']); ?><br>
                            <small>SKU: <?php echo htmlspecialchars($item['product_sku'] ?? 'N/A'); ?></small>
                        </td>
                        <td>$<?php echo htmlspecialchars(number_format((float)$item['unit_price'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format((float)$item['total_price'], 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No items were found for this order (this indicates an issue).</p>
            <?php endif; ?>
            <p class="confirmation-grand-total"><strong>Grand Total: $<?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?></strong></p>
        </div>

        <div class="address-details-confirmation">
            <div class="shipping-address-summary">
                <h3>Shipping Address</h3>
                <p><?php echo htmlspecialchars($order['shipping_full_name']); ?></p>
                <p><?php echo htmlspecialchars($order['shipping_address_line1']); ?></p>
                <?php if (!empty($order['shipping_address_line2'])): ?><p><?php echo htmlspecialchars($order['shipping_address_line2']); ?></p><?php endif; ?>
                <p><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_state_province_region']); ?> <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                <p><?php echo htmlspecialchars($order['shipping_country_code']); ?></p>
                <?php if (!empty($order['shipping_phone_number'])): ?><p>Phone: <?php echo htmlspecialchars($order['shipping_phone_number']); ?></p><?php endif; ?>
            </div>
            <div class="billing-address-summary">
                <h3>Billing Address</h3>
                 <p><?php echo htmlspecialchars($order['billing_full_name']); ?></p>
                <p><?php echo htmlspecialchars($order['billing_address_line1']); ?></p>
                <?php if (!empty($order['billing_address_line2'])): ?><p><?php echo htmlspecialchars($order['billing_address_line2']); ?></p><?php endif; ?>
                <p><?php echo htmlspecialchars($order['billing_city']); ?>, <?php echo htmlspecialchars($order['billing_state_province_region']); ?> <?php echo htmlspecialchars($order['billing_postal_code']); ?></p>
                <p><?php echo htmlspecialchars($order['billing_country_code']); ?></p>
                <?php if (!empty($order['billing_phone_number'])): ?><p>Phone: <?php echo htmlspecialchars($order['billing_phone_number']); ?></p><?php endif; ?>
            </div>
        </div>

        <div class="confirmation-actions">
            <p>Thank you for shopping with BagShop! You can review this order in your account area.</p>
            <a href="<?php echo BASE_URL; ?>account.php" class="btn btn-secondary">Go to My Account</a>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary">Continue Shopping</a>
        </div>

    <?php endif; ?>
</div>

<?php
require_once 'php/includes/footer.php';
?>
