<?php
// Tambahkan di baris paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config.php';

// Cek login - tambahkan ini
if (!isset($_SESSION['id_pengguna']) || !isset($_SESSION['username'])) {
    header('Location: ../login-user.php');
    exit;
}

// TAMBAHKAN DEFINISI VARIABEL $isLogged
$isLogged = isset($_SESSION['id_pengguna']) && isset($_SESSION['username']);

// Load keranjang dari database
function loadCartFromDB($conn, $id_pengguna)
{
    $cart = [];
    $stmt = $conn->prepare("SELECT id_buku, jumlah FROM keranjang WHERE id_pengguna = ?");
    $stmt->bind_param("i", $id_pengguna);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $cart[$r['id_buku']] = (int) $r['jumlah'];
    }
    $stmt->close();
    return $cart;
}

// Inisialisasi session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Sinkronisasi session dengan database
if ($isLogged) {
    $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['id_pengguna']);
}

// Simpan item ke keranjang (insert/update)
function saveCartItemToDB($conn, $id_pengguna, $username, $id_buku, $jumlah)
{
    if ($jumlah <= 0) {
        $stmt = $conn->prepare("DELETE FROM keranjang WHERE id_pengguna = ? AND id_buku = ?");
        $stmt->bind_param("ii", $id_pengguna, $id_buku);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $stmt = $conn->prepare("UPDATE keranjang SET jumlah = ?, username = ? WHERE id_pengguna = ? AND id_buku = ?");
    $stmt->bind_param("isii", $jumlah, $username, $id_pengguna, $id_buku);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $ins = $conn->prepare("INSERT INTO keranjang (id_pengguna, username, jumlah, id_buku) VALUES (?, ?, ?, ?)");
        $ins->bind_param("isii", $id_pengguna, $username, $jumlah, $id_buku);
        $ins->execute();
        $ins->close();
    } else {
        $stmt->close();
    }
}

// Hapus item dari keranjang
function removeCartItemFromDB($conn, $id_pengguna, $id_buku)
{
    $stmt = $conn->prepare("DELETE FROM keranjang WHERE id_pengguna = ? AND id_buku = ?");
    $stmt->bind_param("ii", $id_pengguna, $id_buku);
    $stmt->execute();
    $stmt->close();
}

// Inisialisasi session cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Sinkronisasi session dengan database
if ($isLogged) {
    $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['id_pengguna']);
}

// Tambah item ke keranjang
if (isset($_GET['add']) && trim($_GET['add']) !== '') {
    $id_buku = (int) trim($_GET['add']);
    $currentQty = isset($_SESSION['cart'][$id_buku]) ? (int) $_SESSION['cart'][$id_buku] : 0;
    $newQty = $currentQty + 1;

    if ($isLogged) {
        saveCartItemToDB($conn, $_SESSION['id_pengguna'], $_SESSION['username'], $id_buku, $newQty);
    }
    $_SESSION['cart'][$id_buku] = $newQty;

    header('Location: keranjang-user.php');
    exit;
}

// Hapus item dari keranjang
if (isset($_GET['remove']) && trim($_GET['remove']) !== '') {
    $id_buku = (int) trim($_GET['remove']);
    if ($isLogged) {
        removeCartItemFromDB($conn, $_SESSION['id_pengguna'], $id_buku);
    }
    unset($_SESSION['cart'][$id_buku]);
    header('Location: keranjang-user.php');
    exit;
}

// Update jumlah item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $id_buku => $jumlah) {
        $id_buku = (int) $id_buku;
        $jumlah = (int) $jumlah;

        if ($isLogged) {
            saveCartItemToDB($conn, $_SESSION['id_pengguna'], $_SESSION['username'], $id_buku, $jumlah);
        }

        if ($jumlah <= 0) {
            unset($_SESSION['cart'][$id_buku]);
        } else {
            $_SESSION['cart'][$id_buku] = $jumlah;
        }
    }
    header('Location: keranjang-user.php');
    exit;
}

