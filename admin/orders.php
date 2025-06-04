<?php
// admin/orders.php
require_once __DIR__ . '/../php/includes/admin_auth_check.php'; // Protect this page
require_once __DIR__ . '/../php/db_connect.php';
require_once __DIR__ . '/../php/includes/order_functions.php';

$page_title = "Manage Orders";

// Define allowed order statuses from schema (orders.order_status ENUM)
// Make sure this matches the ENUM definition in your schema.sql
$allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled', 'Refunded', 'Failed'];

// Filtering options
$filter_status = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses) ? $_GET['status'] : '';

// Pagination options
$items_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$options = [
    'status' => $filter_status,
    'page' => $current_page,
    'limit' => $items_per_page
];

$result = get_all_orders_admin($mysqli, $options);
$orders = $result['orders'];
$total_records = $result['total_records'];
$total_pages = ceil($total_records / $items_per_page);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="../css/style.css"> <!-- General site styles -->
    <!-- Embedded styles removed as they should be in css/style.css -->
</head>
<body>
    <header class="admin-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="logout.php" class="logout-link">Logout</a>
    </header>

    <div class="admin-container">
        <p><a href="index.php" class="nav-link">&laquo; Back to Admin Dashboard</a></p>
        <h2><?php echo htmlspecialchars($page_title); ?></h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo htmlspecialchars($_SESSION['message_type'] ?? 'info'); ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <form action="orders.php" method="GET" class="filter-form">
            <div>
                <label for="status">Filter by Status:</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($allowed_statuses as $status_val): ?>
                        <option value="<?php echo htmlspecialchars($status_val); ?>" <?php echo ($filter_status == $status_val) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status_val); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Filter</button>
            <?php if ($filter_status): ?>
                <a href="orders.php" class="clear-filter-button button-link">Clear Filter</a>
            <?php endif; ?>
        </form>

        <div style="overflow-x:auto;"> <!-- For responsive tables -->
        <?php if (!empty($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): // $order keys match DB columns + customer_... names ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars(($order['customer_first_name'] ?? '') . ' ' . ($order['customer_last_name'] ?? '')); ?><br>
                                <small><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(date("M j, Y, g:i a", strtotime($order['order_date']))); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars(strtolower($order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                            <td class="action-links">
                                <a href="order_detail_admin.php?order_id=<?php echo $order['order_id']; ?>" class="button-link view-details-link">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>&status=<?php echo urlencode($filter_status); ?>">Previous</a>
                    <?php else: ?>
                        <span class="disabled">Previous</span>
                    <?php endif; ?>

                    <?php for ($i = 1; \$i <= $total_pages; \$i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current-page"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>&status=<?php echo urlencode($filter_status); ?>">Next</a>
                    <?php else: ?>
                        <span class="disabled">Next</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p>No orders found<?php echo $filter_status ? ' matching the status: "' . htmlspecialchars($filter_status) . '"' : ''; ?>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
