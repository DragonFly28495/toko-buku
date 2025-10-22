<?php
/**
 * @noinspection PhpUndefinedFunctionInspection
 * @noinspection PhpUndefinedClassInspection
 * @var \mysqli $conn
 */
// Tambahkan di baris paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Halaman detail buku untuk pengguna
include __DIR__ . '/../config.php';

$book = null;
$error = '';

// Debug: Cek koneksi database
if (!$conn) {
    $error = 'Koneksi database gagal.';
} elseif (!isset($_GET['id_buku']) || trim($_GET['id_buku']) === '') {
    $error = 'ID buku tidak diberikan.';
} else {
    $id = trim($_GET['id_buku']);
    
    // Debug: Tampilkan ID yang diterima
    error_log("Mencari buku dengan ID: " . $id);
    
    try {
        // Query yang disesuaikan dengan struktur database aktual
        $stmt = $conn->prepare('SELECT id_buku, judul, penulis, penerbit, tanggal_terbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE id_buku = ? LIMIT 1');
        
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $book = $res->fetch_assoc();
            error_log("Buku ditemukan: " . $book['judul']);
        } else {
            $error = 'Buku tidak ditemukan. ID: ' . htmlspecialchars($id);
            error_log("Buku tidak ditemukan dengan ID: " . $id);
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage();
        error_log("Error detail buku: " . $e->getMessage());
    }
}

// Ambil buku rekomendasi hanya jika buku utama ditemukan
$recommendations_category = [];
$recommendations_author = [];
$recommendations_random = [];

if ($book && empty($error)) {
    try {
        // Rekomendasi berdasarkan kategori
    $rec_stmt = $conn->prepare('SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE kategori = ? AND id_buku != ? ORDER BY RAND() LIMIT 4');
        $rec_stmt->bind_param('ss', $book['kategori'], $book['id_buku']);
        $rec_stmt->execute();
        $rec_result = $rec_stmt->get_result();

        while ($row = $rec_result->fetch_assoc()) {
            $recommendations_category[] = $row;
        }
        $rec_stmt->close();

        // Rekomendasi berdasarkan penulis
        $author_stmt = $conn->prepare('SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE penulis = ? AND id_buku != ? ORDER BY RAND() LIMIT 4');
        $author_stmt->bind_param('ss', $book['penulis'], $book['id_buku']);
        $author_stmt->execute();
        $author_result = $author_stmt->get_result();

        while ($row = $author_result->fetch_assoc()) {
            $recommendations_author[] = $row;
        }
        $author_stmt->close();

        // Rekomendasi acak
        $random_stmt = $conn->prepare('SELECT id_buku, judul, penulis, penerbit, harga_regular, diskon, harga_setelah_diskon, stok, kategori, deskripsi, cover_buku FROM buku WHERE id_buku != ? ORDER BY RAND() LIMIT 6');
        $random_stmt->bind_param('s', $book['id_buku']);
        $random_stmt->execute();
        $random_result = $random_stmt->get_result();

        while ($row = $random_result->fetch_assoc()) {
            $recommendations_random[] = $row;
        }
        $random_stmt->close();

    } catch (Exception $e) {
        error_log("Error rekomendasi: " . $e->getMessage());
        // Tetap lanjut meski rekomendasi error
    }
}

// Fungsi untuk menentukan harga yang akan ditampilkan
function getDisplayPrice($book) {
    if (!empty($book['harga_setelah_diskon']) && $book['harga_setelah_diskon'] > 0) {
        return $book['harga_setelah_diskon'];
    }
    return $book['harga_regular'];
}

