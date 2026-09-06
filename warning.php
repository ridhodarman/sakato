<?php
require_once 'auth.php';

// =====================================================
// CEK HAK AKSES WARNING
// =====================================================

$idUser = (int)($_SESSION['id_user'] ?? 0);

$stmtUser = $koneksi->prepare("
    SELECT
        id,
        username,
        nama,
        pic_id,
        lihat_semua_warning
    FROM akun_sakato
    WHERE id = ?
    LIMIT 1
");

$stmtUser->bind_param("i", $idUser);
$stmtUser->execute();

$resultUser = $stmtUser->get_result();
$akunUser = $resultUser->fetch_assoc();

$stmtUser->close();


// Jika akun tidak ditemukan
if (!$akunUser) {
    die("Data akun tidak ditemukan.");
}


// =====================================================
// DATA HAK AKSES
// =====================================================

$userPicId = !empty($akunUser['pic_id'])
    ? (int)$akunUser['pic_id']
    : null;

$lihatSemuaWarning = (int)($akunUser['lihat_semua_warning'] ?? 0);


// =====================================================
// TENTUKAN MODE TAMPILAN
// =====================================================
//
// ?semua=1 hanya boleh digunakan jika
// lihat_semua_warning = 1
//

$tampilkanSemua = false;

if ($lihatSemuaWarning === 1 && isset($_GET['semua']) && $_GET['semua'] == '1') {
    $tampilkanSemua = true;
}


// =====================================================
// VALIDASI AKSES
// =====================================================
//
// Kondisi:
// 1. Punya PIC       -> boleh melihat data PIC tersebut
// 2. Tidak punya PIC + boleh lihat semua -> boleh melihat semua
// 3. Tidak punya PIC + tidak boleh lihat semua -> tidak boleh
//

$aksesWarning = true;
$pesanAkses = '';

if ($userPicId === null && $lihatSemuaWarning !== 1) {

    $aksesWarning = false;

    $pesanAkses = 'Anda tidak memiliki akses ke data ini, hubungi admin.';
}

// =====================================================
// TANGGAL HARI INI
// =====================================================
$hariIni = new DateTime();
$today = $hariIni->format('Y-m-d');


// =====================================================
// AMBIL DATA HARI LIBUR
// =====================================================
$hariLibur = [];

$sqlHariLibur = "
    SELECT tanggal
    FROM hari_libur
    WHERE tanggal IS NOT NULL
      AND tanggal <> '0000-00-00'
";

$resultHariLibur = $koneksi->query($sqlHariLibur);

if (!$resultHariLibur) {
    die("Query hari libur gagal: " . $koneksi->error);
}

while ($libur = $resultHariLibur->fetch_assoc()) {

    $tanggalLibur = $libur['tanggal'];

    // Pastikan hanya Senin-Jumat
    $hari = (int)date('N', strtotime($tanggalLibur));

    if ($hari <= 5) {
        $hariLibur[] = $tanggalLibur;
    }
}


// =====================================================
// SORT HARI LIBUR
// =====================================================

sort($hariLibur);


// =====================================================
// FUNGSI BINARY SEARCH
// =====================================================

function lowerBoundDate($array, $target)
{
    $low = 0;
    $high = count($array);

    while ($low < $high) {

        $mid = intdiv($low + $high, 2);

        if ($array[$mid] < $target) {
            $low = $mid + 1;
        } else {
            $high = $mid;
        }
    }

    return $low;
}


// =====================================================
// HITUNG JUMLAH HARI LIBUR DALAM RANGE
// =====================================================

function countHolidayBetween(
    $startDate,
    $endDate,
    $hariLibur
) {

    if (empty($hariLibur)) {
        return 0;
    }

    if ($endDate < $startDate) {
        return 0;
    }

    $startIndex = lowerBoundDate(
        $hariLibur,
        $startDate
    );

    $endIndex = lowerBoundDate(
        $hariLibur,
        $endDate
    );

    $count = $endIndex - $startIndex;

    if (
        isset($hariLibur[$endIndex]) &&
        $hariLibur[$endIndex] === $endDate
    ) {
        $count++;
    }

    return $count;
}


// =====================================================
// HITUNG HARI KERJA
// =====================================================

function getWorkingDays(
    $startDate,
    $endDate,
    $hariLibur = []
) {

    if (
        empty($startDate) ||
        empty($endDate) ||
        $startDate === '0000-00-00' ||
        $endDate === '0000-00-00'
    ) {
        return 0;
    }

    if ($endDate < $startDate) {
        return 0;
    }

    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);

    $totalDays = (int)$start->diff($end)->days + 1;
    $fullWeeks = intdiv($totalDays, 7);
    $workingDays = $fullWeeks * 5;
    $remainingDays = $totalDays % 7;
    $startDay = (int)$start->format('N');

    for ($i = 0; $i < $remainingDays; $i++) {
        $day = (($startDay - 1 + $i) % 7) + 1;
        if ($day <= 5) {
            $workingDays++;
        }
    }

    $jumlahHariLibur = countHolidayBetween(
        $startDate,
        $endDate,
        $hariLibur
    );

    $workingDays -= $jumlahHariLibur;

    return max(0, $workingDays);
}


