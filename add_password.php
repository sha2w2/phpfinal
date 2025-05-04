<?php
require_once 'db_connect.php';
require_once 'user.php';
require_once 'passwordmanager.php';
require_once 'passwordgenerator.php';

session_start();

$db = new database();
$user = new user($db);
$passwordmanager = new passwordmanager($db);
$passwordgenerator = new passwordgenerator();

if (!$user->isloggedin()) {
    header("location: login.php");
    exit;
}

$encryptionkey = $_SESSION['encryption_key'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servicename = trim($_POST['service_name']);
    $serviceusername = trim($_POST['service_username']);
    $password = trim($_POST['password']);
    $url = trim($_POST['url']);
    $notes = trim($_POST['notes']);
    $category = trim($_POST['category']);

    if (empty($servicename)) $errors[] = "Service name required";
    if (empty($password)) $errors[] = "Password required";

    if (empty($errors)) {
        if ($passwordmanager->addpassword(
            $_SESSION['id'],
            $servicename,
            $serviceusername,
            $password,
            $encryptionkey,
            $url,
            $notes,
            $category
        )) {
            $success = "Password added!";
            $servicename = $serviceusername = $url = $notes = $category = '';
        } else {
            $errors[] = "Failed to add password";
        }
    }
}

// Generate sample password
$samplepassword = $passwordgenerator->generate(12, true, true, true, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Password</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; }
        .error { color: red; }
        .success { color: green; }
        button { padding: 8px 15px; margin-top: 10px; }
        .password-row { display: flex; gap: 10px; }
        .password-row input { flex: 1; }
    </style>
</head>
<body>
    <h1>Add New Password</h1>
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
            <label>Service Name*</label>
            <input type="text" name="service_name" required value="<?= htmlspecialchars($servicename ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="service_username" value="<?= htmlspecialchars($serviceusername ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Password*</label>
            <div class="password-row">
                <input type="password" id="password" name="password" required>
                <button type="button" onclick="document.getElementById('password').value='<?= $samplepassword ?>'">Generate</button>
                <button type="button" onclick="
                    const p = document.getElementById('password');
                    p.type = p.type === 'password' ? 'text' : 'password'
                ">Show</button>
            </div>
        </div>

        <div class="form-group">
            <label>URL</label>
            <input type="text" name="url" value="<?= htmlspecialchars($url ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category">
                <option value="">-- Select --</option>
                <option value="Social" <?= ($category ?? '') === 'Social' ? 'selected' : '' ?>>Social</option>
                <option value="Work" <?= ($category ?? '') === 'Work' ? 'selected' : '' ?>>Work</option>
                <option value="Finance" <?= ($category ?? '') === 'Finance' ? 'selected' : '' ?>>Finance</option>
                <option value="Personal" <?= ($category ?? '') === 'Personal' ? 'selected' : '' ?>>Personal</option>
            </select>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3"><?= htmlspecialchars($notes ?? '') ?></textarea>
        </div>

        <button type="submit">Save Password</button>
    </form>
</body>
</html>