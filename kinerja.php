<?php
// Pastikan tidak ada spasi/enter di sebelum tag <?php di baris paling atas!
ob_start(); // Mulai output buffering dari baris pertama

require_once 'auth.php';

// =====================================================
// API AJAX FOR MODAL DETAIL DATA
// =====================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_detail') {
    // Bersihkan seluruh buffer yang terkumpul dari auth.php atau whitespace
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $pic_id   = isset($_GET['pic_id']) ? (int)$_GET['pic_id'] : 0;
    $kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

    $whereClause = "";
    switch ($kategori) {
        case 'proses':
            $whereClause = "b.status = 'proses'";
            break;
        case 'eskalasi':
            $whereClause = "b.status = 'eskalasi'";
            break;
        case 'waspada':
            $whereClause = "b.status <> 'selesai' AND DATEDIFF(CURDATE(), b.tanggal_mulai) >= l.waspada AND DATEDIFF(CURDATE(), b.tanggal_mulai) < l.kritis";
            break;
        case 'kritis':
            $whereClause = "b.status <> 'selesai' AND DATEDIFF(CURDATE(), b.tanggal_mulai) >= l.kritis AND DATEDIFF(CURDATE(), b.tanggal_mulai) <= l.jatuh_tempo";
            break;
        case 'kadaluarsa':
            $whereClause = "b.status <> 'selesai' AND DATEDIFF(CURDATE(), b.tanggal_mulai) > l.jatuh_tempo";
            break;
        default:
            echo json_encode([]);
            exit;
    }

    $sqlDetail = "
        SELECT 
            b.no_berkas,
            b.tahun,
            b.nama_pemohon,
            l.nama_layanan AS nama_layanan,
            IF(b.tanggal_mulai IS NULL OR b.tanggal_mulai = '0000-00-00', '-', DATE_FORMAT(b.tanggal_mulai, '%d-%m-%Y')) AS tanggal_mulai_formatted,
            IF(b.tanggal_mulai IS NULL OR b.tanggal_mulai = '0000-00-00', NULL, DATEDIFF(CURDATE(), b.tanggal_mulai)) AS umur_berkas
        FROM berkas_rutin b
        JOIN layanan l ON b.layanan_id = l.id
        WHERE l.pic_id = ? AND $whereClause
        ORDER BY b.tanggal_mulai ASC
    ";

    $stmt = $koneksi->prepare($sqlDetail);
    if (!$stmt) {
        echo json_encode(['error' => $koneksi->error]);
        exit;
    }

    $stmt->bind_param("i", $pic_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $detailData = [];
    while ($row = $res->fetch_assoc()) {
        $detailData[] = $row;
    }

    echo json_encode($detailData);
    exit; // Hentikan eksekusi script sepenuhnya
}


// =====================================================
// DATA KINERJA PIC
// =====================================================

$sql = "
SELECT
    p.id AS pic_id,
    p.nama AS nama_pic,

    SUM(
        CASE
            WHEN b.status = 'proses'
            THEN 1
            ELSE 0
        END
    ) AS total_proses,

    SUM(
        CASE
            WHEN b.status = 'eskalasi'
            THEN 1
            ELSE 0
        END
    ) AS total_eskalasi,

    SUM(
        CASE
            WHEN b.status <> 'selesai'
                 AND DATEDIFF(CURDATE(), b.tanggal_mulai) >= l.waspada
                 AND DATEDIFF(CURDATE(), b.tanggal_mulai) < l.kritis
            THEN 1
            ELSE 0
        END
    ) AS total_waspada,

    SUM(
        CASE
            WHEN b.status <> 'selesai'
                 AND DATEDIFF(CURDATE(), b.tanggal_mulai) >= l.kritis
                 AND DATEDIFF(CURDATE(), b.tanggal_mulai) <= l.jatuh_tempo
            THEN 1
            ELSE 0
        END
    ) AS total_kritis,

    SUM(
        CASE
            WHEN b.status <> 'selesai'
                 AND DATEDIFF(CURDATE(), b.tanggal_mulai) > l.jatuh_tempo
            THEN 1
            ELSE 0
        END
    ) AS total_kadaluarsa

FROM pic p
LEFT JOIN layanan l ON l.pic_id = p.id
LEFT JOIN berkas_rutin b ON b.layanan_id = l.id
GROUP BY p.id, p.nama
ORDER BY total_proses DESC
";

$result = $koneksi->query($sql);

if (!$result) {
    die("Query gagal: " . $koneksi->error);
}

// =====================================================
// SIMPAN DATA
// =====================================================

$dataPIC = [];

