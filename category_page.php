<?php
// category_page.php

// DB connection and functions are needed early for category details and page title
require_once 'php/db_connect.php';
require_once 'php/includes/category_functions.php'; // For get_category_by_slug
require_once 'php/includes/product_functions.php';  // For get_all_products

$category_slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$category = null;
$products = [];
$page_error = null;
$page_title = "Category"; // Default title

if (empty($category_slug)) {
    $page_error = "No category specified. Please select a category to view.";
    $page_title = "Category Not Found";
} else {
    $category = get_category_by_slug($mysqli, $category_slug); // Expects 'slug', returns category data with 'id', 'name', 'description', 'slug'
    if (!$category) {
        http_response_code(404); // Not found
        $page_error = "The category you're looking for ('" . htmlspecialchars($category_slug) . "') was not found.";
        $page_title = "Category Not Found";
    } else {
        $page_title = htmlspecialchars($category['name']); // Set page title to category name
        // get_all_products expects 'category_id' in options, and $category['id'] is the correct key from get_category_by_slug
        $products = get_all_products($mysqli, ['category_id' => $category['id']]);
    }
}

require_once 'php/includes/header.php'; // Includes BASE_URL and basic HTML structure
?>

<div class="category-page-container container"> <!-- Added .container for consistency with header/footer structure -->
    <?php if ($page_error): ?>
        <div class="error-message" style="margin-top: 20px; margin-bottom: 20px;"> <!-- Added some margin for standalone error -->
            <?php echo htmlspecialchars($page_error); ?>
            <p style="margin-top:15px;"><a href="<?php echo BASE_URL; ?>index.php" class="button">Go to Homepage</a></p>
        </div>
    <?php elseif ($category): ?>
        <header class="category-header">
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p class="category-description"><?php echo nl2br(htmlspecialchars($category['description'])); ?></p>
            <?php endif; ?>
        </header>

        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): // $product keys are product_id, product_name etc. from get_all_products ?>
                    <div class="product-item">
                        <a href="<?php echo BASE_URL; ?>product_detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" class="product-link">
                            <?php
                            $image_display_path = BASE_URL . 'images/placeholder_product.jpg'; // Default placeholder
                            if (!empty($product['image_url'])) {
                                $image_display_path = BASE_URL . htmlspecialchars($product['image_url']);
                            }
                            ?>
                            <img src="<?php echo $image_display_path; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                        </a>
                        <p class="product-price">$<?php echo htmlspecialchars(number_format((float)$product['price'], 2)); ?></p>
                        <div class="product-item-actions" style="margin-top: 10px;">
                            <a href="<?php echo BASE_URL . 'product_detail.php?id=' . htmlspecialchars($product['product_id']); ?>" class="btn btn-details" style="background-color: #17a2b8; color:white; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 0.9em; display:inline-block; margin-right:5px;">View Details</a>
                            <form action="<?php echo BASE_URL; ?>cart_handler.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-success btn-sm add-to-cart-list-btn" <?php echo ($product['stock_quantity'] > 0) ? '' : 'disabled'; ?>>
                                    <?php echo ($product['stock_quantity'] > 0) ? 'Add to Cart' : 'Out of Stock'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center; padding: 20px;">No products found in the "<?php echo htmlspecialchars($category['name']); ?>" category yet. Please check back later!</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once 'php/includes/footer.php';
?>
