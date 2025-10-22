<?php
include __DIR__ . '/../config.php';

if (!isset($_GET['kd'])) {
    die('Kode transaksi tidak ditemukan.');
}

$kd_transaksi = $_GET['kd'];

// Ambil data transaksi
$stmt = $conn->prepare("SELECT id_buku, jumlah FROM transaksi WHERE kd_transaksi = ?");
$stmt->bind_param("s", $kd_transaksi);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

if ($data) {
    $id_buku = $data['id_buku'];
    $jumlah  = $data['jumlah'];

    $conn->begin_transaction();

    try {
        // Kembalikan stok
        $update = $conn->prepare("UPDATE buku SET stok = stok + ? WHERE id_buku = ?");
        $update->bind_param("ii", $jumlah, $id_buku);
        $update->execute();
        $update->close();

        // Hapus transaksi
        $delete = $conn->prepare("DELETE FROM transaksi WHERE kd_transaksi = ?");
        $delete->bind_param("s", $kd_transaksi);
        $delete->execute();
        $delete->close();

        $conn->commit();
        header("Location: pesanan.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "Gagal menghapus pesanan: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "Transaksi tidak ditemukan.";
}
?>
