<?php
require_once 'db_connect.php';
require_once 'user.php';
require_once 'passwordmanager.php';

session_start();

$db = new Database();
$user = new User($db);
$passwordmanager = new PasswordManager($db);

// Check if user is logged in
if (!$user->isloggedin()) {
    header("location: login.php");
    exit;
}

// Check if password ID is provided
if (!isset($_GET['id'])) {
    header("location: dashboard.php");
    exit;
}

$passwordid = (int)$_GET['id'];

// Verify password ownership before deletion
$conn = $db->getconnection();
$stmt = $conn->prepare("SELECT id FROM stored_passwords WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $passwordid, $_SESSION['id']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $passwordmanager->deletepassword($passwordid);
    $_SESSION['message'] = "Password deleted successfully";
} else {
    $_SESSION['error'] = "Password not found or access denied";
}

header("location: dashboard.php");
exit;
?>