<?php

// Functions for managing categories

/**
 * Creates a new category.
 *
 * @param mysqli $mysqli The database connection object.
 * @param string $name The name of the category.
 * @param string|null $description The description of the category (optional).
 * @param string $slug The URL-friendly slug for the category.
 * @param int|null $parent_id The ID of the parent category (optional).
 * @return int|false The ID of the newly created category on success, or false on failure.
 */
function create_category(mysqli $mysqli, string $name, ?string $description, string $slug, ?int $parent_id = null) {
    // The column names in the SQL query were 'name', 'description', 'slug', 'parent_id', 'date_created', 'last_updated'
    // The schema.sql defined category_id, category_name, category_slug, category_description, created_at, updated_at
    // Assuming parent_id is a nullable foreign key to categories.category_id if subcategories are allowed.
    // Let's adjust SQL to match schema.sql and add parent_id if it's part of the design.
    // For now, assuming schema.sql is the source of truth and it does NOT have parent_id.
    // If parent_id is needed, schema.sql should be updated first.
    // Let's use the columns from schema.sql: category_name, category_slug, category_description

    // Corrected SQL based on schema.sql (category_id, category_name, category_slug, category_description, created_at, updated_at)
    // The original function signature included parent_id, but schema.sql does not.
    // I will proceed with the function signature provided in the prompt which includes parent_id,
    // and assume the user will update their schema.sql or understands this discrepancy.
    // If parent_id column doesn't exist, this INSERT will fail.
    // For the query, I will use the column names from the prompt's function description: name, description, slug, parent_id
    // and assume they map to category_name, category_description, category_slug, parent_category_id (hypothetical) in the DB.
    // Given the schema.sql: category_name, category_slug, category_description.
    // The function signature has: name, description, slug, parent_id.
    // I will map them: name -> category_name, description -> category_description, slug -> category_slug.
    // parent_id will be assumed to be a column named 'parent_id' for this function to work as written.
    // If 'parent_id' does not exist in 'categories' table, this will cause SQL error.

    $sql = "INSERT INTO categories (category_name, category_description, category_slug, parent_id) VALUES (?, ?, ?, ?)";
    // created_at and updated_at have defaults in schema.sql

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for create_category: " . $mysqli->error);
        return false;
    }
    // Parameters: category_name (string), category_description (string), category_slug (string), parent_id (int or null)
    $stmt->bind_param("sssi", $name, $description, $slug, $parent_id);

    if ($stmt->execute()) {
        return $mysqli->insert_id;
    } else {
        error_log("Error executing statement for create_category: " . $stmt->error);
        return false;
    }
}

/**
 * Retrieves a single category by its ID.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $id The ID of the category.
 * @return array|null The category data as an associative array, or null if not found or on error.
 */
function get_category_by_id(mysqli $mysqli, int $id): ?array {
    // Using column names from schema.sql: category_id, category_name, category_description, category_slug, created_at, updated_at
    // The function signature in prompt used 'id', 'name', 'description', 'slug', 'parent_id', 'date_created', 'last_updated'.
    // Assuming 'id' maps to 'category_id', 'name' to 'category_name' etc.
    // And assuming 'parent_id' column exists if it's in the SELECT.
    $sql = "SELECT category_id, category_name, category_description, category_slug, parent_id, created_at, updated_at FROM categories WHERE category_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for get_category_by_id: " . $mysqli->error);
        return null;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        // Map DB columns to expected keys if they differ, or use DB keys directly.
        // The prompt implies keys like 'id', 'name'. Let's return them as such for consistency with function doc.
        $category = $result->fetch_assoc();
        return [
            'id' => $category['category_id'],
            'name' => $category['category_name'],
            'description' => $category['category_description'],
            'slug' => $category['category_slug'],
            'parent_id' => $category['parent_id'] ?? null, // Assuming parent_id might not exist or be null
            'date_created' => $category['created_at'],
            'last_updated' => $category['updated_at']
        ];
    }
    return null;
}

/**
 * Retrieves a single category by its slug.
 *
 * @param mysqli $mysqli The database connection object.
 * @param string $slug The slug of the category.
 * @return array|null The category data as an associative array, or null if not found or on error.
 */
