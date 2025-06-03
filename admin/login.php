<?php
session_start();

// Default page title, can be overridden if login fails etc.
$page_title = "Admin Login";
$error_message = '';

// Check if already logged in and redirect
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php"); // Redirect to the new admin dashboard
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../php/db_connect.php'; // For database connection

    $username_or_email = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $error_message = "Username/Email and Password are required.";
    } else {
        // Prepare SQL to fetch admin by username or email
        // Columns from schema: admin_id, username, email, password_hash, role
        // Renamed admin_id to id in SELECT to match expected $admin['id']
        $sql = "SELECT admin_id as id, username, email, password_hash, role FROM admins WHERE (username = ? OR email = ?) LIMIT 1";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            error_log("Login statement prepare failed: " . $mysqli->error);
            $error_message = "An internal error occurred. Please try again later.";
        } else {
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Removed 'is_active' check as it's not in the current schema
                if (password_verify($password, $admin['password_hash'])) {
                    // Password is correct, login successful
                    session_regenerate_id(true); // Regenerate session ID for security

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_logged_in_at'] = time();

                    // Update last_login timestamp
                    // Using admin_id from schema for the WHERE clause
                    $update_sql = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
                    $update_stmt = $mysqli->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bind_param("i", $admin['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    } else {
                        error_log("Failed to prepare statement for updating last_login: " . $mysqli->error);
                    }

                    header("Location: index.php"); // Redirect to a protected admin page (dashboard)
                    exit;
                } else {
                    // Invalid password
                    $error_message = "Invalid username/email or password.";
                }
            } else {
                // No user found with that username/email
                $error_message = "Invalid username/email or password.";
            }
            $stmt->close();
        }
        if (isset($mysqli)) { // Close connection if it was opened
            $mysqli->close();
        }
    }
    // If login failed, set session error to display on the form
    $_SESSION['login_error'] = $error_message;
    header("Location: login.php"); // Force a GET request to display the error from session
    exit;
}

// Display error message if set in session (from failed POST attempt or other redirects)
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Handle logout message
$logout_message = '';
if (isset($_GET['status']) && $_GET['status'] === 'loggedout') {
    $logout_message = "You have been logged out successfully.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
        .login-container h1 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #555; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        .btn-login { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-login:hover { background-color: #0056b3; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 20px; border-radius: 4px; text-align: center; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php elseif (!empty($logout_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($logout_message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>
</html>
