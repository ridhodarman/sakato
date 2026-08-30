<?php
session_start();

// Panggil file koneksi database
require_once 'inc/koneksi.php';

// Cek apakah request menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil input form
$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';

// Validasi input tidak boleh kosong
if (empty($username) || empty($password)) {
    header('Location: index.php?error=1');
    exit;
}

// Enkripsi password input dengan MD5
$password_md5 = md5($password);

// Cari akun berdasarkan username dan password
$stmt = $koneksi->prepare("SELECT id, username, nama FROM akun_sakato WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password_md5);
$stmt->execute();
$result = $stmt->get_result();

// Jika akun ditemukan
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Set session login
    $_SESSION['sakato_login'] = true;
    $_SESSION['id_user']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['nama']         = $user['nama'];

    $stmt->close();
    header('Location: dashboard.php');
    exit;
}

// Jika username atau password salah
$stmt->close();
header('Location: index.php?error=1');
exit;
?>