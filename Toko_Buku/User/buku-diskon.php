<?php
// Mulai session jika belum dimulai - HARUS DI BARIS PALING ATAS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include koneksi database dengan path yang benar
include __DIR__ . '/../config.php';

// Inisialisasi variabel filter
$discount_filter = isset($_GET['discount']) ? $_GET['discount'] : '';
$price_filter = isset($_GET['price']) ? $_GET['price'] : '';

// Query untuk buku diskon dengan filter
$sql = "SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku 
        FROM buku 
        WHERE diskon > 0 AND stok > 0";

// Terapkan filter diskon
if (!empty($discount_filter)) {
    $sql .= " AND diskon >= $discount_filter";
}

// Terapkan filter harga (disesuaikan dengan range baru)
if (!empty($price_filter)) {
    switch($price_filter) {
        case '1':
            $sql .= " AND harga_setelah_diskon < 100000";
            break;
        case '2':
            $sql .= " AND harga_setelah_diskon BETWEEN 100000 AND 300000";
            break;
        case '3':
            $sql .= " AND harga_setelah_diskon BETWEEN 300000 AND 500000";
            break;
        case '4':
            $sql .= " AND harga_setelah_diskon > 500000";
            break;
    }
}

$sql .= " ORDER BY diskon DESC, judul ASC";

$result = $conn->query($sql);
$discount_books = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $discount_books[] = $row;
    }
}

