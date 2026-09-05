<?php
require_once 'auth.php';

$message = '';
$status  = '';

// -------------------------------------------------------------------
// 1. PROSES TAMBAH DATA LAYANAN
// -------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $nama_layanan = trim($_POST['nama_layanan']);
    $jatuh_tempo  = !empty($_POST['jatuh_tempo']) ? (int)$_POST['jatuh_tempo'] : NULL;
    $waspada       = !empty($_POST['waspada']) ? (int)$_POST['waspada'] : NULL;
    $kritis        = !empty($_POST['kritis']) ? (int)$_POST['kritis'] : NULL;
    $seksi        = trim($_POST['seksi']);
    $pic_id       = !empty($_POST['pic_id']) ? (int)$_POST['pic_id'] : NULL;

    // Diperbaiki: Dihapus pengecekan $status_lay
    if (!empty($nama_layanan)) {
        // Diperbaiki: Kolom 'status' dihapus dari query INSERT
        $stmt = $koneksi->prepare("INSERT INTO layanan (nama_layanan, jatuh_tempo, waspada, kritis, seksi, pic_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiisi", $nama_layanan, $jatuh_tempo, $waspada, $kritis, $seksi, $pic_id);

        if ($stmt->execute()) {
            $status  = 'success';
            $message = 'Data layanan berhasil ditambahkan!';
        } else {
            $status  = 'error';
            $message = 'Gagal menambahkan data layanan!';
        }
        $stmt->close();
    }
}

// -------------------------------------------------------------------
// 2. PROSES EDIT DATA LAYANAN
// -------------------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id           = (int)$_POST['id'];
    $nama_layanan = trim($_POST['nama_layanan']);
    $jatuh_tempo  = !empty($_POST['jatuh_tempo']) ? (int)$_POST['jatuh_tempo'] : NULL;
    $waspada       = !empty($_POST['waspada']) ? (int)$_POST['waspada'] : NULL;
    $kritis        = !empty($_POST['kritis']) ? (int)$_POST['kritis'] : NULL;
    $seksi        = trim($_POST['seksi']);
    $pic_id       = !empty($_POST['pic_id']) ? (int)$_POST['pic_id'] : NULL;

    // Diperbaiki: Dihapus pengecekan $status_lay
    if (!empty($id) && !empty($nama_layanan)) {
        $stmt = $koneksi->prepare("UPDATE layanan SET nama_layanan = ?, jatuh_tempo = ?, waspada = ?, kritis = ?, seksi = ?, pic_id = ? WHERE id = ?");
        $stmt->bind_param("siiisii", $nama_layanan, $jatuh_tempo, $waspada, $kritis, $seksi, $pic_id, $id);

        if ($stmt->execute()) {
            $status  = 'success';
            $message = 'Data layanan berhasil diperbarui!';
        } else {
            $status  = 'error';
            $message = 'Gagal memperbarui data layanan!';
        }
        $stmt->close();
    }
}

// -------------------------------------------------------------------
// 3. PROSES HAPUS DATA LAYANAN
// -------------------------------------------------------------------
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if (!empty($id)) {
        $stmt = $koneksi->prepare("DELETE FROM layanan WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $status  = 'success';
            $message = 'Data layanan berhasil dihapus!';
        } else {
            $status  = 'error';
            $message = 'Gagal menghapus data layanan!';
        }
        $stmt->close();
    }
}

// -------------------------------------------------------------------
// 4. AMBIL DATA PIC UNTUK OPTION DROPDOWN
// -------------------------------------------------------------------
$list_pic = [];
$query_pic = $koneksi->query("SELECT id, nama FROM pic ORDER BY nama ASC");
while ($row_pic = $query_pic->fetch_assoc()) {
    $list_pic[] = $row_pic;
}

// -------------------------------------------------------------------
// 5. AMBIL SEMUA DATA LAYANAN (JOIN DENGAN TABEL PIC)
// -------------------------------------------------------------------
$sql = "SELECT layanan.*, pic.nama AS nama_pic 
        FROM layanan 
        LEFT JOIN pic ON layanan.pic_id = pic.id 
        ORDER BY layanan.id DESC";
$result = $koneksi->query($sql);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO V2 - Kinerja PIC</title>
    <?php include "inc/head.php" ?>
</head>

<body>
<?php
// Ambil nama file dari URL yang sedang diakses (misal: "dashboard.php")
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="container-fluid p-0">
    <div class="row no-gutters min-vh-100">
        <!-- Sidebar Column -->
        <aside class="col-md-3 col-lg-2 text-white p-3 menusidebar">
            <?php include "inc/sidebar.php"; ?>
        </aside>
        
        <!-- Main Content Column -->
        <main class="col-md-9 col-lg-10 p-4">
