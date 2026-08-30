<?php

session_start();

// Pastikan hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil data dari form
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Akun default
$valid_username = 'admin';
$valid_password = 'admin';

// Cek username dan password
if (
    hash_equals($valid_username, $username) &&
    hash_equals($valid_password, $password)
) {

    // Regenerasi session ID untuk keamanan
    session_regenerate_id(true);

    $_SESSION['sakato_login'] = true;
    $_SESSION['sakato_username'] = $username;

    // Login berhasil
    header('Location: ../dashboard.php');
    exit;

}

// Login gagal
header('Location: ../index.php?error=1');
exit;