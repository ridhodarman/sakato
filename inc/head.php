<!-- Bootstrap 4 CSS -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

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
/* =========================================================
   LAYOUT UTAMA
   ========================================================= */

html,
body {
    margin: 0;
    padding: 0;
}

.container-fluid {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.main-content {
    flex: 1;
    min-width: 0;
    padding: 24px;
    position: relative;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

.menusidebar {
    width: 250px;
    min-width: 250px;
    flex: 0 0 250px;

    background: linear-gradient(
        180deg,
        #0f2e50 0%,
        #194c7e 100%
    );

    color: #fff;

    min-height: 100vh;
    height: 100vh;

    position: sticky;
    top: 0;

    overflow-y: auto;
    overflow-x: hidden;

    padding: 16px;

    z-index: 1040;

    scrollbar-width: thin;
}


/* Scrollbar sidebar */
.menusidebar::-webkit-scrollbar {
    width: 5px;
}

.menusidebar::-webkit-scrollbar-track {
    background: transparent;
}

.menusidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.25);
    border-radius: 10px;
}


/* =========================================================
   MENU SIDEBAR
   ========================================================= */

.menusidebar .nav-link {
    min-height: 44px;

    padding: 10px 12px;

    display: flex;
    align-items: center;

    border-radius: 7px;

    white-space: nowrap;

    transition:
        background-color 0.2s ease,
        color 0.2s ease;
}

.menusidebar .nav-link i {
    flex: 0 0 20px;
    width: 20px !important;
    margin-right: 8px;
    text-align: center;
}

.menusidebar .nav-link span {
    overflow: hidden;
    text-overflow: ellipsis;
}


/* =========================================================
   BADGE JUMLAH
   ========================================================= */

.menusidebar .menu-badge {
    min-width: 22px;
    height: 22px;

    padding: 2px 6px;

    margin-left: auto;

    border-radius: 11px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    font-size: 11px;
    font-weight: 700;

    flex-shrink: 0;
}


/* =========================================================
   TOMBOL HAMBURGER
   ========================================================= */

.sidebar-toggle {
    display: none;

    position: fixed;

    top: 12px;
    left: 12px;

    width: 44px;
    height: 44px;

    padding: 0;

    border-radius: 8px;

    z-index: 1050;

    box-shadow: 0 3px 10px rgba(0,0,0,0.20);
}


/* =========================================================
   OVERLAY
   ========================================================= */

.sidebar-overlay {
    display: none;
}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 767.98px) {

    .menusidebar {

        position: fixed;

        top: 0;
        left: 0;
        bottom: 0;

        width: 270px;
        max-width: 85vw;

        min-width: 0;

        height: 100vh;
        min-height: 100vh;

        padding: 16px;

        overflow-y: auto;

        transform: translateX(-100%);

        transition: transform 0.25s ease;

        box-shadow: 5px 0 25px rgba(0,0,0,0.25);

        z-index: 1040;
    }


    .menusidebar.sidebar-open {
        transform: translateX(0);
    }


    .sidebar-toggle {
        display: flex;

        align-items: center;
        justify-content: center;
    }


    .sidebar-overlay {

        position: fixed;

        top: 0;
        left: 0;
        right: 0;
        bottom: 0;

        background: rgba(0,0,0,0.45);

        z-index: 1030;
    }


    .sidebar-overlay.active {
        display: block;
    }


    .main-content {

        width: 100%;

        padding: 70px 12px 20px 12px;
    }


    /* Profile */
    .menusidebar .d-flex {
        max-width: 100%;
    }


    /* Menu lebih mudah disentuh */
    .menusidebar .nav-link {

        min-height: 46px;

        padding: 11px 12px;

        margin-bottom: 4px !important;
    }


    /* Tombol logout */
    .menusidebar #logout {
        min-height: 44px;
    }
}


/* =========================================================
   HP KECIL
   ========================================================= */

@media (max-width: 380px) {

    .menusidebar {
        width: 250px;
    }

    .main-content {
        padding-left: 10px;
        padding-right: 10px;
    }

}
</style>
