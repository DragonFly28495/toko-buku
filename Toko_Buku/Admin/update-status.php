<?php
session_start();
include __DIR__ . '/../config.php';

// Cek admin login
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['logged_in'])) {
    echo "error: Akses ditolak. Silakan login terlebih dahulu.";
    exit;
}

if (isset($_POST['kd_transaksi']) && isset($_POST['status'])) {
    $kd_transaksi = trim($_POST['kd_transaksi']);
    $status = trim($_POST['status']);
    
    // Validasi status
    $allowed_status = ['menunggu_validasi', 'dalam_pengemasan', 'dalam_perjalanan', 'ditolak', 'selesai'];
    if (!in_array($status, $allowed_status)) {
        echo "error: Status tidak valid";
        exit;
    }
    
    $conn->begin_transaction();
    try {
        // Jika status ditolak, kembalikan stok buku
        if ($status === 'ditolak') {
            // Ambil data buku dan jumlah yang dipesan
            $stmt = $conn->prepare("
                SELECT id_buku, jumlah 
                FROM transaksi 
                WHERE kd_transaksi = ?
            ");
            $stmt->bind_param("s", $kd_transaksi);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Kembalikan stok untuk setiap buku dalam transaksi
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
            $stmt->close();
        }
        
        // Update status transaksi
        $update_transaksi = $conn->prepare("UPDATE transaksi SET status = ? WHERE kd_transaksi = ?");
        $update_transaksi->bind_param("ss", $status, $kd_transaksi);
        
        if ($update_transaksi->execute()) {
            $conn->commit();
            echo "success: Status berhasil diupdate menjadi " . str_replace('_', ' ', $status);
        } else {
            throw new Exception("Gagal mengupdate status transaksi");
        }
        
        $update_transaksi->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo "error: " . $e->getMessage();
    }
} else {
    echo "error: Data tidak lengkap";
}
?>