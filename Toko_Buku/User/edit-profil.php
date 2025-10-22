<?php
/**
 * @noinspection PhpUndefinedFunctionInspection
 * @noinspection PhpUndefinedClassInspection
 * @var \mysqli $conn
 */
// edit-profil.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config.php';

// Cek login
// Pastikan hanya pengguna yang sudah login yang bisa mengakses halaman ini.
// Jika belum ada data session, arahkan ke halaman login.
if (!isset($_SESSION['id_pengguna']) || !isset($_SESSION['username'])) {
    header('Location: ../login-user.php');
    exit;
}

// Ambil data dasar dari session
// id_pengguna: digunakan untuk mengambil dan memperbarui baris pengguna di database
// username: ditampilkan dan dapat diubah oleh pengguna
$user_id = $_SESSION['id_pengguna'];
$username = $_SESSION['username'];

// Variabel untuk menyimpan pesan sukses/error yang akan ditampilkan di UI
$success_message = '';
$error_message = '';

// Ambil data user saat ini (termasuk alamat dan no_telp)
// Tujuannya: menampilkan nilai saat ini di form sehingga pengguna dapat melihat dan mengubahnya
$user_data = [];
try {
    $stmt = $conn->prepare("SELECT id_pengguna, username, password, email, foto_profil, alamat, no_telp FROM daftar_pengguna WHERE id_pengguna = ?");
    // bind_param dengan 'i' karena id_pengguna bertipe integer
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Ambil baris pertama sebagai array asosiatif
        $user_data = $result->fetch_assoc();
    }
    $stmt->close();
} catch (Exception $e) {
    // Jika terjadi kesalahan saat mengambil data, simpan pesan error untuk ditampilkan
    $error_message = "Gagal memuat data profil: " . $e->getMessage();
}

