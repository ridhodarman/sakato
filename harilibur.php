<?php
require_once 'auth.php';

// -----------------------------------------------------------------------------
// PROCESS: TAMBAH DATA HARI LIBUR
// -----------------------------------------------------------------------------
if (isset($_POST['tambah'])) {
    $tanggal    = trim($_POST['tanggal']);
    $keterangan = trim($_POST['keterangan']);

    if (!empty($tanggal) && !empty($keterangan)) {
        $stmt = $koneksi->prepare("INSERT INTO hari_libur (tanggal, keterangan) VALUES (?, ?)");
        $stmt->bind_param("ss", $tanggal, $keterangan);

        if ($stmt->execute()) {
            $_SESSION['swal_icon']  = 'success';
            $_SESSION['swal_title'] = 'Berhasil!';
            $_SESSION['swal_text']  = 'Data hari libur berhasil ditambahkan.';
        } else {
            $_SESSION['swal_icon']  = 'error';
            $_SESSION['swal_title'] = 'Gagal!';
            $_SESSION['swal_text']  = 'Gagal menambahkan data hari libur.';
        }
        $stmt->close();
    } else {
        $_SESSION['swal_icon']  = 'warning';
        $_SESSION['swal_title'] = 'Peringatan!';
        $_SESSION['swal_text']  = 'Semua field wajib diisi.';
    }
    header("Location: harilibur.php");
    exit();
}

// -----------------------------------------------------------------------------
// PROCESS: EDIT DATA HARI LIBUR
// -----------------------------------------------------------------------------
if (isset($_POST['edit'])) {
    $id         = intval($_POST['id']);
    $tanggal    = trim($_POST['tanggal']);
    $keterangan = trim($_POST['keterangan']);

    if (!empty($tanggal) && !empty($keterangan) && $id > 0) {
        $stmt = $koneksi->prepare("UPDATE hari_libur SET tanggal = ?, keterangan = ? WHERE id = ?");
        $stmt->bind_param("ssi", $tanggal, $keterangan, $id);

        if ($stmt->execute()) {
            $_SESSION['swal_icon']  = 'success';
            $_SESSION['swal_title'] = 'Berhasil!';
            $_SESSION['swal_text']  = 'Data hari libur berhasil diperbarui.';
        } else {
            $_SESSION['swal_icon']  = 'error';
            $_SESSION['swal_title'] = 'Gagal!';
            $_SESSION['swal_text']  = 'Gagal memperbarui data hari libur.';
        }
        $stmt->close();
    } else {
        $_SESSION['swal_icon']  = 'warning';
        $_SESSION['swal_title'] = 'Peringatan!';
        $_SESSION['swal_text']  = 'Data tidak lengkap.';
    }
    header("Location: harilibur.php");
    exit();
}

// -----------------------------------------------------------------------------
// PROCESS: HAPUS DATA HARI LIBUR
// -----------------------------------------------------------------------------
if (isset($_POST['hapus'])) {
    $id = intval($_POST['id']);

    if ($id > 0) {
        $stmt = $koneksi->prepare("DELETE FROM hari_libur WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['swal_icon']  = 'success';
            $_SESSION['swal_title'] = 'Terhapus!';
            $_SESSION['swal_text']  = 'Data hari libur berhasil dihapus.';
        } else {
            $_SESSION['swal_icon']  = 'error';
            $_SESSION['swal_title'] = 'Gagal!';
            $_SESSION['swal_text']  = 'Gagal menghapus data hari libur.';
        }
        $stmt->close();
    }
    header("Location: harilibur.php");
    exit();
}

// Ambil semua data hari libur diurutkan dari tanggal terbaru
$query  = "SELECT * FROM hari_libur ORDER BY tanggal DESC";
$result = $koneksi->query($query);
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
<div class="container my-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Hari Libur</h5>
                    <!-- Button Modal Tambah -->
                    <button type="button" class="btn btn-light btn-sm font-weight-bold" data-toggle="modal" data-target="#modalTambah">
                        + Tambah Hari Libur
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="">
                                <tr>
                                    <th width="60" class="text-center">No</th>
                                    <th width="160" class="text-center">Tanggal</th>
                                    <th>Keterangan</th>
                                    <th width="160" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td class="text-center">
                                                <?= date('d-m-Y', strtotime($row['tanggal'])); ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['keterangan']); ?></td>
                                            <td class="text-center">
                                                <!-- Button Edit -->
                                                <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit<?= $row['id']; ?>">
                                                    Edit
                                                </button>
                                                <!-- Button Hapus (Triggers SweetAlert confirmation) -->
                                                <button type="button" class="btn btn-danger btn-sm" onclick="konfirmasiHapus(<?= $row['id']; ?>)">
                                                    Hapus
                                                </button>

                                                <!-- Hidden Form Hapus -->
                                                <form id="form-hapus-<?= $row['id']; ?>" action="harilibur.php" method="POST" style="display: none;">
                                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                    <input type="hidden" name="hapus" value="1">
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- MODAL EDIT DATA -->
                                        <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form action="harilibur.php" method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Hari Libur</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                            <div class="form-group">
                                                                <label for="tanggal_edit_<?= $row['id']; ?>">Tanggal</label>
                                                                <input type="date" class="form-control" id="tanggal_edit_<?= $row['id']; ?>" name="tanggal" value="<?= htmlspecialchars($row['tanggal']); ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="keterangan_edit_<?= $row['id']; ?>">Keterangan</label>
                                                                <textarea class="form-control" id="keterangan_edit_<?= $row['id']; ?>" name="keterangan" rows="3" required><?= htmlspecialchars($row['keterangan']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                            <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data hari libur.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH DATA -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="harilibur.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Hari Libur</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tanggal_tambah">Tanggal</label>
                        <input type="date" class="form-control" id="tanggal_tambah" name="tanggal" required>
                    </div>
                    <div class="form-group">
                        <label for="keterangan_tambah">Keterangan</label>
                        <textarea class="form-control" id="keterangan_tambah" name="keterangan" rows="3" placeholder="Masukkan keterangan hari libur" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS Dependencies (jQuery, Popper.js, Bootstrap 4 JS, SweetAlert2) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Fungsi Konfirmasi Hapus Data dengan SweetAlert2
function konfirmasiHapus(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data hari libur ini akan dihapus secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-hapus-' + id).submit();
        }
    });
}
</script>

<?php
// Tampilkan Notifikasi SweetAlert jika ada status dari Session
if (isset($_SESSION['swal_icon'])) {
    $icon  = $_SESSION['swal_icon'];
    $title = $_SESSION['swal_title'];
    $text  = $_SESSION['swal_text'];

    echo "<script>
        Swal.fire({
            icon: '$icon',
            title: '$title',
            text: '$text',
            timer: 2000,
            showConfirmButton: false
        });
    </script>";

    unset($_SESSION['swal_icon']);
    unset($_SESSION['swal_title']);
    unset($_SESSION['swal_text']);
}
?>
        </main>
    </div>
    <div id="modal" class="hidden"></div>
    <div id="toast" class="toast hidden"></div>
    <script src="assets/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {initUser(); render();});
    </script>
</div>
</div>
</body>

</html>