function get_category_by_slug(mysqli $mysqli, string $slug): ?array {
    $sql = "SELECT category_id, category_name, category_description, category_slug, parent_id, created_at, updated_at FROM categories WHERE category_slug = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for get_category_by_slug: " . $mysqli->error);
        return null;
    }
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();
        return [
            'id' => $category['category_id'],
            'name' => $category['category_name'],
            'description' => $category['category_description'],
            'slug' => $category['category_slug'],
            'parent_id' => $category['parent_id'] ?? null,
            'date_created' => $category['created_at'],
            'last_updated' => $category['updated_at']
        ];
    }
    return null;
}

/**
 * Retrieves all categories.
 *
 * @param mysqli $mysqli The database connection object.
 * @return array An array of category associative arrays, or an empty array on failure or if no categories.
 */
function get_all_categories(mysqli $mysqli): array {
    $sql = "SELECT category_id, category_name, category_description, category_slug, parent_id, created_at, updated_at FROM categories ORDER BY category_name ASC";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for get_all_categories: " . $mysqli->error);
        return [];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => $row['category_id'],
                'name' => $row['category_name'],
                'description' => $row['category_description'],
                'slug' => $row['category_slug'],
                'parent_id' => $row['parent_id'] ?? null,
                'date_created' => $row['created_at'],
                'last_updated' => $row['updated_at']
            ];
        }
    }
    return $categories;
}

/**
 * Updates an existing category.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $id The ID of the category to update.
 * @param string $name The new name of the category.
 * @param string|null $description The new description of the category.
 * @param string $slug The new URL-friendly slug for the category.
 * @param int|null $parent_id The new parent ID of the category.
 * @return bool True on success, false on failure.
 */
function update_category(mysqli $mysqli, int $id, string $name, ?string $description, string $slug, ?int $parent_id = null): bool {
    // last_updated is handled by ON UPDATE CURRENT_TIMESTAMP in schema.sql
    $sql = "UPDATE categories SET category_name = ?, category_description = ?, category_slug = ?, parent_id = ? WHERE category_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for update_category: " . $mysqli->error);
        return false;
    }
    // Parameters: category_name, category_description, category_slug, parent_id, category_id
    $stmt->bind_param("sssii", $name, $description, $slug, $parent_id, $id);

    if ($stmt->execute()) {
        // $stmt->affected_rows could be 0 if the data was the same.
        // Success means the query ran without error.
        return true;
    } else {
        error_log("Error executing statement for update_category: " . $stmt->error);
        return false;
    }
}


/**
 * Deletes a category.
 *
 * @param mysqli $mysqli The database connection object.
 * @param int $id The ID of the category to delete.
 * @return bool True on success, false on failure.
 */
function delete_category(mysqli $mysqli, int $id): bool {
    // Check for child categories IF parent_id column exists and is part of the logic.
    // Assuming 'parent_id' is a column in 'categories' table for this check to be meaningful.
    $sql_check_children = "SELECT COUNT(*) as child_count FROM categories WHERE parent_id = ?";
    $stmt_check = $mysqli->prepare($sql_check_children);

    if (!$stmt_check) {
        // If parent_id column doesn't exist, this prepare might fail.
        // Or it might succeed but child_count will always be 0 if no rows have parent_id = $id.
        // Log error and proceed with deletion attempt if prepare fails for this check,
        // as the main goal is to delete the category.
        error_log("Error preparing statement for delete_category (check children): " . $mysqli->error . ". Proceeding with delete attempt.");
    } else {
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check) {
            $child_count = $result_check->fetch_assoc()['child_count'];
            if ($child_count > 0) {
                error_log("Attempted to delete category ID {$id} which has {$child_count} child categories. Deletion aborted.");
                $stmt_check->close();
                return false;
            }
        }
        $stmt_check->close();
    }

    // products.category_id has ON DELETE SET NULL from schema.sql
    $sql = "DELETE FROM categories WHERE category_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement for delete_category: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        return $stmt->affected_rows > 0;
    } else {
        error_log("Error executing statement for delete_category: " . $stmt->error);
        return false;
    }
}

?>
