<?php
// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session dan koneksi database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config.php';

$search = '';
$result = null;
$total = 0;
$books = [];
$bestseller_books = [];
$discount_books = [];
$regular_books = [];

try {
    // Cek apakah ada pencarian
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    }

    // Query untuk semua buku (dengan atau tanpa pencarian)
    if ($search !== '') {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare("SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE judul LIKE ? OR penulis LIKE ? OR kategori LIKE ? ORDER BY id_buku ASC");
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result ? $result->num_rows : 0;
        
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        $stmt->close();
    } else {
        // Query untuk Buku Bestseller berdasarkan transaksi tertinggi + stok tersedia + HARUS ADA TRANSAKSI
// Query untuk Buku Bestseller berdasarkan transaksi tertinggi + stok tersedia + HARUS ADA TRANSAKSI
$bestseller_stmt = $conn->prepare("
    SELECT 
        b.id_buku, 
        b.judul, 
        b.penulis, 
        b.penerbit, 
        b.harga_regular, 
        b.diskon, 
        b.harga_setelah_diskon, 
        b.stok, 
        b.kategori, 
        b.deskripsi, 
        b.cover_buku,
        COALESCE(SUM(t.jumlah), 0) as total_terjual
    FROM 
        buku b
    LEFT JOIN 
        transaksi t ON b.id_buku = t.id_buku AND t.status = 'selesai'
    WHERE 
        b.stok > 0
    GROUP BY 
        b.id_buku
    HAVING 
        total_terjual > 0
    ORDER BY 
        total_terjual DESC
    LIMIT 6
");
        $bestseller_stmt->execute();
        $bestseller_result = $bestseller_stmt->get_result();
        while ($row = $bestseller_result->fetch_assoc()) {
            $bestseller_books[] = $row;
        }
        $bestseller_stmt->close();
        
        // Buku Diskon - 6 buku
        $discount_stmt = $conn->prepare("SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE diskon > 0 AND stok > 0 ORDER BY diskon DESC LIMIT 6");
        $discount_stmt->execute();
        $discount_result = $discount_stmt->get_result();
        while ($row = $discount_result->fetch_assoc()) {
            $discount_books[] = $row;
        }
        $discount_stmt->close();
        
        // Buku Regular (bukan bestseller dan bukan diskon) - 12 buku
        $excluded_ids = [];
        foreach ($bestseller_books as $book) {
            $excluded_ids[] = $book['id_buku'];
        }
        foreach ($discount_books as $book) {
            $excluded_ids[] = $book['id_buku'];
        }
        
        if (!empty($excluded_ids)) {
            $placeholders = str_repeat('?,', count($excluded_ids) - 1) . '?';
            $regular_stmt = $conn->prepare("SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE id_buku NOT IN ($placeholders) AND stok > 0 ORDER BY id_buku ASC LIMIT 12");
            
            // Bind parameters
            $types = str_repeat('i', count($excluded_ids));
            $regular_stmt->bind_param($types, ...$excluded_ids);
        } else {
            $regular_stmt = $conn->prepare("SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE stok > 0 ORDER BY id_buku ASC LIMIT 12");
        }
        
        $regular_stmt->execute();
        $regular_result = $regular_stmt->get_result();
        while ($row = $regular_result->fetch_assoc()) {
            $regular_books[] = $row;
        }
        $regular_stmt->close();
    }

} catch (Exception $e) {
    error_log("Error fetching books: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk - Toko BUKU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../CSS/style_produk_kami-user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</style>
</head>

<body>
    <?php require "navbar.php"; ?>

    <!-- Banner Slider -->
    <div class="banner-container">
        <button class="banner-nav prev">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="banner-nav next">
            <i class="fas fa-chevron-right"></i>
        </button>
        
       <!-- Di file buku-user.php - GANTI bagian banner dengan ini: -->

<!-- Banner Slide 1 - KE SEMUA BUKU -->
<div class="banner-slide active">
    <img src="../Aset/banner1.jpg" alt="Banner 1 - Koleksi Buku Terbaru">
    <div class="banner-overlay">
        <h1 class="banner-title">Temukan Buku Impian Anda</h1>
        <p class="banner-subtitle">Jelajahi koleksi buku terlengkap dengan harga terbaik</p>
    </div>
</div>

<!-- Banner Slide 2 - KE BUKU DISKON -->
<div class="banner-slide">
    <img src="../Aset/banner2.jpg" alt="Banner 2 - Diskon Spesial">
    <div class="banner-overlay">
        <h1 class="banner-title">Diskon Spesial untuk Pembaca Setia</h1>
        <p class="banner-subtitle">Dapatkan penawaran terbaik untuk buku favorit Anda</p>
        <a href="buku-diskon.php" class="banner-btn">Lihat Promo <i class="fas fa-tag"></i></a>
    </div>
</div>

<!-- Banner Slide 3 - KE BUKU BESTSELLER -->
<div class="banner-slide">
    <img src="../Aset/banner3.jpg" alt="Banner 3 - Buku Best Seller">
    <div class="banner-overlay">
        <h1 class="banner-title">Buku Best Seller Terpopuler</h1>
        <p class="banner-subtitle">Buku-buku yang sedang trending dan banyak dibaca</p>
        <a href="buku-bestseller.php" class="banner-btn">Lihat Buku <i class="fas fa-fire"></i></a>
    </div>
</div>
        
        <div class="banner-dots">
            <div class="banner-dot active"></div>
            <div class="banner-dot"></div>
            <div class="banner-dot"></div>
        </div>
    </div>

    <!-- Main Content (Full Width) -->
    <main class="main-content-full">
        
        <?php if (empty($search)): ?>
<!-- Section Buku Bestseller - 6 BUKU -->
<section class="book-section" id="bestseller-books">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-fire"></i> Buku Bestseller</h2>
        <a href="buku-bestseller.php" class="section-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="books-grid">
        <?php if (!empty($bestseller_books)): ?>
            <?php 
            $counter = 0;
            foreach ($bestseller_books as $row): 
                $counter++;
                // Tentukan badge berdasarkan ranking
                $badge_text = "";
                switch($counter) {
                    case 1:
                        $badge_text = "Top 1";
                        break;
                    case 2:
                        $badge_text = "Top 2";
                        break;
                    case 3:
                        $badge_text = "Top 3";
                        break;
                    case 4:
                        $badge_text = "Top 4";
                        break;
                    case 5:
                        $badge_text = "Top 5";
                        break;
                    case 6:
                        $badge_text = "Top 6";
                        break;
                    default:
                        $badge_text = "Bestseller";
                        break;
                }
            ?>
                <a href="detail-buku.php?id_buku=<?= urlencode($row['id_buku']) ?>" class="book-card">
                    <span class="book-badge bestseller-badge top-<?= $counter ?>"><?= $badge_text ?></span>
                    
                    <div class="book-image">
                        <img src="../Uploads/cover_buku/<?= htmlspecialchars($row['cover_buku']) ?>"
                            alt="<?= htmlspecialchars($row['judul']) ?>" 
                            onerror="this.src='../Aset/default-cover.jpg'">
                    </div>
                    
                    <div class="book-info">
                        <p class="book-author"><?= htmlspecialchars($row['penulis']) ?></p>
                        <h3 class="book-title"><?= htmlspecialchars($row['judul']) ?></h3>
                        <span class="book-category"><?= htmlspecialchars($row['kategori']) ?></span>
                        
                        <div class="book-divider"></div>
                        
                        <div class="book-price-section">
                            <div class="price-container">
                                <?php if ($row['diskon'] > 0): ?>
                                    <!-- Untuk buku bestseller yang juga diskon -->
                                    <div class="discount-price-container">
                                        <span class="book-price-original">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                        <span class="book-price">Rp <?= number_format($row['harga_setelah_diskon'], 0, ',', '.') ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="book-price">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-books-found">
                <h3>Tidak ada buku bestseller saat ini</h3>
                <p>Cek kembali nanti untuk update buku bestseller terbaru.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

            <!-- Section Buku Diskon - 6 BUKU -->
            <section class="book-section" id="discount-books">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-tag"></i> Buku Diskon</h2>
                    <a href="buku-diskon.php" class="section-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="books-grid">
                    <?php if (!empty($discount_books)): ?>
                        <?php foreach ($discount_books as $row): ?>
                            <a href="detail-buku.php?id_buku=<?= urlencode($row['id_buku']) ?>" class="book-card">
                                <span class="book-badge discount-badge">Diskon <?= $row['diskon'] ?>%</span>
                                
                                <div class="book-image">
                                    <img src="../Uploads/cover_buku/<?= htmlspecialchars($row['cover_buku']) ?>"
                                        alt="<?= htmlspecialchars($row['judul']) ?>" 
                                        onerror="this.src='../Aset/default-cover.jpg'">
                                </div>
                                
                                <div class="book-info">
                                    <p class="book-author"><?= htmlspecialchars($row['penulis']) ?></p>
                                    <h3 class="book-title"><?= htmlspecialchars($row['judul']) ?></h3>
                                    <span class="book-category"><?= htmlspecialchars($row['kategori']) ?></span>
                                    
                                    <div class="book-divider"></div>
                                    
                                    <div class="book-price-section">
                                        <div class="discount-price-container">
                                            <!-- Harga reguler di atas (dicoret) -->
                                            <span class="book-price-original">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                            <!-- Harga setelah diskon di bawah (lebih besar) -->
                                            <span class="book-price">Rp <?= number_format($row['harga_setelah_diskon'], 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-books-found">
                            <h3>Tidak ada buku diskon saat ini</h3>
                            <p>Cek kembali nanti untuk penawaran diskon terbaru.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Section Semua Buku (Regular) - 12 BUKU -->
            <section class="book-section" id="all-books">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-book"></i> Semua Buku</h2>
                    <a href="semua_buku.php" class="section-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="books-grid">
                    <?php if (!empty($regular_books)): ?>
                        <?php foreach ($regular_books as $row): ?>
                            <a href="detail-buku.php?id_buku=<?= urlencode($row['id_buku']) ?>" class="book-card">                                
                                <div class="book-image">
                                    <img src="../Uploads/cover_buku/<?= htmlspecialchars($row['cover_buku']) ?>"
                                        alt="<?= htmlspecialchars($row['judul']) ?>" 
                                        onerror="this.src='../Aset/default-cover.jpg'">
                                </div>
                                
                                <div class="book-info">
                                    <p class="book-author"><?= htmlspecialchars($row['penulis']) ?></p>
                                    <h3 class="book-title"><?= htmlspecialchars($row['judul']) ?></h3>
                                    <span class="book-category"><?= htmlspecialchars($row['kategori']) ?></span>
                                    
                                    <div class="book-divider"></div>
                                    
                                    <div class="book-price-section">
                                        <div class="price-container">
                                            <span class="book-price">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-books-found">
                            <h3>Tidak ada buku ditemukan</h3>
                            <p>Cek kembali nanti untuk update buku terbaru.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <!-- Section Hasil Pencarian -->
            <section class="book-section" id="search-results">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-search"></i> 
                        Hasil Pencarian: "<?= htmlspecialchars($search) ?>"
                    </h2>
                </div>

                <div class="books-grid">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $row): ?>
                            <a href="detail-buku.php?id_buku=<?= urlencode($row['id_buku']) ?>" class="book-card">
                                <?php if ($row['stok'] > 10): ?>
                                    <span class="book-badge bestseller-badge">Bestseller</span>
                                <?php elseif ($row['stok'] < 5): ?>
                                    <span class="book-badge">Stok Terbatas</span>
                                <?php endif; ?>
                                
                                <?php if ($row['diskon'] > 0): ?>
                                    <span class="book-badge discount-badge" style="right: 8px; left: auto;">Diskon <?= $row['diskon'] ?>%</span>
                                <?php endif; ?>
                                
                                <div class="book-image">
                                    <img src="../Uploads/cover_buku/<?= htmlspecialchars($row['cover_buku']) ?>"
                                        alt="<?= htmlspecialchars($row['judul']) ?>" 
                                        onerror="this.src='../Aset/default-cover.jpg'">
                                </div>
                                
                                <div class="book-info">
                                    <p class="book-author"><?= htmlspecialchars($row['penulis']) ?></p>
                                    <h3 class="book-title"><?= htmlspecialchars($row['judul']) ?></h3>
                                    <span class="book-category"><?= htmlspecialchars($row['kategori']) ?></span>
                                    
                                    <div class="book-divider"></div>
                                    
                                    <div class="book-price-section">
                                        <div class="<?= $row['diskon'] > 0 ? 'discount-price-container' : 'price-container' ?>">
                                            <?php if ($row['diskon'] > 0): ?>
                                                <span class="book-price-original">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                                <span class="book-price">Rp <?= number_format($row['harga_setelah_diskon'], 0, ',', '.') ?></span>
                                            <?php else: ?>
                                                <span class="book-price">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-books-found">
                            <h3>Tidak ada buku ditemukan untuk "<?= htmlspecialchars($search) ?>"</h3>
                            <p>Coba gunakan kata kunci pencarian yang berbeda atau lihat semua buku kami.</p>
                            <a href="buku-user.php" class="btn-view-all">Lihat Semua Buku</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
    
    <?php require "footer.php"; ?>

    <script>
        // Banner Slider Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.banner-slide');
            const dots = document.querySelectorAll('.banner-dot');
            const prevBtn = document.querySelector('.banner-nav.prev');
            const nextBtn = document.querySelector('.banner-nav.next');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(n) {
                slides.forEach(slide => slide.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                
                currentSlide = (n + slides.length) % slides.length;
                
                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');
            }

            function nextSlide() {
                showSlide(currentSlide + 1);
            }

            function prevSlide() {
                showSlide(currentSlide - 1);
            }

            function startSlideShow() {
                slideInterval = setInterval(nextSlide, 5000);
            }

            function resetSlideShow() {
                clearInterval(slideInterval);
                startSlideShow();
            }

            // Event listeners
            prevBtn.addEventListener('click', () => {
                prevSlide();
                resetSlideShow();
            });

            nextBtn.addEventListener('click', () => {
                nextSlide();
                resetSlideShow();
            });

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    showSlide(index);
                    resetSlideShow();
                });
            });

            startSlideShow();

            // Search Functionality
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const bookCards = document.querySelectorAll('.book-card');
                    
                    bookCards.forEach(card => {
                        const title = card.querySelector('.book-title').textContent.toLowerCase();
                        const author = card.querySelector('.book-author').textContent.toLowerCase();
                        const category = card.querySelector('.book-category').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || author.includes(searchTerm) || category.includes(searchTerm)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
        // Tambahkan di bagian script file buku-user.php
document.addEventListener('DOMContentLoaded', function() {
    // Debug banner links
    const bannerLinks = document.querySelectorAll('.banner-btn');
    bannerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            console.log('Banner link clicked:', this.href);
            // Tidak perlu e.preventDefault() - biarkan link bekerja normal
        });
    });
});
    </script>
</body>
</html>