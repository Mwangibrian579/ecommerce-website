<?php
session_start();
require_once "config/database.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - TechStore</title>
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
                    <li class="current"><a href="about.php">About</a></li>
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

    <section id="main">
        <div class="container">
            <article id="main-col">
                <h1 class="page-title">About TechStore</h1>
                <p>
                    Welcome to TechStore, your premier destination for laptops and ICT products in Kenya. 
                    We are committed to providing high-quality technology solutions at affordable prices, 
                    making cutting-edge technology accessible to everyone.
                </p>
                
                <h2>Our Mission</h2>
                <p>
                    To empower individuals and businesses with reliable, affordable, and innovative 
                    technology solutions that enhance productivity and connectivity across Kenya.
                </p>

                <h2>Our Vision</h2>
                <p>
                    To be the leading technology retailer in East Africa, known for quality products, 
                    exceptional customer service, and innovative solutions.
                </p>

                <h2>Why Choose TechStore?</h2>
                <div class="features">
                    <div class="feature">
                        <h3>🛡️ Quality Assurance</h3>
                        <p>All our products come with warranty and quality guarantees.</p>
                    </div>
                    <div class="feature">
                        <h3>🚚 Fast Delivery</h3>
                        <p>Quick and reliable delivery across major towns in Kenya.</p>
                    </div>
                    <div class="feature">
                        <h3>💳 Flexible Payments</h3>
                        <p>Multiple payment options including M-Pesa for your convenience.</p>
                    </div>
                    <div class="feature">
                        <h3>🔧 After-Sales Support</h3>
                        <p>Comprehensive technical support and customer service.</p>
                    </div>
                </div>

                <h2>Our Story</h2>
                <p>
                    Founded in 2023, TechStore started as a small venture with a big dream: to make 
                    quality technology accessible to every Kenyan. Today, we serve thousands of satisfied 
                    customers across the country, providing everything from budget-friendly laptops to 
                    high-performance computing solutions.
                </p>
            </article>

            <aside id="sidebar">
                <div class="dark">
                    <h3>What We Do</h3>
                    <p>
                        We specialize in providing comprehensive ICT solutions including:
                    </p>
                    <ul>
                        <li>Laptops & Computers</li>
                        <li>Computer Accessories</li>
                        <li>Networking Equipment</li>
                        <li>Software Solutions</li>
                        <li>IT Consultancy</li>
                        <li>Technical Support</li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>