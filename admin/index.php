<?php
// admin/index.php (Admin Dashboard)
require_once __DIR__ . '/../php/includes/admin_auth_check.php'; // Protect this page

$page_title = "Admin Dashboard";
$admin_username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../css/style.css"> <!-- General site styles -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .admin-header {
            background-color: #333;
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 1.5em;
        }
        .admin-header a.logout-link { /* Specific class for logout link styling */
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            background-color: #d9534f; /* Red color for logout */
        }
        .admin-header a.logout-link:hover {
            background-color: #c9302c; /* Darker red on hover */
        }
        .admin-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .dashboard-welcome {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            font-size: 1.2em;
            color: #333;
        }
        .dashboard-links ul {
            list-style: none;
            padding: 0;
        }
        .dashboard-links ul li {
            margin-bottom: 10px;
        }
        .dashboard-links ul li a {
            display: block;
            padding: 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }
        .dashboard-links ul li a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Admin Panel</h1>
        <a href="logout.php" class="logout-link">Logout</a>
    </header>

    <div class="admin-container">
        <p class="dashboard-welcome">Welcome, <strong><?php echo htmlspecialchars($admin_username); ?></strong>!</p>

        <h2>Quick Links</h2>
        <nav class="dashboard-links">
            <ul>
                <li><a href="categories.php">Manage Categories</a></li>
                <li><a href="products.php">Manage Products</a></li>
                <li><a href="orders.php">Manage Orders</a></li>
                <!-- Add links to other management sections here as they are created -->
            </ul>
        </nav>

        <!-- You can add more dashboard widgets or information here later -->
        <!-- For example, summary statistics, recent orders, etc. -->

    </div>

</body>
</html>
