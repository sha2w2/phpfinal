<?php
require_once 'db_connect.php';
require_once 'user.php';
require_once 'passwordmanager.php';

session_start();

$db = new database();
$user = new user($db);
$passwordmanager = new passwordmanager($db);

if (!$user->isloggedin()) {
    header("location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$passwordid = (int)$_GET['id'];
$encryptionkey = $_SESSION['encryption_key'];
$errors = [];
$success = '';

// Get password details
$conn = $db->getconnection();
$stmt = $conn->prepare("SELECT * FROM stored_passwords WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $passwordid, $_SESSION['id']);
$stmt->execute();
$password = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$password) {
    header("location: dashboard.php");
    exit;
}

// Decrypt password
$password['password'] = $passwordmanager->decryptpassword($password['encrypted_password'], $encryptionkey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servicename = trim($_POST['service_name']);
    $serviceusername = trim($_POST['service_username']);
    $newpassword = trim($_POST['password']);
    $url = trim($_POST['url']);
    $notes = trim($_POST['notes']);
    $category = trim($_POST['category']);

    // Validate
    if (empty($servicename)) $errors[] = "Service name is required";
    if (empty($newpassword)) $errors[] = "Password is required";

    if (empty($errors)) {
        if ($passwordmanager->updatepassword(
            $passwordid,
            $servicename,
            $serviceusername,
            $newpassword,
            $encryptionkey,
            $url,
            $notes,
            $category
        )) {
            $success = "Password updated successfully!";
            $password = [
                'service_name' => $servicename,
                'service_username' => $serviceusername,
                'password' => $newpassword,
                'url' => $url,
                'notes' => $notes,
                'category' => $category
            ];
        } else {
            $errors[] = "Failed to update password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Password</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        button { padding: 8px 15px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Edit Password</h1>
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
            <input type="text" name="service_name" required 
                   value="<?= htmlspecialchars($password['service_name']) ?>">
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="service_username" 
                   value="<?= htmlspecialchars($password['service_username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Password*</label>
            <input type="password" id="password" name="password" required 
                   value="<?= htmlspecialchars($password['password']) ?>">
            <button type="button" onclick="
                document.getElementById('password').type = 
                document.getElementById('password').type === 'password' ? 'text' : 'password'
            ">Show/Hide</button>
        </div>

        <div class="form-group">
            <label>URL</label>
            <input type="text" name="url" 
                   value="<?= htmlspecialchars($password['url'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category">
                <option value="">-- Select --</option>
                <option value="Social" <?= ($password['category'] ?? '') === 'Social' ? 'selected' : '' ?>>Social</option>
                <option value="Work" <?= ($password['category'] ?? '') === 'Work' ? 'selected' : '' ?>>Work</option>
                <option value="Finance" <?= ($password['category'] ?? '') === 'Finance' ? 'selected' : '' ?>>Finance</option>
                <option value="Personal" <?= ($password['category'] ?? '') === 'Personal' ? 'selected' : '' ?>>Personal</option>
            </select>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3"><?= htmlspecialchars($password['notes'] ?? '') ?></textarea>
        </div>

        <button type="submit">Update Password</button>
    </form>
</body>
</html>