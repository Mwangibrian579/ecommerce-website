<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
require_once "includes/Order.php";

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);
$order->id = isset($_GET['order_id']) ? $_GET['order_id'] : 0;

// Verify order belongs to user
if(!$order->readOne() || $order->user_id != $_SESSION['user_id']) {
    header("Location: orders.php");
    exit();
}

$order_items = $order->getOrderItems();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - TechStore</title>
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
                </ul>
            </nav>
        </div>
    </header>

    <section id="main">
        <div class="container">
            <div class="order-details-container">
                <div class="order-header-details">
                    <h1>Order #<?php echo $order->order_number; ?></h1>
                    <div class="order-meta">
                        <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order->created_at)); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo $order->status; ?>"><?php echo ucfirst($order->status); ?></span></p>
                        <p><strong>Payment Method:</strong> <?php echo strtoupper($order->payment_method); ?></p>
                        <?php if($order->mpesa_receipt_number): ?>
                        <p><strong>M-Pesa Receipt:</strong> <?php echo $order->mpesa_receipt_number; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-content">
                    <div class="order-items-section">
                        <h2>Order Items</h2>
                        <div class="order-items-detailed">
                            <?php while ($item = $order_items->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="order-item-detailed">
                                <div class="item-image">
                                    <img src="<?php echo $item['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                                </div>
                                <div class="item-info">
                                    <h4><?php echo $item['name']; ?></h4>
                                    <p class="item-price">$<?php echo number_format($item['price'], 2); ?> each</p>
                                </div>
                                <div class="item-quantity">
                                    <span>Quantity: <?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="item-total">
                                    <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="order-summary-section">
                        <h2>Order Summary</h2>
                        <div class="summary-details">
                            <div class="summary-line">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($order->total_amount, 2); ?></span>
                            </div>
                            <div class="summary-line">
                                <span>Shipping:</span>
                                <span>$0.00</span>
                            </div>
                            <div class="summary-line">
                                <span>Tax:</span>
                                <span>$0.00</span>
                            </div>
                            <div class="summary-line grand-total">
                                <span><strong>Total:</strong></span>
                                <span><strong>$<?php echo number_format($order->total_amount, 2); ?></strong></span>
                            </div>
                        </div>

                        <div class="shipping-info">
                            <h3>Shipping Address</h3>
                            <p><?php echo nl2br(htmlspecialchars($order->shipping_address)); ?></p>
                        </div>

                        <div class="order-actions">
                            <a href="orders.php" class="button_1">Back to Orders</a>
                            <a href="products.php" class="button_1" style="background: #6c757d;">Continue Shopping</a>
                        </div>
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