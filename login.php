<?php
session_start();
require_once 'db_connect.php';
require_once 'user.php';

$db = new database();
$user = new user($db);

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if (empty($username_err) && empty($password_err)) {
        if ($user->login($username, $password)) {
            $redirect = $_SESSION['redirect_url'] ?? 'welcome.php';
            unset($_SESSION['redirect_url']);
            header("location: " . $redirect);
            exit;
        } else {
            $login_err = "Invalid username or password.";
            $user->logactivity("Failed login attempt for username: $username");
        }
    }
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("location: welcome.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .wrapper { width: 300px; margin: 50px auto; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 8px; }
        .btn { width: 100%; padding: 8px; }
        .help-block { color: #777; font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php if (!empty($login_err)) echo '<p>' . $login_err . '</p>'; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <p>Don't have an account? <a href="signup.php">Sign up now</a>.</p>
            <p><a href="forgot_password.php">Forgot password?</a></p>
        </form>
    </div>
</body>
</html>