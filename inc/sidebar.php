<?php
// =========================================================
// DATA USER LOGIN
// =========================================================

$nama_user = $_SESSION['sakato_nama']
    ?? $_SESSION['nama']
    ?? $_SESSION['sakato_username']
    ?? $_SESSION['username']
    ?? 'User';

$username = $_SESSION['sakato_username']
    ?? $_SESSION['username']
    ?? 'guest';

// ID akun login
$id_user = isset($_SESSION['id_user'])
    ? (int) $_SESSION['id_user']
    : 0;

// Inisial
$inisial = strtoupper(substr(trim($nama_user), 0, 1));


// =========================================================
// JUMLAH WARNING
// =========================================================
//
// Warning dihitung berdasarkan PIC pada tabel layanan.
// layanan.pic_id = akun_sakato.id user yang login.
//
// Gunakan logika warning yang sama dengan halaman warning.php.
// =========================================================

$jumlah_warning = 0;

if (isset($koneksi) && $id_user >= 0) {

    /*
     * Hitung berkas yang PIC-nya adalah user login.
     *
     * Bagian kondisi warning di bawah mengikuti konsep:
     * - berkas masih proses
     * - sudah masuk batas waspada/kritis/jatuh tempo
     *
     * Jika warning.php Anda memiliki rumus khusus,
     * sebaiknya kondisi WHERE ini disamakan persis.
     */

    $sqlWarning = "
        SELECT COUNT(*)
        FROM berkas_rutin b
        INNER JOIN layanan l
            ON l.id = b.layanan_id
        WHERE l.pic_id = ?
          AND b.status = 'proses'
    ";

    $stmtWarning = mysqli_prepare($koneksi, $sqlWarning);

    if ($stmtWarning) {

        mysqli_stmt_bind_param(
            $stmtWarning,
            "i",
            $id_user
        );

        mysqli_stmt_execute($stmtWarning);

        mysqli_stmt_bind_result(
            $stmtWarning,
            $jumlah_warning
        );

        mysqli_stmt_fetch($stmtWarning);

        mysqli_stmt_close($stmtWarning);
    }
}


// =========================================================
// JUMLAH ESKALASI
// =========================================================
//
// Berdasarkan:
// eskalasi.akun_sakato_id = akun yang sedang login
//
// Satu baris eskalasi = satu tujuan akun.
// =========================================================

$jumlah_eskalasi = 0;

if (isset($koneksi) && $id_user >= 0) {

    $sqlEskalasi = "
        SELECT COUNT(*)
        FROM eskalasi e
        INNER JOIN berkas_rutin b
            ON b.id = e.berkas_rutin_id
        WHERE e.akun_sakato_id = ?
          AND b.status = 'eskalasi'
    ";

    $stmtEskalasi = mysqli_prepare($koneksi, $sqlEskalasi);

    if ($stmtEskalasi) {

        mysqli_stmt_bind_param(
            $stmtEskalasi,
            "i",
            $id_user
        );

        mysqli_stmt_execute($stmtEskalasi);

        mysqli_stmt_bind_result(
            $stmtEskalasi,
            $jumlah_eskalasi
        );

        mysqli_stmt_fetch($stmtEskalasi);

        mysqli_stmt_close($stmtEskalasi);
    }
}
?>


<!-- =====================================================
     BRAND / HEADER
     ===================================================== -->

<div class="pb-3 mb-3 border-bottom"
     style="border-color: rgba(255, 255, 255, 0.14) !important;">

    <a href="dashboard.php"
       class="text-white text-decoration-none">

        <span class="h4 font-weight-bold font-bold d-block mb-0">
            SAKATO
        </span>

        <small class="d-block text-white"
               style="font-size: 0.72rem; line-height: 1.3;">

            Sistem Akselerasi Kolaboratif Tunggakan Online<br>
            Kantor Pertanahan Kabupaten Agam

        </small>

    </a>

</div>


<!-- =====================================================
     USER PROFILE
     ===================================================== -->

<div class="d-flex align-items-center p-2 rounded mb-3"
     style="background: rgba(255, 255, 255, 0.09);">

    <!-- Avatar -->
    <div class="rounded-circle bg-white font-weight-bold
                d-flex align-items-center justify-content-center mr-2"

         style="
             width: 38px;
             height: 38px;
             min-width: 38px;
             color: #14385f !important;
             font-size: 1rem;
         ">

        <?= htmlspecialchars($inisial); ?>

    </div>


    <!-- Informasi user -->
    <div class="overflow-hidden">

        <div class="font-weight-bold text-truncate text-white"
             style="font-size: 0.85rem;"
             title="<?= htmlspecialchars($nama_user); ?>">

            <?= htmlspecialchars($nama_user); ?>

        </div>

        <small class="text-white-50 d-block text-truncate"
               style="font-size: 0.75rem;">

            @<?= htmlspecialchars($username); ?>

        </small>

    </div>

</div>


<!-- =====================================================
     NAVIGATION
     ===================================================== -->