// =====================================================
// AMBIL DATA BERKAS
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
        b.posisi_id,

        l.nama_layanan,
        l.jatuh_tempo,
        l.waspada,
        l.kritis,

        p.nama AS nama_pic,
        ps.nama_posisi AS nama_posisi

    FROM berkas_rutin b

    LEFT JOIN layanan l
        ON l.id = b.layanan_id

    LEFT JOIN pic p
        ON p.id = l.pic_id

    LEFT JOIN posisi ps
        ON ps.id = b.posisi_id

    WHERE
        (b.status <> 'selesai' OR b.status IS NULL)
        AND b.tanggal_mulai IS NOT NULL
        AND b.tanggal_mulai <> '0000-00-00'
";


// =====================================================
// FILTER BERDASARKAN PIC
// =====================================================
//
// Jika:
// - user punya pic_id
// - dan belum memilih "semua"
//
// maka hanya tampilkan layanan milik PIC tersebut.
//

if ($userPicId !== null && !$tampilkanSemua) {

    $sql .= "
        AND l.pic_id = " . $userPicId . "
    ";
}


// =====================================================
// JIKA USER TIDAK PUNYA PIC
// DAN TIDAK PUNYA HAK LIHAT SEMUA
//
// Query tidak perlu dijalankan karena akses ditolak.
//

if (!$aksesWarning) {

    $result = false;

} else {

    $sql .= "
        ORDER BY
            b.tanggal_mulai ASC
    ";

    $result = $koneksi->query($sql);

    if (!$result) {
        die("Query gagal: " . $koneksi->error);
    }
}


// =====================================================
// ARRAY DATA
// =====================================================

$dataWaspada = [];
$dataKritis = [];
$dataKadaluarsa = [];

$rekapPosisi = [];
$rekapPosisiDetail = []; // Menyimpan detail list berkas per posisi & kategori

$umurCache = [];


// =====================================================
// PROSES DATA
// =====================================================

