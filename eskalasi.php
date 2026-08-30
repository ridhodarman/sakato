<?php
require_once 'auth.php';

// =====================================================
// TANGGAL HARI INI
// =====================================================

$hariIni = new DateTime();

// =====================================================
// AMBIL DATA HARI LIBUR
// =====================================================

$hariLibur = [];

$sqlHariLibur = "
    SELECT tanggal
    FROM hari_libur
";

$resultHariLibur = $koneksi->query($sqlHariLibur);

if (!$resultHariLibur) {
    die("Query hari libur gagal: " . $koneksi->error);
}

while ($libur = $resultHariLibur->fetch_assoc()) {
    $hariLibur[$libur['tanggal']] = true;
}


// =====================================================
// FUNGSI CEK HARI KERJA
// =====================================================

function isWorkingDay($tanggal, $hariLibur = [])
{
    $date = new DateTime($tanggal);

    // Sabtu dan Minggu
    if ((int)$date->format('N') >= 6) {
        return false;
    }

    // Hari libur dari database
    if (isset($hariLibur[$date->format('Y-m-d')])) {
        return false;
    }

    return true;
}


// =====================================================
// FUNGSI HITUNG UMUR BERKAS
// =====================================================

function getWorkingDays($startDate, $endDate, $hariLibur = [])
{
    $begin = new DateTime($startDate);
    $end   = new DateTime($endDate);

    // Tanggal mulai dan tanggal akhir dihitung secara inklusif
    $end->modify('+1 day');

    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($begin, $interval, $end);

    $workingDays = 0;

    foreach ($daterange as $date) {

        if (isWorkingDay(
            $date->format('Y-m-d'),
            $hariLibur
        )) {
            $workingDays++;
        }
    }

    return $workingDays;
}


// =====================================================
// AMBIL DATA ESKALASI
// =====================================================

$sql = "
    SELECT

        b.id,
        b.no_berkas,
        b.tahun,
        b.nama_pemohon,
        b.tanggal_mulai,
        b.status,
        b.catatan,
        b.tanggal_selesai,

        l.nama_layanan,
        l.jatuh_tempo,
        l.waspada,
        l.kritis,
        l.seksi,

        p.nama AS nama_pic

    FROM berkas_rutin b

    LEFT JOIN layanan l
        ON l.id = b.layanan_id

    LEFT JOIN pic p
        ON p.id = l.pic_id

    WHERE
        b.status = 'eskalasi'

    ORDER BY
        b.tanggal_mulai ASC
";


$result = $koneksi->query($sql);


if (!$result) {

    die("Query gagal: " . $koneksi->error);

}


// =====================================================
// DATA
// =====================================================

$dataEskalasi = [];


// Statistik seksi

$statistikSeksi = [];


// =====================================================
// PROSES DATA
// =====================================================

while ($row = $result->fetch_assoc()) {


// -------------------------------------------------
// UMUR BERKAS DALAM HARI KERJA
// -------------------------------------------------

if (!empty($row['tanggal_mulai'])) {

    $tanggalMulai = new DateTime($row['tanggal_mulai']);

    $umurHari = getWorkingDays(
        $row['tanggal_mulai'],
        $hariIni->format('Y-m-d'),
        $hariLibur
    );

} else {

    $umurHari = 0;

}


    $row['umur_hari'] = $umurHari;


    // -------------------------------------------------
    // SISA JATUH TEMPO
    // -------------------------------------------------

    $jatuhTempo = (int) ($row['jatuh_tempo'] ?? 0);

    if ($jatuhTempo > 0) {

        $sisaHari = $jatuhTempo - $umurHari;

    } else {

        $sisaHari = null;

    }


    $row['sisa_hari'] = $sisaHari;


    // -------------------------------------------------
    // KATEGORI WAKTU
    // -------------------------------------------------

    if ($jatuhTempo > 0) {

        if ($umurHari > $jatuhTempo) {

            $row['kategori_waktu'] = 'Kadaluarsa';

        }

        elseif ($umurHari >= $row['kritis']) {

            $row['kategori_waktu'] = 'Kritis';

        }

        elseif ($umurHari >= $row['waspada']) {

            $row['kategori_waktu'] = 'Waspada';

        }

        else {

            $row['kategori_waktu'] = 'Normal';

        }

    } else {

        $row['kategori_waktu'] = '-';

    }


    // -------------------------------------------------
    // STATISTIK SEKSI
    // -------------------------------------------------

    $seksi = trim($row['seksi'] ?? '');

    if ($seksi == '') {

        $seksi = 'Belum ditentukan';

    }


    if (!isset($statistikSeksi[$seksi])) {

        $statistikSeksi[$seksi] = 0;

    }


    $statistikSeksi[$seksi]++;


    // -------------------------------------------------
    // SIMPAN
    // -------------------------------------------------

    $dataEskalasi[] = $row;

}


