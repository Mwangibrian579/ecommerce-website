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
$orders = $order->getUserOrders($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - TechStore</title>
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
                    <li class="current"><a href="orders.php">My Orders</a></li>
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
            <h1>My Orders</h1>
            
            <?php if($orders->rowCount() > 0): ?>
                <div class="orders-list">
                    <?php while ($order_row = $orders->fetch(PDO::FETCH_ASSOC)): 
                        $order->id = $order_row['id'];
                        $order_items = $order->getOrderItems();
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo $order_row['order_number']; ?></h3>
                                <p class="order-date">Placed on <?php echo date('F j, Y', strtotime($order_row['created_at'])); ?></p>
                            </div>
                            <div class="order-status">
                                <span class="status-badge status-<?php echo $order_row['status']; ?>">
                                    <?php echo ucfirst($order_row['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="order-items-preview">
                                <?php 
                                $item_count = 0;
                                while ($item = $order_items->fetch(PDO::FETCH_ASSOC)): 
                                    $item_count++;
                                    if ($item_count <= 3): // Show only first 3 items
                                ?>
                                <div class="order-item-preview">
                                    <img src="<?php echo $item['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                                    <span><?php echo $item['name']; ?> (x<?php echo $item['quantity']; ?>)</span>
                                </div>
                                <?php 
                                    endif;
                                endwhile; 
                                
                                // Reset and count total items
                                $order_items = $order->getOrderItems();
                                $total_items = $order_items->rowCount();
                                if ($total_items > 3): 
                                ?>
                                <div class="more-items">+<?php echo $total_items - 3; ?> more items</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-summary">
                                <div class="order-total">
                                    <strong>Total: $<?php echo number_format($order_row['total_amount'], 2); ?></strong>
                                </div>
                                <div class="order-actions">
                                    <a href="order_details.php?order_id=<?php echo $order_row['id']; ?>" class="button_1">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <h3>You haven't placed any orders yet</h3>
                    <p>Start shopping to see your orders here!</p>
                    <a href="products.php" class="button_1">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>