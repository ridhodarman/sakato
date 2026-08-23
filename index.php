<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SAKATO V2 - Login</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div id="login" class="login"><div class="loginbox"><div class="hero"><h1>SAKATO</h1><h2>Sistem Akselerasi Kolaboratif Tunggakan Online</h2><p>Prototype V2 untuk monitoring, pencegahan, percepatan, dan eskalasi penyelesaian berkas layanan pertanahan secara kolaboratif.</p><div class="f"><b>Early Warning</b><br>Deteksi otomatis berkas mendekati dan melewati SLA.</div><div class="f"><b>Dashboard Pimpinan</b><br>Prioritas intervensi, tren risiko, dan kinerja unit.</div><div class="f"><b>Kolaborasi & Eskalasi</b><br>Jejak tindak lanjut lintas seksi dan dukungan pimpinan.</div></div><div class="form"><h2>Masuk ke SAKATO V2</h2><div class="muted">Gunakan akun demo untuk simulasi.</div><div class="field"><label>Username</label><input id="user" value="kepala"></div><div class="field"><label>Password</label><input id="pass" type="password" value="kepala"></div><button id="loginBtn" class="btn primary">Masuk</button><div class="demo"><b>Akun demo</b><br>kepala/kepala — Kepala Kantor<br>php/php — Seksi PHP<br>sp/sp — Seksi SP<br>tu/tu — Tata Usaha<br>pic/pic — PIC Pelaksana</div></div></div>
<div id="toast" class="toast hidden"></div>
<script>
document.getElementById('loginBtn').onclick = async () => {
  const k = document.getElementById('user').value.trim().toLowerCase();
  const p = document.getElementById('pass').value;
  const users = {kepala:'kepala',php:'php',sp:'sp',tu:'tu',pic:'pic'};

  if (users[k] && users[k] === p) {
    sessionStorage.setItem('sakato_user', k);
    const f = new FormData();
    f.append('username', k);
    f.append('password', p);
    await fetch('login.php', {method:'POST', body:f});
    location.href = 'dashboard.php';
  } else {
    const t = document.getElementById('toast');
    t.textContent = 'Username/password demo tidak sesuai.';
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 1800);
  }
};
</script>
</body>
</html>