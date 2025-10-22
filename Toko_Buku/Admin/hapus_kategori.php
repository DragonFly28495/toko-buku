<?php

include __DIR__ . '/../config.php';

if( isset($_GET['id_kategori']) ){

    $id_kategori = mysqli_real_escape_string($conn, $_GET['id_kategori']);
    $sql = "DELETE FROM kategori WHERE id_kategori='$id_kategori'";
    $query = mysqli_query($conn, $sql); 

    if( $query ){
        header('Location: kategori-admin.php');
    } else {
        die("gagal menghapus...");
    }

} else {
    die("akses dilarang...");
}
?>