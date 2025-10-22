<?php
// Tambahkan di baris paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config.php");

// Query untuk mendapatkan buku bestseller berdasarkan jumlah transaksi terbanyak
$sql_buku = "
    SELECT 
        b.*, 
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
        total_terjual DESC, 
        b.judul ASC
    LIMIT 5
";
$result_buku = $conn->query($sql_buku);

// Jika tidak ada buku bestseller (semua total_terjual = 0), tampilkan buku dengan stok tersedia secara acak
if ($result_buku->num_rows == 0) {
    $sql_fallback = "
        SELECT * 
        FROM buku 
        WHERE stok > 0 
        ORDER BY RAND() 
        LIMIT 5
    ";
    $result_buku = $conn->query($sql_fallback);
    $fallback_mode = true;
} else {
    $fallback_mode = false;
}

// Query untuk mendapatkan total penjualan semua buku (untuk menentukan bestseller)
$sql_penjualan = "
    SELECT 
        id_buku, 
        SUM(jumlah) as total_terjual
    FROM 
        transaksi 
    WHERE 
        status = 'selesai'
    GROUP BY 
        id_buku
";
$result_penjualan = $conn->query($sql_penjualan);

// Buat array untuk menyimpan total penjualan per buku
$penjualan_per_buku = [];
if ($result_penjualan->num_rows > 0) {
    while ($row = $result_penjualan->fetch_assoc()) {
        $penjualan_per_buku[$row['id_buku']] = $row['total_terjual'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda User - Toko Buku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../CSS/style_beranda-user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        <div class="banner-slide active">
            <img src="../Aset/banner1.jpg" alt="Banner 1 - Koleksi Buku Terbaru">
            <div class="banner-overlay">
                <h1 class="banner-title">Temukan Buku Impian Anda</h1>
                <p class="banner-subtitle">Jelajahi koleksi buku terlengkap dengan harga terbaik</p>
            </div>
        </div>
        <div class="banner-slide">
            <img src="../Aset/banner2.jpg" alt="Banner 2 - Diskon Spesial">
            <div class="banner-overlay">
                <h1 class="banner-title">Diskon Spesial untuk Pembaca Setia</h1>
                <p class="banner-subtitle">Dapatkan penawaran terbaik untuk buku favorit Anda</p>
            </div>
        </div>
        <div class="banner-slide">
            <img src="../Aset/banner3.jpg" alt="Banner 3 - Buku Best Seller">
            <div class="banner-overlay">
                <h1 class="banner-title">Buku Best Seller Terpopuler</h1>
                <p class="banner-subtitle">Buku-buku yang sedang trending dan banyak dibaca</p>
            </div>
        </div>
        
        <div class="banner-dots">
            <div class="banner-dot active"></div>
            <div class="banner-dot"></div>
            <div class="banner-dot"></div>
        </div>
    </div>

    <!-- Kategori Buku -->
    <section class="categories-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-book"></i> Kategori Buku</h2>
                <a href="buku-user.php" class="section-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 class="category-title">Filsafat</h3>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="category-title">Pendidikan</h3>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h3 class="category-title">Sains</h3>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="category-title">Bisnis</h3>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-feather-alt"></i>
                    </div>
                    <h3 class="category-title">Novel</h3>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <h3 class="category-title">Sejarah</h3>
                </div>
            </div>
        </div>
    </section>

<!-- Buku Bestseller -->
<section class="bestseller-section <?= $fallback_mode ? 'recommendation-mode' : '' ?>">
    <div class="section-container">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-<?= $fallback_mode ? 'star' : 'fire' ?>"></i> 
                <?= $fallback_mode ? 'Buku Rekomendasi' : 'Buku Bestseller' ?>
            </h2>
            <a href="buku-bestseller.php" class="section-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="books-grid">
            <?php if ($result_buku->num_rows > 0): ?>
                <?php
                $counter = 0;
                while ($buku = $result_buku->fetch_assoc()):
                    $counter++;
                    $total_terjual = isset($penjualan_per_buku[$buku['id_buku']]) ? $penjualan_per_buku[$buku['id_buku']] : 0;
                    
                    // Tentukan badge berdasarkan ranking untuk 5 buku
                    $badge_text = "";
                    $badge_class = "";
                    if (!$fallback_mode) {
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
                            default:
                                if ($total_terjual > 0) {
                                    $badge_text = "Bestseller";
                                }
                                break;
                        }
                    } else {
                        $badge_text = "Rekomendasi";
                        $badge_class = "fallback-badge";
                    }
                ?>
                    <a href="detail-buku.php?id_buku=<?= $buku['id_buku'] ?>" class="book-card">
                        <?php if (!empty($badge_text)): ?>
                            <div class="bestseller-badge <?= $badge_class ?>"><?= $badge_text ?></div>
                        <?php endif; ?>
                        <div class="book-image">
                            <?php
                            $cover_path = "../uploads/cover_buku/" . $buku['cover_buku'];
                            if (!empty($buku['cover_buku']) && file_exists($cover_path)):
                            ?>
                                <img src="<?= $cover_path ?>" alt="<?= htmlspecialchars($buku['judul']) ?>">
                            <?php else: ?>
                                <img src="../Aset/default-cover.jpg" alt="Cover Default">
                            <?php endif; ?>
                        </div>
                        <div class="book-info">
                            <p class="book-author"><?= htmlspecialchars($buku['penulis']) ?></p>
                            <h3 class="book-title"><?= htmlspecialchars($buku['judul']) ?></h3>
                            <span class="book-category"><?= htmlspecialchars($buku['kategori']) ?></span>
                            <div class="book-price-section">
                                <p class="book-price">Rp <?= number_format($buku['harga_regular'], 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-books">
                    <p>Tidak ada buku tersedia.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

    <!-- Promo Section -->
    <section class="promo-section">
        <div class="promo-container">
            <h2 class="promo-title">Diskon Spesial Untuk Kamu</h2>
            <p class="promo-subtitle">Dapatkan potongan harga hingga 75% untuk koleksi buku pilihan. Jangan lewatkan!</p>
            <a href="buku-user.php" class="promo-btn">
                Belanja Sekarang <i class="fas fa-shopping-cart"></i>
            </a>
        </div>
    </section>

    <!-- Tentang Kami -->
    <section class="about-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-info-circle"></i> Tentang Kami</h2>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <p>
                        Selamat datang di Toko Buku Online terpercaya kami! Kami menyediakan beragam koleksi buku
                        berkualitas dari berbagai genre dan penulis ternama. Dengan pengalaman lebih dari 10 tahun
                        dalam industri buku, kami berkomitmen untuk memberikan pelayanan terbaik kepada seluruh
                        pelanggan.
                    </p>
                    <p>
                        Toko buku ini dirancang khusus untuk memudahkan Anda menjelajahi koleksi, mendapatkan
                        rekomendasi terbaru, serta melakukan pembelian dengan proses yang praktis dan aman.
                        Kami percaya bahwa membaca adalah jendela dunia, dan teknologi dapat membantu menyebarkan
                        kecintaan terhadap buku ke lebih banyak orang.
                    </p>
                    <p>
                        Dengan ribuan pelanggan yang telah mempercayai kami di seluruh Indonesia, kami terus
                        berinovasi untuk memberikan pengalaman berbelanja buku online yang menyenangkan dan memuaskan.
                    </p>
                </div>
                <div class="about-image">
                    <img src="../Aset/banner.jpg" alt="Toko Buku Kami">
                </div>
            </div>
        </div>
    </section>

    <!-- Visi & Misi -->
    <section class="visi-misi-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-bullseye"></i> Visi & Misi</h2>
            </div>
            <div class="visi-misi-container">
                <div class="visi-box">
                    <h3 class="visi-misi-title"><i class="fas fa-eye"></i> Visi Kami</h3>
                    <p class="visi-content">
                        "Menjadi platform toko buku online terdepan yang menginspirasi masyarakat Indonesia
                        untuk mencintai literasi dan pengetahuan melalui akses mudah terhadap buku-buku
                        berkualitas dari berbagai genre."
                    </p>
                </div>

                <div class="misi-box">
                    <h3 class="visi-misi-title"><i class="fas fa-rocket"></i> Misi Kami</h3>
                    <ul class="misi-list">
                        <li>Menyediakan koleksi buku terlengkap dengan kualitas terbaik dari berbagai penerbit ternama
                        </li>
                        <li>Memberikan pengalaman berbelanja yang mudah, nyaman, dan aman bagi seluruh pelanggan</li>
                        <li>Mengembangkan komunitas pembaca melalui program literasi dan diskusi buku berkala</li>
                        <li>Menjangkau seluruh wilayah Indonesia dengan sistem distribusi yang efisien dan terjangkau
                        </li>
                        <li>Terus berinovasi dalam layanan dan teknologi untuk memenuhi kebutuhan pembaca modern</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog/Artikel -->
    <section class="blog-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-newspaper"></i> Artikel & Tips</h2>
            </div>
            <div class="blog-grid">
                <!-- Artikel 1 -->
                <div class="blog-card">
                    <div class="blog-image">
                        <img src="../Aset/banner1.jpg" alt="Tips Membaca">
                    </div>
                    <div class="blog-content">
                        <h3 class="blog-title">5 Tips Membangun Kebiasaan Membaca Setiap Hari</h3>
                        <p class="blog-excerpt">
                            Membaca adalah kebiasaan yang sangat bermanfaat. Berikut adalah tips praktis
                            untuk membangun kebiasaan membaca setiap hari dan menjadikannya bagian dari rutinitas Anda.
                        </p>
                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Artikel 2 -->
                <div class="blog-card">
                    <div class="blog-image">
                        <img src="../Aset/banner2.jpg" alt="Rekomendasi Buku">
                    </div>
                    <div class="blog-content">
                        <h3 class="blog-title">Rekomendasi Buku Terbaik untuk Pengembangan Diri</h3>
                        <p class="blog-excerpt">
                            Ingin mengembangkan diri dan meningkatkan kualitas hidup? Temukan rekomendasi
                            buku terbaik yang dapat membantu perjalanan pengembangan diri Anda.
                        </p>
                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Artikel 3 -->
                <div class="blog-card">
                    <div class="blog-image">
                        <img src="../Aset/banner3.jpg" alt="Merawat Buku">
                    </div>
                    <div class="blog-content">
                        <h3 class="blog-title">Cara Merawat Buku agar Tetap Awet dan Terjaga</h3>
                        <p class="blog-excerpt">
                            Buku yang dirawat dengan baik akan bertahan lebih lama. Pelajari cara merawat
                            koleksi buku Anda agar tetap dalam kondisi prima dan dapat dinikmati untuk waktu yang lama.
                        </p>
                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Featured Section -->
    <section class="featured-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-award"></i> Kenapa Buku Kami Layak Dipilih?</h2>
            </div>

            <div class="featured-grid">
                <div class="featured-card">
                    <div class="featured-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3 class="featured-title">Pengiriman Cepat</h3>
                    <p class="featured-desc">Buku sampai di hari yang sama untuk wilayah tertentu</p>
                </div>

                <div class="featured-card">
                    <div class="featured-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="featured-title">Pembayaran Aman</h3>
                    <p class="featured-desc">Transaksi dijamin aman dengan sistem pembayaran terpercaya</p>
                </div>

                <div class="featured-card">
                    <div class="featured-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="featured-title">Live Chat dengan Admin</h3>
                    <p class="featured-desc">Tanya langsung soal stok, rekomendasi, atau status pesanan</p>
                </div>

                <div class="featured-card">
                    <div class="featured-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="featured-title">Rekomendasi Buku</h3>
                    <p class="featured-desc">Kami bantu pilih buku yang cocok untuk kamu</p>
                </div>
            </div>
        </div>
    </section>
    <?php require "footer.php"; ?>

    <script>
        // Banner Slider
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

        // Event listeners untuk tombol navigasi
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetSlideShow();
        });

        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetSlideShow();
        });

        // Dot click events
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetSlideShow();
            });
        });

        // Mulai auto slide
        startSlideShow();
    </script>
</body>

</html>