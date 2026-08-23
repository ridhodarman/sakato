<?php
require_once 'inc/koneksi.php';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SAKATO V2 - Kinerja PIC</title>
    <link rel="stylesheet" href="assets/style.css">
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
        <aside class="col-md-3 col-lg-2 text-white p-3 min-vh-100 sticky-top" style="background: linear-gradient(180deg, #0f2e50, #194c7e);">
            <?php include "inc/sidebar.php"; ?>
        </aside>
        
        <!-- Main Content Column -->
        <main class="col-md-9 col-lg-10 p-4">
<!-- Tambahkan CDNs FontAwesome & Bootstrap Icons jika belum ada -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Container Menu Cards -->
<div class="row g-4 py-4 justify-content-center">
    
    <!-- Menu Card 1: Kelola Data PIC -->
    <div class="col-12 col-md-6 col-lg-5">
        <a href="pic.php" class="text-decoration-none">
            <div class="card h-100 border-0 text-white rounded-4 overflow-hidden position-relative p-4 card-futuristic card-pic">
                <!-- Overlay Gradient Glowing -->
                <div class="card-glow"></div>
                
                <div class="card-body position-relative z-1 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-3 bg-opacity-10 bg-white">
                                <i class="fas fa-user-gear fa-2x text-info"></i>
                            </div>
                            <span class="badge rounded-pill bg-info bg-opacity-20 text-info px-3 py-2 border border-info border-opacity-25" style="font-size: 0.75rem;">
                                SYSTEM DATA
                            </span>
                        </div>
                        <h3 class="fw-bold fs-4 mb-2 text-white">Kelola Data PIC</h3>
                        <p class="text-white-50 small mb-4">
                            Atur data penanggung jawab atau Person in Charge (PIC) kegiatan.
                        </p>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light border-opacity-10">
                        <span class="small font-monospace text-info">Lihat Detail &rarr;</span>
                        <div class="btn-arrow rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-chevron-right text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Menu Card 2: Kelola Data Layanan -->
    <div class="col-12 col-md-6 col-lg-5">
        <a href="layanan.php" class="text-decoration-none">
            <div class="card h-100 border-0 text-white rounded-4 overflow-hidden position-relative p-4 card-futuristic card-layanan">
                <!-- Overlay Gradient Glowing -->
                <div class="card-glow"></div>

                <div class="card-body position-relative z-1 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-3 bg-opacity-10 bg-white">
                                <i class="fas fa-list-check fa-2x text-warning"></i>
                            </div>
                            <span class="badge rounded-pill bg-warning bg-opacity-20 text-warning px-3 py-2 border border-warning border-opacity-25" style="font-size: 0.75rem;">
                                CONFIGURATION
                            </span>
                        </div>
                        <h3 class="fw-bold fs-4 mb-2 text-white">Kelola Data Layanan</h3>
                        <p class="text-white-50 small mb-4">
                            Atur jenis layanan pertanahan, SLA target waktu, dan kategori berkas.
                        </p>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light border-opacity-10">
                        <span class="small font-monospace text-warning">Lihat Detail &rarr;</span>
                        <div class="btn-arrow rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-chevron-right text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>

<!-- Internal CSS pendukung tampilan Futuristic/Glassmorphism -->
<style>
.card-futuristic {
    background: #0d1e33;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

/* Base Card Styles */
.card-pic {
    background: linear-gradient(145deg, #0e294b 0%, #071527 100%);
}

.card-layanan {
    background: linear-gradient(145deg, #1d2538 0%, #0a111f 100%);
}

/* Hover Effect */
.card-futuristic:hover {
    transform: translateY(-8px) scale(1.02);
    border-color: rgba(255, 255, 255, 0.25) !important;
}

.card-pic:hover {
    box-shadow: 0 15px 35px rgba(13, 202, 240, 0.25);
}

.card-layanan:hover {
    box-shadow: 0 15px 35px rgba(255, 193, 7, 0.25);
}

/* Futuristic Glow Effect */
.card-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 60%);
    pointer-events: none;
    transition: opacity 0.4s ease;
    opacity: 0.5;
}

.card-futuristic:hover .card-glow {
    opacity: 1;
}

/* Icon Wrapper */
.icon-wrapper {
    width: 58px;
    height: 58px;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Arrow Button Animation */
.btn-arrow {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
}

.card-futuristic:hover .btn-arrow {
    background: rgba(255, 255, 255, 0.25);
    transform: translateX(5px);
}
</style>
</main>
</div>
</div>
</body>

</html>