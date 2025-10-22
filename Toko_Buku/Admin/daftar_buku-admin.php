<?php
// Menghubungkan ke file konfigurasi database
include __DIR__ . '/../config.php';

// Inisialisasi variabel pencarian dan filter dari parameter URL (GET)
$search = '';
$filter_diskon = isset($_GET['filter_diskon']) ? $_GET['filter_diskon'] : '';
$filter_stok = isset($_GET['filter_stok']) ? $_GET['filter_stok'] : '';
$filter_harga = isset($_GET['filter_harga']) ? $_GET['filter_harga'] : '';

// Inisialisasi variabel hasil dan total data
$result = null;
$total = 0;

// Menentukan jumlah item per halaman (misalnya 2 baris x 5 buku = 10 buku)
$items_per_page = 10;

// Menentukan halaman saat ini dari parameter URL, default ke halaman 1
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Menghitung offset untuk query LIMIT berdasarkan halaman saat ini
$offset = ($current_page - 1) * $items_per_page;

try {
    // Jika ada parameter pencarian, simpan setelah di-trim (hapus spasi di awal/akhir)
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    }

    // Siapkan array untuk kondisi WHERE dan parameter query
    $where_conditions = [];
    $query_params = [];
    $param_types = ''; // Tipe data untuk bind_param (s = string, i = integer)

    // Tambahkan kondisi pencarian jika ada kata kunci
    if ($search !== '') {
        $like = "%" . $search . "%"; // Format LIKE untuk pencarian sebagian
        $where_conditions[] = "(judul LIKE ? OR penulis LIKE ? OR penerbit LIKE ? OR kategori LIKE ?)";
        $query_params = array_merge($query_params, [$like, $like, $like, $like]);
        $param_types .= 'ssss'; // 4 parameter bertipe string
    }

    // Tambahkan filter diskon jika dipilih
    if ($filter_diskon === 'ya') {
        $where_conditions[] = "diskon > 0";
    }

    // Tambahkan filter stok habis jika dipilih
    if ($filter_stok === 'habis') {
        $where_conditions[] = "stok = 0";
    }

    // Tambahkan filter harga sesuai pilihan
    if ($filter_harga === 'dibawah_100') {
        $where_conditions[] = "harga_setelah_diskon < 100000";
    } elseif ($filter_harga === 'dibawah_250') {
        $where_conditions[] = "harga_setelah_diskon < 250000";
    } elseif ($filter_harga === 'diatas_250') {
        $where_conditions[] = "harga_setelah_diskon >= 250000";
    }

    // Gabungkan semua kondisi WHERE jika ada
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Query untuk menghitung total data (digunakan untuk pagination)
    $count_sql = "SELECT COUNT(*) as total FROM buku $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    
    // Bind parameter pencarian jika ada
    if (!empty($query_params)) {
        $count_stmt->bind_param($param_types, ...$query_params);
    }
    
    // Eksekusi query dan ambil hasil total
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

    // Query untuk mengambil data buku sesuai halaman dan filter
    $sql = "SELECT id_buku, judul, penulis, penerbit, tanggal_terbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku $where_clause ORDER BY id_buku ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    if (!empty($query_params)) {
        // Pastikan tipe data pagination adalah integer
        $items_per_page = (int)$items_per_page;
        $offset = (int)$offset;
        
        // Pisahkan parameter pencarian dan pagination
        $search_params = $query_params;
        $pagination_params = [$items_per_page, $offset];
        
        // Gabungkan tipe data dan parameter
        $types = $param_types . 'ii';
        $all_params = array_merge($search_params, $pagination_params);
        
        // Bind semua parameter ke query
        $stmt->bind_param($types, ...$all_params);
    } else {
        // Jika tidak ada pencarian, hanya bind parameter pagination
        $stmt->bind_param('ii', $items_per_page, $offset);
    }
    
    // Eksekusi query dan ambil hasil data buku
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Hitung total halaman berdasarkan total data dan item per halaman
    $total_pages = ceil($total / $items_per_page);

} catch (mysqli_sql_exception $e) {
    // Tangani error database dan simpan log error
    error_log("Database error: " . $e->getMessage());
    $result = null;
    $total = 0;
    $total_pages = 0;
}
?>


