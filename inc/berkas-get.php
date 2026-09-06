<?php

require_once "koneksi.php";

header('Content-Type: application/json; charset=utf-8');


$id = isset($_POST['id'])
    ? intval($_POST['id'])
    : 0;


if ($id <= 0) {

    echo json_encode([

        'status' => false,

        'message' => 'ID tidak valid.'

    ]);

    exit;

}


$sql = "
    SELECT

        id,
        no_berkas,
        tahun,
        nama_pemohon,
        tanggal_mulai,
        layanan_id,
        posisi_id,
        status,
        catatan,
        tanggal_selesai

    FROM berkas_rutin

    WHERE id = ?

    LIMIT 1
";


$stmt = $koneksi->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows == 0) {

    echo json_encode([

        'status' => false,

        'message' => 'Data tidak ditemukan.'

    ]);

    exit;

}


$data = $result->fetch_assoc();


echo json_encode([

    'status' => true,

    'data' => $data

], JSON_UNESCAPED_UNICODE);