while ($row = $result->fetch_assoc()) {

    $tanggalMulaiString = $row['tanggal_mulai'] ?? '';

    if (
        empty($tanggalMulaiString) ||
        $tanggalMulaiString === '0000-00-00'
    ) {
        continue;
    }

    $tanggalMulai = new DateTime($tanggalMulaiString);
    $row['tanggal_mulai_formatted'] = $tanggalMulai->format('d-m-Y');

    if (isset($umurCache[$tanggalMulaiString])) {
        $umurHari = $umurCache[$tanggalMulaiString];
    } else {
        $umurHari = getWorkingDays(
            $tanggalMulaiString,
            $today,
            $hariLibur
        );
        $umurCache[$tanggalMulaiString] = $umurHari;
    }

    $waspada = (int)($row['waspada'] ?? 0);
    $kritis = (int)($row['kritis'] ?? 0);
    $jatuhTempo = (int)($row['jatuh_tempo'] ?? 0);

    if ($jatuhTempo <= 0) {
        continue;
    }

    $sisaHari = $jatuhTempo - $umurHari;

    if ($umurHari > $jatuhTempo) {
        $kategori = 'kadaluarsa';
    } elseif ($umurHari >= $kritis) {
        $kategori = 'kritis';
    } elseif ($umurHari >= $waspada) {
        $kategori = 'waspada';
    } else {
        continue;
    }

    $row['umur_hari'] = $umurHari;
    $row['sisa_hari'] = $sisaHari;
    $row['kategori'] = $kategori;

    $posisiNama = !empty($row['nama_posisi']) ? $row['nama_posisi'] : 'Tanpa Posisi';

    if (!isset($rekapPosisi[$posisiNama])) {
        $rekapPosisi[$posisiNama] = [
            'waspada' => 0,
            'kritis' => 0,
            'kadaluarsa' => 0
        ];
        
        $rekapPosisiDetail[$posisiNama] = [
            'waspada' => [],
            'kritis' => [],
            'kadaluarsa' => [],
            'total' => []
        ];
    }

    // Format data ringkas untuk Modal (Menambahkan nama_layanan)
    $itemModal = [
        'no_berkas_tahun' => $row['no_berkas'] . '/' . $row['tahun'],
        'nama_pemohon'   => $row['nama_pemohon'],
        'nama_layanan'   => $row['nama_layanan'] ?? '-',
        'tanggal_mulai'  => $row['tanggal_mulai_formatted'],
        'umur_hari'      => $row['umur_hari'] . ' hari',
        'kategori'       => ucfirst($kategori)
    ];

    if ($kategori === 'waspada') {
        $dataWaspada[] = $row;
        $rekapPosisi[$posisiNama]['waspada']++;
        $rekapPosisiDetail[$posisiNama]['waspada'][] = $itemModal;
    } elseif ($kategori === 'kritis') {
        $dataKritis[] = $row;
        $rekapPosisi[$posisiNama]['kritis']++;
        $rekapPosisiDetail[$posisiNama]['kritis'][] = $itemModal;
    } elseif ($kategori === 'kadaluarsa') {
        $dataKadaluarsa[] = $row;
        $rekapPosisi[$posisiNama]['kadaluarsa']++;
        $rekapPosisiDetail[$posisiNama]['kadaluarsa'][] = $itemModal;
    }
    
    // Tambahkan ke daftar total per posisi
    $rekapPosisiDetail[$posisiNama]['total'][] = $itemModal;
}


// =====================================================
// TOTAL KESELURUHAN
// =====================================================

