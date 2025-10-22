<?php
// logout.php - File untuk proses logout yang aman

// Mulai session untuk akses data session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua data session
session_unset();

// Hancurkan session
session_destroy();

// Redirect ke halaman login
header("Location: login-user.php");
exit;
?>