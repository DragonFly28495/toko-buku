
<?php
// Mulai session dan koneksi database - HARUS DI AWAL FILE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sesuaikan path config.php berdasarkan struktur folder
include __DIR__ . '/../config.php';

// Query untuk mengambil semua kategori
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$result_kategori = $conn->query($query_kategori);

// Mengambil parameter filter jika ada
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$harga_filter = isset($_GET['harga']) ? $_GET['harga'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'judul';

// Membangun query dengan filter
$where_conditions = [];
$params = [];
$types = '';

if (!empty($kategori_filter)) {
    $where_conditions[] = "kategori = ?";
    $params[] = $kategori_filter;
    $types .= 's';
}

if (!empty($harga_filter)) {
    switch($harga_filter) {
        case 'bawah_100':
            $where_conditions[] = "harga_regular < 100000";
            break;
        case '100_300':
            $where_conditions[] = "harga_regular BETWEEN 100000 AND 300000";
            break;
        case '300_500':
            $where_conditions[] = "harga_regular BETWEEN 300000 AND 500000";
            break;
        case 'atas_500':
            $where_conditions[] = "harga_regular > 500000";
            break;
    }
}

if (!empty($search_query)) {
    $where_conditions[] = "(judul LIKE ? OR penulis LIKE ? OR kategori LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $types .= 'sss';
}

// Query akhir dengan filter
$query_buku = "SELECT * FROM buku";
if (!empty($where_conditions)) {
    $query_buku .= " WHERE " . implode(" AND ", $where_conditions);
}

// Tambahkan sorting
switch($sort_by) {
    case 'harga_terendah':
        $query_buku .= " ORDER BY harga_regular ASC";
        break;
    case 'harga_tertinggi':
        $query_buku .= " ORDER BY harga_regular DESC";
        break;
    case 'terbaru':
        $query_buku .= " ORDER BY id_buku DESC";
        break;
    case 'diskon':
        $query_buku .= " ORDER BY diskon DESC";
        break;
    default:
        $query_buku .= " ORDER BY judul ASC";
        break;
}

// Eksekusi query dengan filter
if (!empty($params)) {
    $stmt = $conn->prepare($query_buku);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result_buku = $stmt->get_result();
} else {
    $result_buku = $conn->query($query_buku);
}

// Fungsi untuk mendapatkan path gambar yang benar
function getImagePath($cover_buku) {
    // Cek apakah file ada di folder Uploads/cover_buku
    $uploads_path = '../Uploads/cover_buku/' . $cover_buku;
    $asset_path = '../Aset/' . $cover_buku;
    
    if (file_exists($uploads_path)) {
        return $uploads_path;
    } elseif (file_exists($asset_path)) {
        return $asset_path;
    } else {
        // Jika gambar tidak ditemukan, return placeholder
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg xmlns="http://www.w3.org/2000/svg" width="200" height="250" viewBox="0 0 200 250">
                <rect width="200" height="250" fill="#f0f0f0"/>
                <text x="100" y="125" text-anchor="middle" dy=".3em" font-family="Arial" font-size="14" fill="#666">Cover Buku</text>
            </svg>
        ');
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Buku - Toko Buku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../CSS/semua_buku.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== BOOKS GRID - 6 PER ROW MIRIP BUKU-USER.PHP ===== */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
        }

        .book-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            text-decoration: none;
            color: inherit;
        }

        .book-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .book-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background-color: var(--accent);
            color: white;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            z-index: 2;
        }

        .discount-badge {
            background-color: #27ae60;
        }

        .bestseller-badge {
            background-color: #ff6b35;
        }

        .book-image {
            height: 160px;
            overflow: hidden;
            position: relative;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem;
        }

        .book-image img {
            width: auto;
            height: 100%;
            max-height: 140px;
            object-fit: contain;
            transition: transform 0.5s;
        }

        .book-card:hover .book-image img {
            transform: scale(1.05);
        }

        .book-info {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            border-top: 1px solid var(--light-gray);
        }

        .book-author {
            color: var(--gray);
            font-size: 0.75rem;
            margin-bottom: 0.4rem;
            font-weight: 500;
            line-height: 1.2;
        }

        .book-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-ramp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.2rem;
        }

        .book-category {
            display: inline-block;
            background: var(--light-gray);
            color: var(--gray);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-bottom: 0.6rem;
            align-self: flex-start;
        }

        .book-divider {
            height: 1px;
            background: var(--light-gray);
            margin: 0.4rem 0;
        }

        .book-price-section {
            margin-top: auto;
            padding-top: 0.4rem;
        }

        .book-price {
            font-weight: 700;
            color: var(--accent);
            font-size: 0.9rem;
        }

        .book-price-original {
            font-size: 0.75rem;
            color: var(--gray);
            text-decoration: line-through;
            margin-bottom: 0.15rem;
        }

        .price-container {
            display: flex;
            flex-direction: column;
        }

        .discount-price-container {
            display: flex;
            flex-direction: column;
        }

        /* Tombol aksi untuk buku */
        .book-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-detail, .btn-keranjang, .btn-habis {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            font-weight: 500;
        }

        .btn-detail {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--border);
        }

        .btn-detail:hover {
            background: #e9ecef;
        }

        .btn-keranjang {
            background: var(--primary);
            color: white;
        }

        .btn-keranjang:hover {
            background: var(--primary-dark);
        }

        .btn-habis {
            background: var(--light);
            color: var(--gray);
            cursor: not-allowed;
        }

        /* Stok buku */
        .stok-buku {
            font-size: 0.75rem;
            margin: 0.4rem 0;
        }

        .stok-label {
            color: var(--gray);
        }

        .stok-value.tersedia {
            color: #28a745;
            font-weight: 600;
        }

        .stok-value.habis {
            color: var(--danger);
            font-weight: 600;
        }

        /* ===== RESPONSIVE DESIGN ===== */    
        @media (max-width: 1400px) {
            .books-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .books-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 992px) {
            .books-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .books-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .books-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php require "navbar.php"; ?>
    
    <!-- Hero Section -->
    <section class="hero-buku">
        <div class="hero-content">
            <h1>Jelajahi Dunia Literasi</h1>
            <p>Temukan buku impian Anda dari koleksi kami yang lengkap dan terkurasi</p>
            <div class="search-hero">
                <form method="GET" action="" class="hero-search-form" id="heroSearchForm">
                </form>
            </div>
        </div>
        <div class="hero-overlay"></div>
    </section>
    
    <!-- Main Content -->
    <div class="container-buku">
        <!-- Mobile Filter Toggle -->
        <div class="mobile-filter-toggle">
            <button id="filterToggle" class="btn-filter-toggle">
                <i class="fas fa-filter"></i> Filter Buku
            </button>
        </div>
        
        <!-- Sidebar Filter -->
        <div class="sidebar-filter" id="sidebarFilter">
            <div class="filter-header">
                <h3><i class="fas fa-sliders-h"></i> Filter Buku</h3>
                </button>
            </div>
            
            <!-- Form Filter -->
            <form method="GET" action="" class="filter-form" id="filterForm">
                <!-- Search Box -->
                <div class="filter-group">
                    <label for="search" class="filter-label">Cari Buku</label>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="search" name="search" placeholder="Judul, penulis, atau penerbit..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                </div>
                
                <!-- Sort Options -->
                <div class="filter-group">
                    <label for="sort" class="filter-label">Urutkan Berdasarkan</label>
                    <select id="sort" name="sort" class="sort-select">
                        <option value="judul" <?= $sort_by == 'judul' ? 'selected' : '' ?>>Judul (A-Z)</option>
                        <option value="harga_terendah" <?= $sort_by == 'harga_terendah' ? 'selected' : '' ?>>Harga Terendah</option>
                        <option value="harga_tertinggi" <?= $sort_by == 'harga_tertinggi' ? 'selected' : '' ?>>Harga Tertinggi</option>
                        <option value="terbaru" <?= $sort_by == 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="diskon" <?= $sort_by == 'diskon' ? 'selected' : '' ?>>Diskon Terbesar</option>
                    </select>
                </div>
                
                <!-- Filter Kategori -->
                <div class="filter-group">
                    <div class="filter-group-header">
                        <h4><i class="fas fa-tags"></i> Kategori</h4>
                        <span class="filter-count"><?= $result_kategori->num_rows ?></span>
                    </div>
                    <div class="kategori-scroll">
                        <div class="kategori-item">
                            <input type="radio" id="kategori_semua" name="kategori" value="" 
                                   <?= empty($kategori_filter) ? 'checked' : '' ?>>
                            <label for="kategori_semua" class="kategori-label">
                                <span class="checkmark"></span>
                                Semua Kategori
                            </label>
                        </div>
                        <?php 
                        // Reset pointer untuk kategori
                        $result_kategori->data_seek(0);
                        while($kategori = $result_kategori->fetch_assoc()): 
                        ?>
                        <div class="kategori-item">
                            <input type="radio" id="kategori_<?= $kategori['id_kategori'] ?>" 
                                   name="kategori" value="<?= $kategori['nama_kategori'] ?>"
                                   <?= $kategori_filter == $kategori['nama_kategori'] ? 'checked' : '' ?>>
                            <label for="kategori_<?= $kategori['id_kategori'] ?>" class="kategori-label">
                                <span class="checkmark"></span>
                                <?= htmlspecialchars($kategori['nama_kategori']) ?>
                            </label>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Filter Harga -->
                <div class="filter-group">
                    <div class="filter-group-header">
                        <h4><i class="fas fa-money-bill-wave"></i> Rentang Harga</h4>
                    </div>
                    <div class="kategori-item">
                        <input type="radio" id="harga_semua" name="harga" value="" 
                               <?= empty($harga_filter) ? 'checked' : '' ?>>
                        <label for="harga_semua" class="kategori-label">
                            <span class="checkmark"></span>
                            Semua Harga
                        </label>
                    </div>
                    <div class="kategori-item">
                        <input type="radio" id="harga_bawah_100" name="harga" value="bawah_100"
                               <?= $harga_filter == 'bawah_100' ? 'checked' : '' ?>>
                        <label for="harga_bawah_100" class="kategori-label">
                            <span class="checkmark"></span>
                            Di bawah Rp100.000
                        </label>
                    </div>
                    <div class="kategori-item">
                        <input type="radio" id="harga_100_300" name="harga" value="100_300"
                               <?= $harga_filter == '100_300' ? 'checked' : '' ?>>
                        <label for="harga_100_300" class="kategori-label">
                            <span class="checkmark"></span>
                            Rp100.000 - Rp300.000
                        </label>
                    </div>
                    <div class="kategori-item">
                        <input type="radio" id="harga_300_500" name="harga" value="300_500"
                               <?= $harga_filter == '300_500' ? 'checked' : '' ?>>
                        <label for="harga_300_500" class="kategori-label">
                            <span class="checkmark"></span>
                            Rp300.000 - Rp500.000
                        </label>
                    </div>
                    <div class="kategori-item">
                        <input type="radio" id="harga_atas_500" name="harga" value="atas_500"
                               <?= $harga_filter == 'atas_500' ? 'checked' : '' ?>>
                        <label for="harga_atas_500" class="kategori-label">
                            <span class="checkmark"></span>
                            Di atas Rp500.000
                        </label>
                    </div>
                </div>
                
                <!-- Tombol Reset saja (tanpa terapkan) -->
                <div class="filter-buttons">
                    <a href="semua_buku.php" class="btn-reset">
                        <i class="fas fa-redo"></i> Reset Semua Filter
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Daftar Buku -->
        <div class="daftar-buku">
            <div class="buku-header">
                <div class="header-info">
                    <h2>Koleksi Buku Kami</h2>
                </div>
            </div>
            
            <?php if($result_buku->num_rows > 0): ?>
<div class="books-grid" id="bookContainer">
    <?php while($buku = $result_buku->fetch_assoc()): 
        $harga_final = $buku['diskon'] > 0 ? $buku['harga_setelah_diskon'] : $buku['harga_regular'];
        $image_path = getImagePath($buku['cover_buku']);
    ?>
    <a href="detail-buku.php?id_buku=<?= $buku['id_buku'] ?>" class="book-card">
        <?php if($buku['diskon'] > 0): ?>
        <span class="book-badge discount-badge">-<?= $buku['diskon'] ?>%</span>
        <?php endif; ?>
        
        <div class="book-image">
            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($buku['judul']) ?>">
        </div>
        
        <div class="book-info">
            <p class="book-author"><?= htmlspecialchars($buku['penulis']) ?></p>
            <h3 class="book-title"><?= htmlspecialchars($buku['judul']) ?></h3>
            <span class="book-category"><?= htmlspecialchars($buku['kategori']) ?></span>
            
            <div class="book-divider"></div>
            
            <div class="book-price-section">
                <div class="<?= $buku['diskon'] > 0 ? 'discount-price-container' : 'price-container' ?>">
                    <?php if($buku['diskon'] > 0): ?>
                        <span class="book-price-original">Rp<?= number_format($buku['harga_regular'], 0, ',', '.') ?></span>
                        <span class="book-price">Rp<?= number_format($buku['harga_setelah_diskon'], 0, ',', '.') ?></span>
                    <?php else: ?>
                        <span class="book-price">Rp<?= number_format($buku['harga_regular'], 0, ',', '.') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stok-buku">
                <span class="stok-label">Stok: </span>
                <span class="stok-value <?= $buku['stok'] > 0 ? 'tersedia' : 'habis' ?>">
                    <?= $buku['stok'] > 0 ? $buku['stok'] . ' tersedia' : 'Habis' ?>
                </span>
            </div>
        </div>
    </a>
    <?php endwhile; ?>
</div>
            <?php else: ?>
            <div class="tidak-ada-buku">
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>Tidak ada buku yang ditemukan</h3>
                    <p>Maaf, tidak ada buku yang sesuai dengan filter pencarian Anda.</p>
                    <div class="empty-actions">
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php require "footer.php"; ?>
    
    <script>
        // Fungsi untuk menambahkan buku ke keranjang
        function tambahKeKeranjang(idBuku) {
            <?php if(isset($_SESSION['id_pengguna'])): ?>
                fetch('tambah-keranjang.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_buku=' + idBuku
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Show success notification
                        showNotification('Buku berhasil ditambahkan ke keranjang!', 'success');
                        
                        // Update cart count if element exists
                        if(document.getElementById('jumlah-keranjang')) {
                            document.getElementById('jumlah-keranjang').textContent = data.jumlah_keranjang;
                        }
                    } else {
                        showNotification('Gagal menambahkan buku: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan saat menambahkan buku', 'error');
                });
            <?php else: ?>
                showNotification('Anda harus login terlebih dahulu', 'warning');
                setTimeout(() => {
                    window.location.href = '../login-user.php';
                }, 1500);
            <?php endif; ?>
        }
        
        // Notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Auto-submit untuk filter
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filterForm');
            const sortSelect = document.getElementById('sort');
            const searchInput = document.getElementById('search');
            const heroSearchForm = document.getElementById('heroSearchForm');
            const heroSearchInput = document.getElementById('heroSearchInput');
            
            // Auto-submit ketika memilih opsi sort
            if(sortSelect) {
                sortSelect.addEventListener('change', function() {
                    filterForm.submit();
                });
            }
            
            // Auto-submit ketika memilih kategori atau harga (radio buttons)
            const radioButtons = filterForm.querySelectorAll('input[type="radio"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Delay sedikit untuk memastikan nilai sudah berubah
                    setTimeout(() => {
                        filterForm.submit();
                    }, 100);
                });
            });
            
            // Auto-submit search input dengan debounce
            if(searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        filterForm.submit();
                    }, 800); // Submit setelah 800ms tidak ada input
                });
            }
            
            // Auto-submit untuk hero search dengan debounce
            if(heroSearchInput) {
                let heroSearchTimeout;
                heroSearchInput.addEventListener('input', function() {
                    clearTimeout(heroSearchTimeout);
                    heroSearchTimeout = setTimeout(() => {
                        heroSearchForm.submit();
                    }, 800);
                });
            }
            
            // Mobile filter toggle
            const filterToggle = document.getElementById('filterToggle');
            const sidebarFilter = document.getElementById('sidebarFilter');
            const closeFilter = document.getElementById('closeFilter');
            
            if(filterToggle && sidebarFilter) {
                filterToggle.addEventListener('click', function() {
                    sidebarFilter.classList.add('active');
                });
                
                closeFilter.addEventListener('click', function() {
                    sidebarFilter.classList.remove('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    const isClickInsideSidebar = sidebarFilter.contains(event.target);
                    const isClickOnToggle = filterToggle.contains(event.target);
                    
                    if (!isClickInsideSidebar && !isClickOnToggle && sidebarFilter.classList.contains('active')) {
                        sidebarFilter.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>
