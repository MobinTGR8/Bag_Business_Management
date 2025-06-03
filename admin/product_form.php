<?php
// admin/product_form.php
require_once __DIR__ . '/../php/includes/admin_auth_check.php';
require_once __DIR__ . '/../php/db_connect.php';
require_once __DIR__ . '/../php/includes/product_functions.php';
require_once __DIR__ . '/../php/includes/category_functions.php';
require_once __DIR__ . '/../php/includes/file_utils.php'; // For image uploads

$page_title = "Add New Product";
$form_action = "product_form.php";

// Initialize product data array with defaults including new fields
$product_data = [
    'product_id'          => null,
    'product_name'        => '',
    'product_description' => '',
    'product_sku'         => '',
    'price'               => '',
    'category_id'         => null,
    'stock_quantity'      => 0,
    'image_url'           => null,
    'brand'               => '',
    'dimensions'          => '',
    'weight_kg'           => '',
    'material'            => '',
    'color'               => '',
    'is_featured'         => 0, // Default to false (0)
    'is_active'           => 1  // Default to true (1)
];

// Check if this is an edit request
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $product_id_from_get = (int)$_GET['edit_id'];
    $existing_product = get_product_by_id($mysqli, $product_id_from_get);

    if ($existing_product) {
        $page_title = "Edit Product: " . htmlspecialchars($existing_product['product_name']);
        $form_action = "product_form.php?edit_id=" . $product_id_from_get;

        // Overwrite defaults with existing product data
        foreach ($product_data as $key => $value) {
            if (isset($existing_product[$key])) { // Keys from DB should match $product_data keys now
                $product_data[$key] = $existing_product[$key];
            }
        }
        // Ensure product_id is correctly assigned from the DB column name
        $product_data['product_id'] = $existing_product['product_id'];

    } else {
        $_SESSION['message'] = "Product not found for editing.";
        $_SESSION['message_type'] = "error";
        header("Location: products.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize inputs
    $submitted_data = [
        'product_id'          => isset($_POST['product_id']) && is_numeric($_POST['product_id']) ? (int)$_POST['product_id'] : null,
        'product_name'        => trim($_POST['product_name'] ?? ''),
        'product_description' => trim($_POST['product_description'] ?? ''),
        'product_sku'         => trim($_POST['product_sku'] ?? ''),
        'price'               => isset($_POST['price']) ? (float)trim($_POST['price']) : 0.0,
        'category_id'         => isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
        'stock_quantity'      => isset($_POST['stock_quantity']) && is_numeric($_POST['stock_quantity']) ? (int)trim($_POST['stock_quantity']) : 0,
        'current_image_url'   => trim($_POST['current_image_url'] ?? null),
        'brand'               => trim($_POST['brand'] ?? ''),
        'dimensions'          => trim($_POST['dimensions'] ?? ''),
        'weight_kg'           => isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? (float)trim($_POST['weight_kg']) : null,
        'material'            => trim($_POST['material'] ?? ''),
        'color'               => trim($_POST['color'] ?? ''),
        'is_featured'         => isset($_POST['is_featured']) ? 1 : 0,
        'is_active'           => isset($_POST['is_active']) ? 1 : 0,
    ];

    // Update $product_data with submitted data for form repopulation on error
    $product_data = array_merge($product_data, $submitted_data);

    // Basic validation
    if (empty($submitted_data['product_name']) || empty($submitted_data['product_sku']) || $submitted_data['category_id'] === null) {
        $_SESSION['message'] = "Product Name, SKU, and Category are required.";
        $_SESSION['message_type'] = "error";
    } else {
        $uploaded_image_path = $submitted_data['current_image_url'];
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = handle_product_image_upload($_FILES['product_image'], $submitted_data['current_image_url']);
            if ($upload_result === false) {
                $_SESSION['message'] = "Image upload failed. Please check file type, size, and permissions.";
                $_SESSION['message_type'] = "error";
            } elseif (is_string($upload_result)) {
                $uploaded_image_path = $upload_result;
            }
        }

        if (!isset($_SESSION['message']) || $_SESSION['message_type'] !== 'error') {
            // Prepare $db_data with all fields for create/update functions
            $db_data = $submitted_data; // Start with all submitted data
            $db_data['image_url'] = $uploaded_image_path; // Set the final image_url
            unset($db_data['current_image_url']); // Not a DB field
            // Unset product_id for create_product, it's passed as separate param to update_product
            if($submitted_data['product_id'] === null) unset($db_data['product_id']);


            $success = false;
            if ($submitted_data['product_id'] !== null) {
                $success = update_product($mysqli, $submitted_data['product_id'], $db_data);
                $_SESSION['message'] = $success ? "Product updated successfully." : "Failed to update product. Check logs.";
            } else {
                $new_product_id = create_product($mysqli, $db_data);
                $success = ($new_product_id !== false);
                $_SESSION['message'] = $success ? "Product created successfully." : "Failed to create product. Check logs.";
            }

            if ($success) {
                $_SESSION['message_type'] = "success";
                header("Location: products.php");
                exit;
            } else {
                $_SESSION['message_type'] = "error";
            }
        }
    }
}

