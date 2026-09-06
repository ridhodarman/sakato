<!-- Bootstrap 4 CSS -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<!-- FontAwesome -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- SweetAlert2 CSS -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


<!-- jQuery FULL -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

<!-- Bootstrap 4 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- FontAwesome 6 -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css"
>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

<style>
.menusidebar {
    background: linear-gradient(180deg, #0f2e50, #194c7e);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

/* Mobile */
@media (max-width: 767.98px) {
    .menusidebar {
        position: relative;
        height: auto;
        min-height: auto;
        overflow-y: visible;
    }
}

/* =========================================================
   SIDEBAR
   ========================================================= */

.menusidebar {
    background: #14385f;
    min-height: 100vh;
    width: 250px;
    padding: 1rem;
    flex: 0 0 250px;
}

/* Tombol hamburger */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1050;
    width: 44px;
    height: 44px;
    border-radius: 8px;
}

/* Overlay HP */
.sidebar-overlay {
    display: none;
}

/* =========================================================
   RESPONSIVE MOBILE
   ========================================================= */

@media (max-width: 767.98px) {

    .menusidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;

        width: 270px;
        max-width: 85vw;

        min-height: 100vh;

        z-index: 1040;

        overflow-y: auto;

        transform: translateX(-100%);
        transition: transform 0.25s ease;

        box-shadow: 5px 0 20px rgba(0, 0, 0, 0.25);
    }

    .menusidebar.sidebar-open {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: block;
    }

    .sidebar-overlay {
        position: fixed;
        inset: 0;

        background: rgba(0, 0, 0, 0.45);

        z-index: 1030;
    }

    .sidebar-overlay.active {
        display: block;
    }

    /* Supaya isi halaman tidak tertutup tombol hamburger */
    .main-content {
        padding-top: 65px;
    }

    /* Menu lebih nyaman disentuh di HP */
    .menusidebar .nav-link {
        min-height: 44px;
        padding: 10px 12px;
    }

    /* Judul SAKATO sedikit lebih ringkas */
    .menusidebar .h4 {
        font-size: 1.15rem;
    }
}
</style>
