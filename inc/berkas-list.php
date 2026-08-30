<?php

require_once 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');

function addWorkingDays($startDate, $daysToAdd)
{
    $date = new DateTime($startDate);
    $added = 0;

    while ($added < $daysToAdd) {
        $date->modify('+1 day');

        if ($date->format('N') < 6) {
            $added++;
        }
    }

    return $date->format('Y-m-d');
}

function getWorkingDays($startDate, $endDate)
{
    $begin = new DateTime($startDate);
    $end   = new DateTime($endDate);

    $end->modify('+1 day');

    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($begin, $interval, $end);

    $workingDays = 0;

    foreach ($daterange as $date) {
        if ($date->format('N') < 6) {
            $workingDays++;
        }
    }

    return $workingDays;
}

$today = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| DataTables parameters
|--------------------------------------------------------------------------
*/

$draw   = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
$start  = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 50;

if ($length <= 0) {
    $length = 50;
}

if ($length > 100) {
    $length = 100;
}

if ($start < 0) {
    $start = 0;
}

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = '';

if (isset($_GET['search']['value'])) {
    $search = trim($_GET['search']['value']);
}

$where = "WHERE b.status = 'proses' AND b.tanggal_mulai IS NOT NULL";

$params = [];
$types  = '';

if ($search !== '') {

    $where .= "
        AND (
            b.no_berkas LIKE ?
            OR b.nama_pemohon LIKE ?
            OR l.nama_layanan LIKE ?
            OR b.tahun LIKE ?
        )
    ";

    $searchLike = '%' . $search . '%';

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;

    $types .= 'ssss';
}

/*
|--------------------------------------------------------------------------
| Total seluruh data
|--------------------------------------------------------------------------
*/

$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM berkas_rutin b
    WHERE b.status = 'proses'
      AND b.tanggal_mulai IS NOT NULL
";

$qTotal = mysqli_query($koneksi, $sqlTotal);

$totalRecords = (int)mysqli_fetch_assoc($qTotal)['total'];

/*
|--------------------------------------------------------------------------
| Total setelah pencarian
|--------------------------------------------------------------------------
*/

$sqlFiltered = "
    SELECT COUNT(*) AS total
    FROM berkas_rutin b
    LEFT JOIN layanan l ON b.layanan_id = l.id
    $where
";

$stmtFiltered = mysqli_prepare($koneksi, $sqlFiltered);

if (!empty($params)) {
    mysqli_stmt_bind_param(
        $stmtFiltered,
        $types,
        ...$params
    );
}

mysqli_stmt_execute($stmtFiltered);

$resultFiltered = mysqli_stmt_get_result($stmtFiltered);

$totalFiltered = (int)mysqli_fetch_assoc($resultFiltered)['total'];


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$orderColumn = 4;
$orderDir = 'asc';

if (isset($_GET['order'][0]['column'])) {
    $orderColumn = (int)$_GET['order'][0]['column'];
}

if (isset($_GET['order'][0]['dir'])) {
    $orderDir = strtolower($_GET['order'][0]['dir']) === 'desc'
        ? 'desc'
        : 'asc';
}

/*
|--------------------------------------------------------------------------
| Mapping kolom DataTables ke kolom database
|--------------------------------------------------------------------------
*/

$orderColumns = [
    0 => 'b.no_berkas',
    1 => 'b.nama_pemohon',
    2 => 'l.nama_layanan',
    3 => 'b.tanggal_mulai',
    4 => 'b.tanggal_mulai',
    5 => 'b.tanggal_mulai'
];

$orderBy = $orderColumns[$orderColumn] ?? 'b.tanggal_mulai';


