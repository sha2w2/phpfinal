<?php
require_once 'db_connect.php';
require_once 'user.php';
require_once 'passwordmanager.php';

session_start();

$db = new Database();
$user = new User($db);
$passwordmanager = new PasswordManager($db);

if (!$user->isloggedin()) {
    header("location: login.php");
    exit;
}

$encryptionkey = $_SESSION['encryption_key'];
$passwords = $passwordmanager->getpasswords($_SESSION['id'], $encryptionkey);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Dashboard</title>
    <style>
        body { font-family: Arial; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .password-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
        .password-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; }
        .password-actions { margin-top: 10px; }
        button, a.button { padding: 5px 10px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>My Passwords</h1>
        <div>
            <a href="add_password.php" class="button">Add New</a>
            <a href="logout.php" class="button">Logout</a>
        </div>
    </div>

    <div class="password-list">
        <?php foreach ($passwords as $password): ?>
            <div class="password-card">
                <h3><?= htmlspecialchars($password['service_name']) ?></h3>
                <p>User: <?= htmlspecialchars($password['service_username']) ?></p>
                <p>
                    Pass: <span class="password-field">••••••••</span>
                    <span class="actual-password" style="display:none">
                        <?= htmlspecialchars($password['password']) ?>
                    </span>
                    <button class="toggle-password">Show</button>
                </p>
                <?php if ($password['url']): ?>
                    <p>URL: <a href="<?= htmlspecialchars($password['url']) ?>" target="_blank">Link</a></p>
                <?php endif; ?>
                <div class="password-actions">
                    <a href="edit_password.php?id=<?= $password['id'] ?>" class="button">Edit</a>
                    <a href="delete_password.php?id=<?= $password['id'] ?>" class="button" 
                       onclick="return confirm('Delete this password?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const passField = this.parentElement.querySelector('.password-field');
                const actualPass = this.parentElement.querySelector('.actual-password');
                
                if (passField.style.display === 'none') {
                    passField.style.display = 'inline';
                    actualPass.style.display = 'none';
                    this.textContent = 'Show';
                } else {
                    passField.style.display = 'none';
                    actualPass.style.display = 'inline';
                    this.textContent = 'Hide';
                }
            });
        });
    </script>
</body>
</html>