while ($row = $result->fetch_assoc()) {
    $row['total_proses']     = (int) $row['total_proses'];
    $row['total_eskalasi']   = (int) $row['total_eskalasi'];
    $row['total_waspada']    = (int) $row['total_waspada'];
    $row['total_kritis']     = (int) $row['total_kritis'];
    $row['total_kadaluarsa'] = (int) $row['total_kadaluarsa'];

    $dataPIC[] = $row;
}

// =====================================================
// STATISTIK GLOBAL
// =====================================================

$totalPIC        = count($dataPIC);
$totalProses     = 0;
$totalEskalasi   = 0;
$totalWaspada    = 0;
$totalKritis     = 0;
$totalKadaluarsa = 0;

foreach ($dataPIC as $row) {
    $totalProses     += $row['total_proses'];
    $totalEskalasi   += $row['total_eskalasi'];
    $totalWaspada    += $row['total_waspada'];
    $totalKritis     += $row['total_kritis'];
    $totalKadaluarsa += $row['total_kadaluarsa'];
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO</title>
    <?php include "inc/head.php" ?>
    <style>
        /* GENERAL */
        body {
            background: #f5f7fb;
            color: #1e293b;
        }

        .container-fluid {
            padding: 25px;
        }

        /* HEADER */
        .page-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, .20);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .page-subtitle {
            opacity: .7;
            font-size: 14px;
            margin-top: 7px;
        }

        /* STAT CARD */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            min-height: 130px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .07);
            margin-bottom: 20px;
        }

        .stat-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
        }

        .stat-blue::before { background: #2563eb; }
        .stat-orange::before { background: #f97316; }
        .stat-yellow::before { background: #eab308; }
        .stat-red-warning::before { background: #ef4444; }
        .stat-red-dark::before { background: #991b1b; }

        .stat-title {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            margin-top: 5px;
        }

        .stat-description {
            color: #94a3b8;
            font-size: 12px;
        }

        /* MAIN TABLE */
        .table-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .08);
        }

        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .7px;
            white-space: nowrap;
            border: none;
            padding: 14px;
        }

        .table tbody td {
            padding: 15px 12px;
            vertical-align: middle;
            border-color: #f1f5f9;
            font-size: 13px;
        }

        .pic-name {
            font-weight: 700;
            color: #0f172a;
        }

        /* BADGES WITH CLICK FEATURE */
        .badge-custom {
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            min-width: 32px;
            text-align: center;
            border: none;
            transition: all 0.2s ease-in-out;
        }

        .badge-clickable {
            cursor: pointer;
        }

        .badge-clickable:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .badge-proses { background: #eff6ff; color: #1d4ed8; }
        .badge-eskalasi { background: #fff7ed; color: #c2410c; }
        .badge-waspada { background: #fefce8; color: #a16207; }
        .badge-kritis { background: #fef2f2; color: #dc2626; }
        .badge-kadaluarsa { background: #450a0a; color: #ffffff; }

        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .page-title { font-size: 23px; }
        }
    </style>
</head>

<body>
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

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
                <div class="page-header">
                    <h1 class="page-title">Kinerja PIC</h1>
                    <div class="page-subtitle">
                        Monitoring status berkas aktif berdasarkan PIC layanan
                    </div>
                </div>

                <!-- STATISTIK GLOBAL -->
                <div class="row">
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="stat-card stat-blue">
                            <div class="stat-title">Total</div>
                            <div class="stat-number"><?= $totalProses ?></div>
                            <div class="stat-description">Berkas ditangani</div>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="stat-card stat-orange">
                            <div class="stat-title">Eskalasi</div>
                            <div class="stat-number"><?= $totalEskalasi ?></div>
                            <div class="stat-description">Butuh pertimbangan</div>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="stat-card stat-yellow">
                            <div class="stat-title">Waspada</div>
                            <div class="stat-number"><?= $totalWaspada ?></div>
                            <div class="stat-description">Mendekati tenggat</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="stat-card stat-red-warning">
                            <div class="stat-title">Kritis</div>
                            <div class="stat-number"><?= $totalKritis ?></div>
                            <div class="stat-description">Batas waktu terlampaui</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-6">
                        <div class="stat-card stat-red-dark">
                            <div class="stat-title">Kadaluarsa</div>
                            <div class="stat-number"><?= $totalKadaluarsa ?></div>
                            <div class="stat-description">Melewati jatuh tempo</div>
                        </div>
                    </div>
                </div>

                <!-- TABEL KINERJA SELURUH PIC -->
                <div class="table-card">
                    <div class="table-header">
                        <h2 class="table-title">Kinerja Seluruh PIC</h2>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>PIC</th>
                                    <th>Proses</th>
                                    <th>Eskalasi</th>
                                    <th>Waspada</th>
                                    <th>Kritis</th>
                                    <th>Kadaluarsa</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($dataPIC as $ranking => $row): ?>
                                <tr>
                                    <td><strong>#<?= $ranking + 1 ?></strong></td>
                                    <td>
                                        <div class="pic-name">
                                            <?= htmlspecialchars($row['nama_pic']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-custom badge-proses <?= $row['total_proses'] > 0 ? 'badge-clickable' : '' ?>" 
                                              onclick="showDetail(<?= $row['pic_id'] ?>, 'proses', '<?= htmlspecialchars($row['nama_pic'], ENT_QUOTES) ?>', <?= $row['total_proses'] ?>)">
                                            <?= $row['total_proses'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-custom badge-eskalasi <?= $row['total_eskalasi'] > 0 ? 'badge-clickable' : '' ?>" 
                                              onclick="showDetail(<?= $row['pic_id'] ?>, 'eskalasi', '<?= htmlspecialchars($row['nama_pic'], ENT_QUOTES) ?>', <?= $row['total_eskalasi'] ?>)">
                                            <?= $row['total_eskalasi'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-custom badge-waspada <?= $row['total_waspada'] > 0 ? 'badge-clickable' : '' ?>" 
                                              onclick="showDetail(<?= $row['pic_id'] ?>, 'waspada', '<?= htmlspecialchars($row['nama_pic'], ENT_QUOTES) ?>', <?= $row['total_waspada'] ?>)">
                                            <?= $row['total_waspada'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-custom badge-kritis <?= $row['total_kritis'] > 0 ? 'badge-clickable' : '' ?>" 
                                              onclick="showDetail(<?= $row['pic_id'] ?>, 'kritis', '<?= htmlspecialchars($row['nama_pic'], ENT_QUOTES) ?>', <?= $row['total_kritis'] ?>)">
                                            <?= $row['total_kritis'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-custom badge-kadaluarsa <?= $row['total_kadaluarsa'] > 0 ? 'badge-clickable' : '' ?>" 
                                              onclick="showDetail(<?= $row['pic_id'] ?>, 'kadaluarsa', '<?= htmlspecialchars($row['nama_pic'], ENT_QUOTES) ?>', <?= $row['total_kadaluarsa'] ?>)">
                                            <?= $row['total_kadaluarsa'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($dataPIC) == 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted p-5">
                                        Belum ada data PIC.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<!-- MODAL DETAIL BERKAS (BOOTSTRAP 4) -->
<div class="modal fade" id="modalDetailBerkas" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: #0f172a; color: white;">
                <h5 class="modal-title" id="modalDetailLabel">Detail Berkas</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>No. Berkas/Tahun</th>
                                <th>Nama Layanan</th>
                                <th>Nama Pemohon</th>
                                <th>Tanggal Mulai</th>
                                <th>Umur Berkas</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8fafc;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => { 
        if (typeof initUser === 'function') initUser(); 
        if (typeof render === 'function') render(); 
    });

function showDetail(picId, kategori, namaPic, total) {
    if (total === 0) return;

    const modalTitle = document.getElementById('modalDetailLabel');
    const modalBody = document.getElementById('modalTableBody');
    const currentScript = '<?= basename($_SERVER['PHP_SELF']) ?>';

    modalTitle.innerText = `Detail Status '${kategori.toUpperCase()}' - PIC: ${namaPic}`;
    modalBody.innerHTML = `<tr><td colspan="5" class="text-center p-4">Memuat data...</td></tr>`;

    // Menggunakan jQuery modal bawaan Bootstrap 4
    $('#modalDetailBerkas').modal('show');

    fetch(`${currentScript}?action=get_detail&pic_id=${picId}&kategori=${kategori}`)
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error('Server response (Bukan JSON valid):', text);
                throw err;
            }
        })
        .then(data => {
            modalBody.innerHTML = '';
            
            if (data.error) {
                modalBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger p-4">Error: ${data.error}</td></tr>`;
                return;
            }

            if (data.length === 0) {
                modalBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-4">Tidak ada data berkas.</td></tr>`;
                return;
            }

            data.forEach(item => {
                const tanggalMulai = (item.tanggal_mulai_formatted && item.tanggal_mulai_formatted !== '00-00-0000') 
                    ? item.tanggal_mulai_formatted 
                    : '-';
                    
                const umurBerkas = (item.umur_berkas !== null && item.umur_berkas !== undefined) 
                    ? `<span class="badge badge-info">${item.umur_berkas} Hari</span>` 
                    : '-';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${item.no_berkas}/${item.tahun}</strong></td>
                    <td>${item.nama_layanan || '-'}</td>
                    <td>${item.nama_pemohon}</td>
                    <td>${tanggalMulai}</td>
                    <td>${umurBerkas}</td>
                `;
                modalBody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching details:', error);
            modalBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger p-4">Gagal memuat data detail. Periksa Console browser (F12).</td></tr>`;
        });
}
</script>
</body>

</html>