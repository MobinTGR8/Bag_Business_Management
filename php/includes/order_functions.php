<?php
// php/includes/order_functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// require_once __DIR__ . '/product_functions.php'; // Assumed to be included by calling script if needed for pre-checks

/**
 * Creates a new order in the database.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $customer_id The ID of the customer placing the order.
 * @param array $cart_items Array of items from get_cart_items(), each should have 'product_id', 'quantity', 'price'.
 * @param array $shipping_details Associative array of shipping address details.
 * @param array $billing_details Associative array of billing address details.
 * @param string $payment_method Placeholder text for payment method.
 * @param string $currency_code Default 'USD'.
 * @return int|false The new order_id on success, false on failure.
 */
function create_order(
    mysqli $mysqli,
    int $customer_id,
    array $cart_items,
    array $shipping_details,
    array $billing_details,
    string $payment_method = "Offline / Pending",
    string $currency_code = "USD"
): int|false {

    $total_amount = 0.0;
    foreach ($cart_items as $item) {
        // Ensure keys exist and values are appropriate types
        $price = isset($item['price']) ? (float)$item['price'] : 0;
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
        $total_amount += $price * $quantity;
    }

    if (empty($cart_items) || $total_amount <= 0) {
        error_log("create_order: Cart is empty or total amount is zero.");
        return false;
    }

    $mysqli->begin_transaction();

    try {
        // Using order_id as PK for orders table
        $sql_order = "INSERT INTO orders (customer_id, order_date, total_amount, order_status,
                            shipping_full_name, shipping_address_line1, shipping_address_line2,
                            shipping_city, shipping_state_province_region, shipping_postal_code, shipping_country_code, shipping_phone_number,
                            billing_full_name, billing_address_line1, billing_address_line2,
                            billing_city, billing_state_province_region, billing_postal_code, billing_country_code, billing_phone_number,
                            payment_method, payment_status, currency_code, updated_at)
                      VALUES (?, NOW(), ?, 'Pending',
                              ?, ?, ?, ?, ?, ?, ?, ?,
                              ?, ?, ?, ?, ?, ?, ?, ?,
                              ?, 'Pending', ?, NOW())";

        $stmt_order = $mysqli->prepare($sql_order);
        if (!\$stmt_order) throw new Exception("Order prepare failed: " . \$mysqli->error);

        $s_fn = $shipping_details['full_name'] ?? ''; $s_a1 = $shipping_details['address_line1'] ?? ''; $s_a2 = $shipping_details['address_line2'] ?? null;
        $s_city = $shipping_details['city'] ?? ''; $s_spr = $shipping_details['state_province_region'] ?? ''; $s_pc = $shipping_details['postal_code'] ?? '';
        $s_cc = $shipping_details['country_code'] ?? ''; $s_ph = $shipping_details['phone_number'] ?? null;

        $b_fn = $billing_details['full_name'] ?? $s_fn; $b_a1 = $billing_details['address_line1'] ?? $s_a1; $b_a2 = $billing_details['address_line2'] ?? $s_a2;
        $b_city = $billing_details['city'] ?? $s_city; $b_spr = $billing_details['state_province_region'] ?? $s_spr; $b_pc = $billing_details['postal_code'] ?? $s_pc;
        $b_cc = $billing_details['country_code'] ?? $s_cc; $b_ph = $billing_details['phone_number'] ?? $s_ph;

        $stmt_order->bind_param("idsssssssssssssssss",
            $customer_id, $total_amount,
            $s_fn, $s_a1, $s_a2, $s_city, $s_spr, $s_pc, $s_cc, $s_ph,
            $b_fn, $b_a1, $b_a2, $b_city, $b_spr, $b_pc, $b_cc, $b_ph,
            $payment_method, $currency_code
        );

        if (!\$stmt_order->execute()) throw new Exception("Order execute failed: " . \$stmt_order->error);
        $order_id = \$mysqli->insert_id;
        $stmt_order->close();

        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = \$mysqli->prepare($sql_item);
        if (!\$stmt_item) throw new Exception("Order item prepare failed: " . \$mysqli->error);

        foreach ($cart_items as $item) {
            $current_product_id = (int)$item['product_id'];
            $current_quantity = (int)$item['quantity'];
            $current_price_per_unit = (float)$item['price'];
            $current_subtotal_line = $current_price_per_unit * $current_quantity;

            $stmt_item->bind_param("iiidd", $order_id, $current_product_id, $current_quantity, $current_price_per_unit, $current_subtotal_line);
            if (!\$stmt_item->execute()) throw new Exception("Order item execute failed for product ID {$current_product_id}: " . \$stmt_item->error);

            $sql_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?";
            $stmt_stock = \$mysqli->prepare($sql_stock);
            if (!\$stmt_stock) throw new Exception("Stock update prepare failed for product ID {$current_product_id}: " . \$mysqli->error);

            $stmt_stock->bind_param("iii", $current_quantity, $current_product_id, $current_quantity);
            if (!\$stmt_stock->execute()) throw new Exception("Stock update execute failed for product ID {$current_product_id}: " . \$stmt_stock->error);

            if (\$stmt_stock->affected_rows == 0) {
                throw new Exception("Insufficient stock for product ID {$current_product_id} (SKU: {$item['product_sku']}) during final stock update. Order rolled back.");
            }
            \$stmt_stock->close();
        }
        \$stmt_item->close();

        \$mysqli->commit();
        return \$order_id;

    } catch (Exception \$e) {
        \$mysqli->rollback();
        error_log("create_order transaction failed: " . \$e->getMessage());
        return false;
    }
}