function hasDiscount($book) {
    return !empty($book['diskon']) && $book['diskon'] > 0 && !empty($book['harga_regular']) && $book['harga_regular'] > 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $book ? htmlspecialchars($book['judul']) : 'Detail Buku' ?> - Toko Buku</title>
    <link rel="stylesheet" href="../CSS/style_detail_buku-user.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php require "navbar.php"; ?>

    <div class="main-container">
        <div class="detail-wrapper">
            <?php if ($error): ?>
                <div class="error-container">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="error-message">
                        <h3>Terjadi Kesalahan</h3>
                        <p><?= htmlspecialchars($error) ?></p>
                        <p style="font-size: 0.9rem; margin-top: 1rem; color: #856404;">
                            Silakan periksa ID buku atau coba lagi nanti.
                        </p>
                    </div>
                    <a href="buku-user.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Kembali ke Katalog Buku
                    </a>
                </div>
            <?php elseif ($book): ?>
                <!-- Detail Buku -->
                <div class="detail-card">
                    <div class="detail-left">
                        <div class="detail-thumb">
                                <?php if (!empty($book['cover_buku']) && file_exists(__DIR__ . '/../uploads/cover_buku/' . $book['cover_buku'])): ?>
                                <img src="../uploads/cover_buku/<?= htmlspecialchars($book['cover_buku']) ?>"
                                    alt="<?= htmlspecialchars($book['judul']) ?>" 
                                    style="max-width: 100%; height: auto; border-radius: 8px;">
                            <?php else: ?>
                                <div class="no-cover">
                                    <i class="fas fa-book" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                                    <p>Gambar tidak tersedia</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-right">
                        <div class="detail-info">
                                <h1 class="detail-title"><?= htmlspecialchars($book['judul']) ?></h1>

                            <div class="detail-author">
                                <i class="fas fa-user-edit"></i> 
                                <strong>Penulis:</strong> <?= htmlspecialchars($book['penulis']) ?>
                            </div>

                            <div class="detail-publisher">
                                <i class="fas fa-building"></i> 
                                <strong>Penerbit:</strong> <?= htmlspecialchars($book['penerbit']) ?>
                            </div>

                            <?php if (!empty($book['tanggal_terbit']) && $book['tanggal_terbit'] != '0000-00-00'): ?>
                            <div class="detail-publisher">
                                <i class="fas fa-calendar-alt"></i> 
                                <strong>Terbit:</strong> <?= date('d F Y', strtotime($book['tanggal_terbit'])) ?>
                            </div>
                            <?php endif; ?>

                            <div class="detail-meta">
                                <div class="meta-item">
                                    <i class="fas fa-tags"></i>
                                    <span><?= \htmlspecialchars($book['kategori']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Stok: <?= \htmlspecialchars($book['stok']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-barcode"></i>
                                    <span>ID: <?= \htmlspecialchars($book['id_buku']) ?></span>
                                </div>
                            </div>

                            <!-- Price Section -->
                            <div class="price-section">
                                <?php if (hasDiscount($book)): ?>
                                    <span class="original-price">
                                        Rp <?= number_format($book['harga_regular'], 0, ',', '.') ?>
                                    </span>
                                    <span class="discount-badge">
                                        -<?= $book['diskon'] ?>%
                                    </span>
                                    <br>
                                        <span class="final-price">
                                        Rp <?= number_format(getDisplayPrice($book), 0, ',', '.') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="final-price">
                                        Rp <?= number_format(getDisplayPrice($book), 0, ',', '.') ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="stock-badge <?= $book['stok'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <i class="fas fa-<?= $book['stok'] > 0 ? 'check' : 'times' ?>"></i>
                                <?= $book['stok'] > 0 ? 'Stok Tersedia' : 'Stok Habis' ?>
                            </div>

                            <div class="detail-description">
                                <h3><i class="fas fa-align-left"></i> Deskripsi Buku</h3>
                                <?= nl2br(\htmlspecialchars($book['deskripsi'] ?? 'Deskripsi tidak tersedia')) ?>
                            </div>

                            <div class="detail-actions">
                                <a href="buku-user.php" class="btn-back">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Katalog
                                </a>
                                <?php if ($book['stok'] > 0): ?>
                                    <?php if (isset($_SESSION['id_pengguna']) && isset($_SESSION['username'])): ?>
                                        <a href="keranjang-user.php?add=<?= \urlencode($book['id_buku']) ?>" class="btn-add">
                                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                        </a>
                                    <?php else: ?>
                                        <a href="../login-user.php" class="btn-add">
                                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                        </a>
                                        <a href="../login-user.php" class="btn-buy">
                                            <i class="fas fa-bolt"></i> Beli Sekarang
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn-add" disabled>
                                        <i class="fas fa-times"></i> Stok Habis
                                    </button>
                                    <button class="btn-notify" onclick="notifyWhenAvailable(<?= $book['id_buku'] ?>)">
                                        <i class="fas fa-bell"></i> Notifikasi Stok
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

<!-- Rekomendasi Buku -->
<?php if (!empty($recommendations_category) || !empty($recommendations_author) || !empty($recommendations_random)): ?>
    <div class="section-divider">
        <span class="section-divider-text">
            <i class="fas fa-star"></i> Buku Menarik Lainnya
        </span>
    </div>

    <!-- Rekomendasi dari Kategori yang Sama -->
                    <?php if (!empty($recommendations_category)): ?>
        <div class="recommendation-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-folder"></i> Buku Serupa dalam Kategori "<?= htmlspecialchars($book['kategori']) ?>"
                </h2>
            </div>
            <div class="books-grid">
                    <?php foreach (array_slice($recommendations_category, 0, 6) as $rec_book): ?>
                    <a href="detail-buku.php?id_buku=<?= urlencode($rec_book['id_buku']) ?>" class="book-card">
                        <?php if ($rec_book['diskon'] > 0): ?>
                            <span class="book-badge discount-badge">Diskon <?= $rec_book['diskon'] ?>%</span>
                        <?php endif; ?>
                        
                        <div class="book-image">
                                <?php if (!empty($rec_book['cover_buku']) && file_exists(__DIR__ . '/../uploads/cover_buku/' . $rec_book['cover_buku'])): ?>
                                <img src="../uploads/cover_buku/<?= htmlspecialchars($rec_book['cover_buku']) ?>"
                                    alt="<?= htmlspecialchars($rec_book['judul']) ?>">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #6c757d;">
                                    <i class="fas fa-book" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="book-info">
                            <p class="book-author"><?= \htmlspecialchars($rec_book['penulis']) ?></p>
                            <h3 class="book-title"><?= \htmlspecialchars($rec_book['judul']) ?></h3>
                            <span class="book-category"><?= \htmlspecialchars($rec_book['kategori']) ?></span>
                            
                            <div class="book-divider"></div>
                            
                            <div class="book-price-section">
                                <div class="<?= $rec_book['diskon'] > 0 ? 'discount-price-container' : 'price-container' ?>">
                                    <?php if ($rec_book['diskon'] > 0): ?>
                                        <span class="book-price-original">Rp <?= number_format($rec_book['harga_regular'], 0, ',', '.') ?></span>
                                        <span class="book-price">Rp <?= number_format(getDisplayPrice($rec_book), 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="book-price">Rp <?= number_format(getDisplayPrice($rec_book), 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Rekomendasi dari Penulis yang Sama -->
    <?php if (!empty($recommendations_author)): ?>
        <div class="recommendation-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-user-edit"></i> Karya Lain oleh "<?= htmlspecialchars($book['penulis']) ?>"
                </h2>
            </div>
            <div class="books-grid">
                <?php foreach (array_slice($recommendations_author, 0, 6) as $rec_book): ?>
                    <a href="detail-buku.php?id_buku=<?= urlencode($rec_book['id_buku']) ?>" class="book-card">
                        <?php if ($rec_book['diskon'] > 0): ?>
                            <span class="book-badge discount-badge">Diskon <?= $rec_book['diskon'] ?>%</span>
                        <?php endif; ?>
                        
                        <div class="book-image">
                            <?php if (!empty($rec_book['cover_buku']) && file_exists(__DIR__ . '/../uploads/cover_buku/' . $rec_book['cover_buku'])): ?>
                                <img src="../uploads/cover_buku/<?= htmlspecialchars($rec_book['cover_buku']) ?>"
                                    alt="<?= htmlspecialchars($rec_book['judul']) ?>">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #6c757d;">
                                    <i class="fas fa-book" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="book-info">
                            <p class="book-author"><?= htmlspecialchars($rec_book['penulis']) ?></p>
                            <h3 class="book-title"><?= htmlspecialchars($rec_book['judul']) ?></h3>
                            <span class="book-category"><?= htmlspecialchars($rec_book['kategori']) ?></span>
                            
                            <div class="book-divider"></div>
                            
                            <div class="book-price-section">
                                <div class="<?= $rec_book['diskon'] > 0 ? 'discount-price-container' : 'price-container' ?>">
                                    <?php if ($rec_book['diskon'] > 0): ?>
                                        <span class="book-price-original">Rp <?= number_format($rec_book['harga_regular'], 0, ',', '.') ?></span>
                                        <span class="book-price">Rp <?= number_format(getDisplayPrice($rec_book), 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="book-price">Rp <?= number_format(getDisplayPrice($rec_book), 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Rekomendasi Acak -->
    <?php if (!empty($recommendations_random)): ?>
        <div class="recommendation-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-random"></i> Mungkin Anda Suka
                </h2>
            </div>
            <div class="books-grid">
                <?php foreach (array_slice($recommendations_random, 0, 5) as $rec_book): ?>
                    <a href="detail-buku.php?id_buku=<?= urlencode($rec_book['id_buku']) ?>" class="book-card">
                        <?php if ($rec_book['diskon'] > 0): ?>
                            <span class="book-badge discount-badge">Diskon <?= $rec_book['diskon'] ?>%</span>
                        <?php endif; ?>
                        
                        <div class="book-image">
                            <?php if (!empty($rec_book['cover_buku']) && file_exists(__DIR__ . '/../uploads/cover_buku/' . $rec_book['cover_buku'])): ?>
                                <img src="../uploads/cover_buku/<?= htmlspecialchars($rec_book['cover_buku']) ?>"
                                    alt="<?= htmlspecialchars($rec_book['judul']) ?>">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #6c757d;">
                                    <i class="fas fa-book" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="book-info">
                            <p class="book-author"><?= htmlspecialchars($rec_book['penulis']) ?></p>
                            <h3 class="book-title"><?= htmlspecialchars($rec_book['judul']) ?></h3>
                            <span class="book-category"><?= htmlspecialchars($rec_book['kategori']) ?></span>
                            
                            <div class="book-divider"></div>
                            
                            <div class="book-price-section">
                                <div class="<?= $rec_book['diskon'] > 0 ? 'discount-price-container' : 'price-container' ?>">
                                    <?php if ($rec_book['diskon'] > 0): ?>
                                        <span class="book-price-original">Rp <?= number_format($rec_book['harga_regular'], 0, ',', '.') ?></span>
                                        <span class="book-price">Rp <?= number_format(getDisplayPrice($rec_book), 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="book-price">Rp <?= number_format(getDisplayPrice($rec_book), 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php require "footer.php"; ?>

    <script>
        // Fungsi untuk fitur tambahan
        function addToWishlist(bookId) {
            alert('Fitur wishlist akan segera tersedia!');
        }

        function shareBook() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    text: 'Lihat buku menarik ini: <?= addslashes($book['judul'] ?? '') ?>',
                    url: window.location.href
                });
            } else {
                // Fallback untuk browser yang tidak support Web Share API
                const shareUrl = window.location.href;
                navigator.clipboard.writeText(shareUrl).then(() => {
                    alert('Link buku berhasil disalin!');
                });
            }
        }

        function readSample() {
            alert('Fitur baca sample akan segera tersedia!');
        }

        function notifyWhenAvailable(bookId) {
            const email = prompt('Masukkan email Anda untuk notifikasi ketika stok tersedia:');
            if (email) {
                alert('Terima kasih! Kami akan mengirim notifikasi ke ' + email + ' ketika stok tersedia.');
            }
        }
    </script>
</body>
</html>