$all_categories = get_all_categories($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .admin-header { background-color: #333; color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { margin: 0; font-size: 1.5em; }
        .admin-header a { color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #d9534f; }
        .admin-header a:hover { background-color: #c9302c; }
        .admin-container { width: 90%; max-width: 900px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 0 20px; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: repeat(2, 1fr); } }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group textarea,
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 1em; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 5px; vertical-align: middle; }
        .form-group small { color: #777; font-size: 0.9em; }
        .current-image { margin-top: 10px; }
        .current-image img { max-width: 100px; max-height: 100px; border-radius: 4px; border: 1px solid #ddd; object-fit: cover;}
        .btn-submit { background-color: #5cb85c; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1em; }
        .btn-submit:hover { background-color: #4cae4c; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .nav-link { margin-right: 10px; text-decoration: none; color: #007bff; }
        .nav-link:hover { text-decoration: underline; }
        fieldset { margin-top:20px; margin-bottom:20px; padding:15px; border:1px solid #ddd; border-radius: 4px; }
        legend { font-weight: bold; padding: 0 5px; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Admin Panel</h1>
        <a href="logout.php">Logout</a>
    </header>

    <div class="admin-container">
        <p><a href="products.php" class="nav-link">&laquo; Back to Product List</a></p>
        <h2><?php echo htmlspecialchars($page_title); ?></h2>

        <?php if (!empty($_SESSION['message'])): ?>
             <div class="message <?php echo isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : 'error'; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($form_action); ?>" method="POST" enctype="multipart/form-data">
            <?php if ($product_data['product_id']): ?>
                <input type="hidden" name="product_id" value="<?php echo $product_data['product_id']; ?>">
            <?php endif; ?>
            <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($product_data['image_url'] ?? ''); ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product_data['product_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="product_sku">SKU:</label>
                    <input type="text" id="product_sku" name="product_sku" value="<?php echo htmlspecialchars($product_data['product_sku']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description"><?php echo htmlspecialchars($product_data['product_description']); ?></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="price">Price (\$):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars((string)$product_data['price']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($all_categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($product_data['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity:</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" step="1" min="0" value="<?php echo htmlspecialchars($product_data['stock_quantity']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="product_image">Product Image:</label>
                    <input type="file" id="product_image" name="product_image" accept="image/jpeg,image/png,image/gif">
                    <?php if (!empty($product_data['image_url'])): ?>
                        <div class="current-image">
                            <p><small>Current Image:</small></p>
                            <img src="../<?php echo htmlspecialchars($product_data['image_url']); ?>" alt="Current Product Image">
                            <p><small><?php echo htmlspecialchars($product_data['image_url']); ?></small></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <fieldset>
                <legend>Optional Details</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="brand">Brand:</label>
                        <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($product_data['brand']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="color">Color:</label>
                        <input type="text" id="color" name="color" value="<?php echo htmlspecialchars($product_data['color']); ?>">
                    </div>
                </div>
                <div class="form-grid">
                     <div class="form-group">
                        <label for="material">Material:</label>
                        <input type="text" id="material" name="material" value="<?php echo htmlspecialchars($product_data['material']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="dimensions">Dimensions (e.g., L x W x H cm):</label>
                        <input type="text" id="dimensions" name="dimensions" value="<?php echo htmlspecialchars($product_data['dimensions']); ?>">
                    </div>
                </div>
                 <div class="form-group">
                    <label for="weight_kg">Weight (kg):</label>
                    <input type="number" id="weight_kg" name="weight_kg" step="0.01" min="0" value="<?php echo htmlspecialchars((string)$product_data['weight_kg']); ?>">
                </div>
            </fieldset>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_featured" value="1" <?php echo ($product_data['is_featured'] == 1) ? 'checked' : ''; ?>>
                    Is Featured Product?
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo ($product_data['is_active'] == 1) ? 'checked' : ''; ?>>
                    Is Product Active? (Visible in store)
                </label>
            </div>

            <br>
            <button type="submit" class="btn-submit"><?php echo ($product_data['product_id']) ? 'Update Product' : 'Add Product'; ?></button>
            <a href="products.php" style="margin-left: 10px;" class="nav-link">Cancel</a>
        </form>
    </div>
</body>
</html>
