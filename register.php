<?php
session_start();
require_once "config/database.php";
require_once "includes/User.php";

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$error = '';
$success = '';

if($_POST){
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->address = $_POST['address'];
    $user->phone = $_POST['phone'];

    // Check if user already exists
    if($user->userExists()){
        $error = "Username or email already exists.";
    } else {
        // Create user
        if($user->create()){
            $success = "Registration successful! You can now login.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TechStore</title>
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
                <h1>Create Account</h1>
                
                <?php if($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address"><?php echo isset($_POST['address']) ? $_POST['address'] : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
                    </div>

                    <button type="submit" class="button_1">Register</button>
                </form>

                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>