<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../config.php';
$isLoggedIn = isLoggedIn();

$search = '';
$result = null;
$total = 0;
$books = [];

try {
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    }

    if ($search !== '') {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare("SELECT id_buku, judul, penulis, harga_regular, stok, kategori, deskripsi, cover_buku FROM buku WHERE judul LIKE ? OR penulis LIKE ? OR kategori LIKE ? ORDER BY id_buku ASC");
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result ? $result->num_rows : 0;
        
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT id_buku, judul, penulis, harga_regular, stok, kategori, deskripsi, cover_buku FROM buku ORDER BY id_buku ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result ? $result->num_rows : 0;
        
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        $stmt->close();
    }

} catch (Exception $e) {
    error_log("Error fetching books: " . $e->getMessage());
    
    try {
        $fallback_stmt = $conn->prepare("SELECT id_buku, judul, penulis, harga_regular, stok, kategori, deskripsi, cover_buku FROM buku ORDER BY id_buku ASC LIMIT 20");
        $fallback_stmt->execute();
        $fallback_result = $fallback_stmt->get_result();
        while ($row = $fallback_result->fetch_assoc()) {
            $books[] = $row;
        }
        $total = count($books);
        $fallback_stmt->close();
    } catch (Exception $e2) {
        error_log("Fallback query also failed: " . $e2->getMessage());
    }
}

// Ambil jumlah item di keranjang jika user login
$cartCount = 0;
$hasCartItems = false;

if ($isLoggedIn && isset($_SESSION['id_pengguna'])) {
    $userId = $_SESSION['id_pengguna'];
    
    // Query untuk menghitung total item di keranjang
    $sqlCart = "SELECT SUM(jumlah) as total_items FROM keranjang WHERE id_pengguna = ?";
    $stmtCart = $conn->prepare($sqlCart);
    $stmtCart->bind_param("i", $userId);
    $stmtCart->execute();
    $resultCart = $stmtCart->get_result();
    
    if ($rowCart = $resultCart->fetch_assoc()) {
        $cartCount = $rowCart['total_items'] ?? 0;
        $hasCartItems = ($cartCount > 0);
    }
    $stmtCart->close();
}

// Ambil data profil pengguna jika sudah login
$fotoProfil = '../Aset/profil_default.png'; // Default profile picture
$hasFotoProfil = false;

if ($isLoggedIn && isset($_SESSION['id_pengguna'])) {
    $userId = $_SESSION['id_pengguna'];
    $sql = "SELECT foto_profil FROM daftar_pengguna WHERE id_pengguna = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (!empty($row['foto_profil'])) {
            $fotoProfil = '../uploads/profil/' . $row['foto_profil'];
            $hasFotoProfil = true;
        }
        // Jika tidak ada foto_profil, tetap menggunakan profil_default.png
    }
    $stmt->close();
}

// Set active page jika belum diset
if (!isset($activePage)) {
    $activePage = basename($_SERVER['PHP_SELF'], '.php');
}
?>

<header class="navbar">
    <ul class="navbar-container">
        <!-- Logo -->
        <div class="navbar-logo">
            <a href="beranda-user.php">
                <img src="../Aset/IconLivro.png" alt="Logo Toko Buku" class="logo-img">
                <span class="logo-text">Toko Buku</span>
            </a>
        </div>
           <!-- Navigation Links -->
        <nav class="navbar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="beranda-user.php" class="nav-link <?= ($activePage == 'beranda-user' || $activePage == 'beranda') ? 'active' : '' ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a href="buku-user.php" class="nav-link <?= ($activePage == 'buku-user' || $activePage == 'buku') ? 'active' : '' ?>">Produk Kami</a>
                </li>
                <li class="nav-item">
                    <a href="pesanan-user.php" class="nav-link <?= ($activePage == 'pesanan-user' || $activePage == 'pesanan') ? 'active' : '' ?>">Pesanan</a>
                </li>
                <li class="nav-item">
                    <a href="hubungi_admin.php" class="nav-link <?= ($activePage == 'hubungi_admin' || $activePage == 'hubungi') ? 'active' : '' ?>">Hubungi</a>
                </li>
            </ul>
        </nav>

<!-- Search Bar -->
<div class="navbar-search">
    <form action="semua_buku.php" method="GET" class="search-form">
        <div class="search-wrapper">
            <input type="text" name="search" class="search-input" placeholder="Cari judul, penulis, atau kategori..." value="<?= htmlspecialchars($search) ?>" required>
            <button type="submit" class="search-button">
                <i class="fas fa-search search-icon"></i>
            </button>
        </div>
    </form>
</div>


        <!-- User Actions -->
        <div class="navbar-actions">
<!-- Cart -->
<a href="keranjang-user.php" class="action-btn cart-btn">
    <i class="fas fa-shopping-cart"></i>
    <?php if ($hasCartItems): ?>
        <span class="cart-notification-dot"></span>
    <?php endif; ?>
</a>

            <?php if ($isLoggedIn): ?>
                <!-- User Profile (Logged In) -->
<!-- User Profile (Logged In) -->
<div class="user-profile">
    <button class="profile-btn">
        <!-- SELALU gunakan img, bahkan untuk default -->
        <img src="<?= $fotoProfil ?>" alt="Foto Profil" class="profile-img">
        <span class="username"><?= $_SESSION['username'] ?? 'User' ?></span>
        <i class="fas fa-chevron-down dropdown-arrow"></i>
    </button>
    
    <div class="profile-dropdown">
        <a href="edit-profil.php" class="dropdown-item">
            <i class="fas fa-user"></i>
            <span>Akun</span>
        </a>
        <a href="pesanan-user.php" class="dropdown-item">
            <i class="fas fa-receipt"></i>
            <span>Pesanan Saya</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="../logout.php" class="dropdown-item logout-item" onclick="return confirm('Yakin Logout?')">
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </a>
    </div>
</div>
        </div>
            <?php else: ?>
                <!-- Login/Register (Not Logged In) -->
                <div class="auth-buttons">
                    <a href="../login-user.php" class="auth-btn login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="../registrasi-user.php" class="auth-btn register-btn">
                        <i class="fas fa-user-plus"></i>
                        Registrasi
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </ul>
</header>

<script>
    // Profile Dropdown Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.querySelector('.profile-btn');
        const profileDropdown = document.querySelector('.profile-dropdown');
        
        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                profileDropdown.classList.remove('active');
            });
            
            // Prevent dropdown from closing when clicking inside it
            profileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>