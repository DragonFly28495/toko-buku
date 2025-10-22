<?php
include __DIR__ . '/../config.php';

if (isset($_GET['id_pengguna'])) {
    $id_pengguna = $_GET['id_pengguna'];

    $stmt = $conn->prepare("DELETE FROM daftar_pengguna WHERE id_pengguna = ?");
    $stmt->bind_param("s", $id_pengguna);

    if ($stmt->execute()) {
        header('Location: daftar_pengguna-admin.php');
        exit;
    } else {
        die("Gagal menghapus pengguna.");
    }
} else {
    die("Akses dilarang.");
}
?>
