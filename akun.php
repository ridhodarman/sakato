<?php
require_once 'auth.php';

// Cek apakah session sudah aktif, jika belum aktifkan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek Login
if (!isset($_SESSION['sakato_login']) || $_SESSION['sakato_login'] !== true) {
    header("Location: index.php");
    exit;
}

// ==========================================
// 1. PROSES TAMBAH DATA
// ==========================================
if (isset($_POST['tambah'])) {
    $username = trim($_POST['username']);
    $password = md5($_POST['password']);
    $nama     = trim($_POST['nama']);

    $stmt = $koneksi->prepare("INSERT INTO akun_sakato (username, password, nama) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $nama);

    if ($stmt->execute()) {
        $_SESSION['swal_icon']  = 'success';
        $_SESSION['swal_title'] = 'Berhasil!';
        $_SESSION['swal_text']  = 'Data akun berhasil ditambahkan.';
    } else {
        $_SESSION['swal_icon']  = 'error';
        $_SESSION['swal_title'] = 'Gagal!';
        $_SESSION['swal_text']  = 'Gagal menambahkan data: ' . $stmt->error;
    }
    $stmt->close();

    // Redirect untuk mencegah duplikasi saat reload (PRG Pattern)
    header("Location: akun.php");
    exit;
}

// ==========================================
// 2. PROSES EDIT DATA
// ==========================================
if (isset($_POST['edit'])) {
    $id       = $_POST['id'];
    $username = trim($_POST['username']);
    $nama     = trim($_POST['nama']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $password_md5 = md5($password);
        $stmt = $koneksi->prepare("UPDATE akun_sakato SET username = ?, password = ?, nama = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $password_md5, $nama, $id);
    } else {
        $stmt = $koneksi->prepare("UPDATE akun_sakato SET username = ?, nama = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $nama, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['swal_icon']  = 'success';
        $_SESSION['swal_title'] = 'Berhasil!';
        $_SESSION['swal_text']  = 'Data akun berhasil diperbarui.';
    } else {
        $_SESSION['swal_icon']  = 'error';
        $_SESSION['swal_title'] = 'Gagal!';
        $_SESSION['swal_text']  = 'Gagal memperbarui data: ' . $stmt->error;
    }
    $stmt->close();

    header("Location: akun.php");
    exit;
}

// ==========================================
// 3. PROSES HAPUS DATA
// ==========================================
if (isset($_POST['hapus'])) {
    $id = $_POST['id'];

    $stmt = $koneksi->prepare("DELETE FROM akun_sakato WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['swal_icon']  = 'success';
        $_SESSION['swal_title'] = 'Berhasil!';
        $_SESSION['swal_text']  = 'Data akun berhasil dihapus.';
    } else {
        $_SESSION['swal_icon']  = 'error';
        $_SESSION['swal_title'] = 'Gagal!';
        $_SESSION['swal_text']  = 'Gagal menghapus data: ' . $stmt->error;
    }
    $stmt->close();

    header("Location: akun.php");
    exit;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO V2 - Kinerja PIC</title>
    <?php include "inc/head.php"; ?>
</head>

<body>
<?php
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
            <div class="container py-5">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Data Akun Sakato</h5>
                                <button type="button" class="btn btn-light btn-sm font-weight-bold" data-toggle="modal" data-target="#modalTambah">
                                    <i class="fas fa-plus mr-1"></i> Tambah Akun
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th width="5%" class="text-center">No</th>
                                                <th>Username</th>
                                                <th>Nama</th>
                                                <th width="15%" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 1;
                                            $query = $koneksi->query("SELECT id, username, nama FROM akun_sakato ORDER BY id DESC");
                                            if ($query->num_rows > 0):
                                                while ($row = $query->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td class="text-center"><?= $no++; ?></td>
                                                <td><?= htmlspecialchars($row['username']); ?></td>
                                                <td><?= htmlspecialchars($row['nama']); ?></td>
                                                <td class="text-center">
                                                    <!-- Tombol Edit -->
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            data-toggle="modal" 
                                                            data-target="#modalEdit<?= $row['id']; ?>" 
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <!-- Form Hapus dengan Konfirmasi SweetAlert -->
                                                    <form method="POST" action="" class="d-inline form-hapus">
                                                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                        <input type="hidden" name="hapus" value="1">
                                                        <button type="button" class="btn btn-danger btn-sm btn-hapus" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- Modal Edit Akun -->
                                            <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning text-white">
                                                            <h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i>Edit Akun</h5>
                                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                                <div class="form-group">
                                                                    <label>Username</label>
                                                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Nama Lengkap</label>
                                                                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Password Baru</label>
                                                                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin diubah">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                                <button type="submit" name="edit" class="btn btn-warning text-white">Simpan Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Modal Edit -->

                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada data akun.</td>
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

            <!-- Modal Tambah Akun -->
            <div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Tambah Akun Baru</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                                </div>
                                <div class="form-group">
                                    <label>Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
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
            <!-- End Modal Tambah -->

            <script>
                // SweetAlert2 Notifikasi Sukses / Gagal membaca Session
                <?php if (isset($_SESSION['swal_icon'])): ?>
                    Swal.fire({
                        icon: '<?= $_SESSION['swal_icon']; ?>',
                        title: '<?= $_SESSION['swal_title']; ?>',
                        text: '<?= $_SESSION['swal_text']; ?>',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    <?php 
                    // Hapus session alert setelah ditampilkan agar tidak muncul lagi saat refresh
                    unset($_SESSION['swal_icon']);
                    unset($_SESSION['swal_title']);
                    unset($_SESSION['swal_text']);
                    ?>
                <?php endif; ?>

                // Gunakan Event Delegation $(document).on() untuk menghindari TypeError pada tombol hapus
                $(document).on('click', '.btn-hapus', function (e) {
                    e.preventDefault();
                    var form = $(this).closest('.form-hapus');

                    Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: "Data akun akan dihapus secara permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            </script>
        </main>
    </div>
</div>

</body>
</html>