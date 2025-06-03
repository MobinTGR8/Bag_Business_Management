<?php

// Database Configuration
define('DB_HOST', 'localhost');          // Database host (usually 'localhost' for XAMPP)
define('DB_USERNAME', 'root');           // Database username (usually 'root' for XAMPP)
define('DB_PASSWORD', '');               // Database password (empty by default for XAMPP, change if you have set one)
define('DB_NAME', 'bag_shop_db');        // The name of your database

/*
Important Security Note:
In a production environment, this file should be:
1. Placed outside of the web server's document root if possible.
2. Have very restrictive file permissions.
3. Consider using environment variables to store sensitive credentials instead of hardcoding them.
For XAMPP development, placing it here is generally acceptable for ease of use.
*/

// Optional: Site URL and Path Configuration (can be useful later)
// define('SITE_URL', 'http://localhost/your_project_folder_name/'); // Adjust to your project's URL
// define('BASE_PATH', __DIR__ . '/../'); // Path to the project root

?>