// Proses update profil: hanya dijalankan bila form disubmit dengan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_alamat = trim($_POST['alamat'] ?? '');
    $new_no_telp = trim($_POST['no_telp'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi input dasar (cek kosong dan format email)
    // Catatan: validasi ini sederhana; bisa ditingkatkan sesuai kebutuhan (panjang, karakter, dll.)
    if (empty($new_username) || empty($new_email)) {
        $error_message = "Username dan email wajib diisi.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        $conn->begin_transaction();
        try {
            // Jika user ingin mengganti password
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    throw new \Exception("Password saat ini harus diisi untuk mengubah password.");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new \Exception("Password baru dan konfirmasi password tidak cocok.");
                }
                
                if (\strlen($new_password) < 5) {
                    throw new \Exception("Password baru minimal 5 karakter.");
                }
                
                // Verifikasi password saat ini
                $check_stmt = $conn->prepare("SELECT password FROM daftar_pengguna WHERE id_pengguna = ?");
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $db_data = $check_result->fetch_assoc();
                    // Password masih disimpan plain text, jika mau di-hash nanti bisa diubah
                    if ($current_password !== $db_data['password']) {
                        throw new \Exception("Password saat ini salah.");
                    }
                }
                $check_stmt->close();
                
                $password_to_update = $new_password;
            } else {
                $password_to_update = null;
            }
            
            // Handle upload foto profil
            // Jika pengguna mengunggah file, lakukan beberapa validasi (ekstensi, ukuran),
            // beri nama file unik, dan pindahkan file ke folder uploads/profil/
            // Jika tidak mengunggah, tetap gunakan nama file foto lama.
            $foto_profil = $user_data['foto_profil']; // Tetap gunakan foto lama jika tidak diubah
            
            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/profil/';
                
                // Buat folder jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_info = \pathinfo($_FILES['foto_profil']['name']);
                $file_extension = \strtolower($file_info['extension']);
                
                // Validasi tipe file berdasarkan ekstensi sederhana
                // Catatan: untuk keamanan lebih baik cek MIME type juga.
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!\in_array($file_extension, $allowed_extensions)) {
                    throw new \Exception("Hanya file JPG, JPEG, PNG, dan GIF yang diizinkan.");
                }
                
                // Validasi ukuran file (max 2MB)
                if ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                    throw new \Exception("Ukuran file maksimal 2MB.");
                }
                
                // Hapus file foto lama jika ada di server (agar tidak menumpuk file yang tidak terpakai)
                if (!empty($user_data['foto_profil']) && \file_exists($upload_dir . $user_data['foto_profil'])) {
                    \unlink($upload_dir . $user_data['foto_profil']);
                }
                
                // Generate nama file unik
                $new_filename = 'profil_' . $user_id . '_' . \time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (\move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
                    $foto_profil = $new_filename;
                } else {
                    throw new Exception("Gagal mengupload foto profil.");
                }
            }
            
            // Update data user (termasuk alamat dan no_telp)
            // Gunakan prepared statement untuk mencegah SQL injection.
            if ($password_to_update) {
                // Jika ada password baru, sertakan kolom password
                $update_stmt = $conn->prepare("UPDATE daftar_pengguna SET username = ?, email = ?, alamat = ?, no_telp = ?, password = ?, foto_profil = ? WHERE id_pengguna = ?");
                $update_stmt->bind_param("ssssssi", $new_username, $new_email, $new_alamat, $new_no_telp, $password_to_update, $foto_profil, $user_id);
            } else {
                // Jika tidak ada password baru, jangan ubah kolom password
                $update_stmt = $conn->prepare("UPDATE daftar_pengguna SET username = ?, email = ?, alamat = ?, no_telp = ?, foto_profil = ? WHERE id_pengguna = ?");
                $update_stmt->bind_param("sssssi", $new_username, $new_email, $new_alamat, $new_no_telp, $foto_profil, $user_id);
            }
            
            if ($update_stmt->execute()) {
                // Jika update berhasil, perbarui session (jika username diubah)
                if ($new_username !== $username) {
                    $_SESSION['username'] = $new_username;
                }

                // Commit transaksi agar perubahan tersimpan di database
                $conn->commit();
                $success_message = "Profil berhasil diperbarui!";
                
                // Refresh data user
                $refresh_stmt = $conn->prepare("SELECT id_pengguna, username, email, foto_profil, alamat, no_telp FROM daftar_pengguna WHERE id_pengguna = ?");
                $refresh_stmt->bind_param("i", $user_id);
                $refresh_stmt->execute();
                $refresh_result = $refresh_stmt->get_result();
                
                if ($refresh_result->num_rows > 0) {
                    // Ambil kembali data terbaru supaya UI menampilkan nilai yang baru
                    $user_data = $refresh_result->fetch_assoc();
                }
                $refresh_stmt->close();
            } else {
                throw new \Exception("Gagal memperbarui profil.");
            }
            
            $update_stmt->close();
            
        } catch (Exception $e) {
            // Jika ada error selama proses, rollback transaksi agar DB kembali ke keadaan semula
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}

