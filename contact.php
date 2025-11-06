<?php
session_start();
require_once "config/database.php";

$success = '';
$error = '';

if($_POST && isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // Basic validation
    if(empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // In a real application, you would send an email here
        // For now, we'll just show a success message
        $success = "Thank you for your message, $name! We'll get back to you within 24 hours.";
        
        // Clear form
        $_POST = array();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - TechStore</title>
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
                    <li class="current"><a href="contact.php">Contact</a></li>
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
                <h1 class="page-title">Contact Us</h1>
                
                <?php if($success): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form class="contact-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Subject *</label>
                        <input type="text" name="subject" value="<?php echo isset($_POST['subject']) ? $_POST['subject'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" rows="6" required><?php echo isset($_POST['message']) ? $_POST['message'] : ''; ?></textarea>
                    </div>

                    <button type="submit" name="submit" class="button_1">Send Message</button>
                </form>
            </article>

            <aside id="sidebar">
                <div class="dark">
                    <h3>Get In Touch</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <h4>📍 Address</h4>
                            <p>TechStore Building<br>Nairobi, Kenya</p>
                        </div>
                        
                        <div class="contact-item">
                            <h4>📞 Phone</h4>
                            <p>+254 700 000 000<br>+254 711 111 111</p>
                        </div>
                        
                        <div class="contact-item">
                            <h4>✉️ Email</h4>
                            <p>info@techstore.co.ke<br>support@techstore.co.ke</p>
                        </div>
                        
                        <div class="contact-item">
                            <h4>🕒 Business Hours</h4>
                            <p>Monday - Friday: 8:00 AM - 6:00 PM<br>
                               Saturday: 9:00 AM - 4:00 PM<br>
                               Sunday: Closed</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>