<?php
// Inisialisasi koneksi database
include __DIR__ . '/../config.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inisialisasi variabel
$pesan = '';
$id_kategori = '';
$nama_kategori = '';

// Ambil data kategori berdasarkan ID dari URL
if (isset($_GET['id_kategori'])) {
    $id_kategori = trim($_GET['id_kategori']);
    $query = mysqli_query($conn, "SELECT * FROM kategori WHERE id_kategori = '$id_kategori'");
    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $nama_kategori = $data['nama_kategori'];
    } else {
        $pesan = 'Kategori tidak ditemukan.';
    }
} else {
    $pesan = 'ID kategori tidak diberikan.';
}

// Proses update kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id_kategori = trim($_POST['id_kategori']);
    $nama_kategori = trim($_POST['nama_kategori']);

    if ($id_kategori === '' || $nama_kategori === '') {
        $pesan = 'Semua field wajib diisi.';
    } else {
        $sql = "UPDATE kategori SET nama_kategori = '$nama_kategori' WHERE id_kategori = '$id_kategori'";
        $update = mysqli_query($conn, $sql);

        if ($update) {
            header('Location: kategori-admin.php');
            exit;
        } else {
            $pesan = 'Gagal memperbarui kategori. Silakan coba lagi.';
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Kategori - Toko BUKU</title>
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
                <h2>Edit Kategori</h2>
                <p>Perbarui informasi kategori buku</p>
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
                            <i class="fas fa-edit"></i>
                            Edit Kategori
                        </h3>

                        <?php if ($pesan): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($pesan) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($id_kategori !== '' && $nama_kategori !== ''): ?>
                            <form action="" method="post" class="form">
                                <div class="form-group">
                                    <label for="id_kategori" class="form-label">
                                        <i class="fas fa-hashtag"></i>
                                        ID Kategori
                                    </label>
                                    <input type="text" id="id_kategori" name="id_kategori"
                                        value="<?= htmlspecialchars($id_kategori) ?>" class="form-input" readonly>
                                    <small class="form-help">ID Kategori tidak dapat diubah</small>
                                </div>

                                <div class="form-group">
                                    <label for="nama_kategori" class="form-label">
                                        <i class="fas fa-tag"></i>
                                        Nama Kategori
                                    </label>
                                    <input type="text" id="nama_kategori" name="nama_kategori"
                                        value="<?= htmlspecialchars($nama_kategori) ?>" class="form-input" required>
                                    <small class="form-help">Masukkan nama kategori yang baru</small>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" name="edit" class="btn-primary">
                                        <i class="fas fa-save"></i>
                                        Perbarui Kategori
                                    </button>
                                    <a href="kategori-admin.php" class="btn-secondary">
                                        <i class="fas fa-times"></i>
                                        Batal
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                Tidak dapat memuat data kategori. Pastikan ID kategori valid.
                            </div>
                        <?php endif; ?>
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