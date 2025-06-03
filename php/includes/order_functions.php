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

    // Schema for orders table: id, customer_id, order_date, total_amount, order_status,
    // shipping_full_name, shipping_address_line1, shipping_address_line2, shipping_city,
    // shipping_state_province_region, shipping_postal_code, shipping_country_code, shipping_phone_number,
    // billing_full_name, billing_address_line1, billing_address_line2, billing_city,
    // billing_state_province_region, billing_postal_code, billing_country_code, billing_phone_number,
    // payment_method, payment_status, currency_code, notes, updated_at
    // (customer_id from param, order_date=NOW(), total_amount from calc, order_status='Pending')
    // (payment_status='Pending', currency_code from param)

    $mysqli->begin_transaction();

    try {
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

        // Param types: i (customer_id), d (total_amount),
        // 8 s for shipping, 8 s for billing, s (payment_method), s (currency_code)
        // Total: id + 16s + s = idsssssssssssssssss (1 i, 1 d, 17 s)
        $stmt_order->bind_param("idsssssssssssssssss",
            $customer_id, $total_amount,
            $s_fn, $s_a1, $s_a2, $s_city, $s_spr, $s_pc, $s_cc, $s_ph,
            $b_fn, $b_a1, $b_a2, $b_city, $b_spr, $b_pc, $b_cc, $b_ph,
            $payment_method, $currency_code
        );

        if (!\$stmt_order->execute()) throw new Exception("Order execute failed: " . \$stmt_order->error);
        $order_id = \$mysqli->insert_id; // This is orders.order_id
        $stmt_order->close();

        // Schema for order_items: order_item_id, order_id, product_id, quantity, unit_price, total_price
        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = \$mysqli->prepare($sql_item);
        if (!\$stmt_item) throw new Exception("Order item prepare failed: " . \$mysqli->error);

        foreach ($cart_items as $item) {
            $current_product_id = (int)$item['product_id']; // Key from get_cart_items()
            $current_quantity = (int)$item['quantity'];
            $current_price_per_unit = (float)$item['price'];
            $current_subtotal_line = $current_price_per_unit * $current_quantity;

            $stmt_item->bind_param("iiidd", $order_id, $current_product_id, $current_quantity, $current_price_per_unit, $current_subtotal_line);
            if (!\$stmt_item->execute()) throw new Exception("Order item execute failed for product ID {$current_product_id}: " . \$stmt_item->error);

            // Decrement stock in 'products' table. products PK is product_id.
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

/**
 * Retrieves order details including its items.
 *
 * @param mysqli \$mysqli DB connection.
 * @param int \$order_id The ID of the order.
 * @param int|null \$customer_id Optional. If provided, verifies the order belongs to this customer.
 * @return array|null Order details with items, or null if not found or not authorized.
 */
function get_order_details(mysqli \$mysqli, int \$order_id, ?int \$customer_id = null): ?array {
    // orders PK is order_id, customers PK is customer_id
    $sql = "SELECT o.*, c.email as customer_email, c.first_name as customer_first_name, c.last_name as customer_last_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.customer_id
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

    // order_items FK product_id references products.product_id
    $sql_items = "SELECT oi.*, p.product_name, p.product_sku, p.image_url as product_image_url
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = ?";
    $stmt_items = \$mysqli->prepare($sql_items);
    if (!\$stmt_items) { error_log("Get order items prepare failed: " . \$mysqli->error); return \$order; } // Return order without items on item fetch failure

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

/**
 * Retrieves all orders for a specific customer.
 *
 * @param mysqli \$mysqli DB connection.
 * @param int \$customer_id The ID of the customer.
 * @return array Array of orders (basic details).
 */
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

?>
