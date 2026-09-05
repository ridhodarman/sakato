<?php
require_once 'auth.php';

// -----------------------------------------------------------------------------
// PROCESS: TAMBAH DATA
// -----------------------------------------------------------------------------
if (isset($_POST['tambah'])) {
    $nama_posisi = trim($_POST['nama_posisi']);

    if (!empty($nama_posisi)) {
        $stmt = $koneksi->prepare("INSERT INTO posisi (nama_posisi) VALUES (?)");
        $stmt->bind_param("s", $nama_posisi);

        if ($stmt->execute()) {
            $_SESSION['swal_icon'] = 'success';
            $_SESSION['swal_title'] = 'Berhasil!';
            $_SESSION['swal_text'] = 'Data posisi berhasil ditambahkan.';
        } else {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Gagal!';
            $_SESSION['swal_text'] = 'Gagal menambahkan data.';
        }
        $stmt->close();
    }
    header("Location: posisi.php");
    exit();
}

// -----------------------------------------------------------------------------
// PROCESS: EDIT DATA
// -----------------------------------------------------------------------------
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $nama_posisi = trim($_POST['nama_posisi']);

    if (!empty($nama_posisi) && $id > 0) {
        $stmt = $koneksi->prepare("UPDATE posisi SET nama_posisi = ? WHERE id = ?");
        $stmt->bind_param("si", $nama_posisi, $id);

        if ($stmt->execute()) {
            $_SESSION['swal_icon'] = 'success';
            $_SESSION['swal_title'] = 'Berhasil!';
            $_SESSION['swal_text'] = 'Data posisi berhasil diperbarui.';
        } else {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Gagal!';
            $_SESSION['swal_text'] = 'Gagal memperbarui data.';
        }
        $stmt->close();
    }
    header("Location: posisi.php");
    exit();
}

// -----------------------------------------------------------------------------
// PROCESS: HAPUS DATA
// -----------------------------------------------------------------------------
if (isset($_POST['hapus'])) {
    $id = intval($_POST['id']);

    if ($id > 0) {
        $stmt = $koneksi->prepare("DELETE FROM posisi WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['swal_icon'] = 'success';
            $_SESSION['swal_title'] = 'Terhapus!';
            $_SESSION['swal_text'] = 'Data posisi berhasil dihapus.';
        } else {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Gagal!';
            $_SESSION['swal_text'] = 'Gagal menghapus data.';
        }
        $stmt->close();
    }
    header("Location: posisi.php");
    exit();
}

// Fetch all data for display
$query = "SELECT * FROM posisi ORDER BY nama_posisi";
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
                    <h5 class="mb-0">Data Posisi</h5>
                    <!-- Button Modal Tambah -->
                    <button type="button" class="btn btn-light btn-sm font-weight-bold" data-toggle="modal" data-target="#modalTambah">
                        + Tambah Posisi
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablePosisi" class="table table-bordered table-hover mb-0">
                            <thead class="">
                                <tr>
                                    <th width="80" class="text-center">No</th>
                                    <th>Nama Posisi</th>
                                    <th width="180" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['nama_posisi']); ?></td>
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
                                                <form id="form-hapus-<?= $row['id']; ?>" action="posisi.php" method="POST" style="display: none;">
                                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                    <input type="hidden" name="hapus" value="1">
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- MODAL EDIT DATA -->
                                        <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form action="posisi.php" method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Posisi</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                            <div class="form-group">
                                                                <label for="nama_posisi_edit_<?= $row['id']; ?>">Nama Posisi</label>
                                                                <input type="text" class="form-group form-control" id="nama_posisi_edit_<?= $row['id']; ?>" name="nama_posisi" value="<?= htmlspecialchars($row['nama_posisi']); ?>" required>
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
                                        <td colspan="3" class="text-center text-muted">Belum ada data posisi.</td>
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
            <form action="posisi.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Posisi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_posisi_tambah">Nama Posisi</label>
                        <input type="text" class="form-control" id="nama_posisi_tambah" name="nama_posisi" placeholder="Masukkan nama posisi" required>
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



<script>
$(document).ready(function() {
    // Inisialisasi DataTables
    $('#tablePosisi').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});
// Alert Konfirmasi Hapus Data
function konfirmasiHapus(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data posisi yang dihapus tidak bisa dikembalikan!",
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
// Tampilkan Notifikasi SweetAlert jika ada status di Session
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
</div>
</div>
</body>

</html>