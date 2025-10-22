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

$user_id = $_SESSION['id_pengguna'];
$username = $_SESSION['username'];

// ✅ PERBAIKAN: Cek pesan sukses checkout
if (isset($_SESSION['checkout_success'])) {
    $checkout_success = $_SESSION['checkout_success'];
    unset($_SESSION['checkout_success']);
} else {
    $checkout_success = '';
}

// Proses pembatalan pesanan
if (isset($_GET['cancel']) && trim($_GET['cancel']) !== '') {
    $kd_transaksi = trim($_GET['cancel']);

    $conn->begin_transaction();
    try {
        // Ambil data transaksi yang akan dibatalkan
        $stmt = $conn->prepare("
            SELECT id_buku, jumlah 
            FROM transaksi 
            WHERE kd_transaksi = ? AND id_pengguna = ?
        ");
        $stmt->bind_param("si", $kd_transaksi, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Kembalikan stok buku
            while ($row = $result->fetch_assoc()) {
                $update_stmt = $conn->prepare("
                    UPDATE buku 
                    SET stok = stok + ? 
                    WHERE id_buku = ?
                ");
                $update_stmt->bind_param("ii", $row['jumlah'], $row['id_buku']);
                $update_stmt->execute();
                $update_stmt->close();
            }

            // Hapus transaksi
            $delete_stmt = $conn->prepare("
                DELETE FROM transaksi 
                WHERE kd_transaksi = ? AND id_pengguna = ?
            ");
            $delete_stmt->bind_param("si", $kd_transaksi, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();

            $conn->commit();
            $success_message = "Pesanan berhasil dibatalkan.";
        } else {
            throw new Exception("Pesanan tidak ditemukan.");
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Gagal membatalkan pesanan: " . $e->getMessage();
    }
}

// Ambil data pesanan user
$orders = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            kd_transaksi,
            id_buku,
            judul,
            jumlah,
            total_harga,
            metode_pembayaran,
            status,
            created_at
        FROM transaksi 
        WHERE id_pengguna = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Kelompokkan pesanan berdasarkan kd_transaksi
    while ($row = $result->fetch_assoc()) {
        $kd_transaksi = $row['kd_transaksi'];

        if (!isset($orders[$kd_transaksi])) {
            $orders[$kd_transaksi] = [
                'kd_transaksi' => $kd_transaksi,
                'items' => [],
                'total_harga' => 0,
                'metode_pembayaran' => $row['metode_pembayaran'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }

        $orders[$kd_transaksi]['items'][] = [
            'id_buku' => $row['id_buku'],
            'judul' => $row['judul'],
            'jumlah' => $row['jumlah'],
            'total_harga' => $row['total_harga']
        ];

        $orders[$kd_transaksi]['total_harga'] += $row['total_harga'];
    }

    $stmt->close();
} catch (Exception $e) {
    $error_message = "Gagal memuat data pesanan: " . $e->getMessage();
}

// Tambahkan proses konfirmasi pengiriman selesai
if (isset($_GET['complete']) && trim($_GET['complete']) !== '') {
    $kd_transaksi = trim($_GET['complete']);

    try {
        $stmt = $conn->prepare("
            UPDATE transaksi 
            SET status = 'selesai' 
            WHERE kd_transaksi = ? AND id_pengguna = ? AND status = 'dalam_perjalanan'
        ");
        $stmt->bind_param("si", $kd_transaksi, $user_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success_message = "Pesanan berhasil dikonfirmasi sebagai selesai!";
        } else {
            throw new Exception("Gagal mengkonfirmasi pesanan. Pastikan status dalam perjalanan.");
        }

        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Toko Buku</title>
    <link rel="stylesheet" href="style.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gray-color: #7f8c8d;
            --border-color: #eaeaea;
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 6px 20px rgba(0, 0, 0, 0.12);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }

        /* ===== MAIN CONTENT ===== */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            flex: 1;
        }

        .section-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-color);
            font-size: 2.2rem;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--success-color));
            border-radius: 2px;
        }

        /* ===== ORDER CARDS ===== */
        .order-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .order-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .order-id {
            font-size: 1.3rem;
            font-weight: bold;
        }

        .order-date {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        /* ===== STATUS STYLES ===== */
        .status-menunggu_validasi {
            background: var(--warning-color);
            color: white;
        }

        .status-dalam_pengemasan {
            background: var(--primary-color);
            color: white;
        }

        .status-dalam_perjalanan {
            background: #9b59b6;
            color: white;
        }

        .status-selesai {
            background: var(--success-color);
            color: white;
        }

        .status-ditolak {
            background: var(--danger-color);
            color: white;
        }

        /* ===== ORDER DETAILS ===== */
        .order-details {
            padding: 1.5rem;
        }

        .order-items {
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-title {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .item-meta {
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        .item-price {
            text-align: right;
            font-weight: 600;
        }

        /* ===== ORDER SUMMARY ===== */
        .order-summary {
            background: var(--light-color);
            padding: 1.5rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
        }

        .summary-label {
            color: var(--gray-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .summary-value {
            font-weight: bold;
        }

        .total-amount {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--dark-color);
        }

        /* ===== BUTTONS ===== */
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: var(--transition);
            display: inline-block;
            text-align: center;
            font-size: 0.9rem;
        }

        .btn-cancel {
            background: var(--danger-color);
            color: white;
        }

        .btn-cancel:hover {
            background: #c0392b;
        }

        .btn-complete {
            background: var(--success-color);
            color: white;
        }

        .btn-complete:hover {
            background: #219a52;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        /* ===== EMPTY STATE ===== */
        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .empty-icon {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 1rem;
        }

        .empty-text {
            color: var(--gray-color);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        /* ===== ALERT MESSAGES ===== */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: bold;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-summary {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .order-details {
                padding: 1rem;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .item-price {
                text-align: left;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }

            .order-header {
                padding: 1rem;
            }

            .order-id {
                font-size: 1.1rem;
            }

            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.85rem;
            }

            .empty-orders {
                padding: 2rem 1rem;
            }

            .empty-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>

<body>
    <?php require "navbar.php"; ?>

    <div class="container">
        <h1 class="section-title">Pesanan Saya</h1>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div class="empty-icon">📦</div>
                <h2 class="empty-text">Belum ada pesanan</h2>
                <p style="color: var(--gray-color); margin-bottom: 2rem;">Mulai berbelanja dan temukan buku favorit Anda!
                </p>
                <a href="buku-user.php" class="btn-primary">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-id">#<?= htmlspecialchars($order['kd_transaksi']) ?></div>
                            <div class="order-date">
                                Tanggal: <?= date('d F Y H:i', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        <div class="order-status status-<?= htmlspecialchars($order['status']) ?>">
                            <?= str_replace('_', ' ', htmlspecialchars($order['status'])) ?>
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="item-info">
                                        <div class="item-title"><?= htmlspecialchars($item['judul']) ?></div>
                                        <div class="item-meta">
                                            Jumlah: <?= htmlspecialchars($item['jumlah']) ?> buku
                                        </div>
                                    </div>
                                    <div class="item-price">
                                        Rp <?= number_format($item['total_harga'], 0, ',', '.') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-summary">
                            <div class="summary-item">
                                <div class="summary-label">Metode Pembayaran:</div>
                                <div class="summary-value"><?= htmlspecialchars($order['metode_pembayaran']) ?></div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-label">Total Pembayaran:</div>
                                <div class="total-amount">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></div>
                            </div>
                            <div class="order-actions">
                                <?php if (in_array($order['status'], ['menunggu_validasi', 'dalam_pengemasan'])): ?>
                                    <a href="pesanan-user.php?cancel=<?= urlencode($order['kd_transaksi']) ?>"
                                        class="btn btn-cancel"
                                        onclick="return confirm('Yakin ingin membatalkan pesanan #<?= htmlspecialchars($order['kd_transaksi']) ?>?')">
                                        Batalkan Pesanan
                                    </a>
                                <?php elseif ($order['status'] === 'dalam_perjalanan'): ?>
                                    <a href="pesanan-user.php?complete=<?= urlencode($order['kd_transaksi']) ?>"
                                        class="btn btn-complete"
                                        onclick="return confirm('Konfirmasi pesanan #<?= htmlspecialchars($order['kd_transaksi']) ?> sudah diterima?')">
                                        Konfirmasi Selesai
                                    </a>
                                <?php else: ?>
                                    <button class="btn" disabled>
                                        <?=
                                            $order['status'] === 'selesai' ? 'Pesanan Selesai' :
                                            ($order['status'] === 'ditolak' ? 'Pesanan Ditolak' :
                                                ucfirst(str_replace('_', ' ', $order['status'])))
                                            ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php require "footer.php"; ?>

    <script>
        // Konfirmasi pembatalan pesanan
        function confirmCancel(orderId) {
            return confirm(`Yakin ingin membatalkan pesanan #${orderId}?`);
        }

        // Auto-hide alert messages setelah 5 detik
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>

</html>