<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Buku - Toko BUKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style-admin.css">
    <link rel="stylesheet" href="../CSS/style_buku-admin.css">
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
                <h2>Daftar Buku</h2>
                <p>Kelola semua buku yang tersedia di toko</p>
            </div>

            <div class="content-body">
                <!-- Kontrol Tambah dan Pencarian -->
                <div class="table-controls">
                    <a href="form_tambah_buku.php" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Tambah Buku
                    </a>

                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Cari buku berdasarkan judul, penulis, kategori..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i>
                            Cari
                        </button>
                        <?php if (!empty($search) || !empty($filter_diskon) || !empty($filter_stok) || !empty($filter_harga)): ?>
                            <a href="?" class="btn-reset">
                                <i class="fas fa-times"></i>
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Filter Buku -->
                <div class="filter-controls">
                    <form method="GET" class="filter-form">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        
                        <div class="filter-group">
                            <label>Filter Buku:</label>
                            <div class="filter-options">
                                <select name="filter_diskon" onchange="this.form.submit()">
                                    <option value="">Semua Diskon</option>
                                    <option value="ya" <?= $filter_diskon === 'ya' ? 'selected' : '' ?>>Ada Diskon</option>
                                </select>
                                
                                <select name="filter_stok" onchange="this.form.submit()">
                                    <option value="">Semua Stok</option>
                                    <option value="habis" <?= $filter_stok === 'habis' ? 'selected' : '' ?>>Stok Habis</option>
                                </select>
                                
                                <select name="filter_harga" onchange="this.form.submit()">
                                    <option value="">Semua Harga</option>
                                    <option value="dibawah_100" <?= $filter_harga === 'dibawah_100' ? 'selected' : '' ?>>Dibawah Rp 100.000</option>
                                    <option value="dibawah_250" <?= $filter_harga === 'dibawah_250' ? 'selected' : '' ?>>Dibawah Rp 250.000</option>
                                    <option value="diatas_250" <?= $filter_harga === 'diatas_250' ? 'selected' : '' ?>>Diatas Rp 250.000</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Informasi Pencarian -->
                <?php if (!empty($search) || !empty($filter_diskon) || !empty($filter_stok) || !empty($filter_harga)): ?>
                    <div class="search-info">
                        <p>
                            Menampilkan hasil 
                            <?php if (!empty($search)): ?>
                                pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                            <?php endif; ?>
                            <?php if (!empty($filter_diskon) || !empty($filter_stok) || !empty($filter_harga)): ?>
                                <?php if (!empty($search)): ?> | <?php endif; ?>
                                dengan filter:
                                <?php
                                $filters = [];
                                if ($filter_diskon === 'ya') $filters[] = 'Diskon';
                                if ($filter_stok === 'habis') $filters[] = 'Stok Habis';
                                if ($filter_harga === 'dibawah_100') $filters[] = 'Harga < 100rb';
                                if ($filter_harga === 'dibawah_250') $filters[] = 'Harga < 250rb';
                                if ($filter_harga === 'diatas_250') $filters[] = 'Harga > 250rb';
                                echo '<strong>' . implode(', ', $filters) . '</strong>';
                                ?>
                            <?php endif; ?>
                        </p>
                        <p>Ditemukan <strong><?= $total ?></strong> buku</p>
                    </div>
                <?php endif; ?>

                <!-- Grid Buku dengan Pengelompokan -->
                               <div class="books-grid-container">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php 
                        $books = $result->fetch_all(MYSQLI_ASSOC);
                        $total_books = count($books);
                        ?>
                        
                        <!-- Hapus nested loops yang kompleks, gunakan loop sederhana -->
                        <div class="books-grid">
                            <?php foreach ($books as $book): ?>
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="../uploads/cover_buku/<?= htmlspecialchars($book['cover_buku']) ?>" 
                                             alt="<?= htmlspecialchars($book['judul']) ?>"
                                             onerror="this.src='../uploads/cover_buku/default.jpg'">
                                        <?php if ($book['stok'] == 0): ?>
                                            <div class="out-of-stock-badge">Habis</div>
                                        <?php endif; ?>
                                        <?php if ($book['diskon'] > 0): ?>
                                            <div class="discount-badge">-<?= $book['diskon'] ?>%</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="book-info">
                                        <h3 class="book-title"><?= htmlspecialchars($book['judul']) ?></h3>
                                        <p class="book-author"><?= htmlspecialchars($book['penulis']) ?></p>
                                        
                                        <div class="book-meta">
                                            <div class="price-section">
                                                <?php if ($book['diskon'] > 0): ?>
                                                    <span class="original-price">Rp <?= number_format($book['harga_regular'], 0, ',', '.') ?></span>
                                                    <span class="book-price">Rp <?= number_format($book['harga_setelah_diskon'], 0, ',', '.') ?></span>
                                                <?php else: ?>
                                                    <span class="book-price">Rp <?= number_format($book['harga_regular'], 0, ',', '.') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="book-stock <?= $book['stok'] == 0 ? 'out-of-stock' : 'in-stock' ?>">
                                                Stok: <?= $book['stok'] ?>
                                            </span>
                                        </div>
                                        
                                        <div class="book-details">
                                            <p><strong>Penerbit:</strong> <?= htmlspecialchars($book['penerbit']) ?></p>
                                            <p><strong>Kategori:</strong> <?= htmlspecialchars($book['kategori']) ?></p>
                                            <?php if (!empty($book['tanggal_terbit'])): ?>
                                                <p><strong>Terbit:</strong> <?= date('d M Y', strtotime($book['tanggal_terbit'])) ?></p>
                                            <?php endif; ?>
                                            <p class="book-description">
                                                <?= htmlspecialchars(substr($book['deskripsi'], 0, 120)) ?>
                                                <?= strlen($book['deskripsi']) > 120 ? '...' : '' ?>
                                            </p>
                                        </div>
                                        
                                        <div class="book-actions">
                                            <a href="form_edit_buku.php?id_buku=<?= urlencode($book['id_buku']) ?>" 
                                               class="btn-edit-full">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="hapus_buku.php?id_buku=<?= urlencode($book['id_buku']) ?>" 
                                               onclick="return confirm('Yakin hapus <?= addslashes(htmlspecialchars($book['judul'])) ?>?')" 
                                               class="btn-delete-full">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-books-message">
                            <i class="fas fa-book-open fa-3x"></i>
                            <h3>Tidak ada buku ditemukan</h3>
                            <p><?= (!empty($search) || !empty($filter_diskon) || !empty($filter_stok) || !empty($filter_harga)) ? 'Coba dengan pencarian atau filter lain' : 'Mulai tambah buku pertama Anda' ?></p>
                            <a href="form_tambah_buku.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Tambah Buku Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link first">First</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" class="page-link prev">Prev</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="page-link <?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>" class="page-link next">Next</a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="page-link last">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Informasi Total -->
                <div class="table-footer">
                    <span class="total-info">
                        <i class="fas fa-book"></i>
                        Total Buku: <?= $total ?> | Halaman <?= $current_page ?> dari <?= $total_pages ?>
                    </span>
                </div>
            </div>

            <footer class="footer">
                &copy; <?= date('Y') ?> Toko BUKU. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
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

        // Handle responsive behavior on window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        });

        // Auto-hide search info after 5 seconds
        setTimeout(function() {
            const searchInfo = document.querySelector('.search-info');
            if (searchInfo) {
                searchInfo.style.opacity = '0.7';
            }
        }, 5000);

        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const toggle = document.getElementById(id.replace('Dropdown', 'Toggle'));

            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }
    </script>
</body>
</html>