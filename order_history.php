<?php
// order_history.php

// This guard will redirect to login if not authenticated.
require_once __DIR__ . '/php/includes/customer_auth_guard.php';

require_once __DIR__ . '/php/db_connect.php';
require_once __DIR__ . '/php/includes/order_functions.php'; // For get_customer_orders

$page_title = "My Order History";
$customer_id = $_SESSION['customer_id']; // From customer_auth_guard

$orders = get_customer_orders($mysqli, $customer_id);

require_once 'php/includes/header.php'; // Includes BASE_URL
?>

<div class="container account-container order-history-container"> <!-- Reusing .account-container styles -->
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if (empty($orders)): ?>
        <div class="message info" style="text-align:center; padding:20px;">
            <p>You have not placed any orders yet.</p>
            <p><a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary">Start Shopping</a></p>
        </div>
    <?php else: ?>
        <div class="order-history-table-container">
            <table class="order-history-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['order_id']); // Corrected key to order_id ?></td>
                            <td><?php echo htmlspecialchars(date("F j, Y", strtotime($order['order_date']))); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>order_detail_customer.php?order_id=<?php echo $order['order_id']; // Corrected key to order_id ?>" class="btn btn-secondary btn-sm">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p style="margin-top: 30px; text-align:center;">
        <a href="<?php echo BASE_URL; ?>account.php" class="btn btn-outline-secondary">&laquo; Back to My Account</a>
    </p>
</div>

<?php
require_once 'php/includes/footer.php';
?>
