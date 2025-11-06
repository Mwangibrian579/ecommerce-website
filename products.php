<?php
session_start();
require_once "config/database.php";
require_once "includes/Product.php";

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);

// Check if search was performed
$search_keywords = isset($_GET['search']) ? $_GET['search'] : '';
$products = $search_keywords ? $product->search($search_keywords) : $product->read();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - TechStore</title>
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

    <section id="products-header">
        <div class="container">
            <h1>Our Products</h1>
            <form method="GET" action="products.php" class="search-form">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_keywords); ?>">
                <button type="submit" class="button_1">Search</button>
                <?php if($search_keywords): ?>
                    <a href="products.php" class="button_1" style="background: #666; margin-left: 10px;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <section id="products-list">
        <div class="container">
            <?php if($search_keywords): ?>
                <h2>Search Results for "<?php echo htmlspecialchars($search_keywords); ?>"</h2>
            <?php endif; ?>
            
            <div class="products-grid">
                <?php 
                if($products->rowCount() > 0):
                    while ($row = $products->fetch(PDO::FETCH_ASSOC)): 
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $row['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $row['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo $row['name']; ?></h3>
                        <p class="product-category"><?php echo $row['category_name']; ?></p>
                        <p class="product-price">$<?php echo number_format($row['price'], 2); ?></p>
                        <p class="product-stock">In Stock: <?php echo $row['stock_quantity']; ?></p>
                        <a href="product.php?id=<?php echo $row['id']; ?>" class="button_1">View Details</a>
                    </div>
                </div>
                <?php 
                    endwhile;
                else: 
                ?>
                <div class="no-products">
                    <h3>No products found.</h3>
                    <p>Try adjusting your search terms or browse all products.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>