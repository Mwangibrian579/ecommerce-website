<?php
session_start();
require_once "config/database.php";
require_once "includes/User.php";

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

if($_POST){
    echo "<h3>Debug Information:</h3>";
    
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];
    
    echo "Username entered: " . $user->username . "<br>";
    echo "Password entered: " . $user->password . "<br>";
    
    // Check if user exists
    if($user->userExists()){
        echo "✅ User exists in database<br>";
        echo "User ID: " . $user->id . "<br>";
        echo "Stored password hash: " . $user->password . "<br>";
        
        // Debug password verification
        $password_match = password_verify($_POST['password'], $user->password);
        echo "Password verification result: " . ($password_match ? "TRUE" : "FALSE") . "<br>";
        
        if($password_match){
            echo "✅ Login successful!<br>";
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            echo "Session variables set.<br>";
        } else {
            echo "❌ Password verification failed.<br>";
            echo "Trying to verify: '" . $_POST['password'] . "' against stored hash.<br>";
        }
    } else {
        echo "❌ User does not exist.<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
</head>
<body>
    <h2>Debug Login Form</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Debug Login</button>
    </form>
</body>
</html>