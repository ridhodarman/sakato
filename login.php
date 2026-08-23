<?php
session_start();
$users = [
 'kepala'=>'kepala','php'=>'php','sp'=>'sp','tu'=>'tu','pic'=>'pic'
];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';
if (isset($users[$username]) && hash_equals($users[$username], $password)) {
    $_SESSION['sakato_user'] = $username;
    header('Location: dashboard.php'); exit;
}
header('Location: index.php?error=1'); exit;
?>