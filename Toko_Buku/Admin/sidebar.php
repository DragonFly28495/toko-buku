<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isKategoriActive = in_array($currentPage, ['kategori-admin.php', 'form_tambah_kategori.php','form_edit_kategori.php']);
$isBukuActive = in_array($currentPage, ['daftar_buku-admin.php', 'form_tambah_buku.php','form_edit_buku.php']);

// Hitung pesan belum dibaca - PAKAI VARIABLE UNIK BIAR GA BENTROK
$sidebar_unread_count = 0;
if (isset($pdo) && $currentPage != 'kategori-admin.php' && $currentPage != 'daftar_buku-admin.php') {
    try {
        $sidebar_sql = "SELECT COUNT(*) as total FROM pesan WHERE sudah_dibaca = 0 AND is_from_admin = 0";
        $sidebar_stmt = $pdo->prepare($sidebar_sql);
        $sidebar_stmt->execute();
        $sidebar_result = $sidebar_stmt->fetch(PDO::FETCH_ASSOC);
        $sidebar_unread_count = $sidebar_result['total'] ?? 0;
    } catch (Exception $e) {
        $sidebar_unread_count = 0;
    }
}
?>

<!-- Sidebar Navbar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-title">Admin Panel</div>
    </div>

    <div class="sidebar-nav">
        <a href="beranda-admin.php" class="sidebar-link <?= ($currentPage == 'beranda-admin.php') ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>

        <!-- Dropdown Kategori -->
        <div class="sidebar-dropdown">
            <button class="dropdown-toggle <?= $isKategoriActive ? 'active' : '' ?>"
                onclick="toggleDropdown('kategoriDropdown')" id="kategoriToggle">
                <i class="fas fa-tags"></i>
                <span>Kategori Buku</span>
                <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div id="kategoriDropdown" class="dropdown-content <?= $isKategoriActive ? 'show' : '' ?>">
                <a href="kategori-admin.php" class="<?= ($currentPage == 'kategori-admin.php') ? 'active' : '' ?>">
                    <i class="fas fa-list"></i>
                    Lihat Kategori
                </a>
                <a href="form_tambah_kategori.php"
                    class="<?= ($currentPage == 'form_tambah_kategori.php') ? 'active' : '' ?>">
                    <i class="fas fa-plus"></i>
                    Tambah Kategori
                </a>
            </div>
        </div>

        <!-- Dropdown Buku -->
        <div class="sidebar-dropdown">
            <button class="dropdown-toggle <?= $isBukuActive ? 'active' : '' ?>"
                onclick="toggleDropdown('bukuDropdown')" id="bukuToggle">
                <i class="fas fa-book"></i>
                <span>Daftar Buku</span>
                <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div id="bukuDropdown" class="dropdown-content <?= $isBukuActive ? 'show' : '' ?>">
                <a href="daftar_buku-admin.php"
                    class="<?= ($currentPage == 'daftar_buku-admin.php') ? 'active' : '' ?>">
                    <i class="fas fa-list"></i>
                    Lihat Buku
                </a>
                <a href="form_tambah_buku.php" class="<?= ($currentPage == 'form_tambah_buku.php') ? 'active' : '' ?>">
                    <i class="fas fa-plus"></i>
                    Tambah Buku
                </a>
            </div>
        </div>

        <a href="daftar_pengguna-admin.php"
            class="sidebar-link <?= ($currentPage == 'daftar_pengguna-admin.php') ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Daftar Pengguna</span>
        </a>

        <a href="pesanan.php" class="sidebar-link <?= ($currentPage == 'pesanan.php') ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Pesanan</span>
        </a>

        <!-- Menu Pesan Pengguna dengan Notifikasi -->
        <a href="pesan_pengguna.php" class="sidebar-link <?= ($currentPage == 'pesan_pengguna.php') ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i>
            <span>Pesan Pengguna</span>
            <?php if ($sidebar_unread_count > 0): ?>
                <span class="notification-badge"><?= $sidebar_unread_count ?></span>
            <?php endif; ?>
        </a>

        <a href="../login.php" class="sidebar-link logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </a>
    </div>
</nav>