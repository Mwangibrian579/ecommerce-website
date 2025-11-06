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
    <title>Order Confirmation - TechStore</title>
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
                    <li><a href="orders.php">My Orders</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="main">
        <div class="container">
            <div class="confirmation-container">
                <div class="confirmation-header success">
                    <h1>🎉 Order Placed Successfully!</h1>
                    <p>Thank you for your purchase. Your order has been received.</p>
                </div>

                <div class="order-details">
                    <h2>Order Details</h2>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>Order Number:</strong>
                            <span><?php echo $order->order_number; ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Order Date:</strong>
                            <span><?php echo date('F j, Y g:i A', strtotime($order->created_at)); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Total Amount:</strong>
                            <span>$<?php echo number_format($order->total_amount, 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Payment Method:</strong>
                            <span><?php echo strtoupper($order->payment_method); ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Status:</strong>
                            <span class="status-<?php echo $order->status; ?>">
                                <?php echo ucfirst($order->status); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if($order->payment_method == 'mpesa' && $order->status == 'pending'): ?>
                <div class="payment-pending">
                    <h3>🔄 Payment Processing</h3>
                    <p>We've sent an M-Pesa prompt to <strong><?php echo $order->phone_number; ?></strong>.</p>
                    <p>Please check your phone and enter your M-Pesa PIN to complete the payment.</p>
                    <p>Your order status will update automatically once payment is confirmed.</p>
                </div>
                <?php endif; ?>

                <div class="order-items-confirm">
                    <h3>Order Items</h3>
                    <?php while ($item = $order_items->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="confirm-item">
                        <img src="<?php echo $item['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                        <div class="confirm-details">
                            <h4><?php echo $item['name']; ?></h4>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div class="confirm-price">
                            $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="confirmation-actions">
                    <a href="orders.php" class="button_1">View All Orders</a>
                    <a href="products.php" class="button_1" style="background: #6c757d;">Continue Shopping</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>