function get_order_details(mysqli \$mysqli, int \$order_id, ?int \$customer_id = null): ?array {
    $sql = "SELECT o.*, c.email as customer_email, c.first_name as customer_first_name, c.last_name as customer_last_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.customer_id
            WHERE o.order_id = ?";

    $params = [$order_id];
    $types = "i";

    if (\$customer_id !== null) {
        $sql .= " AND o.customer_id = ?";
        $params[] = \$customer_id;
        $types .= "i";
    }

    $stmt = \$mysqli->prepare($sql);
    if (!\$stmt) { error_log("Get order details prepare failed: " . \$mysqli->error); return null; }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = \$stmt->get_result();
    $order = \$result->fetch_assoc();
    $stmt->close();

    if (!\$order) return null;

    $sql_items = "SELECT oi.*, p.product_name, p.product_sku, p.image_url as product_image_url
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = ?";
    $stmt_items = \$mysqli->prepare($sql_items);
    if (!\$stmt_items) { error_log("Get order items prepare failed: " . \$mysqli->error); return \$order; }

    $stmt_items->bind_param("i", \$order_id);
    $stmt_items->execute();
    $result_items = \$stmt_items->get_result();
    $items = [];
    while (\$item = \$result_items->fetch_assoc()) {
        $items[] = \$item;
    }
    $stmt_items->close();
    $order['items'] = \$items;

    return \$order;
}

function get_customer_orders(mysqli \$mysqli, int \$customer_id): array {
    $sql = "SELECT order_id, order_date, total_amount, order_status
            FROM orders
            WHERE customer_id = ?
            ORDER BY order_date DESC";
    $stmt = \$mysqli->prepare($sql);
    if (!\$stmt) { error_log("Get customer orders prepare failed: " . \$mysqli->error); return []; }

    $stmt->bind_param("i", \$customer_id);
    $stmt->execute();
    $result = \$stmt->get_result();
    $orders = [];
    while (\$row = \$result->fetch_assoc()) {
        $orders[] = \$row;
    }
    $stmt->close();
    return \$orders;
}

/**
 * Retrieves all orders for the admin panel with filtering and pagination.
 *
 * @param mysqli \$mysqli DB connection.
 * @param array \$options Associative array for options:
 *                       'status' => filter by order_status (string)
 *                       'page' => current page number for pagination (int, default 1)
 *                       'limit' => items per page for pagination (int, default 20)
 * @return array ['orders' => array of order objects, 'total_records' => int total matching records]
 */
