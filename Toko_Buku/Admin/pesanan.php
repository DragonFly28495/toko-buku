<?php
include __DIR__ . '/../config.php';

$pesananList = [];
$sql = "
    SELECT t.kd_transaksi, t.jumlah, b.judul, t.total_harga, t.metode_pembayaran, t.username, t.status 
    FROM transaksi t
    LEFT JOIN buku b ON t.id_buku = b.id_buku
    ORDER BY t.kd_transaksi DESC
";


$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $pesananList[] = $row;
    }
}

$total = count($pesananList);
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pesanan - Toko BUKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style-admin.css">
    <link rel="stylesheet" href="../CSS/style_pesanan-admin.css">
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Konten Utama -->
        <div class="main-content">
            <!-- Header Konten -->
            <div class="content-header">
                <h2>Daftar Pesanan</h2>
                <p>Kelola dan validasi pesanan dari pengguna</p>
            </div>

            <main class="content-body">
                <!-- Tabel Pesanan -->
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Transaksi</th>
                                    <th>Username</th>
                                    <th>Judul Buku</th>
                                    <th>Jumlah</th>
                                    <th>Total Harga</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pesananList)): ?>
                                    <?php $no = 1;
                                    foreach ($pesananList as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['kd_transaksi']) ?></td>
                                            <td><?= htmlspecialchars($row['username']) ?></td>
                                            <td><?= htmlspecialchars($row['judul']) ?></td>
                                            <td><?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                                                    <?= str_replace('_', ' ', htmlspecialchars($row['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($row['status'] == 'selesai' || $row['status'] == 'ditolak'): ?>
                                                        <!-- Status selesai atau ditolak - tidak bisa diubah -->
                                                        <span class="status-info">
                                                            <?= $row['status'] == 'selesai' ? 'Pesanan telah selesai' : 'Pesanan ditolak' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <!-- Status lainnya - bisa diubah -->
                                                        <select class="status-select"
                                                            data-kd="<?= htmlspecialchars($row['kd_transaksi']) ?>"
                                                            onchange="updateStatus(this)">
                                                            <option value="menunggu_validasi" <?= $row['status'] == 'menunggu_validasi' ? 'selected' : '' ?>>Menunggu Validasi</option>
                                                            <option value="dalam_pengemasan" <?= $row['status'] == 'dalam_pengemasan' ? 'selected' : '' ?>>Dalam Pengemasan</option>
                                                            <option value="dalam_perjalanan" <?= $row['status'] == 'dalam_perjalanan' ? 'selected' : '' ?>>Dalam Perjalanan</option>
                                                            <option value="ditolak" <?= $row['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                                        </select>

                                                        <?php if ($row['status'] == 'dalam_perjalanan'): ?>
                                                            <span class="status-info">Menunggu konfirmasi user</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="no-data">
                                            <i class="fas fa-shopping-cart"></i>
                                            Tidak ada pesanan untuk ditampilkan.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Informasi Total -->
                <div class="table-footer">
                    <div class="total-info">
                        <i class="fas fa-shopping-cart"></i>
                        Total Pesanan: <?= $total ?>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="footer">
                &copy; <?= date('Y') ?> Toko BUKU. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
        function updateStatus(select) {
            const kdTransaksi = select.getAttribute('data-kd');
            const newStatus = select.value;

            if (confirm(`Update status pesanan #${kdTransaksi} menjadi "${newStatus.replace(/_/g, ' ')}"?`)) {
                // Tampilkan loading state
                select.disabled = true;

                fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `kd_transaksi=${encodeURIComponent(kdTransaksi)}&status=${encodeURIComponent(newStatus)}`
                })
                    .then(response => response.text())
                    .then(data => {
                        console.log('Response:', data); // Untuk debugging

                        if (data.startsWith('success:')) {
                            alert(data.replace('success:', '').trim());
                            location.reload();
                        } else if (data.startsWith('error:')) {
                            alert('Error: ' + data.replace('error:', '').trim());
                            location.reload();
                        } else {
                            alert('Error: Response tidak dikenali: ' + data);
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error: Gagal mengupdate status. Periksa koneksi internet.');
                        location.reload();
                    });
            }
        }

        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            const chevron = dropdown.previousElementSibling.querySelector('.chevron');

            dropdown.classList.toggle('show');
            chevron.classList.toggle('fa-chevron-down');
            chevron.classList.toggle('fa-chevron-up');
        }
    </script>
</body>

</html>