$totalWaspada = count($dataWaspada);
$totalKritis = count($dataKritis);
$totalKadaluarsa = count($dataKadaluarsa);

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO</title>
    <?php include "inc/head.php" ?>
    <style>
        .stat-card {
            position: relative;
            overflow: hidden;
            min-height: 155px;
            padding: 24px;
            border-radius: 18px;
            color: #fff;
            border: 1px solid rgba(255,255,255,.15);
            box-shadow: 0 10px 30px rgba(0,0,0,.18), inset 0 1px 0 rgba(255,255,255,.15);
            transition: transform .3s ease, box-shadow .3s ease;
            margin-bottom: 25px;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(0,0,0,.25), inset 0 1px 0 rgba(255,255,255,.2);
        }

        .stat-card::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            right: -60px;
            top: -70px;
            background: rgba(255,255,255,.12);
            filter: blur(2px);
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            right: 30px;
            bottom: -80px;
            background: rgba(255,255,255,.08);
            filter: blur(5px);
        }

        .stat-waspada {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 45%, #ea580c 100%);
            box-shadow: 0 10px 30px rgba(245,158,11,.30);
        }

        .stat-kritis {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 45%, #991b1b 100%);
            box-shadow: 0 10px 30px rgba(239,68,68,.30);
        }

        .stat-kadaluarsa {
            background: linear-gradient(135deg, #475569 0%, #1e293b 50%, #020617 100%);
            box-shadow: 0 10px 30px rgba(15,23,42,.35);
        }

        .stat-content {
            position: relative;
            z-index: 2;
        }

        .stat-title {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: .85;
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 48px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: -2px;
            text-shadow: 0 3px 10px rgba(0,0,0,.20);
            margin: 8px 0;
        }

        .stat-description {
            font-size: 13px;
            opacity: .8;
            margin-top: 8px;
        }

        .stat-icon {
            position: absolute;
            z-index: 1;
            right: 24px;
            top: 50%;
            transform: translateY(-50%);
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            backdrop-filter: blur(8px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 8px 20px rgba(0,0,0,.12);
        }

        .stat-line {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,.15);
        }

        .stat-line span {
            display: block;
            width: 35%;
            height: 100%;
            background: rgba(255,255,255,.8);
            box-shadow: 0 0 12px rgba(255,255,255,.8);
            border-radius: 10px;
        }

        /* Styling Badge Interaktif */
        .btn-modal-trigger {
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.2s ease;
            display: inline-block;
        }
        .btn-modal-trigger:hover {
            transform: scale(1.15);
            opacity: 0.9;
        }

        @media (max-width: 767px) {
            .stat-card {
                min-height: 135px;
                padding: 20px;
            }
            .stat-number {
                font-size: 40px;
            }
            .stat-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
                right: 18px;
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

        <!-- =====================================================
             SIDEBAR DESKTOP / MOBILE
             ===================================================== -->

        <aside class="menusidebar">

            <?php include "inc/sidebar.php"; ?>

        </aside>


        <!-- =====================================================
             OVERLAY MOBILE
             ===================================================== -->

        <div class="sidebar-overlay" id="sidebarOverlay"></div>


        <!-- =====================================================
             MAIN CONTENT
             ===================================================== -->

        <main class="main-content">

            <!-- Tombol hamburger khusus HP -->
            <button type="button"
                    class="btn btn-primary sidebar-toggle"
                    id="sidebarToggle"
                    aria-label="Buka menu">

                <i class="fas fa-bars" id="sidebarToggleIcon"></i>

            </button>
            <div class="container-fluid">

                <!-- HEADER -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">Monitoring Berkas Rutin</h3>

                        <div class="text-muted">
                            Pemantauan berkas berdasarkan batas waktu layanan
                        </div>
                    </div>

                    <div class="d-flex align-items-center">

                        <?php if ($aksesWarning && $lihatSemuaWarning === 1): ?>

                            <?php if ($tampilkanSemua): ?>

                                <a href="warning.php"
                                class="btn btn-primary font-weight-bold shadow-sm mr-2">
                                    <i class="fas fa-user mr-1"></i>
                                    Tampilkan Data PIC Saya
                                </a>

                            <?php else: ?>

                                <a href="warning.php?semua=1"
                                class="btn btn-dark font-weight-bold shadow-sm mr-2">
                                    <i class="fas fa-users mr-1"></i>
                                    Tampilkan Semua Data Early Warning System
                                </a>

                            <?php endif; ?>

                        <?php endif; ?>


                        <?php if ($aksesWarning): ?>

                            <a href="act/warning_export_excel.php<?= $tampilkanSemua ? '?semua=1' : '' ?>"
                            target="_blank"
                            class="btn btn-success font-weight-bold shadow-sm">
                                <i class="fas fa-file-excel mr-1"></i>
                                Export ke Excel
                            </a>

                        <?php endif; ?>

                    </div>
                </div>

                <?php if (!$aksesWarning): ?>

                <!-- TIDAK MEMILIKI AKSES -->
                <div class="alert alert-danger shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lock fa-2x mr-3"></i>

                        <div>
                            <h5 class="alert-heading mb-1">
                                Akses Data Ditolak
                            </h5>

                            <div>
                                Anda tidak memiliki akses ke data ini,
                                hubungi admin.
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($lihatSemuaWarning === 1 && $userPicId === null): ?>

                <!-- BUKAN PIC, TAPI BOLEH LIHAT SEMUA -->
                <div class="alert alert-info shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">

                        <i class="fas fa-info-circle fa-2x mr-3"></i>

                        <div>
                            <h5 class="alert-heading mb-1">
                                Akun Ini Bukan Akun PIC
                            </h5>

                            <div>
                                Akun Anda tidak terhubung dengan PIC tertentu.
                                Saat ini ditampilkan seluruh data
                                Early Warning System.
                            </div>
                        </div>

                    </div>
                </div>

            <?php elseif ($userPicId !== null && $lihatSemuaWarning === 1 && !$tampilkanSemua): ?>

                <!-- PIC + BOLEH LIHAT SEMUA -->
                <div class="alert alert-info shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">

                        <i class="fas fa-info-circle fa-2x mr-3"></i>

                        <div>
                            <h5 class="alert-heading mb-1">
                                Data Early Warning System
                            </h5>

                            <div>
                                Saat ini menampilkan data sesuai PIC Anda.
                                Gunakan tombol
                                <strong>"Tampilkan Semua Data Early Warning System"</strong>
                                untuk melihat seluruh data.
                            </div>
                        </div>

                    </div>
                </div>

            <?php elseif ($userPicId !== null && $lihatSemuaWarning === 1 && $tampilkanSemua): ?>

                <!-- SEDANG MELIHAT SEMUA -->
                <div class="alert alert-warning shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">

                        <i class="fas fa-users fa-2x mr-3"></i>

                        <div>
                            <h5 class="alert-heading mb-1">
                                Menampilkan Semua Data
                            </h5>

                            <div>
                                Saat ini Anda sedang melihat seluruh data
                                Early Warning System.
                            </div>
                        </div>

                    </div>
                </div>

            <?php endif; ?>

                <!-- STATISTIK -->
                <div class="row">
                    <!-- WASPADA -->
                    <div class="col-lg-4 col-md-6">
                        <div class="stat-card stat-waspada">
                            <div class="stat-content">
                                <div class="stat-title">Total Waspada</div>
                                <div class="stat-number"><?= $totalWaspada ?></div>
                                <div class="stat-description">Berkas mendekati batas kritis</div>
                            </div>
                            <div class="stat-icon">⚠</div>
                            <div class="stat-line"><span></span></div>
                        </div>
                    </div>

                    <!-- KRITIS -->
                    <div class="col-lg-4 col-md-6">
                        <div class="stat-card stat-kritis">
                            <div class="stat-content">
                                <div class="stat-title">Total Kritis</div>
                                <div class="stat-number"><?= $totalKritis ?></div>
                                <div class="stat-description">Berkas mendekati jatuh tempo</div>
                            </div>
                            <div class="stat-icon">⛔</div>
                            <div class="stat-line"><span></span></div>
                        </div>
                    </div>

                    <!-- KADALUARSA -->
                    <div class="col-lg-4 col-md-6">
                        <div class="stat-card stat-kadaluarsa">
                            <div class="stat-content">
                                <div class="stat-title">Total Kadaluarsa</div>
                                <div class="stat-number"><?= $totalKadaluarsa ?></div>
                                <div class="stat-description">Sudah melewati jatuh tempo</div>
                            </div>
                            <div class="stat-icon">⏱</div>
                            <div class="stat-line"><span></span></div>
                        </div>
                    </div>
                </div>

                <!-- REKAP BERKAS PER POSISI -->
                <?php if (!empty($rekapPosisi)): ?>
                <div class="card mb-5">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Ringkasan Berkas Berdasarkan Posisi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Posisi</th>
                                        <th class="text-center">Waspada</th>
                                        <th class="text-center">Kritis</th>
                                        <th class="text-center">Kadaluarsa</th>
                                        <th class="text-center">Total Berkas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $noPos = 1;
                                    foreach ($rekapPosisi as $namaPosisi => $jumlah): 
                                        $totalPerPosisi = $jumlah['waspada'] + $jumlah['kritis'] + $jumlah['kadaluarsa'];
                                        if ($totalPerPosisi === 0) continue;

                                        // Data JSON untuk dikirim ke modal
                                        $jsonWaspada    = htmlspecialchars(json_encode($rekapPosisiDetail[$namaPosisi]['waspada']), ENT_QUOTES, 'UTF-8');
                                        $jsonKritis     = htmlspecialchars(json_encode($rekapPosisiDetail[$namaPosisi]['kritis']), ENT_QUOTES, 'UTF-8');
                                        $jsonKadaluarsa = htmlspecialchars(json_encode($rekapPosisiDetail[$namaPosisi]['kadaluarsa']), ENT_QUOTES, 'UTF-8');
                                        $jsonTotal      = htmlspecialchars(json_encode($rekapPosisiDetail[$namaPosisi]['total']), ENT_QUOTES, 'UTF-8');
                                        $namaPosisiAttr = htmlspecialchars($namaPosisi, ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <tr>
                                            <td><?= $noPos++ ?></td>
                                            <td><strong><?= htmlspecialchars($namaPosisi) ?></strong></td>
                                            
                                            <!-- WASPADA -->
                                            <td class="text-center">
                                                <?php if ($jumlah['waspada'] > 0): ?>
                                                    <span class="badge badge-primary text-white btn-modal-trigger" 
                                                          data-posisi="<?= $namaPosisiAttr ?>"
                                                          data-kategori="Waspada"
                                                          data-items='<?= $jsonWaspada ?>'>
                                                        <?= $jumlah['waspada'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">0</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- KRITIS -->
                                            <td class="text-center">
                                                <?php if ($jumlah['kritis'] > 0): ?>
                                                    <span class="badge badge-warning btn-modal-trigger" 
                                                          data-posisi="<?= $namaPosisiAttr ?>"
                                                          data-kategori="Kritis"
                                                          data-items='<?= $jsonKritis ?>'>
                                                        <?= $jumlah['kritis'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">0</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- KADALUARSA -->
                                            <td class="text-center">
                                                <?php if ($jumlah['kadaluarsa'] > 0): ?>
                                                    <span class="badge badge-danger btn-modal-trigger" 
                                                          data-posisi="<?= $namaPosisiAttr ?>"
                                                          data-kategori="Kadaluarsa"
                                                          data-items='<?= $jsonKadaluarsa ?>'>
                                                        <?= $jumlah['kadaluarsa'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">0</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- TOTAL BERKAS -->
                                            <td class="text-center">
                                                <?php if ($totalPerPosisi > 0): ?>
                                                    <span class="badge badge-dark btn-modal-trigger" 
                                                          data-posisi="<?= $namaPosisiAttr ?>"
                                                          data-kategori="Total Berkas"
                                                          data-items='<?= $jsonTotal ?>'>
                                                        <?= $totalPerPosisi ?>
                                                    </span>
                                                <?php else: ?>
                                                    <strong>0</strong>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- TABEL WASPADA -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h5 class="judul-section">
                            <span class="badge badge-warning">WASPADA</span>
                            Berkas Waspada
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-waspada">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>No. Berkas</th>
                                        <th>Nama Pemohon</th>
                                        <th>Layanan</th>
                                        <th>Posisi</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Umur</th>
                                        <th>Sisa Hari</th>
                                        <th>PIC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($totalWaspada == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            Tidak ada berkas dalam status waspada.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($dataWaspada as $no => $row): ?>
                                    <tr>
                                        <td><?= $no + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['no_berkas']) ?>/<?= htmlspecialchars($row['tahun']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_layanan'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['nama_posisi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_mulai_formatted']) ?></td>
                                        <td><?= $row['umur_hari'] ?> hari</td>
                                        <td>
                                            <span class="sisa-hari"><?= $row['sisa_hari'] ?> hari</span>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_pic'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TABEL KRITIS -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h5 class="judul-section">
                            <span class="badge badge-danger">KRITIS</span>
                            Berkas Kritis
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-kritis">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>No. Berkas</th>
                                        <th>Nama Pemohon</th>
                                        <th>Layanan</th>
                                        <th>Posisi</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Umur</th>
                                        <th>Sisa Hari</th>
                                        <th>PIC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($totalKritis == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            Tidak ada berkas dalam status kritis.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($dataKritis as $no => $row): ?>
                                    <tr>
                                        <td><?= $no + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['no_berkas']) ?>/<?= htmlspecialchars($row['tahun']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_layanan'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['nama_posisi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_mulai_formatted']) ?></td>
                                        <td><?= $row['umur_hari'] ?> hari</td>
                                        <td>
                                            <span class="sisa-hari"><?= $row['sisa_hari'] ?> hari</span>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_pic'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TABEL KADALUARSA -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h5 class="judul-section">
                            <span class="badge badge-dark">KADALUARSA</span>
                            Berkas Kadaluarsa
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-kadaluarsa">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>No. Berkas</th>
                                        <th>Nama Pemohon</th>
                                        <th>Layanan</th>
                                        <th>Posisi</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Umur</th>
                                        <th>Lewat</th>
                                        <th>PIC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($totalKadaluarsa == 0): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            Tidak ada berkas dalam status kadaluarsa.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($dataKadaluarsa as $no => $row): ?>
                                    <tr>
                                        <td><?= $no + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['no_berkas']) ?>/<?= htmlspecialchars($row['tahun']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_layanan'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['nama_posisi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_mulai_formatted']) ?></td>
                                        <td><?= $row['umur_hari'] ?> hari</td>
                                        <td>
                                            <span class="text-danger">Terlambat <?= -1*$row['sisa_hari'] ?> hari</span>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_pic'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<!-- MODAL DETAIL BERKAS PER POSISI -->
<div class="modal fade" id="modalDetailPosisi" tabindex="-1" role="dialog" aria-labelledby="modalDetailPosisiTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailPosisiTitle">Detail Berkas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>No.</th>
                                <th>No. Berkas</th>
                                <th>Nama Pemohon</th>
                                <th>Layanan</th>
                                <th>Tanggal Mulai</th>
                                <th>Umur</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.btn-modal-trigger').on('click', function() {
        const posisi = $(this).data('posisi');
        const kategori = $(this).data('kategori');
        const items = $(this).data('items');

        $('#modalDetailPosisiTitle').text('Detail Berkas - ' + posisi + ' (' + kategori + ')');

        let html = '';
        if (items && items.length > 0) {
            items.forEach((item, index) => {
                let badgeClass = 'badge-secondary';
                if (item.kategori === 'Waspada') badgeClass = 'badge-primary';
                else if (item.kategori === 'Kritis') badgeClass = 'badge-warning';
                else if (item.kategori === 'Kadaluarsa') badgeClass = 'badge-danger';

                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${item.no_berkas_tahun}</strong></td>
                        <td>${item.nama_pemohon}</td>
                        <td>${item.nama_layanan}</td>
                        <td>${item.tanggal_mulai}</td>
                        <td>${item.umur_hari}</td>
                        <td><span class="badge ${badgeClass}">${item.kategori}</span></td>
                    </tr>
                `;
            });
        } else {
            html = `<tr><td colspan="7" class="text-center text-muted">Tidak ada berkas.</td></tr>`;
        }

        $('#modalTableBody').html(html);
        $('#modalDetailPosisi').modal('show');
    });
});
</script>

</body>
</html>