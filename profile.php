<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
require_once "includes/User.php";

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

$message = '';

if($_POST){
    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->address = $_POST['address'];
    $user->phone = $_POST['phone'];
    $user->email = $_POST['email'];

    if($user->update()){
        $message = "Profile updated successfully!";
    } else {
        $message = "Failed to update profile.";
    }
    
    // Re-read user data
    $user->readOne();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TechStore</title>
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
            <div class="profile-form">
                <h1>My Profile</h1>
                
                <?php if($message): ?>
                    <div class="success"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo $user->username; ?>" disabled>
                        <small>Username cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?php echo $user->email; ?>">
                    </div>

                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo $user->first_name; ?>">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo $user->last_name; ?>">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address"><?php echo $user->address; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo $user->phone; ?>">
                    </div>

                    <div class="form-group">
                        <label>Member Since</label>
                        <input type="text" value="<?php echo date('F j, Y', strtotime($user->created_at)); ?>" disabled>
                    </div>

                    <button type="submit" class="button_1">Update Profile</button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>