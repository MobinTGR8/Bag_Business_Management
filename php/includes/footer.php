<?php
// php/includes/footer.php
// Ensure BASE_URL is available, it should have been defined in header.php
// If not, define a fallback (though ideally header.php is always included first)
if (!defined('BASE_URL')) {
    // Basic fallback if accessed directly or header was not included
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_path = ($base_path === '.' || $base_path === '/' || $base_path === '\\') ? '' : $base_path;
    define('BASE_URL', rtrim($protocol . $host . $base_path, '/') . '/');
}
?>
            </div> <!-- Closing .container for main content -->
        </main><!-- Closing .site-content -->

        <footer class="site-footer">
            <div class="container">
                <p>&copy; <?php echo date("Y"); ?> BagShop. All rights reserved.</p>
                <p>
                    <a href="<?php echo BASE_URL; ?>privacy.php">Privacy Policy</a> |
                    <a href="<?php echo BASE_URL; ?>terms.php">Terms of Service</a>
                </p>
                <!-- Add more footer content here if needed, like social media links -->
            </div>
        </footer>

        <script src="<?php echo BASE_URL; ?>js/script.js"></script>
        <!-- Add any other global scripts here, or page-specific scripts if needed -->
        <?php if (isset($page_specific_js)): ?>
            <script src="<?php echo BASE_URL; ?>js/<?php echo htmlspecialchars($page_specific_js); ?>"></script>
        <?php endif; ?>
    </body>
    </html>
