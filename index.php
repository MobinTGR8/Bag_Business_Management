<?php
$page_title = "Welcome to BagShop"; // Optional: Set a specific page title
require_once 'php/includes/header.php';
// The header.php now defines BASE_URL and opens <main class="site-content"><div class="container">

require_once 'php/db_connect.php'; // Connect to the database
require_once 'php/includes/category_functions.php'; // For get_all_categories
require_once 'php/includes/product_functions.php';  // For get_all_products

// Fetch categories for display - let's limit to a few for the homepage showcase
$showcase_categories_all = get_all_categories($mysqli);
// You might want to select specific categories or limit them, e.g. array_slice($showcase_categories_all, 0, 3);
$showcase_categories = array_slice($showcase_categories_all, 0, 3);


// Fetch featured products - e.g., 4 products
$featured_products = get_all_products($mysqli, ['is_featured' => true, 'limit' => 4]);

?>

<!-- Main content specific to index.php -->
<section class="hero-section" style="text-align: center; padding: 40px 20px; background-color: #f0f0f0; border-radius: 8px; margin-bottom: 30px;">
    <h2>Featured Bags Collection</h2>
    <p style="font-size: 1.1em; color: #555;">Discover our latest arrivals and top-quality bags for every occasion.</p>
    <a href="<?php echo BASE_URL; ?>products_page.php" class="button" style="display: inline-block; margin-top: 15px; padding: 12px 25px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 1.1em;">Shop All Products</a>
</section>

<section class="category-showcase" style="margin-bottom: 30px;">
    <h3 style="text-align: center; font-size: 1.8em; margin-bottom: 20px;">Shop by Category</h3>
    <?php if (!empty($showcase_categories)): ?>
    <div class="categories-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center;">
        <?php foreach ($showcase_categories as $category): ?>
            <div class="category-item" style="background-color: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <a href="<?php echo BASE_URL . 'category_page.php?slug=' . htmlspecialchars($category['slug']); ?>" style="text-decoration: none; color: #333;">
                    <!-- Placeholder image - ideally categories would have images too -->
                    <img src="<?php echo BASE_URL . 'images/placeholder_category.jpg'; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 100%; max-width: 150px; height: auto; margin-bottom: 10px; border-radius: 4px;">
                    <h4 style="margin: 0; font-size: 1.2em;"><?php echo htmlspecialchars($category['name']); ?></h4>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="text-align:center;">No categories to display right now.</p>
    <?php endif; ?>
</section>

<section class="featured-products">
    <h3 style="text-align: center; font-size: 1.8em; margin-bottom: 20px;">Our Featured Products</h3>
    <?php if (!empty($featured_products)): ?>
    <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <?php foreach ($featured_products as $product): ?>
        <div class="product-item" style="background-color: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <a href="<?php echo BASE_URL . 'product_detail.php?id=' . htmlspecialchars($product['product_id']); ?>" style="text-decoration:none; color:inherit;">
                <?php
                $image_path = !empty($product['image_url']) ? BASE_URL . htmlspecialchars($product['image_url']) : BASE_URL . 'images/placeholder_product.jpg';
                ?>
                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px; margin-bottom: 10px;">
                <h4 style="font-size: 1.1em; margin: 10px 0 5px 0; color: #333;"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                <p class="product-price" style="font-size: 1.1em; color: #007bff; font-weight: bold; margin-bottom: 10px;">
                    $<?php echo htmlspecialchars(number_format((float)$product['price'], 2)); ?>
                </p>
                <button class="btn" style="display: inline-block; background-color: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 0.9em; border:none; cursor:pointer;">View Details</button>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="text-align:center;">No featured products to display right now.</p>
    <?php endif; ?>
</section>

<?php require_once 'php/includes/footer.php'; ?>
