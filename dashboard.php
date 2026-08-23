<?php require __DIR__ . '/auth.php'; ?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SAKATO V2 - Dashboard Pimpinan</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="app"><aside class="side"><div class="brand"><b>SAKATO V2</b><small>Sistem Akselerasi Kolaboratif Tunggakan Online<br>Kantor Pertanahan Kabupaten Agam</small></div><div class="user"><div id="avatar" class="avatar">K</div><div><b id="uname">Kepala Kantor</b><small id="urole">Pimpinan</small></div></div><nav>
<a href="dashboard.php">▦ Dashboard</a>
<a href="databerkas.php">▤ Database Berkas</a>
<a href="warning.php">⚠ Early Warning</a>
<a href="eskalasi.php">↗ Eskalasi</a>
<a href="pic.php">👥 Kinerja PIC</a>
<a href="input.php">＋ Input / Update</a>
<a href="audit.php">☷ Audit Trail</a>
<a href="setting.php">⚙ Pengaturan</a>
</nav><div class="logout"><button id="logout" class="btn light" style="width:100%" onclick="logout()">Keluar</button></div></aside><main class="main"><div class="top"><div><h1>Dashboard Pimpinan</h1><div class="muted">Kondisi terkini berkas, risiko SLA, progres penyelesaian, dan prioritas intervensi.</div></div><div class="top-actions"><span id="date" class="pill"></span><button id="notif" class="btn light notif" onclick="location.href='warning.php'">🔔 Peringatan <span id="notifCount" class="count">0</span></button></div></div><section id="dashboard" class="section active"><div class="grid kpis"><div class="card kpi blue"><div class="label">Berkas Aktif</div><div id="ka" class="val">0</div><div class="muted">Belum selesai</div></div><div class="card kpi amber"><div class="label">Waspada</div><div id="kw" class="val">0</div><div class="muted">70–99% SLA</div></div><div class="card kpi red"><div class="label">Kritis</div><div id="kk" class="val">0</div><div class="muted">≥ 100% SLA</div></div><div class="card kpi green"><div class="label">Selesai</div><div id="ks" class="val">0</div><div class="muted">Progres 100%</div></div><div class="card kpi purple"><div class="label">On-Time Rate</div><div id="kot" class="val">0%</div><div class="muted">Aman + selesai</div></div></div><div class="grid row2"><div class="card"><h3>Tren Status Berkas</h3><div class="chart"><canvas id="trend"></canvas></div><div class="legend"><span><i class="dot da"></i>Aman</span><span><i class="dot dw"></i>Waspada</span><span><i class="dot dk"></i>Kritis</span><span><i class="dot ds"></i>Selesai</span></div></div><div class="card"><h3>Komposisi Risiko</h3><div id="risk"></div></div></div><div class="grid row3"><div class="card"><h3>Prioritas Intervensi</h3><div id="priority" class="list"></div></div><div class="card"><h3>Kinerja Seksi</h3><div id="seksiperf"></div></div><div class="card"><h3>Aktivitas Terbaru</h3><div id="activity" class="timeline"></div></div></div></section></main></div>
<div id="modal" class="hidden"></div><div id="toast" class="toast hidden"></div>
<script src="assets/app.js"></script><script>
document.addEventListener('DOMContentLoaded',()=>{initUser();render();});
</script></body></html>