// =====================================================
// TOTAL
// =====================================================

$totalEskalasi = count($dataEskalasi);


// Urutkan seksi berdasarkan jumlah terbesar

arsort($statistikSeksi);

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO V2 - Kinerja PIC</title>
    <?php include "inc/head.php" ?>
        <style>

        /* =================================================
           GENERAL
           ================================================= */

        body {

            background:
                #f5f7fb;

            color:
                #1e293b;

        }


        .container-fluid {

            padding:
                25px;

        }


        /* =================================================
           HEADER
           ================================================= */

        .page-header {

            background:
                linear-gradient(
                    135deg,
                    #111827,
                    #1e293b
                );

            color:
                white;

            border-radius:
                18px;

            padding:
                28px;

            margin-bottom:
                25px;

            position:
                relative;

            overflow:
                hidden;

            box-shadow:
                0 15px 35px rgba(15,23,42,.20);

        }


        .page-header::after {

            content:
                "";

            position:
                absolute;

            width:
                250px;

            height:
                250px;

            border-radius:
                50%;

            background:
                rgba(255,255,255,.05);

            right:
                -80px;

            top:
                -120px;

        }


        .page-title {

            font-size:
                28px;

            font-weight:
                700;

            margin:
                0;

        }


        .page-subtitle {

            margin-top:
                8px;

            opacity:
                .75;

            font-size:
                14px;

        }


        /* =================================================
           STAT CARD
           ================================================= */

        .stat-card {

            border:
                none;

            border-radius:
                16px;

            background:
                white;

            padding:
                22px;

            height:
                100%;

            box-shadow:
                0 8px 25px rgba(15,23,42,.07);

            position:
                relative;

            overflow:
                hidden;

        }


        .stat-card::before {

            content:
                "";

            position:
                absolute;

            left:
                0;

            top:
                0;

            width:
                5px;

            height:
                100%;

            background:
                #f97316;

        }


        .stat-label {

            color:
                #64748b;

            font-size:
                13px;

            text-transform:
                uppercase;

            letter-spacing:
                1px;

            font-weight:
                600;

        }


        .stat-number {

            font-size:
                42px;

            font-weight:
                800;

            line-height:
                1;

            margin-top:
                8px;

            color:
                #111827;

        }


        .stat-description {

            color:
                #94a3b8;

            font-size:
                13px;

            margin-top:
                8px;

        }


        .stat-icon {

            position:
                absolute;

            right:
                20px;

            top:
                20px;

            width:
                55px;

            height:
                55px;

            border-radius:
                15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                25px;

            background:
                #fff7ed;

            color:
                #f97316;

        }


        /* =================================================
           SEKSI
           ================================================= */

        .section-card {

            background:
                white;

            border-radius:
                16px;

            padding:
                22px;

            box-shadow:
                0 8px 25px rgba(15,23,42,.07);

            height:
                100%;

        }


        .section-title {

            font-size:
                15px;

            font-weight:
                700;

            margin-bottom:
                18px;

        }


        .seksi-item {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            padding:
                10px 0;

            border-bottom:
                1px solid #f1f5f9;

        }


        .seksi-item:last-child {

            border-bottom:
                none;

        }


        .seksi-name {

            font-size:
                13px;

            color:
                #475569;

        }


        .seksi-count {

            background:
                #fff7ed;

            color:
                #ea580c;

            border-radius:
                20px;

            padding:
                4px 10px;

            font-size:
                12px;

            font-weight:
                700;

        }


        /* =================================================
           MAIN TABLE CARD
           ================================================= */

        .table-card {

            background:
                white;

            border-radius:
                18px;

            box-shadow:
                0 8px 30px rgba(15,23,42,.08);

            overflow:
                hidden;

            margin-top:
                25px;

        }


        .table-header {

            padding:
                20px 24px;

            border-bottom:
                1px solid #e2e8f0;

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

        }


        .table-title {

            margin:
                0;

            font-size:
                18px;

            font-weight:
                700;

        }


        .badge-total {

            background:
                #fff7ed;

            color:
                #ea580c;

            padding:
                6px 12px;

            border-radius:
                20px;

            font-size:
                12px;

            font-weight:
                700;

        }


        .table {

            margin:
                0;

        }


        .table thead th {

            background:
                #f8fafc;

            color:
                #64748b;

            font-size:
                11px;

            text-transform:
                uppercase;

            letter-spacing:
                .7px;

            border:
                none;

            padding:
                14px;

            white-space:
                nowrap;

        }


        .table tbody td {

            padding:
                16px 14px;

            vertical-align:
                middle;

            border-color:
                #f1f5f9;

            font-size:
                13px;

        }


        /* =================================================
           BERKAS
           ================================================= */

        .nomor-berkas {

            font-weight:
                700;

            color:
                #0f172a;

        }


        .nama-pemohon {

            font-weight:
                600;

            color:
                #334155;

        }


        .nama-layanan {

            color:
                #64748b;

        }


        /* =================================================
           CATATAN ESKALASI
           ================================================= */

        .catatan-box {

            min-width:
                300px;

            max-width:
                430px;

            background:
                #fff7ed;

            border-left:
                4px solid #f97316;

            border-radius:
                8px;

            padding:
                12px 14px;

            color:
                #7c2d12;

        }


        .catatan-title {

            font-size:
                10px;

            text-transform:
                uppercase;

            font-weight:
                800;

            letter-spacing:
                .8px;

            color:
                #ea580c;

            margin-bottom:
                5px;

        }


        .catatan-text {

            font-size:
                13px;

            line-height:
                1.5;

            white-space:
                normal;

        }


        /* =================================================
           UMUR
           ================================================= */

        .umur {

            font-weight:
                700;

        }


        .umur-warning {

            color:
                #d97706;

        }


        .umur-danger {

            color:
                #dc2626;

        }


        .umur-expired {

            color:
                #7f1d1d;

        }


        /* =================================================
           STATUS WAKTU
           ================================================= */

        .badge-waktu {

            padding:
                5px 9px;

            border-radius:
                20px;

            font-size:
                11px;

            font-weight:
                700;

        }


        .badge-normal {

            background:
                #ecfdf5;

            color:
                #047857;

        }


        .badge-waspada {

            background:
                #fffbeb;

            color:
                #b45309;

        }


        .badge-kritis {

            background:
                #fef2f2;

            color:
                #b91c1c;

        }


        .badge-kadaluarsa {

            background:
                #f1f5f9;

            color:
                #475569;

        }


        /* =================================================
           BUTTON
           ================================================= */

        .btn-detail {

            border-radius:
                8px;

            font-size:
                12px;

            font-weight:
                600;

        }


        /* =================================================
           EMPTY
           ================================================= */

        .empty-state {

            padding:
                70px 20px;

            text-align:
                center;

            color:
                #94a3b8;

        }


        .empty-icon {

            font-size:
                45px;

            margin-bottom:
                15px;

            opacity:
                .5;

        }


        /* =================================================
           RESPONSIVE
           ================================================= */

        @media (max-width: 768px) {

            .container-fluid {

                padding:
                    15px;

            }

            .page-title {

                font-size:
                    23px;

            }

            .table-header {

                display:
                    block;

            }

            .badge-total {

                display:
                    inline-block;

                margin-top:
                    10px;

            }

        }

    </style>
