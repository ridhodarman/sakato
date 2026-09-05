<?php
require_once 'auth.php';

$message = '';
$status  = '';

// 1. PROSES TAMBAH DATA
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $nama = trim($_POST['nama']);

    if (!empty($nama)) {
        $stmt = $koneksi->prepare("INSERT INTO pic (nama) VALUES (?)");
        $stmt->bind_param("s", $nama);

        if ($stmt->execute()) {
            $status  = 'success';
            $message = 'Data PIC berhasil ditambahkan!';
        } else {
            $status  = 'error';
            $message = 'Gagal menambahkan data!';
        }
        $stmt->close();
    }
}

// 2. PROSES EDIT DATA
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id   = (int)$_POST['id'];
    $nama = trim($_POST['nama']);

    if (!empty($id) && !empty($nama)) {
        $stmt = $koneksi->prepare("UPDATE pic SET nama = ? WHERE id = ?");
        $stmt->bind_param("si", $nama, $id);

        if ($stmt->execute()) {
            $status  = 'success';
            $message = 'Data PIC berhasil diperbarui!';
        } else {
            $status  = 'error';
            $message = 'Gagal memperbarui data!';
        }
        $stmt->close();
    }
}

// 3. PROSES HAPUS DATA
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if (!empty($id)) {
        $stmt = $koneksi->prepare("DELETE FROM pic WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $status  = 'success';
            $message = 'Data PIC berhasil dihapus!';
        } else {
            $status  = 'error';
            $message = 'Gagal menghapus data!';
        }
        $stmt->close();
    }
}

// 4. AMBIL SEMUA DATA UNTUK DITAMPILKAN
$result = $koneksi->query("SELECT * FROM pic ORDER BY nama");
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

// Ambil hak akses user yang sedang login
$id_user = $_SESSION['id_user'] ?? 0;

$stmt = $koneksi->prepare("
    SELECT kelola_pic_akun
    FROM akun_sakato
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

// Jika tidak punya akses
if (!$user || (int)$user['kelola_pic_akun'] !== 1) {

    echo '
    <script>
        Swal.fire({
            icon: "error",
            title: "Akses Ditolak",
            text: "Anda tidak memiliki akses untuk halaman ini, hubungi tata usaha atau admin",
            confirmButtonText: "OK"
        }).then(function() {
            window.location.href = "input.php";
        });
    </script>
    ';

    exit;
}

?>

<div class="container-fluid p-0">
    <div class="row no-gutters min-vh-100">
        <!-- Sidebar Column -->
        <aside class="col-md-3 col-lg-2 text-white p-3 menusidebar">
            <?php include "inc/sidebar.php"; ?>
        </aside>
        
        <!-- Main Content Column -->
        <main class="col-md-9 col-lg-10 p-4">
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="font-weight-bold">Daftar Penanggung Jawab (PIC)</h2>
        </div>
        <div class="col-md-4 text-right">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus mr-1"></i> Tambah PIC
            </button>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="tablePIC">
                    <thead>
                        <tr>
                            <th style="width: 10%">No</th>
                            <th style="width: 65%">Nama PIC</th>
                            <th style="width: 25%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td class="text-center">
                                        <!-- Tombol Edit Modal -->
                                        <button class="btn btn-sm btn-warning mr-1" 
                                                data-toggle="modal" 
                                                data-target="#modalEdit<?= $row['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <!-- Tombol Hapus Konfirmasi SweetAlert -->
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?= $row['id']; ?>)">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Edit Data -->
                                <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <form method="POST" action="pic.php">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit PIC</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                    <div class="form-group">
                                                        <label>Nama PIC</label>
                                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama']); ?>" required>
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
                                <td colspan="3" class="text-center text-muted py-4">Belum ada data PIC.</td>
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="pic.php">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah PIC Baru</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Nama PIC</label>
                        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama..." required>
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
        $('#tablePIC').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            }
        });
    });
    // Konfirmasi Hapus Data dengan SweetAlert2
    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'pic.php?delete=' + id;
            }
        });
    }

    // Tampilkan Alert Pesan dan Bersihkan URL
    <?php if (!empty($status)): ?>
        // 1. Langsung hapus parameter URL (?delete=14) agar jika di-F5 tidak terhapus ulang
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }

        // 2. Tampilkan SweetAlert2
        Swal.fire({
            icon: '<?= $status; ?>',
            title: '<?= $status == "success" ? "Berhasil!" : "Gagal!"; ?>',
            text: '<?= $message; ?>',
            timer: 2000,
            showConfirmButton: false
        });
    <?php endif; ?>
</script>
        </main>
    </div>
    <div id="modal" class="hidden"></div>
    <div id="toast" class="toast hidden"></div>
</div>
</div>
</body>

</html>