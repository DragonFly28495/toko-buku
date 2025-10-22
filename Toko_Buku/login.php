<?php
session_start(); // Memulai session untuk menyimpan data login admin
include "config.php"; // Menghubungkan ke file config.php

if (isset($_POST['username']) && isset($_POST['password'])) { // Melakukan variabel perbandingan untuk username dan password
    $username = trim($_POST['username']); // Membersihkan input username dari spasi berlebih
    $password = $_POST['password']; // Mengambil input password apa adanya

    $stmt = $conn->prepare("SELECT id, nama, password FROM admins WHERE nama = ?"); // Mempersiapkan query untuk mencari data admin berdasarkan username | Prepare memastikan apahkah inputan aman
    $stmt->bind_param("s", $username); // s=string | bind_param mengikat parameter ke dalam query yang telah dipersiapkan
    $stmt->execute(); // Eksekusi query
    $result = $stmt->get_result(); // Mendapatkan hasil dari eksekusi query

    if ($result && $result->num_rows > 0) { // Mengecek apakah username ditemukan di database
        $data = $result->fetch_assoc(); // Mengambil data admin dari hasil query | Fetch_assoc mengembalikan baris hasil sebagai array asosiatif
        // Mengecek apakah password yang dimasukkan sesuai dengan yang ada di database
        if (password_verify($password, $data['password']) || $password === $data['password']) {
            // Jika password sesuai, simpan data admin ke dalam session
            // Jika password di database masih dalam format lama (plain text), perbarui ke format hash
            if ($password === $data['password']) { // Cek apakah password yang dimasukkan sama dengan password di database (plain text)
                // Jika sama, buat hash baru dan perbarui di database
                $new_hash = password_hash($password, PASSWORD_DEFAULT); // Membuat hash baru dari password
                $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?"); // Mempersiapkan query untuk memperbarui password di database | Prepare memastikan apahkah inputan aman
                $update_stmt->bind_param("si", $new_hash, $data['id']); // i=integer | Mengikat parameter hash baru dan id admin ke dalam query yang telah dipersiapkan
                $update_stmt->execute(); // Eksekusi query pembaruan
                $update_stmt->close(); // Menutup statement pembaruan setelah selesai digunakan
            }
            // Simpan data admin ke dalam session
            $_SESSION['admin_id'] = $data['id']; // Menyimpan id admin ke dalam session
            $_SESSION['admin_nama'] = $data['nama']; // Menyimpan nama admin ke dalam session
            $_SESSION['logged_in'] = true; // Berhasil Logged_in atau masuk

            header("Location: Admin/beranda-admin.php"); // Berpindah ke dalam folder Admin lalu masuk ke file beranda-admin.php
            exit;
        } else {
            echo '<script>alert("Password tidak sesuai."); window.location.href="login.php";</script>'; // Muncul pesan dan berpindah ke login.php
        }
    } else {
        echo '<script>alert("Username tidak ditemukan."); window.location.href="login.php";</script>'; // Muncul pesan dan berpindah ke login.php
    }

    $stmt->close(); // Menutup statement setelah selesai digunakan
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="CSS/style_login-admin.css">
</head>

<body>
    <div class="form-login">
        <div class="card">
            <div class="card-header">
                <h4 class="text-center">Login Admin</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="" autocomplete="off">
                    <div class="form-group">
                        <label for="username" class="form-label">Username :</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus
                            placeholder="Masukkan username">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password :</label>
                        <input type="password" class="form-control" id="password" name="password" required
                            placeholder="Masukkan password">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="showPassword" onclick="showHide()">
                            <label class="" for="showPassword">Tampilkan Password</label>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn-login">Login</button>
                </form>

                <div class="login-footer">
                    Sistem Administrasi Toko Buku
                </div>
            </div>
        </div>
    </div>

    <script>
        function showHide() {
            var passwordInput = document.getElementById('password');
            var showPasswordCheckbox = document.getElementById('showPassword');

            if (showPasswordCheckbox.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        }

        // Add some interactivity to form elements
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.form-control');

            inputs.forEach(input => {
                // Add focus effect
                input.addEventListener('focus', function () {
                    this.parentElement.classList.add('focused');
                });

                // Remove focus effect
                input.addEventListener('blur', function () {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>

</html>