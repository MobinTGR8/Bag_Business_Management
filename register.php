<?php
// register.php

require_once 'php/db_connect.php';
require_once 'php/includes/customer_functions.php'; // For register_customer()

$page_title = "Create Your Account";

// Initialize variables for form repopulation and error messages
$form_data = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone_number' => ''
];
$errors = [];
$success_message = ''; // This is mainly for display if not redirecting, which we are.

// This message would typically be displayed on the page redirected TO (e.g., login page)
// if (isset($_SESSION['registration_success_message'])) {
//     $success_message = $_SESSION['registration_success_message'];
//     unset($_SESSION['registration_success_message']);
// }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Repopulate form data with submitted values
    $form_data['first_name'] = trim($_POST['first_name'] ?? '');
    $form_data['last_name'] = trim($_POST['last_name'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['phone_number'] = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($form_data['first_name'])) { $errors['first_name'] = "First name is required."; }
    if (empty($form_data['last_name'])) { $errors['last_name'] = "Last name is required."; }
    if (empty($form_data['email'])) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters.";
    }
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // Optional phone number validation (example: basic numeric and common characters)
    if (!empty($form_data['phone_number']) && !preg_match('/^[0-9\s\-\+\(\)]*$/', $form_data['phone_number'])) {
        $errors['phone_number'] = "Invalid phone number format. Use numbers and basic phone characters like +, -, (, ).";
    }


    if (empty($errors)) {
        $registration_data = [
            'first_name' => $form_data['first_name'],
            'last_name' => $form_data['last_name'],
            'email' => $form_data['email'],
            'password' => $password,
            'phone_number' => $form_data['phone_number']
        ];

        $result = register_customer($mysqli, $registration_data);

        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'] . " Please log in."; // Use a generic session message key
            $_SESSION['message_type'] = 'success'; // For header.php to display
            // Redirect to login page (customer_login.php will be created next)
            // Ensure BASE_URL is defined (should be by header.php, but header.php is not included yet at this point of execution)
            if (!defined('BASE_URL')) { // Define if not already set (e.g. if header.php not included before this logic)
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $base_path_direct = dirname($_SERVER['SCRIPT_NAME']);
                $base_path_direct = ($base_path_direct === '.' || $base_path_direct === '/' || $base_path_direct === '\\') ? '' : $base_path_direct;
                define('BASE_URL', rtrim($protocol . $host . $base_path_direct, '/') . '/');
            }
            header("Location: " . BASE_URL . "customer_login.php");
            exit;
        } else {
            $errors['general'] = $result['message'];
        }
    }
}

require_once 'php/includes/header.php'; // Includes BASE_URL and session_start()
?>

<div class="container auth-container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php
    // This general error will show if $errors['general'] was set from register_customer()
    if (!empty($errors['general'])):
    ?>
        <div class="message error"><?php echo htmlspecialchars($errors['general']); ?></div>
    <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>register.php" method="POST" class="auth-form" novalidate>
        <div class="form-group <?php echo isset($errors['first_name']) ? 'has-error' : ''; ?>">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name']); ?>" required>
            <?php if (isset($errors['first_name'])): ?><span class="error-text"><?php echo $errors['first_name']; ?></span><?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($errors['last_name']) ? 'has-error' : ''; ?>">
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name']); ?>" required>
            <?php if (isset($errors['last_name'])): ?><span class="error-text"><?php echo $errors['last_name']; ?></span><?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
            <?php if (isset($errors['email'])): ?><span class="error-text"><?php echo $errors['email']; ?></span><?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($errors['phone_number']) ? 'has-error' : ''; ?>">
            <label for="phone_number">Phone Number (Optional):</label>
            <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number']); ?>" placeholder="e.g., +1-555-123-4567">
            <?php if (isset($errors['phone_number'])): ?><span class="error-text"><?php echo $errors['phone_number']; ?></span><?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required minlength="8">
            <?php if (isset($errors['password'])): ?><span class="error-text"><?php echo $errors['password']; ?></span><?php endif; ?>
        </div>

        <div class="form-group <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <?php if (isset($errors['confirm_password'])): ?><span class="error-text"><?php echo $errors['confirm_password']; ?></span><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <p class="auth-form-footer">Already have an account? <a href="<?php echo BASE_URL; ?>customer_login.php">Log In</a></p>
</div>

<?php
require_once 'php/includes/footer.php';
?>