<div class="container-fluid py-5 px-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="font-weight-bold">Daftar Layanan</h2>
        </div>
        <div class="col-md-4 text-right">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus mr-1"></i> Tambah Layanan
            </button>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="tableLayanan">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Layanan</th>
                            <th>Seksi</th>
                            <th>PIC</th>
                            <th>Jatuh Tempo</th>
                            <th>Waspada</th>
                            <th>Kritis</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_layanan']); ?></td>
                                    <td><?= htmlspecialchars($row['seksi'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row['nama_pic'] ?? '-'); ?></td>
                                    <td><?= $row['jatuh_tempo'] !== null ? $row['jatuh_tempo'] . ' hari' : '-'; ?></td>
                                    <td><?= $row['waspada'] !== null ? $row['waspada'] . ' hari' : '-'; ?></td>
                                    <td><?= $row['kritis'] !== null ? $row['kritis'] . ' hari' : '-'; ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning mr-1" data-toggle="modal" data-target="#modalEdit<?= $row['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id']; ?>)">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Edit Data -->
                                <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <form method="POST" action="layanan.php">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Data Layanan</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label>Nama Layanan <span class="text-danger">*</span></label>
                                                        <input type="text" name="nama_layanan" class="form-control" value="<?= htmlspecialchars($row['nama_layanan']); ?>" required>
                                                    </div>

                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label>Seksi</label>
                                                            <input type="text" name="seksi" class="form-control" value="<?= htmlspecialchars($row['seksi'] ?? ''); ?>">
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label>Penanggung Jawab (PIC)</label>
                                                            <select name="pic_id" class="form-control">
                                                                <option value="">-- Pilih PIC --</option>
                                                                <?php foreach ($list_pic as $pic): ?>
                                                                    <option value="<?= $pic['id']; ?>" <?= $row['pic_id'] == $pic['id'] ? 'selected' : ''; ?>>
                                                                        <?= htmlspecialchars($pic['nama']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="form-row">
                                                        <div class="form-group col-md-4">
                                                            <label>Jatuh Tempo (Hari)</label>
                                                            <input type="number" name="jatuh_tempo" class="form-control" value="<?= $row['jatuh_tempo']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Waspada (Hari)</label>
                                                            <input type="number" name="waspada" class="form-control" value="<?= $row['waspada']; ?>">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <label>Kritis (Hari)</label>
                                                            <input type="number" name="kritis" class="form-control" value="<?= $row['kritis']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada data layanan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Data -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="#">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Layanan Baru</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>Nama Layanan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_layanan" class="form-control" placeholder="Masukkan nama layanan..." required>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Seksi</label>
                            <select name="seksi" class="form-control" placeholder="Nama seksi...">
                                <option></option>
                                <option value="Seksi Survei dan Pemetaan">Seksi Survei dan Pemetaan</option>
                                <option value="Penetapan Hak dan Pendaftaran">Penetapan Hak dan Pendaftaran</option>
                                <option value="Penataan dan Pemberdayaan">Penataan dan Pemberdayaan</option>
                                <option value="Pengadaan Tanah dan Pengembangan">Pengadaan Tanah dan Pengembangan</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Penanggung Jawab (PIC)</label>
                            <select name="pic_id" class="form-control">
                                <option value="">-- Pilih PIC --</option>
                                <?php foreach ($list_pic as $pic): ?>
                                    <option value="<?= $pic['id']; ?>"><?= htmlspecialchars($pic['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Jatuh Tempo (Hari)</label>
                            <input type="number" name="jatuh_tempo" class="form-control" placeholder="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Waspada (Hari)</label>
                            <input type="number" name="waspada" class="form-control" placeholder="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Kritis (Hari)</label>
                            <input type="number" name="kritis" class="form-control" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        // Inisialisasi DataTables
        $('#tableLayanan').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            }
        });
    });
    // Konfirmasi Hapus Data dengan SweetAlert2
    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data layanan yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'layanan.php?delete=' + id;
            }
        });
    }

    // Tampilkan Alert Pesan dan Bersihkan URL
    <?php if (!empty($status)): ?>
        // Hapus parameter URL (?delete=xx) agar tidak terhapus ganda saat dipicu F5 / refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }

        // Tampilkan SweetAlert2
        Swal.fire({
            icon: '<?= $status; ?>',
            title: '<?= $status == "success" ? "Berhasil!" : "Gagal!"; ?>',
            text: '<?= $message; ?>',
            timer: 2000,
            showConfirmButton: false
        });
    <?php endif; ?>
</script>
</div>
</div>
</body>

</html>