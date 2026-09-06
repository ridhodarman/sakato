<?php
require_once 'auth.php';

/*
|--------------------------------------------------------------------------
| ID AKUN YANG SEDANG LOGIN
|--------------------------------------------------------------------------
*/
$id_user = (int)($_SESSION['id_user'] ?? 0);


/*
|--------------------------------------------------------------------------
| AMBIL DATA HAK AKSES AKUN LOGIN
|--------------------------------------------------------------------------
*/
$stmtAkses = $koneksi->prepare("
    SELECT
        id,
        nama,
        atur_eskalasi
    FROM akun_sakato
    WHERE id = ?
    LIMIT 1
");

$stmtAkses->bind_param("i", $id_user);
$stmtAkses->execute();

$resultAkses = $stmtAkses->get_result();
$userLogin = $resultAkses->fetch_assoc();

$stmtAkses->close();


$bolehAturEskalasi = (
    $userLogin &&
    (int)$userLogin['atur_eskalasi'] === 1
);


/*
|--------------------------------------------------------------------------
| PROSES SELESAI ESKALASI
|--------------------------------------------------------------------------
|
| Ketika berkas selesai ditangani:
| 1. Status berkas dikembalikan menjadi proses
| 2. Semua relasi eskalasi berkas tersebut dihapus
|
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['selesai_id'])
) {

    $id = (int)$_POST['selesai_id'];

    if ($id > 0) {

        /*
        |--------------------------------------------------------------
        | Hapus semua tujuan eskalasi berkas
        |--------------------------------------------------------------
        */
        $stmt = $koneksi->prepare("
            DELETE FROM eskalasi
            WHERE berkas_rutin_id = ?
        ");

        if (!$stmt) {
            die("Prepare hapus eskalasi gagal: " . $koneksi->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            die("Hapus eskalasi gagal: " . $stmt->error);
        }

        $stmt->close();


        /*
        |--------------------------------------------------------------
        | Ubah status berkas menjadi proses
        |--------------------------------------------------------------
        */
        $stmt = $koneksi->prepare("
            UPDATE berkas_rutin
            SET status = 'proses'
            WHERE id = ?
            AND status = 'eskalasi'
        ");

        if (!$stmt) {
            die("Prepare update berkas gagal: " . $koneksi->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            die("Update berkas gagal: " . $stmt->error);
        }

        $stmt->close();
    }


    /*
    |--------------------------------------------------------------
    | Redirect agar tidak terjadi resubmit ketika refresh
    |--------------------------------------------------------------
    */
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


/*
|--------------------------------------------------------------------------
| PROSES TAMBAH TUJUAN ESKALASI
|--------------------------------------------------------------------------
|
| Hanya akun dengan atur_eskalasi = 1 yang boleh menjalankan proses ini.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| PROSES SIMPAN TUJUAN ESKALASI
|--------------------------------------------------------------------------
*/

if (
    //PROSES SIMPAN TUJUAN ESKALASI&& 
    isset($_POST['simpan_eskalasi'])
) {

    // =====================================================
    // CEK HAK AKSES
    // =====================================================

    if (!$bolehAturEskalasi) {

        $_SESSION['flash_message'] = [
            'type'  => 'error',
            'title' => 'Akses Ditolak',
            'text'  => 'Anda tidak memiliki akses untuk atur eskalasi.'
        ];

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }


    // =====================================================
    // AMBIL DATA FORM
    // =====================================================

    $berkas_id = (int)($_POST['berkas_id'] ?? 0);

    $akun_tujuan = $_POST['akun_tujuan'] ?? [];

    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';
    // exit;


    // Pastikan selalu array
    if (!is_array($akun_tujuan)) {
        $akun_tujuan = [];
    }


    // =====================================================
    // VALIDASI BERKAS
    // =====================================================

    if ($berkas_id <= 0) {

        $_SESSION['flash_message'] = [
            'type'  => 'error',
            'title' => 'Gagal',
            'text'  => 'ID berkas tidak valid.'
        ];

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }


    // =====================================================
    // CEK BERKAS
    // =====================================================

    $stmt = $koneksi->prepare("
        SELECT id
        FROM berkas_rutin
        WHERE id = ?
        AND status = 'eskalasi'
        LIMIT 1
    ");

    if (!$stmt) {

        $_SESSION['flash_message'] = [
            'type'  => 'error',
            'title' => 'Gagal',
            'text'  => 'Prepare cek berkas gagal: ' . $koneksi->error
        ];

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt->bind_param("i", $berkas_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $berkasValid = $result->fetch_assoc();

    $stmt->close();


    if (!$berkasValid) {

        $_SESSION['flash_message'] = [
            'type'  => 'error',
            'title' => 'Gagal',
            'text'  => 'Berkas tidak ditemukan atau statusnya bukan eskalasi.'
        ];

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }


    // =====================================================
    // TRANSACTION
    // =====================================================

    $koneksi->begin_transaction();

    try {

        // =================================================
        // HAPUS TUJUAN LAMA
        // =================================================

        $stmtDelete = $koneksi->prepare("
            DELETE FROM eskalasi
            WHERE berkas_rutin_id = ?
        ");

        if (!$stmtDelete) {
            throw new Exception(
                "Prepare DELETE gagal: " . $koneksi->error
            );
        }

        $stmtDelete->bind_param("i", $berkas_id);

        if (!$stmtDelete->execute()) {
            throw new Exception(
                "DELETE gagal: " . $stmtDelete->error
            );
        }

        $stmtDelete->close();


        // =================================================
        // INSERT TUJUAN BARU
        // =================================================

        $jumlahBerhasil = 0;

        if (count($akun_tujuan) > 0) {

            $stmtInsert = $koneksi->prepare("
                INSERT INTO eskalasi
                (
                    berkas_rutin_id,
                    akun_sakato_id
                )
                VALUES (?, ?)
            ");

            if (!$stmtInsert) {
                throw new Exception(
                    "Prepare INSERT gagal: " . $koneksi->error
                );
            }


            foreach ($akun_tujuan as $akun_id) {
                // echo '<pre>';

                // echo "BERKAS ID: ";
                // var_dump($berkas_id);

                // echo "\nAKUN TUJUAN:\n";
                // var_dump($akun_tujuan);

                // foreach ($akun_tujuan as $akun_id) {

                //     echo "\n============================\n";
                //     echo "AKUN ID RAW: ";
                //     var_dump($akun_id);

                //     $akun_id = (int)$akun_id;

                //     echo "AKUN ID INT: ";
                //     var_dump($akun_id);

                //     if ($akun_id <= 0) {
                //         echo "SKIP: ID akun <= 0\n";
                //         continue;
                //     }

                //     $stmtCek = $koneksi->prepare("
                //         SELECT id, username, nama
                //         FROM akun_sakato
                //         WHERE id = ?
                //         LIMIT 1
                //     ");

                //     if (!$stmtCek) {
                //         echo "PREPARE CEK AKUN GAGAL: ";
                //         echo $koneksi->error;
                //         continue;
                //     }

                //     $stmtCek->bind_param("i", $akun_id);

                //     if (!$stmtCek->execute()) {
                //         echo "EXECUTE CEK AKUN GAGAL: ";
                //         echo $stmtCek->error;
                //         $stmtCek->close();
                //         continue;
                //     }

                //     $resultCek = $stmtCek->get_result();

                //     $akunAda = $resultCek->fetch_assoc();

                //     echo "HASIL CEK AKUN:\n";
                //     var_dump($akunAda);

                //     $stmtCek->close();

                //     if (!$akunAda) {
                //         echo "SKIP: AKUN TIDAK DITEMUKAN\n";
                //         continue;
                //     }


                //     // INSERT
                //     $stmtInsert = $koneksi->prepare("
                //         INSERT INTO eskalasi
                //         (
                //             berkas_rutin_id,
                //             akun_sakato_id
                //         )
                //         VALUES (?, ?)
                //     ");

                //     if (!$stmtInsert) {
                //         echo "PREPARE INSERT GAGAL: ";
                //         echo $koneksi->error;
                //         continue;
                //     }

                //     $stmtInsert->bind_param(
                //         "ii",
                //         $berkas_id,
                //         $akun_id
                //     );

                //     if (!$stmtInsert->execute()) {

                //         echo "INSERT GAGAL:\n";
                //         echo $stmtInsert->error;

                //     } else {

                //         echo "INSERT BERHASIL!\n";
                //         echo "ID INSERT: ";
                //         echo $stmtInsert->insert_id;
                //     }

                //     $stmtInsert->close();
                // }

                // echo "\n============================\n";
                // echo "SELESAI";

                // echo '</pre>';

                // exit;

                $akun_id = (int)$akun_id;

                if ($akun_id < 0) {
                    continue;
                }


                // =============================================
                // PASTIKAN AKUN ADA
                // =============================================

                $stmtCek = $koneksi->prepare("
                    SELECT id
                    FROM akun_sakato
                    WHERE id = ?
                    LIMIT 1
                ");

                if (!$stmtCek) {
                    throw new Exception(
                        "Prepare cek akun gagal: " . $koneksi->error
                    );
                }

                $stmtCek->bind_param("i", $akun_id);
                $stmtCek->execute();

                $resultCek = $stmtCek->get_result();

                $akunAda = $resultCek->fetch_assoc();

                $stmtCek->close();


                if (!$akunAda) {
                    continue;
                }


                // =============================================
                // INSERT
                // =============================================

                $stmtInsert->bind_param(
                    "ii",
                    $berkas_id,
                    $akun_id
                );

                if (!$stmtInsert->execute()) {
                    throw new Exception(
                        "INSERT gagal untuk akun ID "
                        . $akun_id
                        . ": "
                        . $stmtInsert->error
                    );
                }

                $jumlahBerhasil++;
            }


            $stmtInsert->close();
        }


        // =================================================
        // COMMIT
        // =================================================

        $koneksi->commit();


        // =================================================
        // HASIL
        // =================================================

        $_SESSION['flash_message'] = [
            'type'  => 'success',
            'title' => 'Berhasil!',
            'text'  => 'Tujuan eskalasi berhasil disimpan. '
                     . $jumlahBerhasil
                     . ' akun ditetapkan sebagai tujuan.'
        ];


    } catch (Exception $e) {

        $koneksi->rollback();


        $_SESSION['flash_message'] = [
            'type'  => 'error',
            'title' => 'Gagal!',
            'text'  => $e->getMessage()
        ];
    }


    // =====================================================
    // REDIRECT
    // =====================================================

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}



/*
|--------------------------------------------------------------------------
| TANGGAL HARI INI
|--------------------------------------------------------------------------
*/

$hariIni = new DateTime();


/*
|--------------------------------------------------------------------------
| AMBIL DATA HARI LIBUR
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| FUNGSI CEK HARI KERJA
|--------------------------------------------------------------------------
*/

function isWorkingDay($tanggal, $hariLibur = [])
{

    $date = new DateTime($tanggal);


    /*
    | Sabtu dan Minggu
    */
    if ((int)$date->format('N') >= 6) {
        return false;
    }


    /*
    | Hari libur database
    */
    if (isset($hariLibur[$date->format('Y-m-d')])) {
        return false;
    }


    return true;
}


/*
|--------------------------------------------------------------------------
| FUNGSI HITUNG UMUR BERKAS
|--------------------------------------------------------------------------
*/

function getWorkingDays($startDate, $endDate, $hariLibur = [])
{

    $begin = new DateTime($startDate);
    $end   = new DateTime($endDate);


    /*
    | Tanggal mulai dan tanggal akhir inklusif
    */
    $end->modify('+1 day');


    $interval = new DateInterval('P1D');

    $daterange = new DatePeriod(
        $begin,
        $interval,
        $end
    );


    $workingDays = 0;


    foreach ($daterange as $date) {

        if (
            isWorkingDay(
                $date->format('Y-m-d'),
                $hariLibur
            )
        ) {

            $workingDays++;

        }

    }


    return $workingDays;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA ESKALASI
|--------------------------------------------------------------------------
|
| Tidak lagi menggunakan b.tujuan_eskalasi.
|
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        b.id,
        b.no_berkas,
        b.tahun,
        b.nama_pemohon,
        b.tanggal_mulai,
        b.layanan_id,
        b.posisi_id,
        b.status,
        b.catatan,
        b.tanggal_selesai,
        b.on_update,

        l.nama_layanan,

        p.nama AS nama_pic,

        (
        SELECT COUNT(*)
            FROM eskalasi e2
            WHERE e2.berkas_rutin_id = b.id
        ) AS jumlah_tujuan_eskalasi,

        (
            SELECT GROUP_CONCAT(
                a2.nama
                ORDER BY a2.nama ASC
                SEPARATOR '|||'
            )
            FROM eskalasi e3
            INNER JOIN akun_sakato a2
                ON a2.id = e3.akun_sakato_id
            WHERE e3.berkas_rutin_id = b.id
        ) AS nama_tujuan_eskalasi

    FROM berkas_rutin b

    LEFT JOIN layanan l
        ON l.id = b.layanan_id

    LEFT JOIN pic p
        ON p.id = b.posisi_id

    WHERE b.status = 'eskalasi'

    ORDER BY b.tanggal_mulai ASC
";


$result = $koneksi->query($sql);

if (!$result) {

    die("Query gagal: " . $koneksi->error);

}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$dataEskalasi = [];


/*
|--------------------------------------------------------------------------
| STATISTIK SEKSI
|--------------------------------------------------------------------------
*/

$statistikSeksi = [];


/*
|--------------------------------------------------------------------------
| PROSES DATA
|--------------------------------------------------------------------------
*/

while ($row = $result->fetch_assoc()) {


    /*
    |--------------------------------------------------------------
    | UMUR BERKAS
    |--------------------------------------------------------------
    */

    if (!empty($row['tanggal_mulai'])) {

        $umurHari = getWorkingDays(
            $row['tanggal_mulai'],
            $hariIni->format('Y-m-d'),
            $hariLibur
        );

    } else {

        $umurHari = 0;

    }


    $row['umur_hari'] = $umurHari;


    /*
    |--------------------------------------------------------------
    | SISA JATUH TEMPO
    |--------------------------------------------------------------
    */

    $jatuhTempo = (int)($row['jatuh_tempo'] ?? 0);


    if ($jatuhTempo > 0) {

        $sisaHari = $jatuhTempo - $umurHari;

    } else {

        $sisaHari = null;

    }


    $row['sisa_hari'] = $sisaHari;


    /*
    |--------------------------------------------------------------
    | KATEGORI WAKTU
    |--------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------
    | STATISTIK SEKSI
    |--------------------------------------------------------------
    */

    $seksi = trim($row['seksi'] ?? '');


    if ($seksi == '') {

        $seksi = 'Belum ditentukan';

    }


    if (!isset($statistikSeksi[$seksi])) {

        $statistikSeksi[$seksi] = 0;

    }


    $statistikSeksi[$seksi]++;


    /*
    |--------------------------------------------------------------
    | SIMPAN
    |--------------------------------------------------------------
    */

    $dataEskalasi[] = $row;

}


/*
|--------------------------------------------------------------------------
| TOTAL ESKALASI
|--------------------------------------------------------------------------
*/

$totalEskalasi = count($dataEskalasi);


/*
|--------------------------------------------------------------------------
| URUTKAN SEKSI
|--------------------------------------------------------------------------
*/

arsort($statistikSeksi);


/*
|--------------------------------------------------------------------------
| HITUNG ESKALASI UNTUK AKUN LOGIN
|--------------------------------------------------------------------------
*/

$stmtSaya = $koneksi->prepare("
    SELECT COUNT(DISTINCT e.berkas_rutin_id) AS jumlah
    FROM eskalasi e
    INNER JOIN berkas_rutin b
        ON b.id = e.berkas_rutin_id
    WHERE
        e.akun_sakato_id = ?
        AND b.status = 'eskalasi'
");

$stmtSaya->bind_param("i", $id_user);
$stmtSaya->execute();

$resultSaya = $stmtSaya->get_result();

$dataSaya = $resultSaya->fetch_assoc();

$stmtSaya->close();


$jumlahEskalasiSaya = (int)($dataSaya['jumlah'] ?? 0);


/*
|--------------------------------------------------------------------------
| AMBIL SEMUA AKUN UNTUK TUJUAN ESKALASI
|--------------------------------------------------------------------------
*/

$dataAkun = [];

$sqlAkun = "
    SELECT
        id,
        username,
        nama
    FROM akun_sakato
    ORDER BY nama ASC
";

$resultAkun = $koneksi->query($sqlAkun);

if ($resultAkun) {

    while ($akun = $resultAkun->fetch_assoc()) {

        $dataAkun[] = $akun;

    }

}


/*
|--------------------------------------------------------------------------
| DATA ESKALASI YANG DITUJUKAN KE AKUN LOGIN
|--------------------------------------------------------------------------
*/

$dataEskalasiSaya = [];

$stmtSayaList = $koneksi->prepare("
    SELECT

        b.id,
        b.no_berkas,
        b.tahun,
        b.nama_pemohon,
        b.tanggal_mulai,
        b.catatan,

        l.nama_layanan,
        l.seksi

    FROM eskalasi e

    INNER JOIN berkas_rutin b
        ON b.id = e.berkas_rutin_id

    LEFT JOIN layanan l
        ON l.id = b.layanan_id

    WHERE
        e.akun_sakato_id = ?
        AND b.status = 'eskalasi'

    ORDER BY
        b.tanggal_mulai ASC
");

$stmtSayaList->bind_param("i", $id_user);
$stmtSayaList->execute();

$resultSayaList = $stmtSayaList->get_result();


while ($rowSaya = $resultSayaList->fetch_assoc()) {

    $dataEskalasiSaya[] = $rowSaya;

}

$stmtSayaList->close();

?>

<!doctype html>

<html lang="id">

<head>


<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>SAKATO</title>

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
       KARTU ESKALASI SAYA
       ================================================= */

    .eskalasi-saya {

        cursor:
            pointer;

        transition:
            .2s;

    }


    .eskalasi-saya:hover {

        transform:
            translateY(-3px);

        box-shadow:
            0 12px 30px rgba(15,23,42,.12);

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
       TABLE
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
       CATATAN
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
       CHECKBOX AKUN
       ================================================= */

    .akun-list {

        max-height:
            300px;

        overflow-y:
            auto;

        border:
            1px solid #e2e8f0;

        border-radius:
            8px;

        padding:
            10px;

    }


    .akun-item {

        padding:
            9px 10px;

        border-bottom:
            1px solid #f1f5f9;

    }

    .tujuan-eskalasi {
        min-width: 160px;
    }

    .tujuan-akun {
        display: block;
        padding: 5px 9px;
        margin-bottom: 4px;
        background: #eff6ff;
        color: #1e40af;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .tujuan-akun:last-child {
        margin-bottom: 0;
    }

    .tujuan-akun i {
        width: 16px;
        margin-right: 4px;
        font-size: 11px;
    }


    .akun-item:last-child {

        border-bottom:
            none;

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


            <!-- =================================================
                 HEADER
                 ================================================= -->

            <div class="page-header">

                <h1 class="page-title">

                    Monitoring Eskalasi

                </h1>


                <div class="page-subtitle">

                    Berkas yang memerlukan pertimbangan dan keputusan pimpinan

                </div>

            </div>


            <!-- =================================================
                 STATISTIK
                 ================================================= -->

            <div class="row mb-4">


                <!-- TOTAL ESKALASI -->

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


                <!-- =================================================
                     ESKALASI SAYA
                     ================================================= -->

                <div class="col-lg-4 col-md-6 mb-3">

                    <div
                        class="stat-card eskalasi-saya"
                        data-toggle="modal"
                        data-target="#modalEskalasiSaya"
                    >

                        <div class="stat-icon">

                            👤

                        </div>


                        <div class="stat-label">

                            Eskalasi Saya

                        </div>


                        <div class="stat-number">

                            <?= $jumlahEskalasiSaya ?>

                        </div>


                        <div class="stat-description">

                            Klik untuk melihat berkas yang ditujukan kepada Anda

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     DISTRIBUSI SEKSI
                     ================================================= -->

                <div class="col-lg-4 col-md-12 mb-3">

                    <div class="section-card">

                        <div class="section-title">

                            Distribusi Berdasarkan Seksi

                        </div>


                        <?php if (count($statistikSeksi) > 0): ?>

                            <?php foreach (
                                array_slice(
                                    $statistikSeksi,
                                    0,
                                    4,
                                    true
                                )
                                as $seksi => $jumlah
                            ): ?>

                                <div class="seksi-item">

                                    <span class="seksi-name">

                                        <?= htmlspecialchars($seksi) ?>

                                    </span>


                                    <span class="seksi-count">

                                        <?= $jumlah ?>

                                    </span>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="text-muted">

                                Belum ada data eskalasi.

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 INFORMASI
                 ================================================= -->

            <div class="alert alert-light border mb-4">

                <strong>
                    Informasi:
                </strong>

                Berkas pada halaman ini merupakan berkas dengan status

                <strong class="text-warning">
                    eskalasi
                </strong>

                yang membutuhkan pertimbangan atau keputusan pimpinan.

            </div>


            <!-- =================================================
                 TABEL
                 ================================================= -->

            <div class="table-card">


                <div class="table-header">


                    <h2 class="table-title">

                        Daftar Berkas Eskalasi

                    </h2>


                    <div>

                        <?php if ($bolehAturEskalasi): ?>

                            <span class="badge badge-success mr-2">

                                <i class="fas fa-check"></i>

                                Anda dapat mengatur eskalasi

                            </span>

                        <?php else: ?>

                            <span class="badge badge-secondary mr-2">

                                Hanya lihat

                            </span>

                        <?php endif; ?>


                        <span class="badge-total">

                            <?= $totalEskalasi ?> Berkas

                        </span>

                    </div>

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

                                <th>
                                    Tujuan
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                            </thead>


                            <tbody>


                            <?php foreach ($dataEskalasi as $no => $row): ?>


                                <?php

                                /*
                                |--------------------------------------------------
                                | KELAS UMUR
                                |--------------------------------------------------
                                */

                                if (
                                    $row['kategori_waktu']
                                    == 'Kadaluarsa'
                                ) {

                                    $umurClass = 'umur-expired';

                                }

                                elseif (
                                    $row['kategori_waktu']
                                    == 'Kritis'
                                ) {

                                    $umurClass = 'umur-danger';

                                }

                                elseif (
                                    $row['kategori_waktu']
                                    == 'Waspada'
                                ) {

                                    $umurClass = 'umur-warning';

                                }

                                else {

                                    $umurClass = '';

                                }


                                /*
                                |--------------------------------------------------
                                | BADGE WAKTU
                                |--------------------------------------------------
                                */

                                $badgeClass = 'badge-normal';


                                if (
                                    $row['kategori_waktu']
                                    == 'Waspada'
                                ) {

                                    $badgeClass = 'badge-waspada';

                                }

                                elseif (
                                    $row['kategori_waktu']
                                    == 'Kritis'
                                ) {

                                    $badgeClass = 'badge-kritis';

                                }

                                elseif (
                                    $row['kategori_waktu']
                                    == 'Kadaluarsa'
                                ) {

                                    $badgeClass = 'badge-kadaluarsa';

                                }


                                /*
                                |--------------------------------------------------
                                | CATATAN
                                |--------------------------------------------------
                                */

                                $catatan = trim(
                                    $row['catatan'] ?? ''
                                );


                                if ($catatan == '') {

                                    $catatan =
                                        'Belum ada catatan eskalasi.';

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

                                            <?= htmlspecialchars(
                                                $row['no_berkas']
                                            ) ?>

                                            /

                                            <?= htmlspecialchars(
                                                $row['tahun']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- PEMOHON -->

                                    <td>

                                        <div class="nama-pemohon">

                                            <?= htmlspecialchars(
                                                $row['nama_pemohon']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- LAYANAN -->

                                    <td>

                                        <div class="nama-layanan">

                                            <?= htmlspecialchars(
                                                $row['nama_layanan'] ?? '-'
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- SEKSI -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['seksi'] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- PIC -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['nama_pic'] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- TANGGAL -->

                                    <td>

                                        <?php

                                        $tanggalMulai =
                                            $row['tanggal_mulai'] ?? '';


                                        if (
                                            $tanggalMulai == ''
                                            ||
                                            $tanggalMulai == '0000-00-00'
                                        ) {

                                            echo '-';

                                        } else {

                                            echo date(
                                                "d-m-Y",
                                                strtotime($tanggalMulai)
                                            );

                                        }

                                        ?>

                                    </td>


                                    <!-- UMUR -->

                                    <td>

                                        <span
                                            class="umur <?= $umurClass ?>"
                                        >

                                            <?= $row['umur_hari'] ?>

                                            hari

                                        </span>

                                    </td>


                                    <!-- KONDISI -->

                                    <td>

                                        <span
                                            class="badge-waktu <?= $badgeClass ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $row['kategori_waktu']
                                            ) ?>

                                        </span>


                                        <?php if (
                                            $row['sisa_hari']
                                            !== null
                                        ): ?>

                                            <div class="small text-muted mt-1">

                                                <?php if (
                                                    $row['sisa_hari'] >= 0
                                                ): ?>

                                                    Sisa
                                                    <?= $row['sisa_hari'] ?>
                                                    hari

                                                <?php else: ?>

                                                    Terlambat
                                                    <?= abs(
                                                        $row['sisa_hari']
                                                    ) ?>
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

                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $catatan
                                                    )
                                                ) ?>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- JUMLAH TUJUAN -->

                                    <td>

                                       <?php if (
                                            !empty($row['nama_tujuan_eskalasi'])
                                        ): ?>

                                            <div class="tujuan-eskalasi">

                                                <?php
                                                $daftarTujuan = explode(
                                                    '|||',
                                                    $row['nama_tujuan_eskalasi']
                                                );
                                                ?>

                                                <?php foreach ($daftarTujuan as $namaTujuan): ?>

                                                    <div class="tujuan-akun">

                                                        <i class="fas fa-user"></i>

                                                        <?= htmlspecialchars(trim($namaTujuan)) ?>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php else: ?>

                                            <span class="badge badge-warning">

                                                Belum diatur

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- AKSI -->

                                    <td>

                                        <?php if ($bolehAturEskalasi): ?>

                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm btn-detail"
                                                data-toggle="modal"
                                                data-target="#modalAturEskalasi<?= $row['id'] ?>"
                                            >

                                                <i class="fas fa-user-shield"></i>

                                                Atur Eskalasi

                                            </button>

                                        <?php else: ?>

                                            <button
                                                type="button"
                                                class="btn btn-secondary btn-sm btn-detail"
                                                onclick="aksesDitolak()"
                                            >

                                                <i class="fas fa-lock"></i>

                                                Atur Eskalasi

                                            </button>

                                        <?php endif; ?>


                                        <form
                                            method="POST"
                                            class="form-selesai mt-2"
                                        >

                                            <input
                                                type="hidden"
                                                name="selesai_id"
                                                value="<?= (int)$row['id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-success btn-sm btn-detail"
                                            >

                                                ✓ Selesai

                                            </button>

                                        </form>

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


</div>

<!-- =========================================================
     MODAL ESKALASI SAYA
     ========================================================= -->

<div
    class="modal fade"
    id="modalEskalasiSaya"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
>


<div
    class="modal-dialog modal-xl"
    role="document"
>

    <div class="modal-content">


        <div class="modal-header bg-warning">

            <h5 class="modal-title">

                <i class="fas fa-user"></i>

                Eskalasi Ditujukan Kepada Saya

            </h5>


            <button
                type="button"
                class="close"
                data-dismiss="modal"
            >

                <span>
                    &times;
                </span>

            </button>

        </div>


        <div class="modal-body">


            <div class="alert alert-light border">

                <strong>
                    <?= htmlspecialchars(
                        $userLogin['nama'] ?? 'Pengguna'
                    ) ?>
                </strong>

                memiliki

                <strong>
                    <?= $jumlahEskalasiSaya ?>
                </strong>

                berkas yang ditujukan untuk ditindaklanjuti.

            </div>


            <?php if (
                count($dataEskalasiSaya) > 0
            ): ?>


                <div class="table-responsive">

                    <table class="table table-bordered table-hover">


                        <thead class="thead-light">

                        <tr>

                            <th width="5%">
                                No
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
                                Tanggal Mulai
                            </th>

                            <th>
                                Catatan Eskalasi
                            </th>

                        </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $dataEskalasiSaya
                            as $i => $item
                        ): ?>

                            <tr>

                                <td>
                                    <?= $i + 1 ?>
                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $item['no_berkas']
                                        ) ?>

                                        /

                                        <?= htmlspecialchars(
                                            $item['tahun']
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $item['nama_pemohon']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $item['nama_layanan'] ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $item['seksi'] ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?= !empty(
                                        $item['tanggal_mulai']
                                    )
                                        ? date(
                                            'd-m-Y',
                                            strtotime(
                                                $item['tanggal_mulai']
                                            )
                                        )
                                        : '-'
                                    ?>

                                </td>


                                <td>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $item['catatan'] ?? '-'
                                        )
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="text-center text-muted p-4">

                    <i
                        class="fas fa-inbox fa-3x mb-3"
                    ></i>


                    <div>

                        Tidak ada berkas yang sedang

                        dieskalasikan kepada Anda.

                    </div>

                </div>


            <?php endif; ?>


        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="btn btn-secondary"
                data-dismiss="modal"
            >

                Tutup

            </button>

        </div>


    </div>

</div>


</div>

<!-- =========================================================
     MODAL ATUR ESKALASI UNTUK SETIAP BERKAS
     ========================================================= -->

<?php foreach ($dataEskalasi as $row): ?>


<?php

/*
|--------------------------------------------------------------
| Ambil akun yang sudah menjadi tujuan eskalasi
|--------------------------------------------------------------
*/

$akunSudahDipilih = [];

$stmtTujuan = $koneksi->prepare("
    SELECT akun_sakato_id
    FROM eskalasi
    WHERE berkas_rutin_id = ?
");

$stmtTujuan->bind_param(
    "i",
    $row['id']
);

$stmtTujuan->execute();

$resultTujuan = $stmtTujuan->get_result();


while (
    $tujuan = $resultTujuan->fetch_assoc()
) {

    $akunSudahDipilih[] =
        (int)$tujuan['akun_sakato_id'];

}


$stmtTujuan->close();

?>


<div
    class="modal fade"
    id="modalAturEskalasi<?= $row['id'] ?>"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
>

    <div
        class="modal-dialog modal-lg"
        role="document"
    >

        <div class="modal-content">


            <form
                method="POST"
                action="<?= htmlspecialchars(
                    $_SERVER['PHP_SELF']
                ) ?>"
            >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fas fa-user-shield"></i>

                        Atur Tujuan Eskalasi

                    </h5>


                    <button
                        type="button"
                        class="close text-white"
                        data-dismiss="modal"
                    >

                        <span>
                            &times;
                        </span>

                    </button>

                </div>


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="berkas_id"
                        value="<?= (int)$row['id'] ?>"
                    >


                    <input
                        type="hidden"
                        name="simpan_eskalasi"
                        value="1"
                    >


                    <!-- INFORMASI BERKAS -->

                    <div class="alert alert-light border">

                        <div class="mb-1">

                            <strong>
                                No. Berkas:
                            </strong>

                            <?= htmlspecialchars(
                                $row['no_berkas']
                            ) ?>

                            /

                            <?= htmlspecialchars(
                                $row['tahun']
                            ) ?>

                        </div>


                        <div class="mb-1">

                            <strong>
                                Pemohon:
                            </strong>

                            <?= htmlspecialchars(
                                $row['nama_pemohon']
                            ) ?>

                        </div>


                        <div>

                            <strong>
                                Layanan:
                            </strong>

                            <?= htmlspecialchars(
                                $row['nama_layanan'] ?? '-'
                            ) ?>

                        </div>

                    </div>


                    <label class="font-weight-bold">

                        Tujuan Eskalasi

                    </label>


                    <p class="text-muted small">

                        Centang akun yang akan menerima eskalasi

                        berkas ini. Anda dapat memilih lebih dari

                        satu akun.

                    </p>


                    <div class="akun-list">


                        <?php if (
                            count($dataAkun) > 0
                        ): ?>


                            <?php foreach (
                                $dataAkun
                                as $akun
                            ): ?>


                                <?php

                                $akunId =
                                    (int)$akun['id'];

                                $checked =
                                    in_array(
                                        $akunId,
                                        $akunSudahDipilih,
                                        true
                                    );

                                ?>


                                <div class="akun-item">

                                    <div
                                        class="custom-control custom-checkbox"
                                    >

                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="esk<?= $row['id'] ?>_<?= $akunId ?>"
                                            name="akun_tujuan[]"
                                            value="<?= $akunId ?>"
                                            <?= $checked ? 'checked' : '' ?>
                                        >


                                        <label
                                            class="custom-control-label"
                                            for="esk<?= $row['id'] ?>_<?= $akunId ?>"
                                        >

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $akun['nama']
                                                ) ?>

                                            </strong>


                                            <small class="text-muted">

                                                (
                                                <?= htmlspecialchars(
                                                    $akun['username']
                                                ) ?>
                                                )

                                            </small>

                                        </label>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="text-muted p-3">

                                Belum ada akun yang tersedia.

                            </div>


                        <?php endif; ?>


                    </div>


                    <div class="mt-3">

                        <small class="text-muted">

                            Jika semua checkbox dikosongkan,

                            maka tujuan eskalasi berkas ini akan

                            dihapus.

                        </small>

                    </div>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-save"></i>

                        Simpan Tujuan Eskalasi

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<?php endforeach; ?>

<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

<script>


/*
|--------------------------------------------------------------------------
| AKSES DITOLAK
|--------------------------------------------------------------------------
*/

function aksesDitolak() {

    Swal.fire({

        icon:
            'error',

        title:
            'Akses Ditolak',

        text:
            'Anda tidak memiliki akses untuk atur eskalasi.',

        confirmButtonText:
            'OK'

    });

}


/*
|--------------------------------------------------------------------------
| KONFIRMASI SELESAI ESKALASI
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {


        document
            .querySelectorAll('.form-selesai')
            .forEach(function (form) {


                form.addEventListener(
                    'submit',
                    function (e) {


                        e.preventDefault();


                        Swal.fire({

                            title:
                                'Selesaikan Eskalasi?',

                            text:
                                'Status berkas akan dikembalikan menjadi PROSES dan seluruh tujuan eskalasi akan dihapus.',

                            icon:
                                'warning',

                            showCancelButton:
                                true,

                            confirmButtonText:
                                'Ya, Selesai',

                            cancelButtonText:
                                'Batal',

                            reverseButtons:
                                true,

                            focusCancel:
                                true

                        }).then(function (result) {


                            if (
                                result.isConfirmed
                            ) {

                                form.submit();

                            }

                        });


                    }
                );


            });


    }
);

</script>

<!-- =========================================================
     FLASH MESSAGE
     ========================================================= -->

<?php if (
    isset($_SESSION['flash_message'])
): ?>

<script>

Swal.fire({

    icon:
        '<?= $_SESSION['flash_message']['type']; ?>',

    title:
        '<?= $_SESSION['flash_message']['title']; ?>',

    text:
        '<?= $_SESSION['flash_message']['text']; ?>',

    timer:
        2500,

    showConfirmButton:
        false

});

</script>

<?php

unset(
    $_SESSION['flash_message']
);

endif;

?>

</body>

</html>
