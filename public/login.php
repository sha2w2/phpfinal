<?php

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';


use App\Core\Database; 
use App\Models\User;   

$username = $password = "";
$username_err = $password_err = $login_err = "";


$db = new Database();  
$user = new User($db); 


if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {

    header("location: welcome.php");
    exit; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

   // Validate username input 
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter your username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Validate password input
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Attempt to log in if there are no input errors
    if (empty($username_err) && empty($password_err)) {
        // Call the login method from your User model
        if ($user->login($username, $password)) {
            // Login successful: Set session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username; // Store username in session for welcome page

            // Redirect user 
            $redirect = $_SESSION['redirect_url'] ?? 'welcome.php';
            unset($_SESSION['redirect_url']); 
            header("location: " . $redirect);
            exit;
        } else {
          
            $login_err = "Invalid username or password.";
            
            $user->logActivity("Failed login attempt for username: $username");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php
    if(!empty($login_err)){
        echo '<div>' . htmlspecialchars($login_err) . '</div>';
    }        
    ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div>
            <label for="username">Username</label><br>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>">
            <span><?php echo htmlspecialchars($username_err); ?></span>
        </div>    
        <div>
            <label for="password">Password</label><br>
            <input type="password" name="password" id="password">
            <span><?php echo htmlspecialchars($password_err); ?></span>
        </div>
        <div>
            <input type="submit" value="Login">
        </div>
        <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
    </form>
</body>
</html>