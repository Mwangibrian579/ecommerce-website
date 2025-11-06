<?php
// SUPER SIMPLE LOGIN TEST
session_start();

if(isset($_POST['login'])) {
    $_SESSION['user_id'] = 2;
    $_SESSION['username'] = 'jones';
    echo "SESSION SET! Check <a href='index.php'>homepage</a>";
    exit;
}

if(isset($_POST['logout'])) {
    session_destroy();
    echo "SESSION DESTROYED! Check <a href='index.php'>homepage</a>";
    exit;
}
?>

<h1>Simple Session Test</h1>
<form method="POST">
    <button type="submit" name="login">Set Session (Login)</button>
    <button type="submit" name="logout">Destroy Session (Logout)</button>
</form>
<p><a href="index.php">Check Homepage</a></p>