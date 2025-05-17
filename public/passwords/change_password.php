<?php
require_once 'db_connect.php';
require_once 'user.php';

session_start();

$db = new Database();
$user = new User($db);

if (!$user->isloggedin()) {
    header("location: login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentpassword = $_POST['current_password'];
    $newpassword = $_POST['new_password'];
    $confirmpassword = $_POST['confirm_password'];

    // Validate inputs
    if (empty($currentpassword)) {
        $errors[] = "Current password is required";
    }
    if (empty($newpassword)) {
        $errors[] = "New password is required";
    } elseif (strlen($newpassword) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    if ($newpassword !== $confirmpassword) {
        $errors[] = "Passwords don't match";
    }

    if (empty($errors)) {
        if ($user->changepassword($currentpassword, $newpassword)) {
            $success = "Password changed successfully!";
        } else {
            $errors[] = "Current password incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <style>
        body { font-family: Arial; max-width: 500px; margin: 20px auto; padding: 0 20px; }
        .form-group { margin-bottom: 15px; }
        input { width: 100%; padding: 8px; margin-top: 5px; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        button { padding: 8px 15px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Change Password</h1>
    <a href="dashboard.php">← Back</a>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success"><p><?= htmlspecialchars($success) ?></p></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Current Password*</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>New Password*</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label>Confirm New Password*</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit">Change Password</button>
    </form>
</body>
</html>