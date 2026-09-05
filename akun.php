<?php
session_start();
require_once 'inc/koneksi.php';
require_once 'auth.php';

// ==========================================
// 1. PROSES TAMBAH DATA
// ==========================================
if (isset($_POST['tambah'])) {
    $username                       = trim($_POST['username']);
    $password                       = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password untuk keamanan
    $nama                           = trim($_POST['nama']);
    $pic_id                         = !empty($_POST['pic_id']) ? $_POST['pic_id'] : NULL;
    $kelola_layanan_posisi_harilibur = isset($_POST['kelola_layanan_posisi_harilibur']) ? 1 : 0;
    $lihat_semua_warning            = isset($_POST['lihat_semua_warning']) ? 1 : 0;
    $kelola_pic_akun                = isset($_POST['kelola_pic_akun']) ? 1 : 0;
    $update_berkas                  = isset($_POST['update_berkas']) ? 1 : 0;

    $stmt = $koneksi->prepare("INSERT INTO akun_sakato (username, password, nama, pic_id, kelola_layanan_posisi_harilibur, lihat_semua_warning, kelola_pic_akun, update_berkas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiiii", $username, $password, $nama, $pic_id, $kelola_layanan_posisi_harilibur, $lihat_semua_warning, $kelola_pic_akun, $update_berkas);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Data akun berhasil ditambahkan.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal menambahkan data.'];
    }
    $stmt->close();
    header("Location: akun.php");
    exit();
}

// ==========================================
// 2. PROSES EDIT DATA
// ==========================================
if (isset($_POST['edit'])) {
    $id                             = $_POST['id'];
    $username                       = trim($_POST['username']);
    $nama                           = trim($_POST['nama']);
    $pic_id                         = !empty($_POST['pic_id']) ? $_POST['pic_id'] : NULL;
    $kelola_layanan_posisi_harilibur = isset($_POST['kelola_layanan_posisi_harilibur']) ? 1 : 0;
    $lihat_semua_warning            = isset($_POST['lihat_semua_warning']) ? 1 : 0;
    $kelola_pic_akun                = isset($_POST['kelola_pic_akun']) ? 1 : 0;
    $update_berkas                  = isset($_POST['update_berkas']) ? 1 : 0;

    // Jika password diisi, update password baru
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE akun_sakato SET username=?, password=?, nama=?, pic_id=?, kelola_layanan_posisi_harilibur=?, lihat_semua_warning=?, kelola_pic_akun=?, update_berkas=? WHERE id=?");
        $stmt->bind_param("sssiiiiii", $username, $password, $nama, $pic_id, $kelola_layanan_posisi_harilibur, $lihat_semua_warning, $kelola_pic_akun, $update_berkas, $id);
    } else {
        // Jika password kosong, jangan ubah password lama
        $stmt = $koneksi->prepare("UPDATE akun_sakato SET username=?, nama=?, pic_id=?, kelola_layanan_posisi_harilibur=?, lihat_semua_warning=?, kelola_pic_akun=?, update_berkas=? WHERE id=?");
        $stmt->bind_param("ssiiiiii", $username, $nama, $pic_id, $kelola_layanan_posisi_harilibur, $lihat_semua_warning, $kelola_pic_akun, $update_berkas, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Data akun berhasil diperbarui.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal memperbarui data.'];
    }
    $stmt->close();
    header("Location: akun.php");
    exit();
}

// ==========================================
// 3. PROSES HAPUS DATA
// ==========================================
if (isset($_POST['hapus'])) {
    $id = $_POST['id'];

    $stmt = $koneksi->prepare("DELETE FROM akun_sakato WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Data akun berhasil dihapus.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal menghapus data.'];
    }
    $stmt->close();
    header("Location: akun.php");
    exit();
}

