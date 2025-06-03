<?php
// customer_login.php

require_once 'php/db_connect.php';
require_once 'php/includes/customer_functions.php'; // For login_customer() and is_customer_logged_in()

// BASE_URL should be defined in header.php, but if logic here needs it before header,
// we might need to define it or ensure header is included very early.
// For redirects, it's safer if BASE_URL is available.
// Let's include header.php's BASE_URL logic if not already defined.
if (session_status() === PHP_SESSION_NONE) { // Ensure session is active for is_customer_logged_in and messages
    session_start();
}
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_path_direct = dirname($_SERVER['SCRIPT_NAME']);
    $base_path_direct = ($base_path_direct === '.' || $base_path_direct === '/' || $base_path_direct === '\\') ? '' : $base_path_direct;
    define('BASE_URL', rtrim($protocol . $host . $base_path_direct, '/') . '/');
}


$page_title = "Customer Login";

// If already logged in, redirect to account page or homepage
if (is_customer_logged_in()) {
    header("Location: " . BASE_URL . "account.php"); // Assuming account.php is the destination
    exit;
}

$email_input = ''; // For form repopulation
$error_message = ''; // For login errors
$success_message = ''; // For messages from other pages (e.g., registration)

// Check for success messages from session (e.g., after registration by register.php)
if (isset($_SESSION['registration_success_message'])) {
    $success_message = $_SESSION['registration_success_message'];
    unset($_SESSION['registration_success_message']);
}
// Note: header.php will also display general $_SESSION['message'] if set.
// This $success_message is for the specific flow from register.php.


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = trim($_POST['email'] ?? ''); // Use $email_input for form value
    $password = $_POST['password'] ?? '';

    if (empty($email_input) || empty($password)) {
        $error_message = "Both email and password are required.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        $result = login_customer($mysqli, $email_input, $password);

        if ($result['success']) {
            // login_customer() already sets session variables.
            // Determine redirect target (e.g., intended page or account page)
            $redirect_target = $_SESSION['redirect_to_after_login'] ?? (BASE_URL . "account.php");
            unset($_SESSION['redirect_to_after_login']); // Clear it after use

            header("Location: " . $redirect_target);
            exit;
        } else {
            $error_message = $result['message']; // Error message from login_customer()
        }
    }
}

require_once 'php/includes/header.php';
?>

<div class="container auth-container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php
    // Display success message from registration if present
    if (!empty($success_message)):
    ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php
    // Display login-specific error message if present
    // Note: General session messages are handled by header.php
    elseif (!empty($error_message)):
    ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>customer_login.php" method="POST" class="auth-form" novalidate>
        <div class="form-group">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_input); ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <!-- Optional: Remember Me & Forgot Password -->
        <!--
        <div class="form-group form-group-options" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9em;">
            <label class="checkbox-label" style="font-weight:normal;">
                <input type="checkbox" name="remember_me" style="width:auto; margin-right:5px;"> Remember Me
            </label>
            <a href="<?php echo BASE_URL; ?>forgot_password.php" class="forgot-password-link">Forgot Password?</a>
        </div>
        -->

        <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>

    <p class="auth-form-footer">Don't have an account? <a href="<?php echo BASE_URL; ?>register.php">Sign Up</a></p>
</div>

<?php
require_once 'php/includes/footer.php';
?>
