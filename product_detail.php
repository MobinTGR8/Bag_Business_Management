<?php
// product_detail.php

require_once 'php/db_connect.php';
require_once 'php/includes/product_functions.php'; // Uses the updated get_product_by_id

$product_id_from_get = isset($_GET['id']) ? (int)$_GET['id'] : null;
$product = null;
$page_error = null;
$page_title = "Product Details"; // Default

if (empty($product_id_from_get)) {
    $page_error = "No product specified. Please select a product to view.";
    $page_title = "Product Not Found";
    http_response_code(400); // Bad Request
} else {
    $product = get_product_by_id($mysqli, $product_id_from_get);
    if (!$product) {
        http_response_code(404); // Not Found
        $page_error = "Sorry, the product you are looking for (ID: " . htmlspecialchars($product_id_from_get) . ") could not be found.";
        $page_title = "Product Not Found";
    } else {
        // Keys in $product will be product_name, product_sku, etc. due to p.*
        $page_title = htmlspecialchars($product['product_name']);
    }
}

require_once 'php/includes/header.php'; // Defines BASE_URL
?>

<div class="product-detail-container container"> <!-- Added .container for consistency -->
    <?php if ($page_error): ?>
        <div class="error-message" style="margin-top: 20px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($page_error); ?>
            <p style="margin-top:15px;"><a href="<?php echo BASE_URL; ?>index.php" class="button">Go to Homepage</a></p>
        </div>
    <?php elseif ($product): ?>
        <article class="product-details">
            <div class="product-image-gallery">
                <?php
                $image_display_path = BASE_URL . 'images/placeholder_product.jpg'; // Default placeholder
                if (!empty($product['image_url'])) {
                    $image_display_path = BASE_URL . htmlspecialchars($product['image_url']);
                }
                ?>
                <img src="<?php echo $image_display_path; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="main-product-image">
                <!-- Placeholder for additional gallery images later -->
            </div>

            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>

                <p class="product-price-detail">$<?php echo htmlspecialchars(number_format((float)$product['price'], 2)); ?></p>

                <?php if ($product['stock_quantity'] > 0): ?>
                    <p class="stock-status in-stock">In Stock (<?php echo $product['stock_quantity']; ?> available)</p>
                <?php else: ?>
                    <p class="stock-status out-of-stock">Out of Stock</p>
                <?php endif; ?>

                <div class="product-meta">
                    <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['product_sku']); ?></p>
                    <?php if (!empty($product['category_name']) && !empty($product['category_slug'])): ?>
                        <p><strong>Category:</strong> <a href="<?php echo BASE_URL; ?>category_page.php?slug=<?php echo htmlspecialchars($product['category_slug']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></p>
                    <?php elseif(!empty($product['category_name'])): ?>
                         <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($product['brand'])): ?>
                        <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?></p>
                    <?php endif; ?>
                     <?php if (!empty($product['color'])): ?>
                        <p><strong>Color:</strong> <?php echo htmlspecialchars($product['color']); ?></p>
                    <?php endif; ?>
                </div>

                <?php if(!empty($product['product_description'])): ?>
                <div class="product-description-full">
                    <h3>Product Description</h3>
                    <?php echo nl2br(htmlspecialchars($product['product_description'])); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($product['material']) || !empty($product['dimensions']) || !empty($product['weight_kg'])):?>
                <div class="product-specs">
                    <h3>Specifications</h3>
                    <ul>
                        <?php if (!empty($product['material'])): ?>
                            <li><strong>Material:</strong> <?php echo htmlspecialchars($product['material']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($product['dimensions'])): ?>
                            <li><strong>Dimensions:</strong> <?php echo htmlspecialchars($product['dimensions']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($product['weight_kg'])): ?>
                            <li><strong>Weight:</strong> <?php echo htmlspecialchars($product['weight_kg']); ?> kg</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="product-actions">
                    <form action="<?php echo BASE_URL; ?>cart_actions.php" method="POST"> <!-- Placeholder for cart action -->
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <label for="quantity" style="margin-right: 10px;">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="width: 60px; padding: 8px; margin-right:10px;">
                            <button type="submit" name="add_to_cart" class="btn add-to-cart-btn">Add to Cart</button>
                        <?php else: ?>
                            <button type="button" class="btn add-to-cart-btn" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </form>
                </div>
                 <?php if (isset($product['is_featured']) && $product['is_featured'] == 1): ?>
                    <p style="margin-top:15px; color: #007bff;"><em>This is a featured product!</em></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endif; ?>
</div>

<?php
require_once 'php/includes/footer.php';
?>