/*
|--------------------------------------------------------------------------
| Query HANYA 50 data
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        b.id,
        b.no_berkas,
        b.tahun,
        b.nama_pemohon,
        b.tanggal_mulai,
        b.tanggal_selesai,
        b.status,

        l.nama_layanan,
        l.jatuh_tempo AS sla_hari,
        l.waspada,
        l.kritis

    FROM berkas_rutin b

    LEFT JOIN layanan l
        ON b.layanan_id = l.id

    $where

    ORDER BY $orderBy $orderDir

    LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($koneksi, $sql);

$paramsData = $params;
$typesData = $types . 'ii';

$paramsData[] = $length;
$paramsData[] = $start;

mysqli_stmt_bind_param(
    $stmt,
    $typesData,
    ...$paramsData
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = [];


/*
|--------------------------------------------------------------------------
| Proses maksimal 50 data
|--------------------------------------------------------------------------
*/

while ($row = mysqli_fetch_assoc($result)) {

    $sla = (int)($row['sla_hari'] ?? 5);

    $waspada_limit = isset($row['waspada'])
        ? (int)$row['waspada']
        : ($sla - 2);

    $kritis_limit = isset($row['kritis'])
        ? (int)$row['kritis']
        : ($sla - 1);


    $working_days_elapsed = getWorkingDays(
        $row['tanggal_mulai'],
        $today
    );

    $due_date = addWorkingDays(
        $row['tanggal_mulai'],
        $sla
    );

    $remaining_days = $sla - $working_days_elapsed;


    /*
    |--------------------------------------------------------------------------
    | Status SLA
    |--------------------------------------------------------------------------
    */

    if ($working_days_elapsed > $sla) {

        $sla_status = 'Jatuh Tempo';
        $badge_class = 'bg-dark text-white';

    } elseif ($working_days_elapsed >= $kritis_limit) {

        $sla_status = 'Kritis';
        $badge_class = 'bg-danger';

    } elseif ($working_days_elapsed >= $waspada_limit) {

        $sla_status = 'Waspada';
        $badge_class = 'bg-warning text-dark';

    } else {

        $sla_status = 'Normal';
        $badge_class = 'bg-primary';
    }


    /*
    |--------------------------------------------------------------------------
    | Buat HTML kolom
    |--------------------------------------------------------------------------
    */

    $noBerkas = htmlspecialchars(
        $row['no_berkas'] ?? '-',
        ENT_QUOTES,
        'UTF-8'
    );

    $tahun = htmlspecialchars(
        $row['tahun'] ?? '-',
        ENT_QUOTES,
        'UTF-8'
    );

    $pemohon = htmlspecialchars(
        $row['nama_pemohon'] ?? '-',
        ENT_QUOTES,
        'UTF-8'
    );

    $layanan = htmlspecialchars(
        $row['nama_layanan'] ?? '-',
        ENT_QUOTES,
        'UTF-8'
    );


    /*
    |--------------------------------------------------------------------------
    | Sisa waktu
    |--------------------------------------------------------------------------
    */

    if ($remaining_days < 0) {

        $sisaWaktu =
            '<span class="badge bg-danger bg-opacity-10 fw-bold text-light">'
            . 'Lewat ' . abs($remaining_days) . ' hari'
            . '</span>';

    } elseif ($remaining_days == 0) {

        $sisaWaktu =
            '<span class="badge bg-warning text-dark">'
            . 'Hari Ini'
            . '</span>';

    } else {

        $sisaWaktu =
            '<span class="badge bg-success bg-opacity-10">'
            . $remaining_days . ' hari lagi'
            . '</span>';
    }


    /*
    |--------------------------------------------------------------------------
    | Data untuk DataTables
    |--------------------------------------------------------------------------
    */

    $data[] = [

        '<span class="fw-bold text-navy">'
        . $noBerkas
        . ' / '
        . $tahun
        . '</span>',

        $pemohon,

        $layanan,

        date(
            'd/m/Y',
            strtotime($row['tanggal_mulai'])
        ),

        $sisaWaktu,

        '<span class="badge '
        . $badge_class
        . ' rounded-pill px-2 py-1">'
        . $sla_status
        . '</span>'
    ];
}


/*
|--------------------------------------------------------------------------
| Response DataTables
|--------------------------------------------------------------------------
*/

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data' => $data
], JSON_UNESCAPED_UNICODE);