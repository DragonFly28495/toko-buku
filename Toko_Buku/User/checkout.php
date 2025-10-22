<?php
// PERBAIKI: Pindahkan session_start() ke atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config.php';

// Cek login
if (!isset($_SESSION['id_pengguna']) || !isset($_SESSION['username'])) {
    header('Location: ../login-user.php');
    exit;
}

// PERBAIKI FUNGSI getCurrentCart - SESUAIKAN DENGAN STRUKTUR DATABASE
function getCurrentCart($conn)
{
    $cart = [];
    
    if (isset($_SESSION['id_pengguna']) && !empty($_SESSION['id_pengguna'])) {
        $id_pengguna = $_SESSION['id_pengguna'];
        
        // PERBAIKAN: gunakan harga_regular dan harga_setelah_diskon
        $stmt = $conn->prepare("
            SELECT k.id_buku, k.jumlah, b.judul, b.harga_regular, b.harga_setelah_diskon, b.stok, b.cover_buku 
            FROM keranjang k
            INNER JOIN buku b ON k.id_buku = b.id_buku 
            WHERE k.id_pengguna = ?
        ");
        $stmt->bind_param("i", $id_pengguna);
        $stmt->execute();
        $res = $stmt->get_result();
        
        while ($r = $res->fetch_assoc()) {
            // PERBAIKAN: tentukan harga jual yang benar
            $harga_jual = !empty($r['harga_setelah_diskon']) ? $r['harga_setelah_diskon'] : $r['harga_regular'];
            
            $cart[$r['id_buku']] = [
                'jumlah' => (int) $r['jumlah'],
                'harga_regular' => (int) $r['harga_regular'],
                'harga_setelah_diskon' => $r['harga_setelah_diskon'] ? (int) $r['harga_setelah_diskon'] : null,
                'harga_jual' => $harga_jual,
                'judul' => $r['judul'],
                'stok' => $r['stok'],
                'cover_buku' => $r['cover_buku']
            ];
        }
        $stmt->close();
    }
    
    return $cart;
}

$cart = getCurrentCart($conn);

// PERBAIKI PERHITUNGAN TOTAL - SESUAIKAN DENGAN HARGA JUAL
$total_jumlah = 0;
$total_harga = 0;
$items = [];

foreach ($cart as $id_buku => $item) {
    $total_jumlah += $item['jumlah'];
    $total_harga += $item['jumlah'] * $item['harga_jual'];
    
    $items[$id_buku] = [
        'id_buku' => $id_buku,
        'judul' => $item['judul'],
        'cover_buku' => $item['cover_buku'],
        'qty' => $item['jumlah'],
        'harga_regular' => $item['harga_regular'],
        'harga_setelah_diskon' => $item['harga_setelah_diskon'],
        'harga_jual' => $item['harga_jual'],
        'subtotal' => $item['jumlah'] * $item['harga_jual']
    ];
}

$errors = [];
$success = '';

// ✅ PERBAIKAN: Cek jika ada pesan sukses dari session
if (isset($_SESSION['checkout_success'])) {
    $success = $_SESSION['checkout_success'];
    unset($_SESSION['checkout_success']);
}

// Proses saat tombol Konfirmasi & Bayar ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = trim($_POST['payment_method'] ?? '');

    if ($payment_method === '') {
        $errors[] = 'Pilih metode pembayaran.';
    }

    if (empty($cart)) {
        $errors[] = 'Keranjang kosong.';
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            $kd_transaksi = 'TRX' . time();
            $id_pengguna = $_SESSION['id_pengguna'];
            $username = $_SESSION['username'];

            // Simpan data transaksi
            foreach ($items as $id_buku => $item) {
                $jumlah = $item['qty'];
                $harga = $item['harga_jual']; // PERBAIKAN: gunakan harga_jual
                $judul = $item['judul'];
                $subtotal = $item['subtotal'];

                $stmt = $conn->prepare("
                    INSERT INTO transaksi (kd_transaksi, id_buku, jumlah, total_harga, id_pengguna, username, metode_pembayaran, judul) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("siiissss", $kd_transaksi, $id_buku, $jumlah, $subtotal, $id_pengguna, $username, $payment_method, $judul);
                $stmt->execute();
                $stmt->close();
            }

            // Kurangi stok buku
            foreach ($cart as $id_buku => $item) {
                $jumlah_beli = $item['jumlah'];

                $update = $conn->prepare("
                    UPDATE buku 
                    SET stok = stok - ? 
                    WHERE id_buku = ? AND stok >= ?
                ");
                $update->bind_param("iii", $jumlah_beli, $id_buku, $jumlah_beli);
                $update->execute();

                if ($update->affected_rows === 0) {
                    throw new Exception("Stok tidak cukup untuk buku ID $id_buku.");
                }

                $update->close();
            }

            // Hapus isi keranjang
            $del = $conn->prepare("DELETE FROM keranjang WHERE id_pengguna = ?");
            $del->bind_param("i", $id_pengguna);
            $del->execute();
            $del->close();

            $_SESSION['cart'] = [];

            $conn->commit();

            // ✅ PERBAIKAN: Set pesan sukses dan redirect ke pesanan-user.php
            $_SESSION['checkout_success'] = '✅ Checkout berhasil! Transaksi Anda telah diproses. Kode Transaksi: ' . htmlspecialchars($kd_transaksi) . '. Silakan tunggu konfirmasi lebih lanjut.';

            header("Location: pesanan-user.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Gagal memproses transaksi: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - Toko BUKU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../CSS/style_checkout-user.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php require "navbar.php"; ?>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Checkout</h1>
            <p class="page-subtitle">Lengkapi informasi pembayaran untuk menyelesaikan pesanan Anda</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e)
                    echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="empty-cart">
                <div class="empty-icon">📚</div>
                <h2 class="empty-title">Keranjang Belanja Kosong</h2>
                <p class="empty-text">Silakan tambahkan buku ke keranjang belanja Anda terlebih dahulu.</p>
                <a href="buku-user.php" class="btn-shopping">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="summary-header">
                        <h2>Ringkasan Pesanan</h2>
                    </div>
                    <div class="order-items">
                        <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <?php if (!empty($item['cover_buku']) && file_exists(__DIR__ . '/../uploads/cover_buku/' . $item['cover_buku'])): ?>
                                        <img src="../uploads/cover_buku/<?= htmlspecialchars($item['cover_buku']) ?>"
                                            alt="<?= htmlspecialchars($item['judul']) ?>">
                                    <?php else: ?>
                                        <div style="color: #6c757d; text-align: center; padding: 1rem;">
                                            <small>Gambar tidak tersedia</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <h3 class="item-title"><?= htmlspecialchars($item['judul']) ?></h3>
                                    <div class="item-meta">
                                        <span class="item-quantity">Jumlah: <?= intval($item['qty']) ?></span>
                                    </div>
                                    <div class="item-price">
                                        Rp <?= number_format($item['harga_jual'], 0, ',', '.') ?>
                                        <?php if (!empty($item['harga_setelah_diskon']) && $item['harga_setelah_diskon'] < $item['harga_regular']): ?>
                                            <small style="text-decoration: line-through; color: #6c757d; margin-left: 0.5rem;">
                                                Rp <?= number_format($item['harga_regular'], 0, ',', '.') ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    <strong>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="payment-section">
                    <h2 class="payment-title">Pembayaran</h2>

                    <form method="POST">
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="COD" required>
                                <div class="payment-info">
                                    <div class="payment-icon">COD</div>
                                    <div class="payment-details">
                                        <h4>Cash on Delivery</h4>
                                        <p>Bayar ketika buku diterima</p>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="Bank Transfer" required>
                                <div class="payment-info">
                                    <div class="payment-icon">BT</div>
                                    <div class="payment-details">
                                        <h4>Bank Transfer</h4>
                                        <p>Transfer ke rekening bank kami</p>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="VA" required>
                                <div class="payment-info">
                                    <div class="payment-icon">VA</div>
                                    <div class="payment-details">
                                        <h4>Virtual Account</h4>
                                        <p>Bayar melalui virtual account</p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="order-total">
                            <div class="total-row">
                                <span class="total-label">Total Item:</span>
                                <span class="total-value"><?= $total_jumlah ?> buku</span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Subtotal:</span>
                                <span class="total-value">Rp <?= number_format($total_harga, 0, ',', '.') ?></span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Ongkos Kirim:</span>
                                <span class="total-value">Gratis</span>
                            </div>
                            <div class="total-row grand-total">
                                <span class="total-label">Total Pembayaran:</span>
                                <span class="total-value">Rp <?= number_format($total_harga, 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn-checkout">
                            Konfirmasi & Bayar
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php require "footer.php"; ?>
    <script>
        // Add interactivity to payment methods
        document.addEventListener('DOMContentLoaded', function () {
            const paymentMethods = document.querySelectorAll('.payment-method');

            paymentMethods.forEach(method => {
                const radio = method.querySelector('input[type="radio"]');

                radio.addEventListener('change', function () {
                    // Remove selected class from all methods
                    paymentMethods.forEach(m => m.classList.remove('selected'));

                    // Add selected class to current method
                    if (this.checked) {
                        method.classList.add('selected');
                    }
                });

                // Add click event to the entire label
                method.addEventListener('click', function (e) {
                    if (e.target !== radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            });
        });
    </script>
</body>

</html>