<?php
// php/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session if not already started (e.g., for cart, user session later)
}

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST']; // e.g., localhost or your_domain.com
    // SCRIPT_NAME gives the path to the current script.
    // To get the base path of the project, we need to consider how deep the current script might be.
    // If this header is included by index.php at root, dirname($_SERVER['SCRIPT_NAME']) is '/' or '\'.
    // If included by admin/index.php, it's '/admin'.
    // We want the path to the project root.
    // A common way is to assume the project root is where the main index.php is, or a known level up.
    // For a project structure like /my_project_folder/index.php
    // and /my_project_folder/php/includes/header.php
    // __DIR__ is /path/to/htdocs/my_project_folder/php/includes
    // Document root is /path/to/htdocs/
    // So, str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__) gives /my_project_folder/php/includes
    // We need to go up two levels from /php/includes to get to /my_project_folder/

    // Simpler approach for typical local dev: Assume project root is accessible from web root or one level down.
    // Let's use a common structure where this file is in php/includes under the project root.
    // So, to get to the project root from /php/includes/, we go up two levels.
    // Then, the web path to the project root is derived.
    // This can be tricky if project is in a deeply nested subdir of webroot or uses aliases.

    // A more reliable way if you have a front controller (e.g. index.php at root):
    // $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // if ($base_path === '' || $base_path === DIRECTORY_SEPARATOR) {
    //     $base_path = ''; // At root
    // }
    // define('BASE_URL', rtrim($protocol . $host . $base_path, '/') . '/');
    // Given this file is in php/includes, and index.php is at root.
    // SCRIPT_NAME when index.php includes this will be /index.php or /subdir/index.php
    // Let's use a common configuration that works for root or one-level subdirectory.
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if ($script_dir === '/') {
        $base_path_segment = '';
    } else {
        // If script_dir is /myproject/admin, and project root is /myproject
        // This calculation is complex if header.php is included from various depths.
        // The original calculation was simpler and often works for basic setups.
        // Let's stick to a simpler guess, assuming index.php is at root or one level down from webroot.
        // This typically means dirname($_SERVER['SCRIPT_NAME']) is the project's web-accessible base path.
        $base_path = dirname($_SERVER['SCRIPT_NAME']);
        $base_path = ($base_path === '.' || $base_path === '/' || $base_path === '\\') ? '' : $base_path;
    }
    define('BASE_URL', rtrim($protocol . $host . $base_path, '/') . '/');
}


// Page title - can be overridden by the including page
$current_page_title = isset($page_title) ? htmlspecialchars($page_title) . " - BagShop" : "BagShop - Your Favorite Bags";

// Fetch categories for navigation (placeholder for now, will be dynamic)
// require_once __DIR__ . '/../db_connect.php';
// require_once __DIR__ . '/category_functions.php';
// $nav_categories = get_all_categories($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_page_title; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <!-- Add any other global head elements here, e.g., favicon -->
    <link rel="icon" href="<?php echo BASE_URL; ?>favicon.ico" type="image/x-icon">
</head>
<body>
    <header class="site-header">
        <div class="container"> <!-- Assuming .container class for centering content -->
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>index.php"><h1>BagShop</h1></a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <!-- Placeholder for dynamic categories from DB -->
                    <?php /*
                        if (isset($nav_categories) && !empty($nav_categories)) {
                            foreach ($nav_categories as $category) {
                                echo '<li><a href="' . BASE_URL . 'category_page.php?slug=' . htmlspecialchars($category['slug']) . '">' . htmlspecialchars($category['name']) . '</a></li>';
                            }
                        } else { */ ?>
                            <li><a href="<?php echo BASE_URL; ?>category.php?id=1">Handbags (Static)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>category.php?id=2">Backpacks (Static)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>category.php?id=3">Totes (Static)</a></li>
                    <?php /* } */ ?>
                    <!-- End placeholder -->
                    <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php">Contact</a></li>
                    <li><a href="<?php echo BASE_URL; ?>cart.php">Cart</a></li>
                    <?php if (isset($_SESSION['customer_id'])): // Example for customer login ?>
                        <li><a href="<?php echo BASE_URL; ?>account.php">My Account</a></li>
                        <li><a href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login.php">Login/Register</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL; ?>admin/index.php" style="color: #ffc107;">Admin</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="site-content"> <!-- This div is opened here -->
        <div class="container"> <!-- This div is opened here -->
