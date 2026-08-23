<!-- Brand / Header -->
<div class="pb-3 mb-3 border-bottom" style="border-color: rgba(255, 255, 255, 0.14) !important;">
    <a href="dashboard.php" class="text-white text-decoration-none">
        <span class="h4 font-weight-bold font-bold d-block mb-0">SAKATO V2</span>
        <small class="d-block text-white" style="font-size: 0.72rem; line-height: 1.3;">
            Sistem Akselerasi Kolaboratif Tunggakan Online<br>
            Kantor Pertanahan Kabupaten Agam
        </small>
    </a>
</div>

<!-- User Profile Card -->
<div class="d-flex align-items-center p-2 rounded mb-3" style="background: rgba(255, 255, 255, 0.09);">
    <div class="rounded-circle bg-white text-dark font-weight-bold font-bold d-flex align-items-center justify-content-center mr-2" style="width: 38px; height: 38px; min-width: 38px; color: #14385f !important;">
        K
    </div>
    <div class="overflow-hidden">
        <div class="font-weight-bold font-bold text-truncate text-white" style="font-size: 0.85rem;">Kepala Kantor</div>
        <small class="text-white-50 d-block" style="font-size: 0.75rem;">Pimpinan</small>
    </div>
</div>

<!-- Navigation Menu -->
<nav class="nav nav-pills flex-column mb-auto">
    <?php
    // Daftar menu beserta ikon dan tautannya
    $menus = [
        'dashboard.php'  => ['icon' => 'fas fa-th-large', 'label' => 'Dashboard'],
        'databerkas.php' => ['icon' => 'fas fa-folder', 'label' => 'Database Berkas'],
        'warning.php'    => ['icon' => 'fas fa-exclamation-triangle', 'label' => 'Early Warning'],
        'eskalasi.php'   => ['icon' => 'fas fa-arrow-up-right-dots', 'label' => 'Eskalasi'],
        'pic.php'        => ['icon' => 'fas fa-users', 'label' => 'Kinerja PIC'],
        'input.php'      => ['icon' => 'fas fa-plus-circle', 'label' => 'Input / Update'],
        'audit.php'      => ['icon' => 'fas fa-history', 'label' => 'Audit Trail'],
        'setting.php'    => ['icon' => 'fas fa-cog', 'label' => 'Pengaturan']
    ];

    foreach ($menus as $file => $menu):
        $isActive = ($current_page == $file);
        $activeClass = $isActive ? 'active text-white' : 'text-white-50';
        $activeStyle = $isActive ? 'background-color: rgba(255, 255, 255, 0.2); font-weight: 600;' : '';
    ?>
        <a href="<?= $file; ?>" 
           class="nav-link <?= $activeClass; ?> mb-1 rounded d-flex align-items-center" 
           style="<?= $activeStyle; ?> transition: all 0.2s;"
           onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.14)'; this.style.color='#fff';"
           onmouseout="this.style.backgroundColor='<?= $isActive ? 'rgba(255, 255, 255, 0.2)' : 'transparent'; ?>'; this.style.color='<?= $isActive ? '#fff' : 'rgba(255, 255, 255, 0.5)'; ?>';">
            <i class="<?= $menu['icon']; ?> mr-2 me-2" style="width: 18px;"></i> <?= $menu['label']; ?>
        </a>
    <?php endforeach; ?>
</nav>

<!-- Logout Button -->
<div class="pt-3 mt-3 border-top" style="border-color: rgba(255,255,255,0.14) !important;">
    <button id="logout" class="btn btn-light btn-block w-100 text-center" style="background: #eef3f8; color: #29405b; font-weight: 700;" onclick="logout()">
        <i class="fas fa-sign-out-alt mr-2 me-2"></i> Keluar
    </button>
</div>