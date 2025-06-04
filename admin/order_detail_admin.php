<?php
// admin/order_detail_admin.php
require_once __DIR__ . '/../php/includes/admin_auth_check.php'; // Protect this page
require_once __DIR__ . '/../php/db_connect.php';
require_once __DIR__ . '/../php/includes/order_functions.php';

$page_title = "Order Details";
$order_id_from_get = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$order = null;
$page_error = null;

// Define allowed order statuses for the dropdown - should match ENUM in DB and order_functions.php
$allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled', 'Refunded', 'Failed'];

if (empty($order_id_from_get)) {
    $page_error = "No order ID specified.";
    http_response_code(400);
} else {
    $order = get_order_details($mysqli, $order_id_from_get, null); // null for customer_id = admin view
    if (!\$order) {
        $page_error = "Order (ID: " . htmlspecialchars($order_id_from_get) . ") not found.";
        http_response_code(404);
    } else {
        // Use order_id from the fetched order data for consistency
        $page_title = "Order Details - #" . htmlspecialchars($order['order_id']);
    }
}

// Handle status update POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $posted_order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $new_status = isset($_POST['order_status']) ? trim($_POST['order_status']) : '';

    // Validate that the order_id from POST matches the one in the URL (which $order is based on)
    // And that the order actually exists (already checked by initial page load for $order)
    if ($posted_order_id && $posted_order_id == $order_id_from_get && $order) { // Use $order_id_from_get for consistency
        // Validate $new_status against $allowed_statuses (already defined in the file)
        if (in_array($new_status, $allowed_statuses)) {
            if (update_order_status($mysqli, $posted_order_id, $new_status)) {
                $_SESSION['message'] = "Order status updated successfully to '" . htmlspecialchars($new_status) . "'.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to update order status. Please try again.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Invalid order status selected: " . htmlspecialchars($new_status);
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Invalid order ID or order data mismatch for status update.";
        $_SESSION['message_type'] = "error";
    }

    // Redirect back to this same page to show updated status and message (PRG pattern)
    header("Location: order_detail_admin.php?order_id=" . $order_id_from_get); // Use $order_id_from_get for the redirect
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="../css/style.css"> <!-- General site styles -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .admin-header { background-color: #2c3e50; color: #ecf0f1; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .admin-header h1 { margin: 0; font-size: 1.6em; }
        .admin-header a.logout-link { color: #ecf0f1; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #e74c3c; transition: background-color 0.3s; }
        .admin-header a.logout-link:hover { background-color: #c0392b; }

        .admin-container { width: 95%; max-width:1100px; margin: 25px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .admin-container h2 { font-size: 1.8em; color: #333; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom:10px;}

        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 5px; text-align:center; }
        /* .success, .error, .info classes are in global style.css */

        .order-detail-grid { display: grid; grid-template-columns: 1fr; gap: 25px; }
        @media (min-width: 992px) { .order-detail-grid { grid-template-columns: 2.5fr 1fr; } }

        .order-main-details, .order-actions-summary { padding:20px; background-color:#fdfdfd; border-radius:6px; border:1px solid #e9e9e9;}
        .order-main-details h3, .order-actions-summary h3 { font-size: 1.3em; margin-top:0; margin-bottom:15px; padding-bottom:10px; border-bottom:1px dotted #ccc; color:#007bff;}

        .detail-section { margin-bottom: 25px; }
        .detail-section p, .detail-section ul li { margin-bottom: 6px; color: #555; font-size:0.95em; line-height:1.6; }
        .detail-section ul { list-style: none; padding-left: 0; }
        .detail-section p strong { color: #333; margin-right:5px; }

        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th, .items-table td { padding: 10px; border: 1px solid #ddd; text-align: left; vertical-align:middle; font-size:0.9em; }
        .items-table th { background-color: #f0f2f5; font-weight:600; color:#444; }
        .item-image-admin { width: 50px; height: 50px; object-fit: cover; border-radius: 3px; border:1px solid #eee; }
        .grand-total-admin { text-align: right; font-size: 1.25em; font-weight: bold; margin-top: 15px; color: #007bff; }

        .status-update-form { margin-top: 15px; padding:15px; background-color:#f0f8ff; border-radius:5px; }
        .status-update-form .form-group { margin-bottom:15px; }
        .status-update-form label { font-weight: bold; margin-right: 10px; display:block; margin-bottom:5px; }
        .status-update-form select, .status-update-form button { padding: 9px 14px; border-radius: 4px; border: 1px solid #ccc; margin-right:10px; font-size:0.95em; }
        .status-update-form button { background-color: #007bff; color:white; cursor:pointer; border-color:#007bff; }
        .status-update-form button:hover { background-color: #0056b3; border-color:#0056b3; }

        .address-display p { margin: 3px 0; }
        .nav-link { margin-right: 10px; text-decoration: none; color: #007bff; font-weight:500; }
        .nav-link:hover { text-decoration: underline; }

        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: bold; color: #fff; text-transform: capitalize; display:inline-block; letter-spacing:0.5px; }
        .status-pending { background-color: #ffc107; color: #333; }
        .status-processing { background-color: #17a2b8; }
        .status-shipped { background-color: #007bff; }
        .status-completed { background-color: #28a745; }
        .status-cancelled { background-color: #dc3545; }
        .status-refunded { background-color: #6c757d; }
        .status-failed { background-color: #343a40; }

    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Admin Panel</h1>
        <a href="logout.php" class="logout-link">Logout</a>
    </header>

    <div class="admin-container">
        <p><a href="orders.php" class="nav-link">&laquo; Back to Order List</a></p>
        <h2><?php echo htmlspecialchars($page_title); ?></h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo htmlspecialchars($_SESSION['message_type'] ?? 'info'); ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <?php if ($page_error): ?>
            <div class="message error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php elseif ($order): ?>
            <div class="order-detail-grid">
                <section class="order-main-details">
                    <div class="detail-section customer-details">
                        <h3>Customer Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars(($order['customer_first_name'] ?? '') . ' ' . ($order['customer_last_name'] ?? '')); ?></p>
                        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($order['customer_email'] ?? ''); ?>"><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></a></p>
                        <p><strong>Customer ID:</strong> <?php echo htmlspecialchars($order['customer_id']); ?></p>
                    </div>

                    <div class="detail-section order-summary">
                        <h3>Order Summary</h3>
                        <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['order_date']))); ?></p>
                        <p><strong>Current Status:</strong> <span class="status-badge status-<?php echo htmlspecialchars(strtolower($order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></p>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
                        <p><strong>Currency:</strong> <?php echo htmlspecialchars($order['currency_code']); ?></p>
                        <p><strong>Grand Total:</strong> $<?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?></p>
                    </div>

                    <div class="detail-section items-ordered">
                        <h3>Items Ordered (<?php echo count($order['items']); ?>)</h3>
                        <?php if(!empty($order['items'])): ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Unit Price</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $item_image_admin_url = '../images/placeholder_product.jpg'; // Default
                                        if (!empty($item['product_image_url'])) {
                                            $item_image_admin_url = '../' . htmlspecialchars($item['product_image_url']);
                                        }
                                        ?>
                                        <img src="<?php echo $item_image_admin_url; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image-admin">
                                    </td>
                                    <td>
                                        <a href="../product_detail.php?id=<?php echo $item['product_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['product_sku'] ?? 'N/A'); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format((float)$item['price_per_unit'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format((float)$item['total_price'], 2)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="grand-total-admin"><strong>Order Total: $<?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?></strong></p>
                        <?php else: ?>
                            <p>No items found for this order.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <aside class="order-actions-summary">
                    <h2>Update Order Status</h2>
                    <form action="order_detail_admin.php?order_id=<?php echo $order['order_id']; ?>" method="POST" class="status-update-form">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <div class="form-group">
                            <label for="order_status">New Status:</label>
                            <select name="order_status" id="order_status">
                                <?php foreach ($allowed_statuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($order['order_status'] == $status) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </form>

                    <hr style="margin: 25px 0;">

                    <div class="detail-section shipping-address">
                        <h3>Shipping Address</h3>
                        <div class="address-display">
                            <p><?php echo htmlspecialchars($order['shipping_full_name']); ?></p>
                            <p><?php echo htmlspecialchars($order['shipping_address_line1']); ?></p>
                            <?php if (!empty($order['shipping_address_line2'])): ?><p><?php echo htmlspecialchars($order['shipping_address_line2']); ?></p><?php endif; ?>
                            <p><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_state_province_region']); ?> <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                            <p><?php echo htmlspecialchars($order['shipping_country_code']); ?></p>
                            <?php if (!empty($order['shipping_phone_number'])): ?><p>Phone: <?php echo htmlspecialchars($order['shipping_phone_number']); ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-section billing-address">
                        <h3>Billing Address</h3>
                        <div class="address-display">
                            <p><?php echo htmlspecialchars($order['billing_full_name']); ?></p>
                            <p><?php echo htmlspecialchars($order['billing_address_line1']); ?></p>
                            <?php if (!empty($order['billing_address_line2'])): ?><p><?php echo htmlspecialchars($order['billing_address_line2']); ?></p><?php endif; ?>
                            <p><?php echo htmlspecialchars($order['billing_city']); ?>, <?php echo htmlspecialchars($order['billing_state_province_region']); ?> <?php echo htmlspecialchars($order['billing_postal_code']); ?></p>
                            <p><?php echo htmlspecialchars($order['billing_country_code']); ?></p>
                            <?php if (!empty($order['billing_phone_number'])): ?><p>Phone: <?php echo htmlspecialchars($order['billing_phone_number']); ?></p><?php endif; ?>
                        </div>
                    </div>
                </aside>
            </div> <!-- end .order-detail-grid -->
        <?php else: ?>
            <!-- This case should be covered by $page_error already if order is null and no other error message was set -->
             <?php if(empty($page_error)) echo "<div class='message info'>Could not load order details.</div>"; ?>
        <?php endif; ?>
    </div>
</body>
</html>