</head>

<body>
<?php
// Ambil nama file dari URL yang sedang diakses (misal: "dashboard.php")
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="container-fluid p-0">
    <div class="row no-gutters min-vh-100">
        <!-- Sidebar Column -->
        <aside class="col-md-3 col-lg-2 text-white p-3 menusidebar">
            <?php include "inc/sidebar.php"; ?>
        </aside>
        
        <!-- Main Content Column -->
        <main class="col-md-9 col-lg-10 p-4">
<div class="container-fluid">


    <!-- ================================================= -->
    <!-- HEADER -->
    <!-- ================================================= -->

    <div class="page-header">

        <h1 class="page-title">

            Monitoring Eskalasi

        </h1>

        <div class="page-subtitle">

            Berkas yang memerlukan pertimbangan dan keputusan pimpinan

        </div>

    </div>



    <!-- ================================================= -->
    <!-- STATISTIK -->
    <!-- ================================================= -->

    <div class="row mb-4">


        <div class="col-lg-4 col-md-6 mb-3">

            <div class="stat-card">

                <div class="stat-icon">

                    ⚠

                </div>

                <div class="stat-label">

                    Total Eskalasi

                </div>

                <div class="stat-number">

                    <?= $totalEskalasi ?>

                </div>

                <div class="stat-description">

                    Berkas membutuhkan pertimbangan pimpinan

                </div>

            </div>

        </div>


        <div class="col-lg-8 col-md-6 mb-3">

            <div class="section-card">

                <div class="section-title">

                    Distribusi Eskalasi Berdasarkan Seksi

                </div>


                <?php if (count($statistikSeksi) > 0): ?>


                    <div class="row">

                        <?php foreach ($statistikSeksi as $seksi => $jumlah): ?>

                            <div class="col-lg-4 col-md-6">

                                <div class="seksi-item">

                                    <span class="seksi-name">

                                        <?= htmlspecialchars($seksi) ?>

                                    </span>

                                    <span class="seksi-count">

                                        <?= $jumlah ?>

                                    </span>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>


                <?php else: ?>

                    <div class="text-muted">

                        Belum ada data eskalasi.

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>



    <!-- ================================================= -->
    <!-- INFORMASI -->
    <!-- ================================================= -->

    <div class="alert alert-light border mb-4">

        <strong>

            Informasi:

        </strong>

        Berkas pada halaman ini merupakan berkas dengan status

        <strong class="text-warning">

            eskalasi

        </strong>

        yang membutuhkan pertimbangan atau keputusan pimpinan

        karena tidak dapat diselesaikan secara langsung oleh PIC

        dan pelaksana kegiatan.

    </div>



    <!-- ================================================= -->
    <!-- TABEL -->
    <!-- ================================================= -->

    <div class="table-card">


        <div class="table-header">

            <h2 class="table-title">

                Daftar Berkas Eskalasi

            </h2>


            <span class="badge-total">

                <?= $totalEskalasi ?> Berkas

            </span>

        </div>


        <div class="table-responsive">


            <?php if ($totalEskalasi == 0): ?>


                <div class="empty-state">

                    <div class="empty-icon">

                        ✓

                    </div>

                    <h5>

                        Tidak Ada Berkas Eskalasi

                    </h5>

                    <div>

                        Saat ini tidak terdapat berkas yang membutuhkan

                        pertimbangan pimpinan.

                    </div>

                </div>


            <?php else: ?>


                <table class="table table-hover">


                    <thead>

                    <tr>

                        <th>

                            No.

                        </th>

                        <th>

                            No. Berkas

                        </th>

                        <th>

                            Pemohon

                        </th>

                        <th>

                            Layanan

                        </th>

                        <th>

                            Seksi

                        </th>

                        <th>

                            PIC

                        </th>

                        <th>

                            Tanggal Mulai

                        </th>

                        <th>

                            Umur

                        </th>

                        <th>

                            Kondisi Waktu

                        </th>

                        <th>

                            Catatan Eskalasi

                        </th>


                    </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($dataEskalasi as $no => $row): ?>


                        <?php

                        /*
                         * Tentukan warna umur
                         */

                        if ($row['kategori_waktu'] == 'Kadaluarsa') {

                            $umurClass = 'umur-expired';

                        }

                        elseif ($row['kategori_waktu'] == 'Kritis') {

                            $umurClass = 'umur-danger';

                        }

                        elseif ($row['kategori_waktu'] == 'Waspada') {

                            $umurClass = 'umur-warning';

                        }

                        else {

                            $umurClass = '';

                        }


                        /*
                         * Badge kondisi
                         */

                        $badgeClass = 'badge-normal';

                        if ($row['kategori_waktu'] == 'Waspada') {

                            $badgeClass = 'badge-waspada';

                        }

                        elseif ($row['kategori_waktu'] == 'Kritis') {

                            $badgeClass = 'badge-kritis';

                        }

                        elseif ($row['kategori_waktu'] == 'Kadaluarsa') {

                            $badgeClass = 'badge-kadaluarsa';

                        }


                        /*
                         * Catatan
                         */

                        $catatan = trim($row['catatan'] ?? '');

                        if ($catatan == '') {

                            $catatan = 'Belum ada catatan eskalasi.';

                        }

                        ?>


                        <tr>


                            <!-- NO -->

                            <td>

                                <?= $no + 1 ?>

                            </td>


                            <!-- NO BERKAS -->

                            <td>

                                <div class="nomor-berkas">

                                    <?= htmlspecialchars($row['no_berkas']) ?>

                                    /

                                    <?= htmlspecialchars($row['tahun']) ?>

                                </div>

                            </td>


                            <!-- PEMOHON -->

                            <td>

                                <div class="nama-pemohon">

                                    <?= htmlspecialchars($row['nama_pemohon']) ?>

                                </div>

                            </td>


                            <!-- LAYANAN -->

                            <td>

                                <div class="nama-layanan">

                                    <?= htmlspecialchars($row['nama_layanan'] ?? '-') ?>

                                </div>

                            </td>


                            <!-- SEKSI -->

                            <td>

                                <?= htmlspecialchars($row['seksi'] ?? '-') ?>

                            </td>


                            <!-- PIC -->

                            <td>

                                <?= htmlspecialchars($row['nama_pic'] ?? '-') ?>

                            </td>


                            <!-- TANGGAL -->

                            <td>

                                <?= htmlspecialchars($row['tanggal_mulai']) ?>

                            </td>


                            <!-- UMUR -->

                            <td>

                                <span class="umur <?= $umurClass ?>">

                                    <?= $row['umur_hari'] ?>

                                    hari

                                </span>

                            </td>


                            <!-- KONDISI -->

                            <td>

                                <span class="badge-waktu <?= $badgeClass ?>">

                                    <?= htmlspecialchars($row['kategori_waktu']) ?>

                                </span>


                                <?php if ($row['sisa_hari'] !== null): ?>

                                    <div class="small text-muted mt-1">

                                        <?php if ($row['sisa_hari'] >= 0): ?>

                                            Sisa
                                            <?= $row['sisa_hari'] ?>
                                            hari

                                        <?php else: ?>

                                            Terlambat
                                            <?= abs($row['sisa_hari']) ?>
                                            hari

                                        <?php endif; ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- CATATAN -->

                            <td>

                                <div class="catatan-box">

                                    <div class="catatan-title">

                                        Alasan / Catatan Eskalasi

                                    </div>

                                    <div class="catatan-text">

                                        <?= nl2br(htmlspecialchars($catatan)) ?>

                                    </div>

                                </div>

                            </td>



                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>


            <?php endif; ?>


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
</div>
</body>

</html>