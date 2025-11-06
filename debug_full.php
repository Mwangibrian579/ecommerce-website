<?php
session_start();
echo "<h2>Full Session Debug</h2>";

// Test session
echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Test database connection
require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();
if($db) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
}

// Test user class
require_once "includes/User.php";
$user = new User($db);
echo "✅ User class loaded<br>";

if($_POST){
    echo "<h3>Login Attempt:</h3>";
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];
    
    echo "Username: " . $user->username . "<br>";
    echo "Password: " . $user->password . "<br>";
    
    if($user->login()){
        echo "✅ Login verification successful<br>";
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        
        echo "Session after login: <pre>" . print_r($_SESSION, true) . "</pre>";
        
        // Test redirect
        echo "<script>alert('Login successful! Redirecting...'); window.location.href = 'index.php';</script>";
        // header("Location: index.php");
        // exit();
    } else {
        echo "❌ Login verification failed<br>";
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Test Login</button>
</form>

<hr>
<a href="index.php">Go to Homepage to check session</a>