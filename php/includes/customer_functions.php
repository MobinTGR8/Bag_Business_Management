<?php
// php/includes/customer_functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Registers a new customer.
 *
 * @param mysqli $mysqli The database connection object.
 * @param array $data Associative array containing 'first_name', 'last_name', 'email', 'password'.
 *                    Optional: 'phone_number', 'address_line1', 'address_line2', 'city', 'state_province', 'postal_code', 'country'.
 * @return array ['success' => bool, 'message' => string, 'customer_id' => int|null]
 */
function register_customer(mysqli $mysqli, array $data): array {
    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';

    // Basic Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => "First name, last name, email, and password are required."];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => "Invalid email format."];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => "Password must be at least 8 characters long."];
    }

    // Check if email already exists
    $sql_check_email = "SELECT customer_id FROM customers WHERE email = ? LIMIT 1";
    $stmt_check = $mysqli->prepare($sql_check_email);
    if (!$stmt_check) {
        error_log("Prepare failed (check email): " . $mysqli->error);
        return ['success' => false, 'message' => "An internal error occurred during registration (DBP)."];
    }
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        return ['success' => false, 'message' => "An account with this email address already exists."];
    }
    $stmt_check->close();

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    if (!$password_hash) {
        error_log("Password hashing failed for email: " . $email);
        return ['success' => false, 'message' => "An internal error occurred during registration (PH)."];
    }

    // Prepare for optional fields
    $phone_number = trim($data['phone_number'] ?? null);
    // Other address fields can be added similarly if provided in $data

    // Using column names from schema.sql: first_name, last_name, email, password_hash, registration_date
    // is_active and email_verified are not in the current schema.sql for customers
    $sql_insert = "INSERT INTO customers (first_name, last_name, email, password_hash, phone_number, registration_date)
                   VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_insert = $mysqli->prepare($sql_insert);
    if (!$stmt_insert) {
        error_log("Prepare failed (insert customer): " . $mysqli->error);
        return ['success' => false, 'message' => "An internal error occurred during registration (DBI)."];
    }
    // Types: s(first_name), s(last_name), s(email), s(password_hash), s(phone_number)
    $stmt_insert->bind_param("sssss", $first_name, $last_name, $email, $password_hash, $phone_number);

    if ($stmt_insert->execute()) {
        $new_customer_id = $mysqli->insert_id;
        $stmt_insert->close();
        return ['success' => true, 'message' => "Registration successful!", 'customer_id' => $new_customer_id];
    } else {
        error_log("Execute failed (insert customer): " . $stmt_insert->error);
        $stmt_insert->close();
        if ($mysqli->errno == 1062) {
             return ['success' => false, 'message' => "An account with this email address already exists."];
        }
        return ['success' => false, 'message' => "Registration failed. Please try again."];
    }
}

/**
 * Logs in a customer.
 *
 * @param mysqli $mysqli The database connection object.
 * @param string $email The customer's email.
 * @param string $password The customer's password.
 * @return array ['success' => bool, 'message' => string]
 */
function login_customer(mysqli $mysqli, string $email, string $password): array {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => "Email and password are required."];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => "Invalid email format."];
    }

    // Selecting columns based on schema.sql (customer_id, first_name, last_name, email, password_hash)
    // 'is_active' is not in the current schema for customers.
    $sql = "SELECT customer_id, first_name, last_name, email, password_hash FROM customers WHERE email = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!\$stmt) {
        error_log("Prepare failed (login customer): " . $mysqli->error);
        return ['success' => false, 'message' => "An internal error occurred (DBP)."];
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $customer = $result->fetch_assoc();
        $stmt->close();

        // Removed is_active check as it's not in schema
        // if (!\$customer['is_active']) {
        //     return ['success' => false, 'message' => "Your account is inactive. Please contact support."];
        // }

        if (password_verify($password, $customer['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['customer_first_name'] = $customer['first_name'];
            $_SESSION['customer_last_name'] = $customer['last_name'];
            $_SESSION['customer_email'] = $customer['email'];
            $_SESSION['customer_logged_in_at'] = time();

            $update_sql = "UPDATE customers SET last_login = NOW() WHERE customer_id = ?";
            $update_stmt = $mysqli->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param("i", $customer['customer_id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                error_log("Failed to prepare/execute update last_login for customer ID: " . $customer['customer_id'] . " - " . $mysqli->error);
            }
            return ['success' => true, 'message' => "Login successful!"];
        } else {
            return ['success' => false, 'message' => "Invalid email or password."];
        }
    } else {
        if(\$stmt) \$stmt->close();
        return ['success' => false, 'message' => "Invalid email or password."];
    }
}

/**
 * Checks if a customer is currently logged in.
 *
 * @return bool True if logged in, false otherwise.
 */
function is_customer_logged_in(): bool {
    return isset($_SESSION['customer_id']) && isset($_SESSION['customer_email']);
}

/**
 * Logs out the current customer.
 */
function logout_customer(): void {
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_first_name']);
    unset($_SESSION['customer_last_name']);
    unset($_SESSION['customer_email']);
    unset($_SESSION['customer_logged_in_at']);
    // Consider session_regenerate_id(true); if you want to keep other session data (like cart)
    // but give the user a new session ID for their "guest" state.
    // If full logout including cart is desired, session_destroy() might be used, but cart would be lost.
}

/**
 * Retrieves customer details by ID (excluding password hash).
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $customer_id The ID of the customer.
 * @return array|null Customer data as an associative array, or null if not found or on error.
 */
function get_customer_details(mysqli $mysqli, int $customer_id): ?array {
    // Selecting columns based on schema.sql. 'is_active' and 'email_verified' are not present.
    $sql = "SELECT customer_id, first_name, last_name, email, phone_number, registration_date, last_login
            FROM customers WHERE customer_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!\$stmt) {
        error_log("Prepare failed (get customer details): " . \$mysqli->error);
        return null;
    }
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $customer_data = $result->fetch_assoc();
        $stmt->close();
        return $customer_data;
    }
    if(\$stmt) \$stmt->close();
    return null;
}

?>
