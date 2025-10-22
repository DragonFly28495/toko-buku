<?php
// =========================
// Halaman Buku Bestseller
// =========================
// File ini menampilkan daftar buku terlaris (bestseller).
// Penjelasan singkat untuk pemula:
// - Kita memulai session untuk menyimpan data user (jika ada yang login).
// - Lalu kita include file config.php yang berisi koneksi database ($conn).
// - Kemudian menjalankan query untuk mengambil data buku berdasarkan jumlah transaksi.
// - Terakhir menampilkan hasilnya dalam bentuk kartu (card) di HTML.

// Mulai session (jika belum dimulai) — penting supaya fungsi session dapat dipakai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include konfigurasi/koneksi database.
// __DIR__ memberikan path folder file ini, lalu ../config.php menyesuaikan lokasi config di root project.
include __DIR__ . '/../config.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Bestseller - Toko Buku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary: #3a86ff;
            --primary-dark: #2667cc;
            --secondary: #2d3748;
            --accent: #ff6b35;
            --light-gray: #f8f9fa;
            --gray: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --transition: all 0.3s ease;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--secondary);
            background-color: #f5f7fa;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===== HERO SECTION ===== */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ===== BESTSELLER RANKING SECTION ===== */
        .bestseller-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .bestseller-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .bestseller-header h2 {
            font-size: 2rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .bestseller-header h2 i {
            color: var(--accent);
        }

        .bestseller-header p {
            color: var(--gray);
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }

        /* ===== RANKING CARDS ===== */
        .ranking-card {
            display: flex;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }

        .ranking-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .rank-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            flex-shrink: 0;
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            position: relative;
        }

        .rank-1 {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }

        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0 0%, #A0A0A0 100%);
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
        }

        .rank-4, .rank-5, .rank-6, .rank-7, .rank-8, .rank-9, .rank-10 {
            background: linear-gradient(#ff6b35 0%, #ff3d00 100%);
        }

        .rank-badge::after {
            content: '';
            position: absolute;
            top: 0;
            right: -10px;
            width: 0;
            height: 0;
            border-top: 40px solid transparent;
            border-bottom: 40px solid transparent;
            border-left: 10px solid;
        }

        .rank-1::after {
            border-left-color: #FFD700;
        }

        .rank-2::after {
            border-left-color: #C0C0C0;
        }

        .rank-3::after {
            border-left-color: #CD7F32;
        }

        .rank-4::after, .rank-5::after, .rank-6::after, 
        .rank-7::after, .rank-8::after, .rank-9::after, .rank-10::after {
            border-left-color: #ff6b35;
        }

        .book-content {
            display: flex;
            flex: 1;
            padding: 1.5rem;
            gap: 1.5rem;
        }

        .book-image {
            width: 120px;
            height: 160px;
            flex-shrink: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .book-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            line-height: 1.3;
        }

        .book-author {
            color: var(--gray);
            font-size: 1rem;
            margin-bottom: 0.8rem;
        }

        .book-category {
            display: inline-block;
            background: var(--light-gray);
            color: var(--gray);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            align-self: flex-start;
        }

        .book-description {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .book-price {
            display: flex;
            flex-direction: column;
        }

        .price-original {
            font-size: 0.9rem;
            color: var(--gray);
            text-decoration: line-through;
        }

        .price-final {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
        }

        .discount-badge {
            background: var(--success);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }

        .book-actions {
            display: flex;
            gap: 0.8rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 992px) {
            .book-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .book-image {
                width: 100%;
                height: 200px;
            }
            
            .book-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .book-actions {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .bestseller-header h2 {
                font-size: 1.7rem;
            }
            
            .rank-badge {
                width: 60px;
                font-size: 1.2rem;
            }
            
            .rank-badge::after {
                border-top-width: 30px;
                border-bottom-width: 30px;
            }
            
            .book-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }
            
            .hero {
                padding: 2rem 0;
            }
            
            .book-content {
                padding: 1rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>
    <?php require "navbar.php"; ?>

    <!-- Hero Section -->
        <!-- Bagian ini hanya berisi header halaman (judul dan keterangan singkat).
             Untuk pemula: HTML statis saja, tidak ada pemrosesan PHP di sini. -->
    <section class="hero">
        <div class="container">
            <h1><i class="fas fa-fire"></i> Buku Bestseller</h1>
            <p>10 buku terlaris pilihan pembaca kami</p>
        </div>
    </section>

    <!-- Bestseller Ranking Section -->
    <div class="container">
        <div class="bestseller-container">

            <?php
                // =========
                // Bagian PHP
                // =========
                // Kita bungkus query dengan try/catch supaya bila terjadi error (mis. koneksi putus)
                // kita dapat menampilkan pesan yang ramah dan mencatat log error.
                // Query untuk 10 Buku Bestseller
                // Penjelasan singkat SQL: kita pilih data buku dan jumlah total terjual dari tabel transaksi.
                // LEFT JOIN memastikan buku tetap muncul walau tidak ada transaksi (tapi kita pakai HAVING > 0)
            try {
                // Query untuk 10 Buku Bestseller berdasarkan transaksi tertinggi + stok tersedia + HARUS ADA TRANSAKSI
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
                    LIMIT 10
                ");
                $bestseller_stmt->execute();
                $bestseller_result = $bestseller_stmt->get_result();
                $rank = 1;
                
                if ($bestseller_result->num_rows > 0) {
                    while ($row = $bestseller_result->fetch_assoc()):
            ?>
                <div class="ranking-card">
                    <div class="rank-badge rank-<?= $rank ?>">
                        <?= $rank ?>
                    </div>
                    
                    <div class="book-content">
                        <div class="book-image">
                            <img src="../Uploads/cover_buku/<?= htmlspecialchars($row['cover_buku']) ?>"
                                alt="<?= htmlspecialchars($row['judul']) ?>" 
                                onerror="this.src='../Aset/default-cover.jpg'">
                        </div>
                        
                        <div class="book-details">
                            <h3 class="book-title"><?= htmlspecialchars($row['judul']) ?></h3>
                            <p class="book-author">Oleh <?= htmlspecialchars($row['penulis']) ?></p>
                            <span class="book-category"><?= htmlspecialchars($row['kategori']) ?></span>
                            
                            <p class="book-description">
                                <?= 
                                    strlen($row['deskripsi']) > 150 
                                    ? substr($row['deskripsi'], 0, 150) . '...' 
                                    : $row['deskripsi'] 
                                ?>
                            </p>
                            
                            <div class="book-meta">
                                <div class="book-price">
                                    <?php if ($row['diskon'] > 0): ?>
                                        <span class="price-original">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                        <div style="display: flex; align-items: center;">
                                            <span class="price-final">Rp <?= number_format($row['harga_setelah_diskon'], 0, ',', '.') ?></span>
                                            <span class="discount-badge">-<?= $row['diskon'] ?>%</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="price-final">Rp <?= number_format($row['harga_regular'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="book-actions">
                                    <a href="detail-buku.php?id_buku=<?= urlencode($row['id_buku']) ?>" class="btn btn-outline">
                                        <i class="fas fa-info-circle"></i> Detail
                                    </a>
                                    <a href="tambah-keranjang.php?id_buku=<?= urlencode($row['id_buku']) ?>&jumlah=1" class="btn btn-primary">
                                        <i class="fas fa-cart-plus"></i> Beli
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                    $rank++;
                    endwhile; 
                } else {
            ?>
                <div class="no-books-found" style="text-align: center; padding: 3rem; background: white; border-radius: 12px; box-shadow: var(--shadow);">
                    <i class="fas fa-book-open" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--secondary); margin-bottom: 0.5rem;">Belum ada buku bestseller</h3>
                    <p style="color: var(--gray);">Cek kembali nanti untuk update buku terlaris.</p>
                    <a href="buku-user.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-book"></i> Lihat Semua Buku
                    </a>
                </div>
            <?php
                }
                
                $bestseller_stmt->close();
            } catch (Exception $e) {
                error_log("Error fetching bestseller books: " . $e->getMessage());
                echo '<div class="error-message" style="text-align: center; padding: 2rem; background: #ffe6e6; color: var(--danger); border-radius: 8px;">';
                echo '<i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>';
                echo '<h3>Terjadi kesalahan</h3>';
                echo '<p>Gagal memuat data buku bestseller. Silakan coba lagi nanti.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <?php require "footer.php"; ?>
</body>

</html>