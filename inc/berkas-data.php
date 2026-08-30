<?php

require_once "koneksi.php";

header('Content-Type: application/json; charset=utf-8');


// =====================================================
// PARAMETER DATATABLES
// =====================================================

$draw = isset($_POST['draw'])
    ? intval($_POST['draw'])
    : 0;

$start = isset($_POST['start'])
    ? intval($_POST['start'])
    : 0;

$length = isset($_POST['length'])
    ? intval($_POST['length'])
    : 25;

$search = isset($_POST['search']['value'])
    ? trim($_POST['search']['value'])
    : '';


// =====================================================
// ORDER
// =====================================================

$columns = [
    0 => 'b.id',
    1 => 'b.no_berkas',
    2 => 'b.nama_pemohon',
    3 => 'b.tanggal_mulai',
    4 => 'l.nama_layanan'
];

$orderColumnIndex = isset($_POST['order'][0]['column'])
    ? intval($_POST['order'][0]['column'])
    : 0;

$orderDir = isset($_POST['order'][0]['dir'])
    ? strtolower($_POST['order'][0]['dir'])
    : 'desc';

if (!in_array($orderDir, ['asc', 'desc'])) {
    $orderDir = 'desc';
}

$orderColumn = $columns[$orderColumnIndex] ?? 'b.id';


// =====================================================
// TOTAL DATA
// =====================================================

$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM berkas_rutin b
";

$resultTotal = $koneksi->query($sqlTotal);
$totalData = $resultTotal->fetch_assoc()['total'];


// =====================================================
// WHERE SEARCH
// =====================================================

$where = "";
$params = [];
$types = "";

if ($search !== '') {
    $where = "
        WHERE
            CAST(b.no_berkas AS CHAR) LIKE ?
            OR CAST(b.tahun AS CHAR) LIKE ?
            OR b.nama_pemohon LIKE ?
            OR l.nama_layanan LIKE ?
            OR p.nama_posisi LIKE ?
            OR b.status LIKE ?
            OR b.catatan LIKE ?
    ";

    $searchParam = "%{$search}%";

    $params = [
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam
    ];

    $types = "sssssss";
}


// =====================================================
// TOTAL DATA SETELAH SEARCH
// =====================================================

$sqlFiltered = "
    SELECT COUNT(*) AS total
    FROM berkas_rutin b
    LEFT JOIN layanan l
        ON l.id = b.layanan_id
    LEFT JOIN posisi p
        ON p.id = b.posisi_id
    $where
";

$stmtFiltered = $koneksi->prepare($sqlFiltered);

if (!empty($params)) {
    $stmtFiltered->bind_param($types, ...$params);
}

$stmtFiltered->execute();
$resultFiltered = $stmtFiltered->get_result();
$filteredData = $resultFiltered->fetch_assoc()['total'];


// =====================================================
// DATA
// =====================================================

$sql = "
    SELECT
        b.id,
        b.no_berkas,
        b.tahun,
        b.nama_pemohon,
        b.tanggal_mulai,
        b.layanan_id,
        l.nama_layanan,
        b.posisi_id,
        p.nama_posisi,
        b.status,
        b.catatan,
        b.tanggal_selesai,
        b.on_update
    FROM berkas_rutin b
    LEFT JOIN layanan l
        ON l.id = b.layanan_id
    LEFT JOIN posisi p
        ON p.id = b.posisi_id
    $where
    ORDER BY $orderColumn $orderDir
    LIMIT ?, ?
";

$stmt = $koneksi->prepare($sql);

// Tambahkan LIMIT ke parameter
$paramsData = $params;
$paramsData[] = $start;
$paramsData[] = $length;

$typesData = $types . "ii";

$stmt->bind_param($typesData, ...$paramsData);
$stmt->execute();
$result = $stmt->get_result();


// =====================================================
// HASIL
// =====================================================

$data = [];
$nomor = $start + 1;

while ($row = $result->fetch_assoc()) {

    // -----------------------------------------------
    // STATUS
    // -----------------------------------------------
    $status = strtolower($row['status'] ?? '');

    if ($status == 'selesai') {
        $statusBadge = '<span class="status-badge status-selesai">
                            Selesai
                        </span>';
    } elseif ($status == 'eskalasi') {
        $statusBadge = '<span class="status-badge status-eskalasi">
                            Eskalasi
                        </span>';
    } else {
        $statusBadge = '<span class="status-badge status-proses">
                            Proses
                        </span>';
    }

    // -----------------------------------------------
    // POSISI
    // -----------------------------------------------
    $namaPosisi = trim($row['nama_posisi'] ?? '');
    if ($namaPosisi == '') {
        $namaPosisi = '-';
    }

    // -----------------------------------------------
    // CATATAN
    // -----------------------------------------------
    $catatan = trim($row['catatan'] ?? '');
    if ($catatan == '') {
        $catatan = '-';
    }

    // -----------------------------------------------
    // TANGGAL SELESAI
    // -----------------------------------------------
    $tanggalSelesai = $row['tanggal_selesai'] ?? '';
    if ($tanggalSelesai == '' || $tanggalSelesai == '0000-00-00') {
        $tanggalSelesai = '-';
    }

    // -----------------------------------------------
    // terakhir update
    // -----------------------------------------------
    $tanggalUpdate = $row['on_update'] ?? '';
    if ($tanggalUpdate == '' || $tanggalUpdate == '0000-00-00') {
        $tanggalUpdate = '-';
    }


    // -----------------------------------------------
    // KETERANGAN
    // -----------------------------------------------
    $keterangan = '
        <div class="keterangan">
            <div>
                <strong>Status:</strong><br>
                ' . $statusBadge . '
            </div>

            <div class="mt-1">
                <strong>Posisi Berkas:</strong><br>
                ' . htmlspecialchars($namaPosisi) . '
            </div>

            <div class="mt-1">
                <strong>Catatan:</strong><br>
                ' . nl2br(htmlspecialchars($catatan)) . '
            </div>

            <div class="mt-1">
                <strong>Tanggal Selesai:</strong><br>
                ' . htmlspecialchars($tanggalSelesai) . '
            </div>
            <div class="mt-1">
                <label>Terakhir update:</label><br>
                ' . htmlspecialchars($tanggalUpdate) . '
            </div>
        </div>
    ';

    // -----------------------------------------------
    // AKSI
    // -----------------------------------------------
    $aksi = '
        <button type="button"
                class="btn btn-sm btn-primary btnUpdate"
                data-id="' . intval($row['id']) . '"
                data-posisi-id="' . intval($row['posisi_id'] ?? 0) . '">
            Update
        </button>
    ';

    $data[] = [
        'no' => $nomor,
        'no_berkas' => htmlspecialchars($row['no_berkas']) . '/' . htmlspecialchars($row['tahun']),
        'nama_pemohon' => htmlspecialchars($row['nama_pemohon']),
        'tanggal_mulai' => htmlspecialchars($row['tanggal_mulai']),
        'nama_layanan' => htmlspecialchars($row['nama_layanan'] ?? '-'),
        'nama_posisi' => htmlspecialchars($namaPosisi),
        'keterangan' => $keterangan,
        'aksi' => $aksi
    ];

    $nomor++;
}


// =====================================================
// OUTPUT DATATABLES
// =====================================================

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => intval($totalData),
    'recordsFiltered' => intval($filteredData),
    'data' => $data
], JSON_UNESCAPED_UNICODE);