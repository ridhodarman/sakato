<?php require __DIR__ . '/auth.php'; ?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SAKATO V2 - Database Berkas</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="app"><aside class="side"><div class="brand"><b>SAKATO V2</b><small>Sistem Akselerasi Kolaboratif Tunggakan Online<br>Kantor Pertanahan Kabupaten Agam</small></div><div class="user"><div id="avatar" class="avatar">K</div><div><b id="uname">Kepala Kantor</b><small id="urole">Pimpinan</small></div></div><nav>
<a href="dashboard.php">▦ Dashboard</a>
<a href="databerkas.php">▤ Database Berkas</a>
<a href="warning.php">⚠ Early Warning</a>
<a href="eskalasi.php">↗ Eskalasi</a>
<a href="pic.php">👥 Kinerja PIC</a>
<a href="input.php">＋ Input / Update</a>
<a href="audit.php">☷ Audit Trail</a>
<a href="setting.php">⚙ Pengaturan</a>
</nav><div class="logout"><button id="logout" class="btn light" style="width:100%" onclick="logout()">Keluar</button></div></aside><main class="main"><div class="top"><div><h1>Database Berkas</h1><div class="muted">Monitoring terpusat sesuai scope pengguna.</div></div><div class="top-actions"><span id="date" class="pill"></span></div></div><section id="berkas" class="section active"><div class="card"><h3>Database Monitoring Berkas</h3><div class="toolbar"><input id="search" placeholder="Cari berkas, layanan, pemohon, PIC..."><select id="fs"><option value="">Semua Status</option><option>Aman</option><option>Waspada</option><option>Kritis</option><option>Selesai</option></select><select id="fk"><option value="">Semua Seksi</option></select><button id="csv" class="btn light">Ekspor CSV</button></div><div class="tablewrap"><table><thead><tr><th>No. Berkas</th><th>Layanan</th><th>Seksi</th><th>Pemohon</th><th>PIC</th><th>Umur</th><th>SLA</th><th>Progres</th><th>Status</th><th>Kendala</th><th>Aksi</th></tr></thead><tbody id="tbody"></tbody></table></div></div></section></main></div>
<div id="modal" class="hidden"></div><div id="toast" class="toast hidden"></div>
<script src="assets/app.js"></script><script>
document.addEventListener('DOMContentLoaded',()=>{initUser();render();});
</script></body></html>