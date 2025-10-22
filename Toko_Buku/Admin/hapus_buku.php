<?php

include __DIR__ . '/../config.php';

if( isset($_GET['id_buku']) ){

    $id_buku = mysqli_real_escape_string($conn, $_GET['id_buku']);
    $sql = "DELETE FROM buku WHERE id_buku='$id_buku'";
    $query = mysqli_query($conn, $sql); 

    if( $query ){
        header('Location: daftar_buku-admin.php');
    } else {
        die("gagal menghapus...");
    }

} else {
    die("akses dilarang...");
}



?>