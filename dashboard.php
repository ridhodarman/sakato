<?php
require_once 'auth.php';

/**
 * Ambil seluruh tanggal hari libur
 */
$hariLibur = [];

$q_hari_libur = mysqli_query($koneksi, "
    SELECT tanggal
    FROM hari_libur
");

if (!$q_hari_libur) {
    die('Query hari libur gagal: ' . mysqli_error($koneksi));
}

while ($row_libur = mysqli_fetch_assoc($q_hari_libur)) {
    $hariLibur[$row_libur['tanggal']] = true;
}


/**
 * Mengecek apakah suatu tanggal merupakan hari kerja
 *
 * Hari kerja:
 * - Senin-Jumat
 * - Bukan tanggal yang ada di tabel hari_libur
 */
function isWorkingDay($date, $hariLibur = [])
{
    $dateObj = new DateTime($date);

    // Sabtu / Minggu
    if ($dateObj->format('N') >= 6) {
        return false;
    }

    // Hari libur nasional / libur yang tersimpan di database
    if (isset($hariLibur[$dateObj->format('Y-m-d')])) {
        return false;
    }

    return true;
}


/**
 * Menghitung tanggal jatuh tempo berdasarkan penambahan hari kerja
 */
function addWorkingDays($startDate, $daysToAdd, $hariLibur = [])
{
    $date = new DateTime($startDate);
    $added = 0;

    while ($added < $daysToAdd) {

        $date->modify('+1 day');

        if (isWorkingDay($date->format('Y-m-d'), $hariLibur)) {
            $added++;
        }
    }

    return $date->format('Y-m-d');
}


/**
 * Menghitung jumlah hari kerja antara dua tanggal
 *
 * Tidak menghitung:
 * - Sabtu
 * - Minggu
 * - Hari libur dari tabel hari_libur
 */
function getWorkingDays($startDate, $endDate, $hariLibur = [])
{
    $begin = new DateTime($startDate);
    $end   = new DateTime($endDate);

    // Tetap inklusif sampai tanggal akhir
    $end->modify('+1 day');

    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($begin, $interval, $end);

    $workingDays = 0;

    foreach ($daterange as $date) {

        if (isWorkingDay($date->format('Y-m-d'), $hariLibur)) {
            $workingDays++;
        }
    }

    return $workingDays;
}

$today = date('Y-m-d');

// 1. Query KPI Base
$q_proses    = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM berkas_rutin WHERE status = 'proses'");
$q_selesai   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM berkas_rutin WHERE status = 'selesai'");
$q_eskalasi  = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM berkas_rutin WHERE status = 'eskalasi'");

$total_proses   = mysqli_fetch_assoc($q_proses)['total'] ?? 0;
$total_selesai  = mysqli_fetch_assoc($q_selesai)['total'] ?? 0;
$total_eskalasi = mysqli_fetch_assoc($q_eskalasi)['total'] ?? 0;

// 2. Calculation & Detail Mapping
$count_normal      = 0;
$count_waspada     = 0;
$count_kritis      = 0;
$count_jatuh_tempo = 0;

$count_ontime = 0;
$count_late   = 0;


/*
|--------------------------------------------------------------------------
| STATISTIK SLA
|--------------------------------------------------------------------------
| Kita tetap mengambil data yang diperlukan untuk menghitung statistik,
| tetapi TIDAK lagi membuat $berkas_aktif_list.
|
| Data tabel ditangani oleh DataTables -> list_berkas.php
|--------------------------------------------------------------------------
*/

$q_sla = mysqli_query($koneksi, "
    SELECT
        b.tanggal_mulai,
        b.tanggal_selesai,
        b.status,
        l.jatuh_tempo AS sla_hari,
        l.waspada,
        l.kritis
    FROM berkas_rutin b
    LEFT JOIN layanan l
        ON b.layanan_id = l.id
    WHERE b.tanggal_mulai IS NOT NULL
");

if (!$q_sla) {
    die('Query SLA gagal: ' . mysqli_error($koneksi));
}


while ($row = mysqli_fetch_assoc($q_sla)) {

    $sla = (int)($row['sla_hari'] ?? 5);

    $waspada_limit = isset($row['waspada'])
        ? (int)$row['waspada']
        : ($sla - 2);

    $kritis_limit = isset($row['kritis'])
        ? (int)$row['kritis']
        : ($sla - 1);


    /*
    |--------------------------------------------------------------------------
    | BERKAS DALAM PROSES
    |--------------------------------------------------------------------------
    */

    if ($row['status'] === 'proses') {

        $working_days_elapsed = getWorkingDays(
            $row['tanggal_mulai'],
            $today,
            $hariLibur
        );


        /*
        | Jatuh Tempo
        */

        if ($working_days_elapsed > $sla) {

            $count_jatuh_tempo++;

        }

        /*
        | Kritis
        */

        elseif ($working_days_elapsed >= $kritis_limit) {

            $count_kritis++;

        }

        /*
        | Waspada
        */

        elseif ($working_days_elapsed >= $waspada_limit) {

            $count_waspada++;

        }

        /*
        | Normal
        */

        else {

            $count_normal++;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | BERKAS SELESAI
    |--------------------------------------------------------------------------
    */

    if ($row['status'] === 'selesai') {

        $end_date_selesai = !empty($row['tanggal_selesai'])
            ? $row['tanggal_selesai']
            : $today;


        $working_days_actual = getWorkingDays(
            $row['tanggal_mulai'],
            $end_date_selesai,
            $hariLibur
        );


        if ($working_days_actual <= $sla) {

            $count_ontime++;

        } else {

            $count_late++;

        }
    }
}


/*
|--------------------------------------------------------------------------
| WINRATE
|--------------------------------------------------------------------------
*/

$total_selesai_eval = $count_ontime + $count_late;

$ontime_rate = ($total_selesai_eval > 0)
    ? round(
        ($count_ontime / $total_selesai_eval) * 100,
        1
      )
    : 0;
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO V2 - Kinerja PIC</title>
    <?php include "inc/head.php" ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background-color: #f4f7fb; color: #172033; font-family: 'Segoe UI', Arial, sans-serif; }
        .kpi-card { 
            border: none; 
            border-radius: 12px; 
            transition: transform 0.2s, box-shadow 0.2s; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            min-height: 100px;
        }
        .kpi-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.08); 
        }
        .kpi-icon { 
            width: 44px; 
            height: 44px; 
            border-radius: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.2rem;
        }
        .bg-navy { background-color: #0f2e50; color: #fff; }
        .table-v-middle td, .table-v-middle th { vertical-align: middle; }
    </style>
</head>

<body>
<div class="container-fluid p-0">
    <div class="row no-gutters min-vh-100">
        <!-- Sidebar -->
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <aside class="col-md-3 col-lg-2 text-white p-3 menusidebar">
            <?php include "inc/sidebar.php"; ?>
        </aside>

        <!-- Main Content -->
        <main class="col-md-9 col-lg-10 p-4">
            <div class="container-fluid py-2">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0">Dashboard Monitoring Berkas</h3>
                        <small class="text-muted">Sistem Akselerasi Kolaboratif Tunggakan Online</small>
                    </div>
                    <span class="badge bg-navy px-3 py-2 rounded-pill fs-6 shadow-sm">
                        <i class="far fa-calendar-alt me-1"></i> <?= date('d M Y'); ?>
                    </span>
                </div>

                <!-- KPI Cards Row -->
                <div class="row g-3 mb-4">
                    <!-- Dalam Proses -->
                    <div class="col-12 col-sm-6 col-xl-2">
                        <div class="card kpi-card p-3 bg-white border-start border-primary border-4 h-100 justify-content-center">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Dalam Proses</small>
                                    <h3 class="fw-bold mb-0 text-primary"><?= $total_proses; ?></h3>
                                </div>
                                <div class="kpi-icon bg-primary bg-opacity-10"><i class="fas fa-spin"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Selesai -->
                    <div class="col-12 col-sm-6 col-xl-2">
                        <div class="card kpi-card p-3 bg-white border-start border-success border-4 h-100 justify-content-center">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Selesai</small>
                                    <h3 class="fw-bold mb-0 text-success"><?= $total_selesai; ?></h3>
                                </div>
                                <div class="kpi-icon bg-success bg-opacity-10"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Eskalasi -->
                    <div class="col-12 col-sm-6 col-xl-2">
                        <div class="card kpi-card p-3 bg-white border-start border-secondary border-4 h-100 justify-content-center">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Eskalasi</small>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $total_eskalasi; ?></h3>
                                </div>
                                <div class="kpi-icon bg-secondary bg-opacity-10 text-dark"><i class="fas fa-arrow-up-right-dots"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Waspada -->
                    <div class="col-12 col-sm-6 col-xl-2">
                        <div class="card kpi-card p-3 bg-white border-start border-warning border-4 h-100 justify-content-center">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Waspada</small>
                                    <h3 class="fw-bold mb-0 text-warning"><?= $count_waspada; ?></h3>
                                </div>
                                <div class="kpi-icon bg-warning bg-opacity-10"><i class="fas fa-exclamation-triangle"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Kritis -->
                    <div class="col-12 col-sm-6 col-xl-2">
                        <div class="card kpi-card p-3 bg-white border-start border-danger border-4 h-100 justify-content-center">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Kritis</small>
                                    <h3 class="fw-bold mb-0 text-danger"><?= $count_kritis; ?></h3>
                                </div>
                                <div class="kpi-icon bg-danger bg-opacity-10"><i class="fas fa-circle-exclamation"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Jatuh Tempo -->
                    <div class="col-12 col-sm-6 col-xl-2">
                        <div class="card kpi-card p-3 bg-white border-start border-dark border-4 h-100 justify-content-center">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Jatuh Tempo</small>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $count_jatuh_tempo; ?></h3>
                                </div>
                                <div class="kpi-icon bg-dark bg-opacity-10"><i class="fas fa-times-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row g-4 mb-4">
                    <!-- Winrate Penyelesaian Berkas (Doughnut Chart) -->
                    <div class="col-12 col-md-6 col-xl-6">
                        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0"><i class="fas fa-trophy me-2 text-success"></i>Winrate Penyelesaian Berkas (SOP)</h6>
                                <span class="badge bg-success bg-opacity-10 fw-bold fs-6">
                                    <?= $ontime_rate; ?>% Tepat Waktu
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-center" style="height: 240px;">
                                <canvas id="winrateChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- SLA Condition (Bar Chart) -->
                    <div class="col-12 col-md-6 col-xl-6">
                        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white">
                            <h6 class="fw-bold mb-3"><i class="fas fa-shield-halved me-2 text-warning"></i>Kondisi SLA Berkas Berjalan</h6>
                            <div class="d-flex align-items-center justify-content-center" style="height: 240px;">
                                <canvas id="slaChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Due Date Monitoring -->
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-danger"></i>Monitoring Tenggat Waktu Berkas</h6>
                                    <small class="text-muted">Daftar berkas aktif beserta proyeksi status SLA.</small>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table
                                    id="tableBerkas"
                                    class="table table-hover table-v-middle mb-0"
                                    style="width:100%"
                                >

                                    <thead class="table-light">

                                        <tr>
                                            <th>No. Berkas</th>
                                            <th>Pemohon</th>
                                            <th>Layanan</th>
                                            <th>Tgl Mulai</th>
                                            <th>Sisa Waktu</th>
                                            <th>Status SLA</th>
                                        </tr>

                                    </thead>

                                    <tbody>
                                    </tbody>

                                </table>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<!-- Chart Initialization Script -->
<script>
    // ==========================================================
    // 1. Chart Winrate Penyelesaian Berkas Sesuai SOP (Doughnut)
    // ==========================================================
    const ctxWinrate = document.getElementById('winrateChart').getContext('2d');

    new Chart(ctxWinrate, {
        type: 'doughnut',
        data: {
            labels: ['Tepat Waktu (SOP)', 'Terlambat'],
            datasets: [{
                data: [
                    <?= $count_ontime; ?>,
                    <?= $count_late; ?>
                ],
                backgroundColor: [
                    '#198754', // Hijau (Ontime)
                    '#dc3545'  // Merah (Terlambat)
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let total = <?= $total_selesai_eval; ?>;
                            let val = context.raw;
                            let percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                            return ' ' + context.label + ': ' + val + ' Berkas (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });


    // ==========================================================
    // 2. Chart Kondisi SLA Berkas Berjalan
    // ==========================================================
    const ctxSla = document.getElementById('slaChart').getContext('2d');

    new Chart(ctxSla, {
        type: 'bar',
        data: {
            labels: [
                'Normal',
                'Waspada',
                'Kritis',
                'Jatuh Tempo'
            ],
            datasets: [{
                label: 'Jumlah Berkas',
                data: [
                    <?= $count_normal; ?>,
                    <?= $count_waspada; ?>,
                    <?= $count_kritis; ?>,
                    <?= $count_jatuh_tempo; ?>
                ],
                backgroundColor: [
                    '#0d6efd',
                    '#ffc107',
                    '#dc3545',
                    '#212529'
                ],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.raw + ' Berkas';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>

<script>

$(document).ready(function () {

    $('#tableBerkas').DataTable({

        processing: true,

        serverSide: true,

        pageLength: 50,

        lengthMenu: [
            [50, 100, 250],
            [50, 100, 250]
        ],

        ajax: {
            url: 'inc/berkas-list.php',
            type: 'GET'
        },

        columns: [

            {
                data: 0
            },

            {
                data: 1
            },

            {
                data: 2
            },

            {
                data: 3
            },

            {
                data: 4,
                orderable: false,
                searchable: false
            },

            {
                data: 5,
                orderable: false,
                searchable: false
            }

        ],

        order: [
            [3, 'asc']
        ],

        language: {

            processing: 'Memuat data...',

            search: 'Cari:',

            lengthMenu:
                'Tampilkan _MENU_ data',

            info:
                'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',

            infoEmpty:
                'Tidak ada data',

            zeroRecords:
                'Data tidak ditemukan',

            paginate: {

                first: 'Pertama',

                last: 'Terakhir',

                next: 'Berikutnya',

                previous: 'Sebelumnya'
            }

        }

    });

});

</script>

</body>
</html>