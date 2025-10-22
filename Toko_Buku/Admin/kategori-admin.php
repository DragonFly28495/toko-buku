<?php
// Inisialisasi koneksi database dan pencarian data kategori
include __DIR__ . '/../config.php';

$search = '';
$result = null;
$total = 0;

try {
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    }

    // Gunakan prepared statement saat ada pencarian untuk menghindari SQL injection
    if ($search !== '') {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare("SELECT id_kategori, nama_kategori FROM kategori WHERE id_kategori LIKE ? OR nama_kategori LIKE ? ORDER BY id_kategori ASC");
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result ? $result->num_rows : 0;
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result ? $result->num_rows : 0;
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    // Di lingkungan development, Anda bisa log $e->getMessage();
    $result = null;
    $total = 0;
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kategori Admin - Toko BUKU</title>
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
                <h2>Daftar Kategori</h2>
                <p>Kelola kategori buku di toko Anda</p>
            </div>

            <div class="content-body">
                <!-- Kontrol Tambah dan Pencarian -->
                <div class="table-controls">
                    <a href="form_tambah_kategori.php" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah Kategori
                    </a>

                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Cari kategori..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i>
                            Cari
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="?" class="btn-reset">
                                <i class="fas fa-times"></i>
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabel Kategori -->
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Kategori</th>
                                    <th>Nama Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php $no = 1;
                                    while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['id_kategori']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                            <td class="action-buttons">
                                                <a href="form_edit_kategori.php?id_kategori=<?= urlencode($row['id_kategori']) ?>"
                                                    class="btn-edit">
                                                    <i class="fas fa-edit"></i>
                                                    Edit
                                                </a>
                                                <a href="hapus_kategori.php?id_kategori=<?= urlencode($row['id_kategori']) ?>"
                                                    onclick="return confirm('Yakin hapus <?= addslashes(htmlspecialchars($row['nama_kategori'])) ?>?')"
                                                    class="btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="no-data">
                                            <i class="fas fa-inbox"></i>
                                            Tidak ada kategori untuk ditampilkan.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Informasi Total -->
                <div class="table-footer">
                    <span class="total-info">
                        <i class="fas fa-list"></i>
                        Total Kategori: <?= $total ?>
                    </span>
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