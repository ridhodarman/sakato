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
  <title>SAKATO V2 - Login System</title>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Google Fonts - Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body, html {
      height: 100%;
      width: 100%;
      overflow-x: hidden;
    }

    /* BACKGROUND UTAMA - FOKUS KANAN/TENGAH GAMBAR */
    .bg-container {
      min-height: 100vh;
      width: 100%;
      background-image: url('atrbpn.jpg');
      background-size: cover;
      background-position: center bottom; /* Fokus pada bagian bawah gambar gedung */
      background-repeat: no-repeat;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: flex-end; /* Form dipindah ke kanan agar gedung di tengah/kiri terlihat */
      padding: 40px;
    }

    /* OVERLAY SHADOW TIPIS AGAR GEDUNG TETAP NAMPAK */
    .bg-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(to right, rgba(0,0,0,0.2), rgba(0,0,0,0.6));
      z-index: 1;
    }

    /* LAYOUT KONTEN */
    .content-wrapper {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 40px;
    }

    /* TEKS DESKRIPSI DI SISI KIRI (TRANSPARAN) */
    .info-side {
      flex: 1;
      color: white;
      text-shadow: 0 2px 10px rgba(0,0,0,0.7);
    }

    .info-side h1 {
      font-size: 3.5rem;
      font-weight: 700;
      letter-spacing: 2px;
      margin-bottom: 5px;
      color: #fff;
    }

    .info-side h2 {
      font-size: 1.2rem;
      font-weight: 500;
      margin-bottom: 20px;
      color: #f0a500; /* Warna aksen emas/kuning ATR BPN */
    }

    .info-side p {
      font-size: 1rem;
      line-height: 1.6;
      max-width: 500px;
      margin-bottom: 30px;
      background: rgba(0, 0, 0, 0.4);
      padding: 15px 20px;
      border-left: 4px solid #f0a500;
      border-radius: 0 8px 8px 0;
      backdrop-filter: blur(5px);
    }

    /* KARTU FORM LOGIN (GLASSMORPHISM) */
    .form-card {
      width: 420px;
      max-width: 100%;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
      border: 1px solid rgba(255,255,255,0.4);
    }

    .form-card h3 {
      font-size: 1.8rem;
      color: #1e293b;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .form-card .subtitle {
      color: #64748b;
      font-size: 0.9rem;
      margin-bottom: 30px;
    }

    .input-group {
      margin-bottom: 20px;
    }

    .input-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: #334155;
      margin-bottom: 8px;
    }

    .input-group input {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s ease;
      background-color: rgba(255,255,255,0.8);
    }

    .input-group input:focus {
      border-color: #0284c7;
      box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
      background-color: #fff;
    }

    .btn-submit {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #0f172a, #0284c7);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
      margin-top: 10px;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(2, 132, 199, 0.4);
    }

    .demo-info {
      margin-top: 25px;
      padding: 12px 15px;
      background: rgba(241, 245, 249, 0.8);
      border-radius: 8px;
      font-size: 0.85rem;
      color: #475569;
      border: 1px dashed #cbd5e1;
    }

    /* RESPONSIVE UNTUK HP & TABLET */
    @media (max-width: 900px) {
      .content-wrapper {
        flex-direction: column;
        justify-content: center;
      }

      .info-side {
        text-align: center;
      }

      .info-side p {
        margin: 0 auto 20px auto;
        border-left: none;
        border-bottom: 4px solid #f0a500;
        border-radius: 8px;
      }

      .bg-container {
        padding: 20px;
        justify-content: center;
      }
    }
  </style>
</head>

<body>

  <div class="bg-container">
    <div class="content-wrapper">

      <!-- SISTEM INFO (SISI KIRI) -->
      <div class="info-side">
        <h1>SAKATO</h1>
        <h2>Sistem Akselerasi Kolaboratif Tunggakan Online</h2>
        <p>
          Kantor Pertanahan Kab. Agam<br>
          <small style="opacity: 0.8; font-weight: 300;">Platform monitoring, pencegahan, percepatan, dan eskalasi penyelesaian berkas layanan pertanahan.</small>
        </p>
      </div>

      <!-- FORM LOGIN (SISI KANAN) -->
      <div class="form-card">
        <h3>Selamat Datang</h3>
        <p class="subtitle">Silakan masukkan akun Anda untuk melanjutkan.</p>

        <form action="act/login.php" method="POST">
          <div class="input-group">
            <label for="user">Username</label>
            <input
              type="text"
              id="user"
              name="username"
              autocomplete="username"
              placeholder="Masukkan username"
              required
            >
          </div>

          <div class="input-group">
            <label for="pass">Password</label>
            <input
              type="password"
              id="pass"
              name="password"
              autocomplete="current-password"
              placeholder="Masukkan password"
              required
            >
          </div>

          <button type="submit" class="btn-submit">
            Login
          </button>
        </form>

        <div class="demo-info">
          <strong>Akun Demo:</strong><br>
          Username: <code>admin</code> | Password: <code>admin</code>
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
    confirmButtonText: 'Coba Lagi',
    confirmButtonColor: '#0284c7'
});

window.history.replaceState({}, document.title, 'index.php');
</script>

<?php elseif ($error === '2'): ?>

<script>
Swal.fire({
    icon: 'warning',
    title: 'Akses Ditolak',
    text: 'Silakan login terlebih dahulu.',
    confirmButtonText: 'OK',
    confirmButtonColor: '#0284c7'
});

window.history.replaceState({}, document.title, 'index.php');
</script>

<?php endif; ?>

</body>
</html>