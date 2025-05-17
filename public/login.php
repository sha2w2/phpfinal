<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../Models/user.php'; 

$db = new Database();  
$user = new User($db); 

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
            $user->logActivity("Failed login attempt for username: $username");
        }
    }
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("location: welcome.php");
    exit;
}
?>