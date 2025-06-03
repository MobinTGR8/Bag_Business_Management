<?php
// admin/products.php
require_once __DIR__ . '/../php/includes/admin_auth_check.php'; // Protect this page
require_once __DIR__ . '/../php/db_connect.php';
require_once __DIR__ . '/../php/includes/product_functions.php';
require_once __DIR__ . '/../php/includes/category_functions.php'; // For category filter dropdown

$page_title = "Manage Products";

// Handle filtering
$filter_category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';

$options = [];
if ($filter_category_id) {
    $options['category_id'] = $filter_category_id;
}
if (!empty($search_term)) {
    $options['search_term'] = $search_term;
}

// Fetch all products with filter options
$products = get_all_products($mysqli, $options);
$all_categories = get_all_categories($mysqli); // For the filter dropdown

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="../css/style.css"> <!-- General site styles -->
    <style>
        /* Using styles from categories.php, ensure consistency or use a shared admin.css */
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .admin-header { background-color: #333; color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { margin: 0; font-size: 1.5em; }
        .admin-header a { color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #d9534f; } /* Logout red */
        .admin-header a:hover { background-color: #c9302c; }

        .admin-container { width: 95%; max-width:1400px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: middle; }
        th { background-color: #f2f2f2; white-space: nowrap;}
        .action-links a, .button-link {
            display: inline-block;
            padding: 6px 10px;
            margin-right: 5px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            color: white;
            margin-bottom: 3px; /* For wrapping */
        }
        .edit-link { background-color: #5bc0de; } /* Info blue */
        .edit-link:hover { background-color: #31b0d5; }
        .delete-link { background-color: #d9534f; } /* Danger red */
        .delete-link:hover { background-color: #c9302c; }

        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .button { display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-bottom:15px; }
        .button:hover { background-color: #0056b3; }
        .product-thumbnail { max-width: 50px; max-height: 50px; border-radius: 3px; object-fit: cover; }
        .filter-form { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .filter-form label { font-weight: bold; }
        .filter-form select, .filter-form input[type="text"], .filter-form button { padding: 8px; border-radius: 4px; border: 1px solid #ddd; }
        .filter-form button { background-color: #007bff; color:white; cursor:pointer; }
        .filter-form button:hover { background-color: #0056b3;}
        .filter-form .clear-filter-link { margin-left: 10px; text-decoration: none; color: #007bff; }
        .filter-form .clear-filter-link:hover { text-decoration: underline; }
        .nav-link { margin-right: 10px; text-decoration: none; color: #007bff; }
        .nav-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Admin Panel</h1>
        <a href="logout.php" class="logout-link">Logout</a>
    </header>

    <div class="admin-container">
        <p><a href="index.php" class="nav-link">&laquo; Back to Dashboard</a></p>
        <h2><?php echo htmlspecialchars($page_title); ?></h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : 'success'; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <p><a href="product_form.php" class="button">Add New Product</a></p>

        <form action="products.php" method="GET" class="filter-form">
            <div>
                <label for="category_id">Category:</label>
                <select name="category_id" id="category_id">
                    <option value="">All</option>
                    <?php foreach ($all_categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($filter_category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="search_term">Search:</label>
                <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Name, SKU, Desc...">
            </div>
            <button type="submit">Filter / Search</button>
            <?php if ($filter_category_id || !empty($search_term)): ?>
                <a href="products.php" class="clear-filter-link">Clear Filters</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($products)): ?>
            <div style="overflow-x:auto;"> <!-- For responsive tables -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                            <td>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-thumbnail">
                                <?php else: ?>
                                    <span style="font-size:0.8em; color:#aaa;">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_sku']); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format((float)$product['price'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                            <td class="action-links">
                                <a href="product_form.php?edit_id=<?php echo $product['product_id']; ?>" class="button-link edit-link">Edit</a>
                                <a href="product_delete.php?id=<?php echo $product['product_id']; ?>" class="button-link delete-link" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php else: ?>
            <p>No products found<?php echo ($filter_category_id || !empty($search_term)) ? ' matching your criteria' : ''; ?>. <a href="product_form.php">Add one?</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
