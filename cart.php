<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
require_once "includes/Cart.php";
require_once "includes/Product.php";

$database = new Database();
$db = $database->getConnection();

$cart = new Cart($db);
$cart->user_id = $_SESSION['user_id'];

$message = '';

// Handle cart actions
if($_POST){
    if(isset($_POST['update_quantity'])) {
        $cart->product_id = $_POST['product_id'];
        $cart->quantity = $_POST['quantity'];
        if($cart->updateQuantity()) {
            $message = "Cart updated successfully!";
        }
    } elseif(isset($_POST['remove_item'])) {
        $cart->product_id = $_POST['product_id'];
        if($cart->removeFromCart()) {
            $message = "Item removed from cart!";
        }
    } elseif(isset($_POST['clear_cart'])) {
        if($cart->clearCart()) {
            $message = "Cart cleared!";
        }
    } elseif(isset($_POST['add_to_cart'])) {
        $cart->product_id = $_POST['product_id'];
        $cart->quantity = $_POST['quantity'];
        if($cart->addToCart()) {
            $message = "Item added to cart!";
        } else {
            $message = "Failed to add item to cart.";
        }
    }
}

// Get cart items
$cart_items = $cart->getCartItems();
$cart_summary = $cart->getCartSummary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - TechStore</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><span class="highlight">Tech</span>Store</h1>
            </div>
<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="products.php">Products</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li class="current"><a href="profile.php">My Profile</a></li>
            <li><a href="orders.php">My Orders</a></li>
            <li><a href="cart.php">Cart 
                <?php 
                if(isset($_SESSION['user_id'])) {
                    require_once "config/database.php";
                    require_once "includes/Cart.php";
                    $database = new Database();
                    $db = $database->getConnection();
                    $cart = new Cart($db);
                    $cart->user_id = $_SESSION['user_id'];
                    $summary = $cart->getCartSummary();
                    if($summary['total_items'] > 0) {
                        echo "(" . $summary['total_items'] . ")";
                    }
                }
                ?>
            </a></li>
            <li><a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
        </div>
    </header>

    <section id="main">
        <div class="container">
            <h1>Shopping Cart</h1>
            
            <?php if($message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if($cart_items->rowCount() > 0): ?>
                <div class="cart-summary">
                    <p>Total Items: <strong><?php echo $cart_summary['total_items']; ?></strong></p>
                    <p>Total Price: <strong>$<?php echo number_format($cart_summary['total_price'], 2); ?></strong></p>
                </div>

                <div class="cart-items">
                    <?php while ($item = $cart_items->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="<?php echo $item['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                        </div>
                        <div class="cart-item-details">
                            <h3><?php echo $item['name']; ?></h3>
                            <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                            <p class="stock">In Stock: <?php echo $item['stock_quantity']; ?></p>
                        </div>
                        <div class="cart-item-actions">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <label>Qty:</label>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>">
                                <button type="submit" name="update_quantity" class="button_1">Update</button>
                            </form>
                            <form method="POST" class="remove-form">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" name="remove_item" class="button_1" style="background: #dc3545;">Remove</button>
                            </form>
                        </div>
                        <div class="cart-item-total">
                            <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="cart-actions">
                    <form method="POST">
                        <button type="submit" name="clear_cart" class="button_1" style="background: #6c757d;">Clear Cart</button>
                    </form>
                    <a href="checkout.php" class="button_1" style="background: #28a745;">Proceed to Checkout</a>
                </div>

            <?php else: ?>
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Browse our <a href="products.php">products</a> to add items to your cart.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>