// Tentukan path foto profil untuk ditampilkan
$display_foto_profil = '../Aset/profil_default.png';
if (!empty($user_data['foto_profil'])) {
    $display_foto_profil = '../uploads/profil/' . $user_data['foto_profil'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Toko Buku</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --gray-color: #7f8c8d;
            --border-color: #eaeaea;
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 6px 20px rgba(0, 0, 0, 0.12);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }

        /* ===== MAIN CONTENT ===== */
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            flex: 1;
        }

        .section-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-color);
            font-size: 2.2rem;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--success-color));
            border-radius: 2px;
        }

        /* ===== PROFILE LAYOUT ===== */
        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* ===== PROFILE CARD ===== */
        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 2rem;
            transition: var(--transition);
        }

        .profile-card:hover {
            box-shadow: var(--shadow-medium);
        }

        /* ===== PROFILE SIDEBAR ===== */
        .profile-sidebar {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-avatar {
            position: relative;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 1.5rem;
            border: 4px solid var(--primary-color);
            background: var(--light-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 4rem;
            font-weight: bold;
        }

        .profile-info h2 {
            margin: 0 0 0.5rem 0;
            color: var(--dark-color);
            font-size: 1.5rem;
        }

        .profile-info p {
            margin: 0;
            color: var(--gray-color);
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray-color);
        }

        /* ===== FORM STYLES ===== */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 100px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: block;
            padding: 0.75rem 1rem;
            background: var(--light-color);
            border: 1px dashed var(--border-color);
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-label:hover {
            background: #e9ecef;
            border-color: var(--primary-color);
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray-color);
        }

        /* ===== PASSWORD INPUT STYLES ===== */
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-wrapper .form-input {
            padding-right: 3rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .toggle-password:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .toggle-password.active {
            color: var(--primary-color);
        }

        .password-note {
            font-size: 0.9rem;
            color: var(--gray-color);
            margin-top: 0.5rem;
        }

        /* ===== BUTTONS ===== */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex: 1;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn-secondary {
            background: var(--light-color);
            color: var(--dark-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .btn-change-photo {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 1rem;
            transition: var(--transition);
        }

        .btn-change-photo:hover {
            background: var(--secondary-color);
        }

        /* ===== ALERT MESSAGES ===== */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: bold;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* ===== SECTION HEADER ===== */
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-header i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .section-header h3 {
            margin: 0;
            color: var(--dark-color);
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                order: 2;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .section-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }

            .profile-card {
                padding: 1.5rem;
            }

            .profile-avatar {
                width: 140px;
                height: 140px;
            }

            .profile-stats {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php require "navbar.php"; ?>

    <div class="container">
        <h1 class="section-title">Edit Profil</h1>
        
        <!-- Alert Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-layout">
            <!-- Sidebar Profil -->
            <div class="profile-card profile-sidebar">
                <div class="profile-avatar">
                    <?php if (file_exists($display_foto_profil)): ?>
                        <img src="<?= $display_foto_profil ?>" 
                             alt="Foto Profil <?= htmlspecialchars($user_data['username'] ?? 'User') ?>" 
                             id="avatar-preview">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="avatar-preview">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($user_data['username'] ?? 'User') ?></h2>
                    <p><?= htmlspecialchars($user_data['email'] ?? '') ?></p>
                    <button type="button" class="btn-change-photo" onclick="document.getElementById('foto_profil').click()">
                        <i class="fas fa-camera"></i> Ganti Foto
                    </button>
                        <!-- NOTE: input file dipindahkan ke dalam form agar tersedia di saat submit -->
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Transaksi</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Keranjang</span>
                    </div>
                </div>
            </div>

            <!-- Form Edit Profil -->
            <div class="profile-card">
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Informasi Akun -->
                    <div class="section-header">
                        <i class="fas fa-user-circle"></i>
                        <h3>Informasi Akun</h3>
                    </div>
                    
                    <div class="form-row">
                        <!-- Username -->
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-input" 
                                   value="<?= htmlspecialchars($user_data['username'] ?? '') ?>" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <!-- Alamat dan Telepon -->
                    <div class="form-row">
                        <!-- Alamat -->
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" class="form-input" rows="3"><?= htmlspecialchars($user_data['alamat'] ?? '') ?></textarea>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="form-group">
                            <label for="no_telp">Nomor Telepon</label>
                            <input type="text" id="no_telp" name="no_telp" class="form-input" 
                                   value="<?= htmlspecialchars($user_data['no_telp'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="section-header">
                        <i class="fas fa-lock"></i>
                        <h3>Ubah Password</h3>
                    </div>
                    
                    <p class="password-note">Kosongkan jika tidak ingin mengubah password</p>
                    
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="current_password" name="current_password" class="form-input">
                            <button type="button" class="toggle-password" data-target="current_password">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="new_password" name="new_password" class="form-input">
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input">
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit buttons -->
                    <div class="form-group">
                        <label for="foto_profil">Foto Profil</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="foto_profil" name="foto_profil" class="file-input" accept="image/*">
                            <span id="file-name" class="file-name">Belum ada foto dipilih</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div> 
    
    <?php require "footer.php"; ?>
    
    <script>
        // Simple password toggle
        var toggleButtons = document.querySelectorAll('.toggle-password');
        
        for (var i = 0; i < toggleButtons.length; i++) {
            toggleButtons[i].addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye-slash';
                }
            });
        }
        
        // File input preview
        document.getElementById('foto_profil').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'Belum ada foto dipilih';
            document.getElementById('file-name').textContent = fileName;
            
            // Preview image
            if (e.target.files && e.target.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    var avatarPreview = document.getElementById('avatar-preview');
                    if (avatarPreview.classList.contains('avatar-placeholder')) {
                        avatarPreview.classList.remove('avatar-placeholder');
                        avatarPreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview Foto Profil">';
                    } else {
                        avatarPreview.querySelector('img').src = e.target.result;
                    }
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Simple alert hide
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            for (var i = 0; i < alerts.length; i++) {
                alerts[i].style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>