<?php
// admin/category_form.php
session_start();

require_once '../php/db_connect.php'; // Provides $mysqli
require_once '../php/includes/category_functions.php';

$page_title = "Add New Category";
$form_action = "category_form.php"; // Default action is add

$category_id = null; // ID of the category being edited, null for new category
$name = '';
$slug = '';
$description = '';
$parent_id_current = null; // Current parent_id of the category being edited or selected

// Check if this is an edit request
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $category_id = (int)$_GET['edit_id'];
    $existing_category = get_category_by_id($mysqli, $category_id);

    if ($existing_category) {
        $page_title = "Edit Category: " . htmlspecialchars($existing_category['name']);
        $form_action = "category_form.php?edit_id=" . $category_id; // Keep edit_id in action

        $name = $existing_category['name'];
        $slug = $existing_category['slug'];
        $description = $existing_category['description'] ?? '';
        $parent_id_current = $existing_category['parent_id'];
    } else {
        $_SESSION['message'] = "Category not found for editing (ID: $category_id).";
        $_SESSION['message_type'] = "error";
        header("Location: categories.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parent_id_input = trim($_POST['parent_id'] ?? '');

    // If parent_id is empty string or "0", treat as NULL. Otherwise, cast to int.
    $parent_id_new = ($parent_id_input === '' || $parent_id_input === '0') ? null : (int)$parent_id_input;

    // Basic validation
    if (empty($name) || empty($slug)) {
        // Store message to be displayed on the form page itself
        $_SESSION['form_submission_error'] = "Category Name and Slug are required.";
        // Values for $name, $slug, $description, $parent_id_current are already set to be redisplayed
    } else {
        $success = false;
        $operation_type = '';

        if ($category_id !== null) { // Update existing category
            $operation_type = 'update';
            // Ensure a category is not set as its own parent during an update
            if ($parent_id_new !== null && $parent_id_new === $category_id) {
                 $_SESSION['form_submission_error'] = "A category cannot be its own parent.";
            } else {
                $success = update_category($mysqli, $category_id, $name, $description, $slug, $parent_id_new);
            }
        } else { // Create new category
            $operation_type = 'create';
            $new_category_id = create_category($mysqli, $name, $description, $slug, $parent_id_new);
            $success = ($new_category_id !== false);
            if ($success) $category_id = $new_category_id; // For potential further use
        }

        if (isset($_SESSION['form_submission_error'])) {
             // Error already set (e.g. self-parenting), stay on page
             // No redirect here
        } elseif ($success) {
            $_SESSION['message'] = "Category " . ($operation_type === 'update' ? 'updated' : 'created') . " successfully.";
            $_SESSION['message_type'] = "success";
            header("Location: categories.php");
            exit;
        } else {
            $_SESSION['form_submission_error'] = "Failed to " . ($operation_type ?: 'process') . " category. Possible duplicate name/slug or database error. Check logs.";
            // Stay on page, values will be redisplayed.
        }
    }
    // If validation failed or DB operation failed, we fall through to display the form again with current values and error message.
    // Update $parent_id_current to what was submitted if there was an error, so dropdown reflects selection.
    $parent_id_current = $parent_id_new;
}

// Fetch all categories for the parent dropdown
// Exclude the current category being edited from the list of potential parents
$all_categories_for_dropdown = get_all_categories($mysqli);
$parent_options = [];
foreach ($all_categories_for_dropdown as $cat) {
    if ($category_id !== null && $cat['id'] == $category_id) {
        continue; // Prevent a category from being its own parent
    }
    $parent_options[] = $cat;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Basic admin styles - reusing some from categories.php for consistency */
        body { font-family: sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 0; }
        .admin-container { width: 90%; max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.95em;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group small { display: block; margin-top: 5px; color: #777; font-size: 0.85em;}
        .btn-submit { background-color: #5cb85c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .btn-submit:hover { background-color: #4cae4c; }
        .cancel-link { margin-left: 10px; color: #337ab7; text-decoration: none; }
        .cancel-link:hover { text-decoration: underline; }

        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.95em; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php
        // Display error message from form submission attempt, if any
        if (isset($_SESSION['form_submission_error'])):
        ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['form_submission_error']); ?>
            </div>
            <?php unset($_SESSION['form_submission_error']); // Clear message after displaying ?>
        <?php
        // Or display general message if redirected from another page (e.g., category not found for edit)
        elseif (isset($_SESSION['message'])):
        ?>
             <div class="message <?php echo isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : 'error'; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); // Clear message after displaying ?>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($form_action); ?>" method="POST">
            <div class="form-group">
                <label for="name">Category Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="slug">Slug:</label>
                <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slug); ?>" required>
                <small>URL-friendly version of the name (e.g., "leather-handbags"). Should be unique.</small>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <label for="parent_id">Parent Category:</label>
                <select id="parent_id" name="parent_id">
                    <option value="0">None (Top-level Category)</option>
                    <?php foreach ($parent_options as $option_cat): ?>
                        <option value="<?php echo $option_cat['id']; ?>" <?php echo ($parent_id_current == $option_cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($option_cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Assigning a parent makes this a sub-category.</small>
            </div>
            <button type="submit" class="btn-submit"><?php echo ($category_id !== null) ? 'Update Category' : 'Add Category'; ?></button>
            <a href="categories.php" class="cancel-link">Cancel</a>
        </form>
    </div>
</body>
</html>
