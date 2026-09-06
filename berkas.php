<?php
require_once 'auth.php';

// Ambil daftar layanan untuk form tambah dan edit
$layanan = [];
$sqlLayanan = "SELECT id, nama_layanan 
               FROM layanan 
               ORDER BY nama_layanan ASC";
$resultLayanan = $koneksi->query($sqlLayanan);
while ($row = $resultLayanan->fetch_assoc()) {
    $layanan[] = $row;
}

// Ambil daftar posisi untuk form tambah dan edit
$posisi = [];
$sqlPosisi = "SELECT id, nama_posisi 
              FROM posisi 
              ORDER BY nama_posisi ASC";
$resultPosisi = $koneksi->query($sqlPosisi);
while ($row = $resultPosisi->fetch_assoc()) {
    $posisi[] = $row;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO V2 - Kinerja PIC</title>
    <?php include "inc/head.php" ?>
        <style>

        body {
            background: #f5f6fa;
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .keterangan {
            line-height: 1.5;
            min-width: 250px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .status-proses {
            background: #17a2b8;
            color: white;
        }

        .status-eskalasi {
            background: #ffc107;
            color: #212529;
        }

        .status-selesai {
            background: #28a745;
            color: white;
        }

        .dt-buttons {
            margin-bottom: 10px;
        }

    </style>

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
<div class="container-fluid mt-4">

    <div class="card">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="mb-1">
                        Data Berkas Rutin
                    </h4>

                    <small class="text-muted">
                        Pengelolaan data berkas rutin
                    </small>
                </div>

                <button type="button"
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalTambah">

                    <i class="fas fa-plus"></i>
                    Tambah Berkas

                </button>

            </div>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table id="tableBerkas"
                       class="table table-bordered table-striped table-hover"
                       style="width:100%">

                    <thead>

                    <tr>

                        <th>No.</th>

                        <th>No. Berkas</th>

                        <th>Nama Pemohon</th>

                        <th>Tanggal Mulai</th>

                        <th>Layanan</th>

                        <th>Keterangan</th>

                        <th>Aksi</th>

                    </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- ===================================================== -->
<!-- MODAL TAMBAH -->
<!-- ===================================================== -->

<div class="modal fade"
     id="modalTambah"
     tabindex="-1"
     role="dialog">

    <div class="modal-dialog modal-lg"
         role="document">

        <div class="modal-content">

            <form id="formTambah">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Tambah Data Berkas
                    </h5>

                    <button type="button"
                            class="close"
                            data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>

                <div class="modal-body">

                    <div class="form-row">

                        <div class="form-group col-md-6">

                            <label>
                                No. Berkas
                            </label>

                            <input type="number"
                                   name="no_berkas"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="form-group col-md-6">

                            <label>
                                Tahun
                            </label>

                            <input type="number"
                                   name="tahun"
                                   class="form-control"
                                   value="<?= date('Y') ?>"
                                   required>

                        </div>

                    </div>


                    <div class="form-group">

                        <label>
                            Nama Pemohon
                        </label>

                        <input type="text"
                               name="nama_pemohon"
                               class="form-control"
                               maxlength="250"
                               required>

                    </div>


                    <div class="form-group">

                        <label>
                            Jenis Layanan
                        </label>

                        <select name="layanan_id"
                                class="form-control"
                                required>

                            <option value="">
                                -- Pilih Layanan --
                            </option>

                            <?php foreach ($layanan as $l): ?>

                                <option value="<?= $l['id'] ?>">
                                    <?= htmlspecialchars($l['nama_layanan']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Tanggal Mulai
                        </label>

                        <input type="date"
                               name="tanggal_mulai"
                               class="form-control"
                               value="<?= date('Y-m-d') ?>"
                               >

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                        Batal

                    </button>

                    <button type="submit"
                            class="btn btn-primary">

                        Simpan Data

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ===================================================== -->
<!-- MODAL UPDATE -->
<!-- ===================================================== -->

<div class="modal fade" id="modalUpdate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formUpdate">
                <div class="modal-header">
                    <h5 class="modal-title">Update Data Berkas</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>No. Berkas</label>
                            <input type="number" name="no_berkas" id="edit_no_berkas" class="form-control" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Tahun</label>
                            <input type="number" name="tahun" id="edit_tahun" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nama Pemohon</label>
                        <input type="text" name="nama_pemohon" id="edit_nama_pemohon" class="form-control" maxlength="250" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Layanan</label>
                        <select name="layanan_id" id="edit_layanan_id" class="form-control" required>
                            <option value="">-- Pilih Layanan --</option>
                            <?php foreach ($layanan as $l): ?>
                                <option value="<?= $l['id'] ?>">
                                    <?= htmlspecialchars($l['nama_layanan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pilihan Posisi Berkas Terakhir -->
                    <div class="form-group">
                        <label>Posisi Berkas Terakhir</label>
                        <select name="posisi_id" id="edit_posisi_id" class="form-control" required>
                            <option value="">-- Pilih Posisi --</option>
                            <?php foreach ($posisi as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['nama_posisi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" class="form-control">
                    </div>

                    <hr>

                    <h6 class="mb-3">Status Penyelesaian</h6>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="proses">Proses</option>
                            <option value="eskalasi">Eskalasi</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan" id="edit_catatan" class="form-control" rows="4"></textarea>
                    </div>


                    <div class="form-group">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" class="form-control">
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger" id="btnHapus">Hapus</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>

$(document).ready(function () {


    // =====================================================
    // DATATABLE
    // =====================================================

    let table = $('#tableBerkas').DataTable({

        processing: true,

        serverSide: true,

        responsive: true,

        pageLength: 50,

        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
        ],

        ajax: {
            url: 'inc/berkas-data.php',
            type: 'POST'
        },

        order: [
            [0, 'desc']
        ],

        columns: [

            {
                data: 'no'
            },

            {
                data: 'no_berkas'
            },

            {
                data: 'nama_pemohon'
            },

            {
                data: 'tanggal_mulai'
            },

            {
                data: 'nama_layanan'
            },

            {
                data: 'keterangan',
                orderable: false
            },

            {
                data: 'aksi',
                orderable: false,
                searchable: false
            }

        ],

        language: {

            processing: "Memproses...",

            search: "Cari:",

            lengthMenu: "Tampilkan _MENU_ data",

            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",

            infoEmpty: "Tidak ada data",

            zeroRecords: "Data tidak ditemukan",

            paginate: {

                first: "Pertama",

                last: "Terakhir",

                next: "Berikutnya",

                previous: "Sebelumnya"

            }

        }

    });


// =====================================================
// TAMBAH DATA
// =====================================================

$('#formTambah').submit(function(e) {

    e.preventDefault();

    let form = this;

    Swal.fire({

        title: 'Simpan data?',

        text: 'Data berkas akan disimpan.',

        icon: 'question',

        showCancelButton: true,

        confirmButtonText: 'Ya, simpan',

        cancelButtonText: 'Batal'

    }).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        $.ajax({

            url: 'inc/berkas-save.php',

            type: 'POST',

            data: $(form).serialize(),

            dataType: 'json',

            beforeSend: function() {

                Swal.fire({

                    title: 'Menyimpan...',

                    allowOutsideClick: false,

                    didOpen: () => {

                        Swal.showLoading();

                    }

                });

            },

            success: function(response) {

                if (response.status) {

                    // 1. Sembunyikan modal
                    $('#modalTambah').modal('hide');

                    // 2. PAKSA HAPUS backdrop & reset style body
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css({
                        'overflow': 'auto',
                        'padding-right': ''
                    });

                    // 3. Reset form dan reload tabel
                    form.reset();
                    table.ajax.reload(null, false);

                    // 4. Tampilkan alert sukses
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                } else {

                    Swal.fire({

                        icon: 'error',

                        title: 'Gagal',

                        text: response.message

                    });

                }

            },

            error: function() {

                Swal.fire({

                    icon: 'error',

                    title: 'Error',

                    text: 'Terjadi kesalahan pada server.'

                });

            }

        });

    });

});


// =====================================================
// BUKA MODAL UPDATE
// =====================================================
$(document).on('click', '.btnUpdate', function() {

    // Cek hak akses update berkas
    let bolehUpdate = <?= (int)($_SESSION['update_berkas'] ?? 0) ?>;

    if (bolehUpdate !== 1) {
        Swal.fire({
            icon: 'error',
            title: 'Akses Ditolak',
            text: 'Anda tidak memiliki akses untuk update berkas.',
            confirmButtonText: 'OK'
        });

        return;
    }

    let id = $(this).data('id');

    $.ajax({
        url: 'inc/berkas-get.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',

        success: function(response) {

            if (!response.status) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: response.message
                });
                return;
            }

            let data = response.data;

            $('#edit_id').val(data.id);
            $('#edit_no_berkas').val(data.no_berkas);
            $('#edit_tahun').val(data.tahun);
            $('#edit_nama_pemohon').val(data.nama_pemohon);
            $('#edit_layanan_id').val(data.layanan_id);
            $('#edit_posisi_id').val(data.posisi_id);
            $('#edit_tanggal_mulai').val(data.tanggal_mulai);
            $('#edit_status').val(data.status);
            $('#edit_catatan').val(data.catatan);
            $('#edit_tanggal_selesai').val(data.tanggal_selesai);

            $('#modalUpdate').modal('show');
        },

        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Gagal mengambil data berkas.'
            });
        }
    });
});


// =====================================================
// UPDATE DATA
// =====================================================
$('#formUpdate').submit(function(e) {
    e.preventDefault();
    let form = this;

    Swal.fire({
        title: 'Simpan perubahan?',
        text: 'Data berkas akan diperbarui.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, simpan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: 'inc/berkas-save.php',
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            beforeSend: function() {
                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.status) {
                    $('#modalUpdate').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan pada server.'
                });
            }
        });
    });
});


    // =====================================================
    // HAPUS DATA
    // =====================================================

    $('#btnHapus').click(function() {

        let id = $('#edit_id').val();


        Swal.fire({

            title: 'Hapus data?',

            text: 'Data yang dihapus tidak dapat dikembalikan!',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#d33',

            cancelButtonColor: '#6c757d',

            confirmButtonText: 'Ya, hapus',

            cancelButtonText: 'Batal'

        }).then((result) => {

            if (!result.isConfirmed) {
                return;
            }


            $.ajax({

                url: 'inc/berkas-delete.php',

                type: 'POST',

                data: {
                    id: id
                },

                dataType: 'json',

                beforeSend: function() {

                    Swal.fire({

                        title: 'Menghapus...',

                        allowOutsideClick: false,

                        didOpen: () => {

                            Swal.showLoading();

                        }

                    });

                },

                success: function(response) {

                    if (response.status) {

                        $('#modalUpdate').modal('hide');

                        table.ajax.reload(null, false);

                        Swal.fire({

                            icon: 'success',

                            title: 'Berhasil',

                            text: response.message,

                            timer: 1500,

                            showConfirmButton: false

                        });

                    } else {

                        Swal.fire({

                            icon: 'error',

                            title: 'Gagal',

                            text: response.message

                        });

                    }

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                    Swal.fire({

                        icon: 'error',

                        title: 'Error',

                        html: `
                            Terjadi kesalahan pada server.
                            <br><br>
                            <small>${xhr.responseText}</small>
                        `

                    });

                }

            });

        });

    });


});

</script>
</div>
</div>
</body>

</html>