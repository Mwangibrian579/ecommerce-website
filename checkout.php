<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
require_once "includes/Cart.php";
require_once "includes/User.php";
require_once "includes/Order.php";
require_once "includes/MpesaService.php";

$database = new Database();
$db = $database->getConnection();

$cart = new Cart($db);
$cart->user_id = $_SESSION['user_id'];

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get cart items and summary
$cart_items = $cart->getCartItems();
$cart_summary = $cart->getCartSummary();

// Check if cart is empty
if($cart_items->rowCount() == 0) {
    header("Location: cart.php");
    exit();
}

$error = '';
$success = '';

// Process checkout
if($_POST && isset($_POST['place_order'])) {
    $phone = '254' . substr(preg_replace('/[^0-9]/', '', $_POST['phone']), -9);
    $shipping_address = $_POST['shipping_address'];
    $payment_method = $_POST['payment_method'];
    
    // Validate phone number (Kenyan format)
    if (strlen($phone) != 12 || !preg_match('/^254[0-9]{9}$/', $phone)) {
        $error = "Please enter a valid Kenyan phone number (e.g., 07... or 2547...)";
    } else {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Create order
            $order = new Order($db);
            $order->user_id = $_SESSION['user_id'];
            $order->total_amount = $cart_summary['total_price'];
            $order->payment_method = $payment_method;
            $order->phone_number = $phone;
            $order->shipping_address = $shipping_address;
            
            if($order->create()) {
                // Add order items
                $cart_items = $cart->getCartItems(); // Refresh cart items
                while ($item = $cart_items->fetch(PDO::FETCH_ASSOC)) {
                    $order->addOrderItem($item['product_id'], $item['quantity'], $item['price']);
                }
                
                if($payment_method == 'mpesa') {
                    // Initialize M-Pesa service
                    $mpesa = new MpesaService();
                    
if($payment_method == 'mpesa') {
    // Initialize M-Pesa service
    $mpesa = new MpesaService();
    
    // For development, use simulation. In production, use actual STK Push
    $stkResponse = $mpesa->simulateSTKPush($phone, $cart_summary['total_price'], $order->order_number);
    
    // Change to this for real M-Pesa testing:
    // $stkResponse = $mpesa->stkPush($phone, $cart_summary['total_price'], $order->order_number, 'Payment for order ' . $order->order_number);
    
    if($stkResponse['success']) {
        // Store payment details
        $payment_query = "INSERT INTO payments 
                         SET order_id=:order_id, phone_number=:phone_number, 
                             amount=:amount, merchant_request_id=:merchant_request_id,
                             checkout_request_id=:checkout_request_id";
        $stmt = $db->prepare($payment_query);
        $stmt->bindParam(":order_id", $order->id);
        $stmt->bindParam(":phone_number", $phone);
        $stmt->bindParam(":amount", $cart_summary['total_price']);
        $stmt->bindParam(":merchant_request_id", $stkResponse['MerchantRequestID']);
        $stmt->bindParam(":checkout_request_id", $stkResponse['CheckoutRequestID']);
        $stmt->execute();
        
        // Clear cart
        $cart->clearCart();
        
        $db->commit();
        
        // Redirect to order confirmation
        header("Location: order_confirmation.php?order_id=" . $order->id);
        exit();
    } else {
        $error = "M-Pesa request failed: " . $stkResponse['message'];
        $db->rollBack();
    }
}
                } else {
                    // Cash on delivery
                    $cart->clearCart();
                    $db->commit();
                    header("Location: order_confirmation.php?order_id=" . $order->id);
                    exit();
                }
            } else {
                $error = "Failed to create order. Please try again.";
                $db->rollBack();
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TechStore</title>
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
                    <li><a href="cart.php">Cart (<?php echo $cart_summary['total_items']; ?>)</a></li>
                    <li><a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="main">
        <div class="container">
            <h1>Checkout</h1>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="checkout-container">
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="order-items">
                        <?php 
                        $cart_items = $cart->getCartItems(); // Refresh for display
                        while ($item = $cart_items->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                        <div class="checkout-item">
                            <img src="<?php echo $item['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                            <div class="item-details">
                                <h4><?php echo $item['name']; ?></h4>
                                <p>Qty: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="item-total">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="order-totals">
                        <div class="total-line">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($cart_summary['total_price'], 2); ?></span>
                        </div>
                        <div class="total-line">
                            <span>Shipping:</span>
                            <span>$0.00</span>
                        </div>
                        <div class="total-line grand-total">
                            <span><strong>Total:</strong></span>
                            <span><strong>$<?php echo number_format($cart_summary['total_price'], 2); ?></strong></span>
                        </div>
                    </div>
                </div>

                <div class="checkout-form">
                    <h2>Shipping & Payment</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label>Phone Number (for M-Pesa) *</label>
                            <input type="tel" name="phone" required 
                                   value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ($user->phone ?: ''); ?>"
                                   placeholder="e.g., 0712345678 or 254712345678">
                            <small>Enter your Safaricom number for M-Pesa payment</small>
                        </div>

                        <div class="form-group">
                            <label>Shipping Address *</label>
                            <textarea name="shipping_address" required placeholder="Enter your complete shipping address"><?php echo isset($_POST['shipping_address']) ? $_POST['shipping_address'] : ($user->address ?: ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Payment Method *</label>
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="mpesa" checked>
                                    <span class="payment-method">
                                        <strong>M-Pesa</strong>
                                        <small>Pay via M-Pesa STK Push</small>
                                    </span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="cash_on_delivery">
                                    <span class="payment-method">
                                        <strong>Cash on Delivery</strong>
                                        <small>Pay when you receive your order</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="place_order" class="button_1" style="background: #28a745; width: 100%; padding: 15px; font-size: 18px;">
                                Complete Order - $<?php echo number_format($cart_summary['total_price'], 2); ?>
                            </button>
                        </div>

                        <div class="payment-notice">
                            <p><strong>For M-Pesa Payments:</strong></p>
                            <p>You will receive an M-Pesa prompt on your phone to complete the payment.</p>
                            <p>Ensure your phone is nearby and you have sufficient funds.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>