// Ambil data PIC untuk dropdown opsi
$query_pic = $koneksi->query("SELECT id, nama FROM pic ORDER BY nama ASC");
$data_pic = [];
if ($query_pic) {
    while ($row = $query_pic->fetch_assoc()) {
        $data_pic[] = $row;
    }
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

<div class="container-fluid mt-4 mb-5">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-users-cog"></i> Kelola Data Akun</h4>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus-circle"></i> Tambah Akun
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableAkun" class="table table-bordered table-striped table-hover width-100">
                    <thead class="thead-dark">
                        <tr>
                            <th width="5%">No</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>#</th>
                            <th>Hak Akses</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $sql = "SELECT akun_sakato.*, pic.nama AS nama_pic 
                                FROM akun_sakato
                                LEFT JOIN pic ON akun_sakato.pic_id = pic.id 
                                ORDER BY akun_sakato.id";
                        $result = $koneksi->query($sql);

                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['nama_pic'] ?? '-'); ?></td>
                            <td>
                                <?php if ($row['kelola_layanan_posisi_harilibur']): ?>
                                    <span class="badge badge-success">Kelola Data Layanan, Posisi & Hari Libur</span>
                                <?php endif; ?>

                                <?php if ($row['lihat_semua_warning']): ?>
                                    <span class="badge badge-success">Lihat Semua Berkas Warning</span>
                                <?php endif; ?>

                                <?php if ($row['kelola_pic_akun']): ?>
                                    <span class="badge badge-success">Kelola Data PIC & Akun</span>
                                <?php endif; ?>

                                <?php if ($row['update_berkas']): ?>
                                    <span class="badge badge-success">Update Berkas</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit<?= $row['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="konfirmasiHapus(<?= $row['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <!-- Hidden Form Hapus -->
                                <form id="formHapus<?= $row['id']; ?>" method="POST" action="akun.php" style="display:none;">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="hapus" value="1">
                                </form>
                            </td>
                        </tr>

                        <!-- MODAL EDIT DATA -->
                        <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <form method="POST" action="akun.php">
                                        <div class="modal-header bg-warning text-white">
                                            <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit Akun</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <div class="form-group">
                                                <label>Username</label>
                                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small></label>
                                                <input type="password" name="password" class="form-control" placeholder="Password Baru">
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Pengguna</label>
                                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama']); ?>" required>
                                            </div>
                                            <label class="font-weight-bold">Hak Akses:</label>
                                            <div class="form-group border p-3 rounded">
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input" id="edit_hk1_<?= $row['id']; ?>" name="kelola_layanan_posisi_harilibur" value="1" <?= $row['kelola_layanan_posisi_harilibur'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="edit_hk1_<?= $row['id']; ?>">Kelola Data Layanan, Posisi, dan Hari Libur</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input" id="edit_hk2_<?= $row['id']; ?>" name="lihat_semua_warning" value="1" <?= $row['lihat_semua_warning'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="edit_hk2_<?= $row['id']; ?>">Lihat Semua Berkas Warning</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input" id="edit_hk3_<?= $row['id']; ?>" name="kelola_pic_akun" value="1" <?= $row['kelola_pic_akun'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="edit_hk3_<?= $row['id']; ?>">Kelola Data PIC & Akun</label>
                                                </div>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="edit_hk4_<?= $row['id']; ?>" name="update_berkas" value="1" <?= $row['update_berkas'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="edit_hk4_<?= $row['id']; ?>">Update Berkas</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Pilih PIC (kosongkan jika bukan akun PIC)</label>
                                                <select name="pic_id" class="form-control">
                                                    <option value="">-- Pilih PIC (Opsional) --</option>
                                                    <?php foreach ($data_pic as $pic): ?>
                                                        <option value="<?= $pic['id']; ?>" <?= ($pic['id'] == $row['pic_id']) ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($pic['nama']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                            <button type="submit" name="edit" class="btn btn-warning">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH DATA -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="akun.php">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Tambah Akun Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Pengguna</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <label class="font-weight-bold">Hak Akses:</label>
                    <div class="form-group border p-3 rounded">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="tambah_hk1" name="kelola_layanan_posisi_harilibur" value="1">
                            <label class="custom-control-label" for="tambah_hk1">Kelola Layanan Posisi Hari Libur</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="tambah_hk2" name="lihat_semua_warning" value="1">
                            <label class="custom-control-label" for="tambah_hk2">Lihat Semua Warning</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="tambah_hk3" name="kelola_pic_akun" value="1">
                            <label class="custom-control-label" for="tambah_hk3">Kelola PIC Akun</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="tambah_hk4" name="update_berkas" value="1">
                            <label class="custom-control-label" for="tambah_hk4">Update Berkas</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih PIC (kosongkan jika bukan akun PIC)</label>
                        <select name="pic_id" class="form-control">
                            <option value="">-- Pilih PIC (Opsional) --</option>
                            <?php foreach ($data_pic as $pic): ?>
                                <option value="<?= $pic['id']; ?>"><?= htmlspecialchars($pic['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
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
    $('#tableAkun').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});

// SwatAlert Konfirmasi Hapus
function konfirmasiHapus(id) {
    Swal.fire({
        title: 'Apakah kamu yakin?',
        text: "Data akun ini akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formHapus' + id).submit();
        }
    });
}
</script>

<!-- SweetAlert Notifikasi Flash Message -->
<?php if (isset($_SESSION['flash_message'])): ?>
<script>
    Swal.fire({
        icon: '<?= $_SESSION['flash_message']['type']; ?>',
        title: '<?= $_SESSION['flash_message']['title']; ?>',
        text: '<?= $_SESSION['flash_message']['text']; ?>',
        timer: 2500,
        showConfirmButton: false
    });
</script>
<?php 
    unset($_SESSION['flash_message']);
endif; 
?>
        </main>
    </div>
</div>

</body>
</html>