<?php
// php/includes/product_functions.php

/**
 * Creates a new product.
 *
 * @param mysqli $mysqli The database connection object.
 * @param array $data Associative array of product data. Expected keys matching products table schema.
 * @return int|false The ID of the newly created product on success, or false on failure.
 */
function create_product(mysqli $mysqli, array $data): int|false {
    // Define default values for all fields including new ones
    $defaults = [
        'product_description' => null,
        'category_id' => null,
        'stock_quantity' => 0,
        'image_url' => null,
        'brand' => null,
        'dimensions' => null,
        'weight_kg' => null,
        'material' => null,
        'color' => null,
        'is_featured' => 0, // Default to false (0)
        'is_active' => 1    // Default to true (1)
    ];
    // Ensure all keys from $data are considered, then merge with defaults for missing ones
    // Prioritize $data values, then fill in with $defaults if a key is not in $data
    $data = array_merge($defaults, $data);


    // Validate required fields (basic check)
    if (empty($data['product_name']) || empty($data['product_sku']) || !isset($data['price'])) {
        error_log("Create product error: Required fields product_name, product_sku, price missing.");
        return false;
    }
    // category_id is also required by product_form.php, but can be null in DB.
    // If form makes it required, it should be in the check above. For now, assume it can be null if DB allows.

    $sql = "INSERT INTO products (
                product_name, product_description, product_sku, price, category_id, stock_quantity, image_url,
                brand, dimensions, weight_kg, material, color, is_featured, is_active,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for create_product: " . $mysqli->error);
        return false;
    }

    // Types:  s (name), s (desc), s (sku), d (price), i (cat_id), i (stock), s (img_url)
    //         s (brand), s (dims), d (weight), s (material), s (color), i (featured), i (active)
    $stmt->bind_param("sssdiisssdsdii",
        $data['product_name'],
        $data['product_description'],
        $data['product_sku'],
        $data['price'],
        $data['category_id'],
        $data['stock_quantity'],
        $data['image_url'],
        $data['brand'],
        $data['dimensions'],
        $data['weight_kg'],
        $data['material'],
        $data['color'],
        $data['is_featured'],
        $data['is_active']
    );

    if ($stmt->execute()) {
        return $mysqli->insert_id;
    } else {
        error_log("Error executing statement for create_product: " . $stmt->error);
        return false;
    }
}

/**
 * Retrieves a single product by its ID (product_id).
 * Includes category name for convenience.
 * All product fields are selected by p.*
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $product_id The ID of the product.
 * @return array|null The product data as an associative array (with category_name), or null if not found or on error.
 */
function get_product_by_id(mysqli $mysqli, int $product_id): ?array {
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.product_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for get_product_by_id: " . $mysqli->error);
        return null;
    }
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Retrieves all products.
 * Includes category name.
 * All product fields are selected by p.*
 *
 * @param mysqli $mysqli The database connection object.
 * @param array $options Associative array for options (e.g., 'category_id', 'search_term').
 * @return array An array of product associative arrays (with category_name), or an empty array on failure or if no products.
 */
function get_all_products(mysqli $mysqli, array $options = []): array {
    $sql = "SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id";

    $where_clauses = [];
    $params = [];
    $types = "";

    if (!empty($options['category_id']) && is_numeric($options['category_id'])) {
        $where_clauses[] = "p.category_id = ?";
        $params[] = $options['category_id'];
        $types .= "i";
    }

    if (!empty($options['search_term'])) {
        $where_clauses[] = "(p.product_name LIKE ? OR p.product_sku LIKE ? OR p.product_description LIKE ? OR p.brand LIKE ? OR p.material LIKE ? OR p.color LIKE ?)";
        $searchTerm = "%" . $options['search_term'] . "%";
        for ($i = 0; $i < 6; $i++) { // Added brand, material, color to search
            $params[] = $searchTerm;
        }
        $types .= "ssssss";
    }

    if (isset($options['is_featured']) && $options['is_featured'] === true) {
        $where_clauses[] = "p.is_featured = 1";
        // No parameter needed for literal 1, so $params and $types are not changed here for this clause.
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " ORDER BY p.product_name ASC";

    if (!empty($options['limit']) && is_numeric($options['limit'])) {
        $sql .= " LIMIT ?";
        $params[] = (int)$options['limit']; // Add to existing params array
        $types .= "i"; // Add to existing types string
    }

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for get_all_products: " . $mysqli->error . " SQL: " . $sql);
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

/**
 * Updates an existing product.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $product_id The ID of the product to update.
 * @param array $data Associative array of product data to update. Keys should match products table columns.
 * @return bool True on success, false on failure.
 */
function update_product(mysqli $mysqli, int $product_id, array $data): bool {
    $set_parts = [];
    $params = [];
    $types = "";

    // Define all updatable fields from products schema and their types
    $allowed_fields = [
        'product_name' => 's',
        'product_description' => 's',
        'product_sku' => 's',
        'price' => 'd',
        'category_id' => 'i',
        'stock_quantity' => 'i',
        'image_url' => 's',
        'brand' => 's',
        'dimensions' => 's',
        'weight_kg' => 'd',
        'material' => 's',
        'color' => 's',
        'is_featured' => 'i', // TINYINT is treated as integer
        'is_active' => 'i'    // TINYINT is treated as integer
    ];

    foreach ($allowed_fields as $field => $type) {
        if (array_key_exists($field, $data)) {
            $set_parts[] = "`{$field}` = ?";
            $params[] = $data[$field]; // Value can be null for nullable fields
            $types .= $type;
        }
    }

    if (empty($set_parts)) {
        error_log("Update product error: No data provided for update or only non-allowed fields given.");
        return true; // No actual update, but not an error. Or false if strict.
    }

    $params[] = $product_id;
    $types .= "i";

    $sql = "UPDATE products SET " . implode(", ", $set_parts) . ", updated_at = NOW() WHERE product_id = ?";

    $stmt = $mysqli->prepare($sql);
    if (!\$stmt) {
        error_log("Error preparing statement for update_product: " . \$mysqli->error);
        return false;
    }

    // Note: Using call_user_func_array for bind_param with dynamic params is an option for older PHP
    // For PHP 5.6+ spread operator ... is fine.
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Error executing statement for update_product: " . \$stmt->error);
        return false;
    }
}

/**
 * Deletes a product by its product_id.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $product_id The ID of the product to delete.
 * @return bool True on success (row deleted), false on failure.
 */
function delete_product(mysqli $mysqli, int $product_id): bool {
    $sql = "DELETE FROM products WHERE product_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!\$stmt) {
        error_log("Error preparing statement for delete_product: " . \$mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        return $stmt->affected_rows > 0;
    } else {
        error_log("Error executing statement for delete_product: " . \$stmt->error . ". This might be due to foreign key constraints (e.g., product is in order_items).");
        return false;
    }
}

?>