// Hitung diskon maksimal untuk ditampilkan di hero section
$max_discount = 0;
if (!empty($discount_books)) {
    foreach ($discount_books as $book) {
        if ($book['diskon'] > $max_discount) {
            $max_discount = $book['diskon'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Diskon - Toko Buku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES & RESET ===== */
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --accent-hover: #c0392b;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 8px;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --box-shadow-hover: 0 6px 20px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: #f5f7fa;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        /* ===== PROMO HERO SECTION ===== */
        .promo-hero {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .promo-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }

        .promo-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        .promo-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
        }

        .promo-subtitle {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .promo-highlight {
            background: var(--accent);
            color: white;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-size: 0.9rem;
        }

        /* ===== MAIN LAYOUT ===== */
        .main-layout {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 2rem;
        }

        /* ===== FILTER SIDEBAR ===== */
        .filter-sidebar {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .filter-title {
            font-size: 1.2rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-reset {
            color: var(--primary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-reset:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
            display: block;
        }

        .filter-select {
            width: 100%;
            padding: 0.5rem 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            background: white;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        /* ===== CONTENT AREA ===== */
        .content-area {
            flex: 1;
        }

        /* ===== BOOK SECTION STYLES ===== */
        .book-section {
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .filter-info {
            font-size: 0.85rem;
            color: var(--gray);
            background: var(--light-gray);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }

        /* ===== BOOKS GRID - 6 PER ROW ===== */
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
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .book-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background-color: #27ae60;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            z-index: 2;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .book-image {
            height: 140px;
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
            max-height: 120px;
            object-fit: contain;
            transition: transform 0.5s;
        }
        
        .book-card:hover .book-image img {
            transform: scale(1.05);
        }
        
        .book-info {
            padding: 0.8rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            border-top: 1px solid var(--light-gray);
        }
        
        .book-author {
            color: var(--gray);
            font-size: 0.7rem;
            margin-bottom: 0.3rem;
            font-weight: 500;
            line-height: 1.2;
        }
        
        .book-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.4rem;
            line-height: 1.2;
            display: -webkit-box;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 1.8rem;
        }
        
        .book-category {
            display: inline-block;
            background: var(--light-gray);
            color: var(--gray);
            padding: 0.15rem 0.4rem;
            border-radius: 8px;
            font-size: 0.65rem;
            margin-bottom: 0.5rem;
            align-self: flex-start;
        }
        
        .book-divider {
            height: 1px;
            background: var(--light-gray);
            margin: 0.3rem 0;
        }
        
        .book-price-section {
            margin-top: auto;
            padding-top: 0.3rem;
        }
        
        .book-price {
            font-weight: 700;
            color: var(--accent);
            font-size: 0.8rem;
        }
        
        .book-price-original {
            font-size: 0.65rem;
            color: var(--gray);
            text-decoration: line-through;
            margin-bottom: 0.1rem;
        }
        
        .price-container {
            display: flex;
            flex-direction: column;
        }
        
        .discount-price-container {
            display: flex;
            flex-direction: column;
        }

        /* ===== NO BOOKS FOUND STYLES ===== */
        .no-books-found {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem 1rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-books-found h3 {
            color: var(--secondary);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .no-books-found p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            font-size: 0.9rem;
        }

        .btn-view-all {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .btn-view-all:hover {
            background: var(--primary-dark);
        }

        /* ===== MOBILE FILTER TOGGLE ===== */
        .mobile-filter-toggle {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            margin-bottom: 1rem;
            cursor: pointer;
            width: 100%;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-overlay {
            display: none;
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
                gap: 1rem;
            }
            
            .book-image {
                height: 130px;
            }
            
            .book-image img {
                max-height: 110px;
            }
            
            .book-info {
                padding: 0.7rem;
            }
            
            .book-title {
                font-size: 0.7rem;
                height: 1.6rem;
            }
            
            .book-author {
                font-size: 0.65rem;
            }

            .main-layout {
                grid-template-columns: 200px 1fr;
                gap: 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .books-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .main-layout {
                grid-template-columns: 180px 1fr;
                gap: 1.2rem;
            }

            .filter-sidebar {
                padding: 1rem;
            }

            .promo-title {
                font-size: 2rem;
            }

            .promo-subtitle {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .books-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }

            .promo-title {
                font-size: 1.8rem;
            }

            .promo-subtitle {
                font-size: 0.9rem;
            }

            .mobile-filter-toggle {
                display: flex;
            }

            .main-layout {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filter-sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
                overflow-y: auto;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }

            .filter-sidebar.active {
                left: 0;
            }

            .filter-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }

            .filter-overlay.active {
                display: block;
            }
        }
        
        @media (max-width: 576px) {
            .books-grid {
                grid-template-columns: 1fr;
            }
            
            .main-layout {
                padding: 1.5rem 1rem;
            }

            .promo-title {
                font-size: 1.6rem;
            }

            .promo-subtitle {
                font-size: 0.85rem;
            }

            .book-image {
                height: 160px;
            }
            
            .book-image img {
                max-height: 140px;
            }

            .filter-sidebar {
                width: 260px;
            }
        }

        @media (max-width: 480px) {
            .books-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php require "navbar.php"; ?>

    <!-- Promo Hero Section -->
    <section class="promo-hero">
        <div class="promo-container">
            <h1 class="promo-title">Buku Diskon Spesial</h1>
            <p class="promo-subtitle">Dapatkan buku berkualitas dengan harga terbaik. Hemat hingga 75% untuk koleksi buku pilihan dari berbagai kategori.</p>
            <div class="promo-highlight">
                <i class="fas fa-tag"></i>
                Diskon hingga <?= $max_discount > 0 ? $max_discount . '%' : '75%' ?>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-layout">
        <!-- Filter Sidebar -->
        <button class="mobile-filter-toggle">
            <i class="fas fa-filter"></i> Filter Buku
        </button>

        <div class="filter-overlay"></div>

        <aside class="filter-sidebar">
            <div class="filter-header">
                <h3 class="filter-title"><i class="fas fa-sliders-h"></i> Filter</h3>
                <a href="buku-diskon.php" class="filter-reset">Reset</a>
            </div>
            
            <form method="GET" action="" id="filterForm">
                <div class="filter-group">
                    <label class="filter-label">Diskon</label>
                    <select name="discount" class="filter-select">
                        <option value="">Semua Diskon</option>
                        <option value="10" <?= $discount_filter == '10' ? 'selected' : '' ?>>Diskon ≥ 10%</option>
                        <option value="25" <?= $discount_filter == '25' ? 'selected' : '' ?>>Diskon ≥ 25%</option>
                        <option value="50" <?= $discount_filter == '50' ? 'selected' : '' ?>>Diskon ≥ 50%</option>
                        <option value="75" <?= $discount_filter == '75' ? 'selected' : '' ?>>Diskon ≥ 75%</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Harga</label>
                    <select name="price" class="filter-select">
                        <option value="">Semua Harga</option>
                        <option value="1" <?= $price_filter == '1' ? 'selected' : '' ?>>Di bawah Rp100.000</option>
                        <option value="2" <?= $price_filter == '2' ? 'selected' : '' ?>>Rp100.000 - Rp300.000</option>
                        <option value="3" <?= $price_filter == '3' ? 'selected' : '' ?>>Rp300.000 - Rp500.000</option>
                        <option value="4" <?= $price_filter == '4' ? 'selected' : '' ?>>Di atas Rp500.000</option>
                    </select>
                </div>
            </form>
        </aside>

        <!-- Content Area -->
        <main class="content-area">
            <!-- Books Section -->
            <section class="book-section">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-tag"></i> Buku Diskon</h2>
                    <div>
                        <span class="filter-info">
                            <?= count($discount_books) ?> buku ditemukan
                            <?php 
                            if (!empty($discount_filter)) {
                                echo " • Diskon ≥ " . $discount_filter . "%";
                            }
                            if (!empty($price_filter)) {
                                $price_ranges = [
                                    '1' => 'Harga < Rp100rb',
                                    '2' => 'Harga Rp100-300rb',
                                    '3' => 'Harga Rp300-500rb',
                                    '4' => 'Harga > Rp500rb'
                                ];
                                echo " • " . $price_ranges[$price_filter];
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="books-grid">
                    <?php if (!empty($discount_books)): ?>
                        <?php foreach ($discount_books as $row): ?>
                            <a href="detail-buku.php?id_buku=<?= urlencode($row['id_buku']) ?>" class="book-card">
                                <span class="book-badge">Diskon <?= $row['diskon'] ?>%</span>
                                
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
                            <h3>Tidak ada buku diskon ditemukan</h3>
                            <p>
                                <?php 
                                if (!empty($discount_filter) || !empty($price_filter)) {
                                    echo "Coba ubah filter pencarian Anda atau ";
                                }
                                ?>
                                Cek kembali nanti untuk penawaran diskon terbaru.
                            </p>
                            <a href="buku-diskon.php" class="btn-view-all">Lihat Semua Buku Diskon</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
    
    <?php require "footer.php"; ?>

    <script>
        // Auto-submit form when filter changes
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.filter-select');
            const filterForm = document.getElementById('filterForm');
            
            // Auto-submit form when filter changes
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    filterForm.submit();
                });
            });
            
            // Mobile filter toggle
            const mobileFilterToggle = document.querySelector('.mobile-filter-toggle');
            const filterSidebar = document.querySelector('.filter-sidebar');
            const filterOverlay = document.querySelector('.filter-overlay');
            
            if (mobileFilterToggle && filterSidebar) {
                mobileFilterToggle.addEventListener('click', function() {
                    filterSidebar.classList.add('active');
                    filterOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
                
                filterOverlay.addEventListener('click', function() {
                    filterSidebar.classList.remove('active');
                    filterOverlay.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            }
            
            // Close filter on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    filterSidebar.classList.remove('active');
                    filterOverlay.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });
    </script>
</body>

</html>