// Ambil data buku - BAGIAN YANG DIPERBAIKI
$cart_items = [];
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $safe_ids = array_map('intval', $ids);
    $in = implode(',', $safe_ids);
    
    // PERBAIKAN: gunakan harga_regular dan harga_setelah_diskon
    $sql = "SELECT id_buku, judul, harga_regular, harga_setelah_diskon, stok, cover_buku FROM buku WHERE id_buku IN ($in)";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $id_buku = $r['id_buku'];
            $qty = $_SESSION['cart'][$id_buku];
            
            // PERBAIKAN: tentukan harga jual yang benar
            $harga_jual = !empty($r['harga_setelah_diskon']) ? $r['harga_setelah_diskon'] : $r['harga_regular'];
            $subtotal = $harga_jual * $qty;
            $total_price += $subtotal;
            $cart_items[$id_buku] = array_merge($r, [
                'qty' => $qty, 
                'subtotal' => $subtotal,
                'harga_jual' => $harga_jual
            ]);
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang Belanja - Toko BUKU</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../CSS/style_keranjang-user.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 </head>

<body>
    <?php require "navbar.php"; ?>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">🛒 Keranjang Belanja</h1>
            <p class="page-subtitle">Kelola buku-buku pilihan Anda sebelum checkout</p>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-icon">📚</div>
                <h2 class="empty-title">Keranjang Anda Kosong</h2>
                <p class="empty-text">Belum ada buku yang ditambahkan ke keranjang belanja.</p>
                <a href="buku-user.php" class="btn-shopping">Mulai Belanja Sekarang</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="update_cart" value="1">

                <div class="cart-container">
                    <!-- Daftar Item -->
                    <div class="cart-items">
                        <div class="cart-header">
                            <h2>Item Belanja (<?= count($cart_items) ?> buku)</h2>
                        </div>

                        <?php foreach ($cart_items as $id => $item): ?>
                            <div class="cart-item">
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
                                    <div>
                                        <h3 class="item-title"><?= htmlspecialchars($item['judul']) ?></h3>
                                        <div class="item-meta">Stok tersedia: <?= htmlspecialchars($item['stok']) ?></div>
                                        <div class="item-price">
                                            Rp <?= number_format($item['harga_jual'], 0, ',', '.') ?>
                                            <?php if (!empty($item['harga_setelah_diskon']) && $item['harga_setelah_diskon'] < $item['harga_regular']): ?>
                                                <small style="text-decoration: line-through; color: #6c757d; margin-left: 0.5rem;">
                                                    Rp <?= number_format($item['harga_regular'], 0, ',', '.') ?>
                                                </small>
                                                <small style="color: #e74c3c; margin-left: 0.5rem;">
                                                    (Diskon <?= number_format((($item['harga_regular'] - $item['harga_setelah_diskon']) / $item['harga_regular']) * 100, 0) ?>%)
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="item-controls">
                                        <div class="quantity-control">
                                            <label for="qty_<?= $id ?>"
                                                style="font-weight: 500; margin-right: 0.5rem;">Jumlah:</label>
                                            <input class="qty-input" type="number" min="0"
                                                max="<?= htmlspecialchars($item['stok']) ?>"
                                                name="qty[<?= htmlspecialchars($id) ?>]"
                                                value="<?= htmlspecialchars($item['qty']) ?>" id="qty_<?= $id ?>">
                                        </div>

                                        <a href="keranjang-user.php?remove=<?= urlencode($id) ?>" class="btn-remove">
                                            Hapus
                                        </a>

                                        <div class="item-subtotal">
                                            Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="cart-actions">
                            <button type="submit" class="btn-update">🔄 Perbarui Keranjang</button>
                            <a href="buku-user.php" class="btn-continue" style="width: auto; display: inline-block;">
                                ← Lanjutkan Belanja
                            </a>
                        </div>
                    </div>

                    <!-- Ringkasan Belanja -->
                    <div class="cart-summary">
                        <h3 class="summary-title">Ringkasan Belanja</h3>

                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value">Rp <?= number_format($total_price, 0, ',', '.') ?></span>
                        </div>

                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>Rp <?= number_format($total_price, 0, ',', '.') ?></span>
                        </div>

                        <a href="checkout.php" class="btn-checkout">
                            🛍️ Lanjut ke Checkout
                        </a>

                        <a href="buku-user.php" class="btn-continue">
                            + Tambah Buku Lain
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php require "footer.php"; ?>
</body>

</html>