<?php
require_once 'inc/koneksi.php';


// =====================================================
// DATA KINERJA PIC
// =====================================================

$sql = "

SELECT

    p.id AS pic_id,
    p.nama AS nama_pic,

    COUNT(b.id) AS total_berkas,

    SUM(
        CASE
            WHEN b.status = 'proses'
            THEN 1
            ELSE 0
        END
    ) AS total_proses,

    SUM(
        CASE
            WHEN b.status = 'eskalasi'
            THEN 1
            ELSE 0
        END
    ) AS total_eskalasi,

    SUM(
        CASE
            WHEN
                b.status <> 'selesai'
                AND DATEDIFF(CURDATE(), b.tanggal_mulai) > l.jatuh_tempo
            THEN 1
            ELSE 0
        END
    ) AS total_kadaluarsa


FROM pic p


LEFT JOIN layanan l

    ON l.pic_id = p.id


LEFT JOIN berkas_rutin b

    ON b.layanan_id = l.id


GROUP BY

    p.id,
    p.nama


ORDER BY

    total_berkas DESC

";


$result = $koneksi->query($sql);


if (!$result) {

    die("Query gagal: " . $koneksi->error);

}


// =====================================================
// SIMPAN DATA
// =====================================================

$dataPIC = [];

while ($row = $result->fetch_assoc()) {

    $row['total_berkas'] = (int) $row['total_berkas'];
    $row['total_proses'] = (int) $row['total_proses'];
    $row['total_eskalasi'] = (int) $row['total_eskalasi'];
    $row['total_kadaluarsa'] = (int) $row['total_kadaluarsa'];

    $dataPIC[] = $row;

}


// =====================================================
// STATISTIK GLOBAL
// =====================================================

$totalPIC = count($dataPIC);

$totalBerkas = 0;
$totalProses = 0;
$totalEskalasi = 0;
$totalKadaluarsa = 0;


foreach ($dataPIC as $row) {

    $totalBerkas += $row['total_berkas'];
    $totalProses += $row['total_proses'];
    $totalEskalasi += $row['total_eskalasi'];
    $totalKadaluarsa += $row['total_kadaluarsa'];

}

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO V2 - Kinerja PIC</title>
    <?php include "inc/head.php" ?>
    <style>

/* =====================================================
   GENERAL
   ===================================================== */

body {
    background: #f5f7fb;
    color: #1e293b;
}

.container-fluid {
    padding: 25px;
}

/* =====================================================
   HEADER
   ===================================================== */

.page-header {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: white;
    border-radius: 18px;
    padding: 28px;
    margin-bottom: 25px;
    box-shadow: 0 15px 35px rgba(15,23,42,.20);
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.page-subtitle {
    opacity: .7;
    font-size: 14px;
    margin-top: 7px;
}

/* =====================================================
   STAT CARD
   ===================================================== */

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    min-height: 130px;
    box-shadow: 0 8px 25px rgba(15,23,42,.07);
    margin-bottom: 20px;
}

.stat-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
}

.stat-blue::before {
    background: #2563eb;
}

.stat-orange::before {
    background: #f97316;
}

.stat-red::before {
    background: #dc2626;
}

.stat-title {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-number {
    font-size: 36px;
    font-weight: 800;
    margin-top: 5px;
}

.stat-description {
    color: #94a3b8;
    font-size: 12px;
}

/* =====================================================
   MAIN TABLE
   ===================================================== */

.table-card {
    background: white;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(15,23,42,.08);
}

.table-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.table-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
}

.table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .7px;
    white-space: nowrap;
    border: none;
    padding: 14px;
}

.table tbody td {
    padding: 15px 12px;
    vertical-align: middle;
    border-color: #f1f5f9;
    font-size: 13px;
}

/* =====================================================
   PIC NAME
   ===================================================== */

.pic-name {
    font-weight: 700;
    color: #0f172a;
}

/* =====================================================
   BADGE
   ===================================================== */

