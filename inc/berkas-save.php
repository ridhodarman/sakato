<?php

require_once "koneksi.php";

header('Content-Type: application/json; charset=utf-8');


// =====================================================
// DATA
// =====================================================

$id = isset($_POST['id'])
    ? intval($_POST['id'])
    : 0;

$no_berkas = isset($_POST['no_berkas'])
    ? intval($_POST['no_berkas'])
    : 0;

$tahun = isset($_POST['tahun'])
    ? intval($_POST['tahun'])
    : 0;

$nama_pemohon = trim($_POST['nama_pemohon'] ?? '');

$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';

$layanan_id = isset($_POST['layanan_id'])
    ? intval($_POST['layanan_id'])
    : 0;

// Posisi Berkas
$posisi_id = isset($_POST['posisi_id']) && intval($_POST['posisi_id']) > 0
    ? intval($_POST['posisi_id'])
    : NULL;

// Data update
$status = $_POST['status'] ?? 'proses';

$catatan = trim($_POST['catatan'] ?? '');

$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';


// =====================================================
// VALIDASI
// =====================================================

if ($no_berkas <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'No. berkas wajib diisi.'
    ]);
    exit;
}

if ($tahun <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Tahun tidak valid.'
    ]);
    exit;
}

if ($nama_pemohon == '') {
    echo json_encode([
        'status' => false,
        'message' => 'Nama pemohon wajib diisi.'
    ]);
    exit;
}


if ($layanan_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Jenis layanan wajib dipilih.'
    ]);
    exit;
}


// =====================================================
// VALIDASI STATUS
// =====================================================

if (!in_array($status, ['proses', 'eskalasi', 'selesai'])) {
    $status = 'proses';
}


// =====================================================
// TAMBAH DATA
// =====================================================

if ($id <= 0) {
    $sql = "
        INSERT INTO berkas_rutin
        (
            no_berkas,
            tahun,
            nama_pemohon,
            tanggal_mulai,
            layanan_id,
            posisi_id,
            status,
            catatan,
            tanggal_selesai
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $koneksi->prepare($sql);

    // Tipe parameter: i (int), i (int), s (string), s (string), i (int), i (int), s (string), s (string), s (string)
    $stmt->bind_param(
        "iissiisss",
        $no_berkas,
        $tahun,
        $nama_pemohon,
        $tanggal_mulai,
        $layanan_id,
        $posisi_id,
        $status,
        $catatan,
        $tanggal_selesai
    );

    if ($stmt->execute()) {
        echo json_encode([
            'status' => true,
            'message' => 'Data berkas berhasil ditambahkan.'
        ]);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Data gagal ditambahkan: ' . $stmt->error
        ]);
    }
    exit;
}


// =====================================================
// UPDATE DATA
// =====================================================

$sql = "
    UPDATE berkas_rutin
    SET
        no_berkas = ?,
        tahun = ?,
        nama_pemohon = ?,
        tanggal_mulai = ?,
        layanan_id = ?,
        posisi_id = ?,
        status = ?,
        catatan = ?,
        tanggal_selesai = ?
    WHERE id = ?
";

$stmt = $koneksi->prepare($sql);

// Tipe parameter: i (int), i (int), s (string), s (string), i (int), i (int), s (string), s (string), s (string), i (int)
$stmt->bind_param(
    "iissiisssi",
    $no_berkas,
    $tahun,
    $nama_pemohon,
    $tanggal_mulai,
    $layanan_id,
    $posisi_id,
    $status,
    $catatan,
    $tanggal_selesai,
    $id
);

if ($stmt->execute()) {
    echo json_encode([
        'status' => true,
        'message' => 'Data berkas berhasil diperbarui.'
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'Data gagal diperbarui: ' . $stmt->error
    ]);
}