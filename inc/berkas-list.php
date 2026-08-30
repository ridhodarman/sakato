<?php

require_once 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');

$today = date('Y-m-d');


/*
|--------------------------------------------------------------------------
| HARI LIBUR
|--------------------------------------------------------------------------
*/

$hariLibur = [];

$qLibur = mysqli_query($koneksi, "
    SELECT tanggal
    FROM hari_libur
");

if ($qLibur) {

    while ($rowLibur = mysqli_fetch_assoc($qLibur)) {

        /*
         * Jangan masukkan tanggal kosong / 0000-00-00
         */
        if (
            !empty($rowLibur['tanggal']) &&
            $rowLibur['tanggal'] !== '0000-00-00'
        ) {
            $hariLibur[$rowLibur['tanggal']] = true;
        }
    }
}


/*
|--------------------------------------------------------------------------
| FUNGSI JUMLAH HARI KERJA
|--------------------------------------------------------------------------
|
| NULL dan 0000-00-00 dianggap kosong.
|
*/

function getWorkingDays($startDate, $endDate, $hariLibur = [])
{
    /*
     * Tanggal kosong tidak dihitung
     */
    if (
        empty($startDate) ||
        empty($endDate) ||
        $startDate === '0000-00-00' ||
        $endDate === '0000-00-00'
    ) {
        return 0;
    }


    /*
     * Jika tanggal akhir lebih kecil dari tanggal mulai
     */
    if ($endDate < $startDate) {
        return 0;
    }


    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);


    /*
     * Jumlah hari kalender inklusif
     */
    $totalDays = (int)$start->diff($end)->days + 1;


    /*
     * Minggu penuh
     */
    $fullWeeks = intdiv($totalDays, 7);


    /*
     * 5 hari kerja setiap minggu
     */
    $workingDays = $fullWeeks * 5;


    /*
     * Sisa hari
     */
    $remainingDays = $totalDays % 7;

    $startDay = (int)$start->format('N');


    for ($i = 0; $i < $remainingDays; $i++) {

        $day = (($startDay - 1 + $i) % 7) + 1;

        if ($day <= 5) {
            $workingDays++;
        }
    }


    /*
     * Kurangi hari libur yang jatuh pada Senin-Jumat
     */
    foreach ($hariLibur as $holidayDate => $dummy) {

        if (
            $holidayDate >= $startDate &&
            $holidayDate <= $endDate
        ) {

            $holidayDay = (int)date(
                'N',
                strtotime($holidayDate)
            );

            if ($holidayDay <= 5) {
                $workingDays--;
            }
        }
    }


    return max(0, $workingDays);
}


/*
|--------------------------------------------------------------------------
| DATATABLES PARAMETER
|--------------------------------------------------------------------------
*/

$draw = isset($_GET['draw'])
    ? (int)$_GET['draw']
    : 1;


$start = isset($_GET['start'])
    ? (int)$_GET['start']
    : 0;


$length = isset($_GET['length'])
    ? (int)$_GET['length']
    : 50;


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
| SEARCH
|--------------------------------------------------------------------------
*/

$search = '';

if (isset($_GET['search']['value'])) {
    $search = trim($_GET['search']['value']);
}


/*
|--------------------------------------------------------------------------
| WHERE
|--------------------------------------------------------------------------
|
| NULL dan 0000-00-00 dianggap kosong.
|
*/

$where = "
    WHERE b.status = 'proses'
      AND b.tanggal_mulai IS NOT NULL
      AND b.tanggal_mulai <> '0000-00-00'
";


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


    $types = 'ssss';
}


/*
|--------------------------------------------------------------------------
| TOTAL DATA
|--------------------------------------------------------------------------
*/

$sqlTotal = "
    SELECT COUNT(*) AS total

    FROM berkas_rutin

    WHERE status = 'proses'
      AND tanggal_mulai IS NOT NULL
      AND tanggal_mulai <> '0000-00-00'
";


$qTotal = mysqli_query(
    $koneksi,
    $sqlTotal
);


$totalRecords = 0;


if ($qTotal) {

    $rowTotal = mysqli_fetch_assoc($qTotal);

    $totalRecords = (int)(
        $rowTotal['total'] ?? 0
    );
}


/*
|--------------------------------------------------------------------------
| TOTAL FILTERED
|--------------------------------------------------------------------------
|
| Kalau tidak ada pencarian, tidak perlu
| melakukan COUNT kedua.
|
*/

