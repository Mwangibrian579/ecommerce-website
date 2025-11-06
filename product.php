<?php
session_start();
require_once "config/database.php";
require_once "includes/Product.php";

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);

// Get product ID from URL
$product->id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Product ID not found.');

// Read the product details
if($product->readOne()) {
    // Product exists
} else {
    // Product doesn't exist
    die('ERROR: Product not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product->name; ?> - TechStore</title>
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
            <li><a href="profile.php">My Profile</a></li>
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

    <section id="product-detail">
        <div class="container">
            <div class="product-detail-container">
                <div class="product-image-large">
                    <img src="<?php echo $product->image_url ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $product->name; ?>">
                </div>
                <div class="product-info-detail">
                    <h1><?php echo $product->name; ?></h1>
                    <p class="product-category">Category: <?php echo $product->category_name; ?></p>
                    <p class="product-price">$<?php echo number_format($product->price, 2); ?></p>
                    <p class="product-stock">In Stock: <?php echo $product->stock_quantity; ?> units</p>
                    
                    <div class="product-description">
                        <h3>Description</h3>
                        <p><?php echo $product->description; ?></p>
                    </div>

                    <?php if($product->specifications): ?>
                    <div class="product-specifications">
                        <h3>Specifications</h3>
                        <?php 
                        $specs = json_decode($product->specifications, true);
                        if($specs): 
                        ?>
                        <ul>
                            <?php foreach($specs as $key => $value): ?>
                            <li><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo $value; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

<div class="product-actions">
    <?php if(isset($_SESSION['user_id'])): ?>
        <form method="POST" action="cart.php">
            <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product->stock_quantity; ?>">
            <button type="submit" class="button_1" name="add_to_cart">Add to Cart</button>
        </form>
    <?php else: ?>
        <p><a href="login.php" class="button_1">Login to Add to Cart</a></p>
    <?php endif; ?>
</div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>