<nav class="nav nav-pills flex-column mb-auto">

    <?php

    $menus = [

        'dashboard.php' => [
            'icon'  => 'fas fa-th-large',
            'label' => 'Dashboard'
        ],

        'berkas.php' => [
            'icon'  => 'fas fa-folder',
            'label' => 'Database Berkas'
        ],

        'warning.php' => [
            'icon'  => 'fas fa-exclamation-triangle',
            'label' => 'Early Warning',
            'badge' => $jumlah_warning,
            'badge_class' => 'badge-warning'
        ],

        'eskalasi.php' => [
            'icon'  => 'fas fa-arrow-up-right-dots',
            'label' => 'Eskalasi',
            'badge' => $jumlah_eskalasi,
            'badge_class' => 'badge-danger'
        ],

        'kinerja.php' => [
            'icon'  => 'fas fa-users',
            'label' => 'Kinerja PIC'
        ],

        'input.php' => [
            'icon'  => 'fas fa-plus-circle',
            'label' => 'Input / Update'
        ],

    ];

    foreach ($menus as $file => $menu):

        $isActive = ($current_page == $file);

        $activeClass = $isActive
            ? 'active text-white'
            : 'text-white-50';

        $activeStyle = $isActive
            ? 'background-color: rgba(255, 255, 255, 0.2); font-weight: 600;'
            : '';

    ?>

        <a href="<?= htmlspecialchars($file); ?>"
           class="nav-link <?= $activeClass; ?> mb-1 rounded
                  d-flex align-items-center"
           style="<?= $activeStyle; ?> transition: all 0.2s;"

           onmouseover="this.style.backgroundColor='rgba(255,255,255,0.14)'; this.style.color='#fff';"

           onmouseout="this.style.backgroundColor='<?= $isActive
               ? 'rgba(255,255,255,0.2)'
               : 'transparent'; ?>'; this.style.color='<?= $isActive
               ? '#fff'
               : 'rgba(255,255,255,0.5)'; ?>';">


            <!-- ICON -->
            <i class="<?= htmlspecialchars($menu['icon']); ?> mr-2 me-2"
               style="width: 18px;">
            </i>


            <!-- LABEL -->
            <span class="flex-grow-1">
                <?= htmlspecialchars($menu['label']); ?>
            </span>


            <!-- BADGE -->
            <?php if (
                isset($menu['badge']) &&
                (int)$menu['badge'] > 0
            ): ?>

                <span class="badge <?= htmlspecialchars($menu['badge_class']); ?> menu-badge">

                    <?= (int)$menu['badge']; ?>

                </span>

            <?php endif; ?>


        </a>

    <?php endforeach; ?>

</nav>


<!-- =====================================================
     LOGOUT
     ===================================================== -->

<div class="pt-3 mt-3 border-top"
     style="border-color: rgba(255,255,255,0.14) !important;">

    <button id="logout"
            class="btn btn-light btn-block w-100 text-center"

            style="
                background: #eef3f8;
                color: #29405b;
                font-weight: 700;
            "

            onclick="keluar()">

        <i class="fas fa-sign-out-alt mr-2 me-2"></i>

        Keluar

    </button>

</div>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.querySelector('.menusidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    const icon = document.getElementById('sidebarToggleIcon');

    if (!sidebar || !toggle || !overlay || !icon) {
        return;
    }


    // =====================================================
    // BUKA / TUTUP SIDEBAR
    // =====================================================

    toggle.addEventListener('click', function () {

        const isOpen = sidebar.classList.toggle('sidebar-open');

        overlay.classList.toggle('active', isOpen);

        if (isOpen) {

            // Ubah ☰ menjadi X
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');

            toggle.setAttribute('aria-label', 'Tutup menu');

        } else {

            // Ubah X menjadi ☰
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');

            toggle.setAttribute('aria-label', 'Buka menu');

        }

    });


    // =====================================================
    // TUTUP KLIK OVERLAY
    // =====================================================

    overlay.addEventListener('click', function () {

        tutupSidebar();

    });


    // =====================================================
    // TUTUP KLIK MENU
    // =====================================================

    sidebar.querySelectorAll('.nav-link').forEach(function (link) {

        link.addEventListener('click', function () {

            if (window.innerWidth <= 767) {

                tutupSidebar();

            }

        });

    });


    // =====================================================
    // TUTUP DENGAN ESC
    // =====================================================

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {

            tutupSidebar();

        }

    });


    // =====================================================
    // FUNGSI TUTUP SIDEBAR
    // =====================================================

    function tutupSidebar() {

        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('active');

        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');

        toggle.setAttribute('aria-label', 'Buka menu');

    }


    // =====================================================
    // JIKA KEMBALI KE DESKTOP
    // =====================================================

    window.addEventListener('resize', function () {

        if (window.innerWidth > 767) {

            tutupSidebar();

        }

    });

});


function keluar() {
    window.location.href = "act/logout.php";
}
</script>