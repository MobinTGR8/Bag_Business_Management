<?php
// cart.php
// This page displays the contents of the shopping cart.

require_once 'php/db_connect.php';         // For $mysqli
require_once 'php/includes/product_functions.php'; // For get_product_by_id (used by get_cart_items)
require_once 'php/includes/cart_functions.php';  // For cart logic

$cart_items = get_cart_items($mysqli);
$cart_subtotal = calculate_cart_subtotal($cart_items);
$cart_total_item_quantity = get_cart_total_item_quantity();

$page_title = "Your Shopping Cart";
require_once 'php/includes/header.php'; // Includes session_start() and BASE_URL
?>

<div class="cart-page-container container">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if (empty($cart_items)): ?>
        <div class="cart-empty">
            <p>Your cart is currently empty.</p>
            <p><a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary">Continue Shopping</a></p>
        </div>
    <?php else: ?>
        <!-- Main form not strictly needed if each item update is its own form and other actions are links -->
        <!-- <form action="<?php echo BASE_URL; ?>cart_handler.php" method="POST" id="cart-form"> -->
            <!-- <input type="hidden" name="action" value="update_multiple"> -->

            <div class="cart-items-table-container">
                <table class="cart-items-table">
                    <thead>
                        <tr>
                            <th colspan="2">Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): // $item keys are product_id, product_name, etc. ?>
                            <tr class="cart-item-row" data-product-id="<?php echo $item['product_id']; ?>">
                                <td class="product-image-cell">
                                    <?php
                                    $product_link = BASE_URL . 'product_detail.php?id=' . $item['product_id'];
                                    $image_display_path = BASE_URL . 'images/placeholder_product.jpg';
                                    if (!empty($item['image_url'])) {
                                        $image_display_path = BASE_URL . htmlspecialchars($item['image_url']);
                                    }
                                    ?>
                                    <a href="<?php echo $product_link; ?>">
                                        <img src="<?php echo $image_display_path; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="cart-item-image">
                                    </a>
                                </td>
                                <td class="product-name-cell">
                                    <a href="<?php echo $product_link; ?>"><?php echo htmlspecialchars($item['product_name']); ?></a>
                                    <?php if(!empty($item['product_sku'])): ?>
                                        <small>SKU: <?php echo htmlspecialchars($item['product_sku']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                        <p class="error-message stock-warning">Not enough stock! Max available: <?php echo $item['stock_quantity']; ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="product-price-cell">$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                                <td class="product-quantity-cell">
                                    <form action="<?php echo BASE_URL; ?>cart_handler.php" method="POST" class="update-quantity-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['stock_quantity']; ?>" class="quantity-input" aria-label="Quantity for <?php echo htmlspecialchars($item['product_name']); ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm update-cart-btn">Update</button>
                                    </form>
                                </td>
                                <td class="product-total-cell">$<?php echo htmlspecialchars(number_format($item['line_total'], 2)); ?></td>
                                <td class="product-remove-cell">
                                    <a href="<?php echo BASE_URL; ?>cart_handler.php?action=remove&product_id=<?php echo $item['product_id']; ?>" class="btn btn-danger btn-sm remove-item-btn" title="Remove <?php echo htmlspecialchars($item['product_name']); ?> from cart" onclick="return confirm('Are you sure you want to remove this item from your cart?');">&times;</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary">
                <div class="cart-totals">
                    <h3>Cart Total</h3>
                    <p>Subtotal: <span class="cart-subtotal-amount">$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></span></p>
                    <p><small>Shipping & taxes calculated at checkout.</small></p>
                    <p><strong>Total Items in Cart:</strong> <?php echo htmlspecialchars($cart_total_item_quantity); ?></p>
                </div>

                <div class="cart-actions">
                    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-secondary">Continue Shopping</a>
                    <a href="<?php echo BASE_URL; ?>cart_handler.php?action=clear" class="btn btn-warning clear-cart-btn" onclick="return confirm('Are you sure you want to clear your entire cart?');">Clear Cart</a>
                    <a href="<?php echo BASE_URL; ?>checkout.php" class="btn btn-primary btn-checkout <?php echo empty($cart_items) ? 'disabled' : ''; ?>">Proceed to Checkout</a>
                </div>
            </div>
        <!-- </form> --> <!-- End main cart form if used -->
    <?php endif; ?>
</div>

<?php
require_once 'php/includes/footer.php';
?>
