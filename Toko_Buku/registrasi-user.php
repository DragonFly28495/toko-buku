<?php
session_start();
include "config.php";

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_repeat = $_POST['password_repeat'];

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($password_repeat)) {
        $pesan = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = 'Format email tidak valid.';
    } elseif ($password !== $password_repeat) {
        $pesan = 'Password dan konfirmasi tidak cocok.';
    } else {
        // Simpan password langsung (plain text)
        $stmt = $conn->prepare("INSERT INTO daftar_pengguna (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: login-user.php");
            exit;
        } else {
            $pesan = 'Registrasi gagal. Username atau email mungkin sudah digunakan.';
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/style_registrasi-user.css">
    <title>Form Registrasi</title>
</head>
<body>
    <div class="container">
        <div class="image-section">
            <div class="image-content">
                <h3>Bergabunglah Dengan Kami</h3>
                <!-- Container untuk gambar -->
                <div class="image-container">
                    <img src="Aset/IconLivro.png" alt="Livro Icon">
                </div>
            </div>
        </div>
        
        <div class="register-section">
            <a href="login-user.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
            </a>
            
            <div class="logo">
                <h1>Daftar Akun Baru</h1>
           </div>
            
            <?php if (!empty($pesan)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($pesan) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off" id="registerForm">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" id="username" required placeholder="Masukkan Username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>
                    <div class="input-feedback" id="usernameFeedback"></div>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" required placeholder="Masukkan Email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    <div class="input-feedback" id="emailFeedback"></div>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" required placeholder="Masukkan Password">
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_repeat">Ulangi Password:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password_repeat" id="password_repeat" required placeholder="Ulangi Password">
                        <button type="button" class="password-toggle" id="passwordRepeatToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="input-feedback" id="passwordMatchFeedback"></div>
                </div>

                <button type="submit" class="btn">
                    <i class=""></i>
                    Daftar
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const passwordRepeatToggle = document.getElementById('passwordRepeatToggle');
        const passwordRepeatInput = document.getElementById('password_repeat');
        
        function setupPasswordToggle(toggle, input) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }
        
        setupPasswordToggle(passwordToggle, passwordInput);
        setupPasswordToggle(passwordRepeatToggle, passwordRepeatInput);
        
        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const hints = {
                length: document.getElementById('hintLength'),
                upper: document.getElementById('hintUpper'),
                lower: document.getElementById('hintLower'),
                number: document.getElementById('hintNumber')
            };
            
            let strength = 0;
            let validHints = 0;
            
            // Check password length
            if (password.length >= 8) {
                strength += 25;
                hints.length.classList.add('valid');
                hints.length.querySelector('i').className = 'fas fa-check-circle';
                validHints++;
            } else {
                hints.length.classList.remove('valid');
                hints.length.querySelector('i').className = 'fas fa-times-circle';
            }
            
            // Check for uppercase letters
            if (/[A-Z]/.test(password)) {
                strength += 25;
                hints.upper.classList.add('valid');
                hints.upper.querySelector('i').className = 'fas fa-check-circle';
                validHints++;
            } else {
                hints.upper.classList.remove('valid');
                hints.upper.querySelector('i').className = 'fas fa-times-circle';
            }
            
            // Check for lowercase letters
            if (/[a-z]/.test(password)) {
                strength += 25;
                hints.lower.classList.add('valid');
                hints.lower.querySelector('i').className = 'fas fa-check-circle';
                validHints++;
            } else {
                hints.lower.classList.remove('valid');
                hints.lower.querySelector('i').className = 'fas fa-times-circle';
            }
            
            // Check for numbers
            if (/[0-9]/.test(password)) {
                strength += 25;
                hints.number.classList.add('valid');
                hints.number.querySelector('i').className = 'fas fa-check-circle';
                validHints++;
            } else {
                hints.number.classList.remove('valid');
                hints.number.querySelector('i').className = 'fas fa-times-circle';
            }
            
            // Update strength bar
            strengthBar.className = 'strength-bar';
            if (strength <= 25) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 75) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
        
        // Password match checker
        passwordRepeatInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const passwordRepeat = this.value;
            const feedback = document.getElementById('passwordMatchFeedback');
            
            if (passwordRepeat === '') {
                feedback.textContent = '';
                feedback.className = 'input-feedback';
            } else if (password === passwordRepeat) {
                feedback.textContent = 'Password cocok';
                feedback.className = 'input-feedback valid';
                feedback.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok';
            } else {
                feedback.textContent = 'Password tidak cocok';
                feedback.className = 'input-feedback invalid';
                feedback.innerHTML = '<i class="fas fa-times-circle"></i> Password tidak cocok';
            }
        });
        
        // Real-time validation
        document.getElementById('username').addEventListener('input', function() {
            const feedback = document.getElementById('usernameFeedback');
            if (this.value.length < 3) {
                feedback.textContent = 'Username harus minimal 3 karakter';
                feedback.className = 'input-feedback invalid';
                feedback.innerHTML = '<i class="fas fa-times-circle"></i> Username harus minimal 3 karakter';
            } else {
                feedback.textContent = 'Username tersedia';
                feedback.className = 'input-feedback valid';
                feedback.innerHTML = '<i class="fas fa-check-circle"></i> Username tersedia';
            }
        });
        
        document.getElementById('email').addEventListener('input', function() {
            const feedback = document.getElementById('emailFeedback');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(this.value)) {
                feedback.textContent = 'Format email valid';
                feedback.className = 'input-feedback valid';
                feedback.innerHTML = '<i class="fas fa-check-circle"></i> Format email valid';
            } else {
                feedback.textContent = 'Format email tidak valid';
                feedback.className = 'input-feedback invalid';
                feedback.innerHTML = '<i class="fas fa-times-circle"></i> Format email tidak valid';
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const passwordRepeat = passwordRepeatInput.value;
            
            if (password !== passwordRepeat) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok. Silakan periksa kembali.');
                passwordRepeatInput.focus();
            }
        });
    </script>
</body>
</html>