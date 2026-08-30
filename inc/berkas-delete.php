<?php

require_once "koneksi.php";

header('Content-Type: application/json; charset=utf-8');

try {

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


    // Cek apakah data ada
    $sqlCheck = "
        SELECT id
        FROM berkas_rutin
        WHERE id = ?
        LIMIT 1
    ";

    $stmtCheck = $koneksi->prepare($sqlCheck);

    if (!$stmtCheck) {
        throw new Exception($koneksi->error);
    }

    $stmtCheck->bind_param("i", $id);

    $stmtCheck->execute();

    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows == 0) {

        echo json_encode([
            'status' => false,
            'message' => 'Data berkas tidak ditemukan.'
        ]);

        exit;
    }


    // Hapus data
    $sqlDelete = "
        DELETE FROM berkas_rutin
        WHERE id = ?
    ";

    $stmtDelete = $koneksi->prepare($sqlDelete);

    if (!$stmtDelete) {
        throw new Exception($koneksi->error);
    }

    $stmtDelete->bind_param("i", $id);

    if (!$stmtDelete->execute()) {

        throw new Exception($stmtDelete->error);
    }


    echo json_encode([
        'status' => true,
        'message' => 'Data berkas berhasil dihapus.'
    ]);

} catch (Throwable $e) {

    http_response_code(200);

    echo json_encode([
        'status' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);

}