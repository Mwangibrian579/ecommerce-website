<?php
session_start();
require_once "config/database.php";
require_once "includes/User.php";

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$error = '';

if($_POST){
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];

    if($user->login()){
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        
        // Debug output
        error_log("Login successful for user: " . $user->username);
        error_log("Session user_id: " . $_SESSION['user_id']);
        error_log("Session username: " . $_SESSION['username']);
        
        // Redirect to homepage
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}

// Debug current session
error_log("Current session data: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TechStore</title>
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

    <section id="main">
        <div class="container">
            <div class="auth-form">
                <h1>Login to Your Account</h1>
                
                <?php if($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label>Username or Email</label>
                        <input type="text" name="username" required value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>

                    <button type="submit" class="button_1">Login</button>
                </form>

                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>