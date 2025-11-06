<?php
session_start();
require_once "config/database.php";
require_once "includes/Product.php";

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$featured_products = $product->readFeatured();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechStore - Laptops & ICT Products</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><span class="highlight">G&S</span>Technologies</h1>
            </div>
<nav>
    <ul>
        <li class="current"><a href="index.php">Home</a></li>
        <li><a href="products.php">Products</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="profile.php">My Profile</a></li>
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

    <section id="showcase">
        <div class="container">
            <h1>Affordable Laptops & ICT Products</h1>
            <p>Find the best deals on laptops, accessories, and other ICT products</p>
        </div>
    </section>

    <section id="newsletter">
        <div class="container">
            <h1>Subscribe To Our Newsletter</h1>
            <form>
                <input type="email" placeholder="Enter Email...">
                <button type="submit" class="button_1">Subscribe</button>
            </form>
        </div>
    </section>

    <section id="featured-products">
        <div class="container">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <?php while ($row = $featured_products->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $row['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $row['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo $row['name']; ?></h3>
                        <p class="product-price">$<?php echo number_format($row['price'], 2); ?></p>
                        <p class="product-description"><?php echo substr($row['description'], 0, 100); ?>...</p>
                        <a href="product.php?id=<?php echo $row['id']; ?>" class="button_1">View Details</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section id="boxes">
        <div class="container">
            <div class="box">
                <img src="assets/images/laptop-icon.png" alt="Laptops">
                <h3>Laptops</h3>
                <p>Latest models from top brands</p>
            </div>
            <div class="box">
                <img src="assets/images/accessories-icon.png" alt="Accessories">
                <h3>Accessories</h3>
                <p>Keyboards, mice, bags and more</p>
            </div>
            <div class="box">
                <img src="assets/images/components-icon.png" alt="Components">
                <h3>Components</h3>
                <p>RAM, SSDs, processors and more</p>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>