if ($search === '') {

    $totalFiltered = $totalRecords;

} else {

    $sqlFiltered = "
        SELECT COUNT(*) AS total

        FROM berkas_rutin b

        LEFT JOIN layanan l
            ON b.layanan_id = l.id

        $where
    ";


    $stmtFiltered = mysqli_prepare(
        $koneksi,
        $sqlFiltered
    );


    if (!empty($params)) {

        mysqli_stmt_bind_param(
            $stmtFiltered,
            $types,
            ...$params
        );
    }


    mysqli_stmt_execute(
        $stmtFiltered
    );


    $resultFiltered =
        mysqli_stmt_get_result(
            $stmtFiltered
        );


    $rowFiltered =
        mysqli_fetch_assoc(
            $resultFiltered
        );


    $totalFiltered = (int)(
        $rowFiltered['total'] ?? 0
    );


    mysqli_stmt_close(
        $stmtFiltered
    );
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$orderColumn = 3;

$orderDir = 'asc';


if (isset($_GET['order'][0]['column'])) {

    $orderColumn =
        (int)$_GET['order'][0]['column'];
}


if (isset($_GET['order'][0]['dir'])) {

    $orderDir =
        strtolower(
            $_GET['order'][0]['dir']
        ) === 'desc'
        ? 'DESC'
        : 'ASC';
}


/*
|--------------------------------------------------------------------------
| MAPPING ORDER
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


$orderBy =
    $orderColumns[$orderColumn]
    ?? 'b.tanggal_mulai';


/*
|--------------------------------------------------------------------------
| DATA HANYA SESUAI HALAMAN DATATABLES
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


$stmt = mysqli_prepare(
    $koneksi,
    $sql
);


$paramsData = $params;

$typesData = $types . 'ii';


$paramsData[] = $length;
$paramsData[] = $start;


mysqli_stmt_bind_param(
    $stmt,
    $typesData,
    ...$paramsData
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$data = [];


/*
|--------------------------------------------------------------------------
| PROSES DATA
|--------------------------------------------------------------------------
|
| Maksimal hanya 50/100 data.
|
*/

while ($row = mysqli_fetch_assoc($result)) {

    /*
     * Pengaman tambahan.
     *
     * Seharusnya data 0000-00-00 sudah tidak masuk
     * karena sudah difilter oleh WHERE.
     */
    if (
        empty($row['tanggal_mulai']) ||
        $row['tanggal_mulai'] === '0000-00-00'
    ) {
        continue;
    }


    /*
    |--------------------------------------------------------------------------
    | SLA
    |--------------------------------------------------------------------------
    */

    $sla = (int)(
        $row['sla_hari'] ?? 5
    );


    $waspada_limit = isset(
        $row['waspada']
    )
        ? (int)$row['waspada']
        : max(0, $sla - 2);


    $kritis_limit = isset(
        $row['kritis']
    )
        ? (int)$row['kritis']
        : max(0, $sla - 1);


    /*
    |--------------------------------------------------------------------------
    | HITUNG HARI KERJA
    |--------------------------------------------------------------------------
    */

    $working_days_elapsed =
        getWorkingDays(
            $row['tanggal_mulai'],
            $today,
            $hariLibur
        );


    $remaining_days =
        $sla - $working_days_elapsed;


    /*
    |--------------------------------------------------------------------------
    | STATUS SLA
    |--------------------------------------------------------------------------
    */

    if ($working_days_elapsed > $sla) {

        $sla_status = 'Jatuh Tempo';

        $badge_class =
            'bg-dark text-white';

    } elseif (
        $working_days_elapsed >=
        $kritis_limit
    ) {

        $sla_status = 'Kritis';

        $badge_class =
            'bg-danger';

    } elseif (
        $working_days_elapsed >=
        $waspada_limit
    ) {

        $sla_status = 'Waspada';

        $badge_class =
            'bg-warning text-dark';

    } else {

        $sla_status = 'Normal';

        $badge_class =
            'bg-primary';
    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE OUTPUT
    |--------------------------------------------------------------------------
    */

    $noBerkas =
        htmlspecialchars(
            $row['no_berkas'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        );


    $tahun =
        htmlspecialchars(
            $row['tahun'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        );


    $pemohon =
        htmlspecialchars(
            $row['nama_pemohon'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        );


    $layanan =
        htmlspecialchars(
            $row['nama_layanan'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        );


    /*
    |--------------------------------------------------------------------------
    | SISA WAKTU
    |--------------------------------------------------------------------------
    */

    if ($remaining_days < 0) {

        $sisaWaktu =
            '<span class="badge bg-danger text-white">'
            . 'Lewat '
            . abs($remaining_days)
            . ' hari'
            . '</span>';

    } elseif ($remaining_days == 0) {

        $sisaWaktu =
            '<span class="badge bg-warning text-dark">'
            . 'Hari Ini'
            . '</span>';

    } else {

        $sisaWaktu =
            '<span class="badge bg-success text-white">'
            . $remaining_days
            . ' hari lagi'
            . '</span>';
    }


    /*
    |--------------------------------------------------------------------------
    | TANGGAL MULAI
    |--------------------------------------------------------------------------
    */

    $tanggalMulai = '-';

    if (
        !empty($row['tanggal_mulai']) &&
        $row['tanggal_mulai'] !== '0000-00-00'
    ) {

        $timestamp = strtotime(
            $row['tanggal_mulai']
        );

        if ($timestamp !== false) {

            $tanggalMulai =
                date(
                    'd/m/Y',
                    $timestamp
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DATA DATATABLES
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

        $tanggalMulai,

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
| RESPONSE DATATABLES
|--------------------------------------------------------------------------
*/

echo json_encode(

    [

        'draw' =>
            $draw,

        'recordsTotal' =>
            $totalRecords,

        'recordsFiltered' =>
            $totalFiltered,

        'data' =>
            $data

    ],

    JSON_UNESCAPED_UNICODE
);