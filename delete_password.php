<?php
require_once 'db_connect.php';
require_once 'User.php';
require_once 'PasswordManager.php';

session_start();

$db = new Database();
$user = new User($db);
$passwordManager = new PasswordManager($db);

if (!$user->isLoggedIn()) {
    header("location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$passwordId = (int)$_GET['id'];

// Verify the password belongs to the current user
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT id FROM stored_passwords WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $passwordId, $_SESSION['id']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $passwordManager->deletePassword($passwordId);
}

header("location: dashboard.php");
exit;
?>