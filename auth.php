<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// echo "<pre>"; print_r($_SESSION); echo "</pre>"; die();

if (!isset($_SESSION['sakato_login']) || $_SESSION['sakato_login'] !== true) {
    header("Location: index.php");
    exit;
}


require_once 'inc/koneksi.php';
?>