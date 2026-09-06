<?php
session_start();

// Panggil file koneksi database
require_once '../inc/koneksi.php';

// Cek apakah request menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil input form
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input tidak boleh kosong
if ($username === '' || $password === '') {
    header('Location: index.php?error=1');
    exit;
}

// Cari akun berdasarkan username saja
$stmt = $koneksi->prepare("
    SELECT id, username, nama, password, update_berkas
    FROM akun_sakato
    WHERE username = ?
    LIMIT 1
");

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

// Jika akun ditemukan
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password dengan hash yang tersimpan di database
    if (password_verify($password, $user['password'])) {

        // Regenerasi session ID untuk mencegah session fixation
        session_regenerate_id(true);

        // Set session login
        $_SESSION['sakato_login'] = true;
        $_SESSION['id_user']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama']         = $user['nama'];
        $_SESSION['update_berkas']= (int)$user['update_berkas'];

        $stmt->close();

        header('Location: ../dashboard.php');
        exit;
    }
}

// Username atau password salah
$stmt->close();

header('Location: ../index.php?error=1');
exit;
?>