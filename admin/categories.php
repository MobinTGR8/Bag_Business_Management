<?php
// admin/categories.php
require_once __DIR__ . '/../php/includes/admin_auth_check.php';
// session_start(); // session_start() is now handled by admin_auth_check.php if needed

require_once '../php/db_connect.php';
require_once '../php/includes/category_functions.php';

$page_title = "Manage Categories";

// Fetch all categories
$categories = get_all_categories($mysqli);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="../css/style.css"> // Assuming a general style.css exists
    <style>
        /* Basic admin styles */
        body { font-family: sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 0; }
        .admin-container { width: 90%; max-width: 1200px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .action-links a, .button {
            display: inline-block;
            padding: 8px 12px;
            margin-right: 5px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .button, .action-links a[href*="category_form.php"] { background-color: #5cb85c; color: white; }
        .action-links a[href*="category_form.php"]:hover { background-color: #4cae4c; }

        .action-links a[href*="category_delete.php"] { background-color: #d9534f; color: white; }
        .action-links a[href*="category_delete.php"]:hover { background-color: #c9302c; }

        .message { padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.95em; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo isset($_SESSION['message_type']) ? htmlspecialchars($_SESSION['message_type']) : 'success'; ?>">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <p><a href="category_form.php" class="button">Add New Category</a></p>

        <?php if (!empty($categories)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Parent ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['id']); ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                            <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($category['parent_id'] ?? 'None'); ?></td>
                            <td class="action-links">
                                <a href="category_form.php?edit_id=<?php echo $category['id']; ?>">Edit</a>
                                <a href="category_delete.php?id=<?php echo $category['id']; ?>" onclick="return confirm('Are you sure you want to delete this category? This might also affect products referencing this category if ON DELETE SET NULL is used, or prevent deletion if ON DELETE RESTRICT is used and products reference it.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No categories found. <a href="category_form.php">Add one now!</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
