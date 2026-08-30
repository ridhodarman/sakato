<?php
session_start();

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['sakato_login']) && $_SESSION['sakato_login'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SAKATO V2 - Login</title>

  <link rel="stylesheet" href="assets/style.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

  <div id="login" class="login">
    <div class="loginbox">

      <div class="hero">
        <h1>SAKATO</h1>

        <h2>Sistem Akselerasi Kolaboratif Tunggakan Online</h2>

        <p>
          Prototype V2 untuk monitoring, pencegahan, percepatan, dan
          eskalasi penyelesaian berkas layanan pertanahan secara kolaboratif.
        </p>

        <div class="f">
          <b>Early Warning</b><br>
          Deteksi otomatis berkas mendekati dan melewati SLA.
        </div>

        <div class="f">
          <b>Dashboard Pimpinan</b><br>
          Prioritas intervensi, tren risiko, dan kinerja unit.
        </div>

        <div class="f">
          <b>Kolaborasi & Eskalasi</b><br>
          Jejak tindak lanjut lintas seksi dan dukungan pimpinan.
        </div>
      </div>

      <div class="form">

        <h2>Masuk ke SAKATO V2</h2>

        <div class="muted">
          Gunakan akun untuk masuk ke sistem.
        </div>

        <form action="act/login.php" method="POST">

          <div class="field">
            <label for="user">Username</label>
            <input
              type="text"
              id="user"
              name="username"
              autocomplete="username"
              required
            >
          </div>

          <div class="field">
            <label for="pass">Password</label>
            <input
              type="password"
              id="pass"
              name="password"
              autocomplete="current-password"
              required
            >
          </div>

          <button type="submit" class="btn primary">
            Masuk
          </button>

        </form>

        <div class="demo">
          <b>Akun Demo</b><br>
          Username: admin<br>
          Password: admin
        </div>

      </div>
    </div>
  </div>

<?php if ($error === '1'): ?>

<script>
Swal.fire({
    icon: 'error',
    title: 'Login Gagal',
    text: 'Username atau password salah.',
    confirmButtonText: 'OK'
});

// Hapus ?error=1 dari URL tanpa reload halaman
window.history.replaceState({}, document.title, 'index.php');
</script>

<?php elseif ($error === '2'): ?>

<script>
Swal.fire({
    icon: 'warning',
    title: 'Akses Ditolak',
    text: 'Silakan login terlebih dahulu.',
    confirmButtonText: 'OK'
});

// Hapus ?error=2 dari URL tanpa reload halaman
window.history.replaceState({}, document.title, 'index.php');
</script>

<?php endif; ?>

</body>
</html>