function get_all_orders_admin(mysqli \$mysqli, array \$options = []): array {
    $sql_select = "SELECT o.*, c.first_name as customer_first_name, c.last_name as customer_last_name, c.email as customer_email";
    $sql_from = " FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id"; // Corrected join to c.customer_id
    $sql_where = " WHERE 1=1";

    $params = [];
    $types = "";

    if (!empty(\$options['status'])) {
        $sql_where .= " AND o.order_status = ?";
        $params[] = \$options['status'];
        $types .= "s";
    }

    $sql_count = "SELECT COUNT(o.order_id) as total" . \$sql_from . \$sql_where; // Corrected to o.order_id
    $stmt_count = \$mysqli->prepare(\$sql_count);
    if (!\$stmt_count) {
        error_log("Prepare failed (count orders admin): " . \$mysqli->error);
        return ['orders' => [], 'total_records' => 0];
    }
    if (!empty(\$params)) {
        \$stmt_count->bind_param(\$types, ...\$params);
    }
    \$stmt_count->execute();
    \$result_count = \$stmt_count->get_result();
    \$total_records = \$result_count->fetch_assoc()['total'] ?? 0;
    \$stmt_count->close();

    $limit = isset(\$options['limit']) && is_numeric(\$options['limit']) ? (int)\$options['limit'] : 20;
    $page = isset(\$options['page']) && is_numeric(\$options['page']) ? (int)\$options['page'] : 1;
    $offset = (\$page > 0) ? (\$page - 1) * \$limit : 0;

    $sql_main = \$sql_select . \$sql_from . \$sql_where . " ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
    $current_param_count = count(\$params);
    \$params[] = \$limit;
    \$params[] = \$offset;
    // Update types string only if new params were added
    if (count(\$params) > \$current_param_count) {
      $types .= str_repeat("i", count(\$params) - \$current_param_count);
    }

    \$stmt_main = \$mysqli->prepare(\$sql_main);
    if (!\$stmt_main) {
        error_log("Prepare failed (get all orders admin): " . \$mysqli->error);
        return ['orders' => [], 'total_records' => (int)\$total_records];
    }

    if (!empty(\$types)) { // Check if types string is not empty before binding
      \$stmt_main->bind_param(\$types, ...\$params);
    }

    \$stmt_main->execute();
    \$result_main = \$stmt_main->get_result();
    \$orders = [];
    while (\$row = \$result_main->fetch_assoc()) {
        \$orders[] = \$row;
    }
    \$stmt_main->close();

    return ['orders' => \$orders, 'total_records' => (int)\$total_records];
}


/**
 * Updates the status of an order.
 *
 * @param mysqli \$mysqli DB connection.
 * @param int \$order_id The ID of the order to update.
 * @param string \$new_status The new status for the order.
 * @return bool True on success, false on failure.
 */
function update_order_status(mysqli \$mysqli, int \$order_id, string \$new_status): bool {
    // Ensure order_status ENUM in schema.sql matches these values
    \$allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled', 'Refunded', 'Failed'];

    if (!in_array(\$new_status, \$allowed_statuses)) {
        error_log("Attempted to set invalid order status: " . \$new_status . " for order ID: " . \$order_id);
        return false;
    }

    $sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?"; // Corrected to order_id
    \$stmt = \$mysqli->prepare(\$sql);
    if (!\$stmt) {
        error_log("Prepare failed (update order status): " . \$mysqli->error);
        return false;
    }

    \$stmt->bind_param("si", \$new_status, \$order_id);
    if (\$stmt->execute()) {
        \$stmt->close();
        return true;
    } else {
        error_log("Execute failed (update order status): " . \$stmt->error . " for order ID: " . \$order_id);
        \$stmt->close();
        return false;
    }
}

?>