.badge-custom {
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.badge-proses {
    background: #eff6ff;
    color: #1d4ed8;
}

.badge-eskalasi {
    background: #fff7ed;
    color: #c2410c;
}

.badge-kadaluarsa {
    background: #fef2f2;
    color: #b91c1c;
}

/* =====================================================
   RESPONSIVE
   ===================================================== */

@media (max-width: 768px) {
    .container-fluid {
        padding: 15px;
    }

    .page-title {
        font-size: 23px;
    }
}

</style>
</head>

<body>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="container-fluid p-0">
    <div class="row no-gutters min-vh-100">
        <!-- Sidebar Column -->
        <aside class="col-md-3 col-lg-2 text-white p-3 min-vh-100 sticky-top" style="background: linear-gradient(180deg, #0f2e50, #194c7e);">
            <?php include "inc/sidebar.php"; ?>
        </aside>
        
        <!-- Main Content Column -->
        <main class="col-md-9 col-lg-10 p-4">
            <div class="container-fluid">

                <!-- =====================================================
                     HEADER
                     ===================================================== -->

                <div class="page-header">
                    <h1 class="page-title">
                        Kinerja PIC
                    </h1>
                    <div class="page-subtitle">
                        Monitoring status berkas aktif berdasarkan PIC layanan
                    </div>
                </div>

                <!-- =====================================================
                     STATISTIK GLOBAL
                     ===================================================== -->

                <div class="row">

                    <div class="col-lg-4 col-md-6">
                        <div class="stat-card stat-blue">
                            <div class="stat-title">
                                Total Berkas
                            </div>
                            <div class="stat-number">
                                <?= $totalBerkas ?>
                            </div>
                            <div class="stat-description">
                                Seluruh berkas yang ditangani PIC
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="stat-card stat-orange">
                            <div class="stat-title">
                                Eskalasi
                            </div>
                            <div class="stat-number">
                                <?= $totalEskalasi ?>
                            </div>
                            <div class="stat-description">
                                Membutuhkan pertimbangan pimpinan
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="stat-card stat-red">
                            <div class="stat-title">
                                Kadaluarsa
                            </div>
                            <div class="stat-number">
                                <?= $totalKadaluarsa ?>
                            </div>
                            <div class="stat-description">
                                Berkas melewati jatuh tempo
                            </div>
                        </div>
                    </div>

                </div>

                <!-- =====================================================
                     TABEL
                     ===================================================== -->

                <div class="table-card">

                    <div class="table-header">
                        <h2 class="table-title">
                            Kinerja Seluruh PIC
                        </h2>
                    </div>

                    <div class="table-responsive">

                        <table class="table table-hover mb-0">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>PIC</th>
                                    <th>Total</th>
                                    <th>Proses</th>
                                    <th>Eskalasi</th>
                                    <th>Kadaluarsa</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($dataPIC as $ranking => $row): ?>

                                <tr>

                                    <!-- RANKING -->
                                    <td>
                                        <strong>
                                            #<?= $ranking + 1 ?>
                                        </strong>
                                    </td>

                                    <!-- PIC -->
                                    <td>
                                        <div class="pic-name">
                                            <?= htmlspecialchars($row['nama_pic']) ?>
                                        </div>
                                    </td>

                                    <!-- TOTAL -->
                                    <td>
                                        <strong>
                                            <?= $row['total_berkas'] ?>
                                        </strong>
                                    </td>

                                    <!-- PROSES -->
                                    <td>
                                        <span class="badge-custom badge-proses">
                                            <?= $row['total_proses'] ?>
                                        </span>
                                    </td>

                                    <!-- ESKALASI -->
                                    <td>
                                        <span class="badge-custom badge-eskalasi">
                                            <?= $row['total_eskalasi'] ?>
                                        </span>
                                    </td>

                                    <!-- KADALUARSA -->
                                    <td>
                                        <span class="badge-custom badge-kadaluarsa">
                                            <?= $row['total_kadaluarsa'] ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            <?php if (count($dataPIC) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-5">
                                        Belum ada data PIC.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>
        </main>
    </div>
    <div id="modal" class="hidden"></div>
    <div id="toast" class="toast hidden"></div>
    <script src="assets/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {initUser(); render();});
    </script>
</div>
</body>

</html>