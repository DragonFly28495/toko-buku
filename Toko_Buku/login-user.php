<?php
// --- BAGIAN 1: PERSIAPAN AWAL ---

// Mulai session untuk menyimpan data login user
// Session seperti 'kartu anggota' yang dikasih ke user setelah login sukses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan ke file config yang berisi setting database
// Seperti memasang koneksi ke 'pusat data'
include "config.php";

// --- BAGIAN 2: CEK APAKAH USER MENGIRIM FORM LOGIN ---

// Cek: apakah user mengirim form login? 
// $_SERVER['REQUEST_METHOD'] === 'POST' artinya form dikirim via POST
// isset($_POST['username'], $_POST['password']) artinya kedua field terisi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    
    // Ambil data dari form dan bersihkan
    $username = trim($_POST['username']);  // trim() buang spasi di kiri-kanan
    $password = $_POST['password'];        // ambil password

    // --- BAGIAN 3: CEK KE DATABASE ---
    
    // Siapkan query untuk cek username di database
    // Tanda ? untuk keamanan (anti SQL Injection)
    $stmt = $conn->prepare("SELECT id_pengguna, username, password FROM daftar_pengguna WHERE username = ?");
    
    // Isi tanda ? dengan username yang diinput user
    // "s" artinya tipe data string
    $stmt->bind_param("s", $username);
    
    // Jalankan query ke database
    $stmt->execute();
    
    // Ambil hasil query
    $result = $stmt->get_result();

    // --- BAGIAN 4: PROSES VERIFIKASI ---
    
    // Cek apakah username ditemukan di database
    if ($result && $result->num_rows > 0) {
        // Username DITEMUKAN, ambil data user
        $data = $result->fetch_assoc();

        // VERIFIKASI PASSWORD: cocokkan password input dengan database
        // ⚠️ PERHATIAN: Ini cara TIDAK AMAN! Lihat penjelasan di bawah
        if ($password == $data['password']) {
            
            // --- BAGIAN 5: LOGIN SUKSES ---
            
            // Simpan data user ke session (seperti kasih kartu anggota)
            $_SESSION['id_pengguna'] = $data['id_pengguna'];
            $_SESSION['username'] = $data['username'];

            // Tentukan kemana user akan diarahkan setelah login
            if (isset($_SESSION['redirect_url'])) {
                // Jika ada URL sebelumnya yang disimpan, arahkan kesana
                $redirect_url = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);  // hapus dari memory
                header("Location: $redirect_url");
            } else {
                // Jika tidak, arahkan ke halaman beranda default
                header("Location: User/beranda-user.php");
            }
            exit; // Stop eksekusi script setelah redirect
            
        } else {
            // Password SALAH
            $error = "Password tidak sesuai.";
        }
    } else {
        // Username TIDAK DITEMUKAN di database
        $error = "Username tidak ditemukan.";
    }
    
    // Tutup koneksi query untuk hemat resource
    $stmt->close();
  
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/style_login-user.css">
    <title>Login User</title>
</head>
<body>
    <div class="container">
        <div class="image-section">
            <div class="image-content">
                <h3>Selamat Datang di Toko Buku</h3>
                <!-- Container untuk gambar -->
                <div class="image-container">
                    <img src="Aset/IconLivro.png" alt="Livro Icon">
                </div>
            </div>
        </div>
        
        
<div class="login-section">
  <div class="logo">
    <h1>Toko Buku</h1>
    <p>Masuk ke akun Anda</p>
  </div>

  <?php if (isset($error)): ?>
    <div class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="" autocomplete="off">
    <div class="form-group">
      <label for="username">Username:</label>
      <div class="input-with-icon">
        <i class="fas fa-user input-icon"></i>
        <input type="text" id="username" name="username" required placeholder="Masukkan username"
          value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="password">Password:</label>
      <div class="input-with-icon">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" id="password" name="password" required placeholder="Masukkan password">
        <button type="button" class="password-toggle" id="passwordToggle">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn">
      <i class="" style="margin-right: 8px;"></i>
      Masuk
    </button>

    <div class="link">
      <p>Belum punya akun? <a href="registrasi-user.php">Daftar di sini</a></p>
    </div>
  </form>
</div>
    </div>

    <script>
        // Toggle password visibility
const passwordToggle = document.getElementById('passwordToggle');
const passwordInput = document.getElementById('password');

passwordToggle.addEventListener('click', function () {
  const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  passwordInput.setAttribute('type', type);

  const icon = this.querySelector('i');
  icon.classList.toggle('fa-eye');
  icon.classList.toggle('fa-eye-slash');
});
        
        // Form validation and enhancement
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Harap isi semua field yang diperlukan.');
            }
        });
        
        // Add focus effects
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>