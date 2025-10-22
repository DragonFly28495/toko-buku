<?php
include __DIR__ . '/../config.php';

$search = '';
$result = null;
$total = 0;

try {
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    }

    if ($search !== '') {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare("SELECT id_pengguna, username, email, alamat, no_telp, password 
                                FROM daftar_pengguna 
                                WHERE id_pengguna LIKE ? OR username LIKE ? OR email LIKE ? OR alamat LIKE ? OR no_telp LIKE ? OR password LIKE ? 
                                ORDER BY id_pengguna ASC");
        $stmt->bind_param('ssss', $like, $like, $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT id_pengguna, username, email, alamat, no_telp, password 
                                FROM daftar_pengguna 
                                ORDER BY id_pengguna ASC");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result ? $result->num_rows : 0;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $result = null;
    $total = 0;
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pengguna - Toko BUKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style-admin.css">
    <link rel="stylesheet" href="../CSS/style_daftar_pengguna-admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Konten Utama -->
        <div class="main-content">
            <!-- Header Konten -->
            <div class="content-header">
                <h2>Daftar Pengguna</h2>
                <p>Kelola data pengguna sistem</p>
            </div>

            <main class="content-body">
                <!-- Form Pencarian -->
                <div class="table-controls">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Cari pengguna berdasarkan ID, username, email..."
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

                <!-- Tabel Pengguna -->
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Pengguna</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Alamat</th>
                                    <th>No. Telp</th>
                                    <th>Password</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['id_pengguna']) ?></td>
                                            <td><?= htmlspecialchars($row['username']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['alamat']) ?></td>
                                            <td><?= htmlspecialchars($row['no_telp']) ?></td>
                                            <td>••••••</td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="hapus_pengguna.php?id_pengguna=<?= urlencode($row['id_pengguna']) ?>"
                                                       onclick="return confirm('Yakin hapus <?= addslashes(htmlspecialchars($row['username'])) ?>?')"
                                                       class="btn-delete">
                                                        <i class="fas fa-trash"></i>
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">
                                            <i class="fas fa-users-slash"></i>
                                            Tidak ada pengguna untuk ditampilkan.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Informasi Total -->
                <div class="table-footer">
                    <div class="total-info">
                        <i class="fas fa-users"></i>
                        Total Pengguna: <?= $total ?>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="footer">
                &copy; <?= date('Y') ?> Toko BUKU. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            const chevron = dropdown.previousElementSibling.querySelector('.chevron');

            dropdown.classList.toggle('show');
            chevron.classList.toggle('fa-chevron-down');
            chevron.classList.toggle('fa-chevron-up');
        }
    </script>
</body>
</html>