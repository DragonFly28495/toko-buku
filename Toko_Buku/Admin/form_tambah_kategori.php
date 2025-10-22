<?php
// Inisialisasi koneksi database
include __DIR__ . '/../config.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inisialisasi variabel pesan
$pesan = '';

// Proses tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    // Ambil data dari formulir
    $id_kategori = trim($_POST['id_kategori']);
    $nama_kategori = trim($_POST['nama_kategori']);

    // Validasi sederhana
    if ($id_kategori === '' || $nama_kategori === '') {
        $pesan = 'Semua field wajib diisi.';
    } else {
        // Query tambah kategori
        $sql = "INSERT INTO kategori (id_kategori, nama_kategori) VALUES ('$id_kategori', '$nama_kategori')";
        $query = mysqli_query($conn, $sql);

        if ($query) {
            header('Location: kategori-admin.php');
            exit;
        } else {
            $pesan = 'Gagal menambahkan kategori. Silakan coba lagi.';
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Kategori - Toko BUKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style-admin.css">
     <link rel="stylesheet" href="../CSS/style_kategori-admin.css">
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <div class="content-header">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Tambah Kategori</h2>
                <p>Tambahkan kategori buku baru ke dalam sistem</p>
            </div>

            <div class="content-body">
                <!-- Tombol Kembali -->
                <a href="kategori-admin.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Daftar Kategori
                </a>

                <!-- Form Container -->
                <div class="form-container">
                    <div class="form-card">
                        <h3 class="form-title">
                            <i class="fas fa-plus-circle"></i>
                            Tambah Kategori Baru
                        </h3>
                        
                        <?php if ($pesan): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($pesan) ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="post" class="form">
                            <div class="form-group">
                                <label for="id_kategori" class="form-label">
                                    <i class="fas fa-hashtag"></i>
                                    ID Kategori
                                </label>
                                <input type="text" id="id_kategori" name="id_kategori" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label for="nama_kategori" class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Nama Kategori
                                </label>
                                <input type="text" id="nama_kategori" name="nama_kategori" class="form-input" required>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="tambah" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    Simpan Kategori
                                </button>
                                <a href="kategori-admin.php" class="btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <footer class="footer">
                &copy; <?= date('Y') ?> Toko BUKU. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
        // Toggle dropdown dengan animasi
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const toggle = document.getElementById(id.replace('Dropdown', 'Toggle'));

            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }

        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        });

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('mobile-open');
                }
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-toggle')) {
                const dropdowns = document.querySelectorAll('.dropdown-content');
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });

                const toggles = document.querySelectorAll('.dropdown-toggle');
                toggles.forEach(toggle => {
                    toggle.classList.remove('active');
                });
            }
        });

        // Handle responsive behavior on window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        });
    </script>
</body>

</html>