<?php
// account.php (Customer Account Dashboard)

// customer_auth_guard.php handles session_start() and login check.
// It will redirect to customer_login.php if not logged in.
require_once __DIR__ . '/php/includes/customer_auth_guard.php';

// db_connect.php and customer_functions.php are included by customer_auth_guard.php
// or by header.php later, but if we need $mysqli for specific queries here, ensure it's available.
// For now, most data comes from session.
// If we needed to fetch fresh data, e.g., get_customer_details($mysqli, $_SESSION['customer_id']);
// then require_once __DIR__ . '/php/db_connect.php'; would be needed here if not already done.

$page_title = "My Account";

// Retrieve customer's first name from session, default to 'Valued Customer'
$customer_name = isset($_SESSION['customer_first_name']) ? htmlspecialchars($_SESSION['customer_first_name']) : 'Valued Customer';
$customer_email = isset($_SESSION['customer_email']) ? htmlspecialchars($_SESSION['customer_email']) : 'N/A';


require_once 'php/includes/header.php'; // Includes BASE_URL, session messages display
?>

<div class="container account-container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>
    <p class="welcome-message">Welcome back, <strong><?php echo $customer_name; ?></strong>!</p>

    <div class="account-sections">
        <section class="account-section profile-summary">
            <h2>Profile Summary</h2>
            <p><strong>Name:</strong> <?php echo $customer_name . (isset($_SESSION['customer_last_name']) ? ' ' . htmlspecialchars($_SESSION['customer_last_name']) : ''); ?></p>
            <p><strong>Email:</strong> <?php echo $customer_email; ?></p>
            <?php
            // Example: Fetch more details if needed
            // $customer_details = get_customer_details($mysqli, $_SESSION['customer_id']);
            // if($customer_details && !empty($customer_details['phone_number'])) {
            //    echo "<p><strong>Phone:</strong> " . htmlspecialchars($customer_details['phone_number']) . "</p>";
            // }
            ?>
        </section>

        <section class="account-section">
            <h2>Order History</h2>
            <p>You have no recent orders. <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-sm btn-info" style="text-decoration:none;">Start Shopping!</a></p>
            <!-- Placeholder: Future content will list orders -->
            <!-- <a href="<?php echo BASE_URL; ?>order_history.php" class="btn btn-secondary">View Full Order History</a> -->
        </section>

        <section class="account-section">
            <h2>Account Management</h2>
            <ul class="management-links">
                <li><a href="#">Edit Profile Details</a> <span class="placeholder-tag">(Coming Soon)</span></li>
                <li><a href="#">Manage Shipping Addresses</a> <span class="placeholder-tag">(Coming Soon)</span></li>
                <li><a href="#">Change Password</a> <span class="placeholder-tag">(Coming Soon)</span></li>
            </ul>
        </section>

        <section class="account-section quick-links-section">
            <h2>Quick Links</h2>
            <ul class="management-links">
                <li><a href="<?php echo BASE_URL; ?>index.php">Continue Shopping</a></li>
                <li><a href="<?php echo BASE_URL; ?>customer_logout.php" class="text-danger">Logout</a></li>
            </ul>
        </section>
    </div>
</div>

<?php
require_once 'php/includes/footer.php';
?>
