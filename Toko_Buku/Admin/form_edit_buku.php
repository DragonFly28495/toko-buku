<?php
include __DIR__ . '/../config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pesan = '';
$id_buku = '';
$judul = '';
$penulis = '';
$penerbit = '';
$tanggal_terbit = '';
$harga_regular = '';
$diskon = '';
$stok = '';
$kategori = '';
$deskripsi = '';
$cover_buku = '';

// Ambil data kategori dari database
$kategori_list = [];
try {
    $stmt = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pesan = 'Gagal mengambil data kategori: ' . $e->getMessage();
}

// Ambil data buku berdasarkan ID
if (isset($_GET['id_buku'])) {
    $id_buku = trim($_GET['id_buku']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM buku WHERE id_buku = :id_buku");
        $stmt->execute([':id_buku' => $id_buku]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $judul = $data['judul'];
            $penulis = $data['penulis'];
            $penerbit = $data['penerbit'];
            $tanggal_terbit = $data['tanggal_terbit'];
            $harga_regular = $data['harga_regular'];
            $diskon = $data['diskon'];
            $stok = $data['stok'];
            $kategori = $data['kategori'];
            $deskripsi = $data['deskripsi'];
            $cover_buku = $data['cover_buku'];
        } else {
            $pesan = 'Buku tidak ditemukan.';
        }
    } catch (PDOException $e) {
        $pesan = 'Gagal mengambil data buku: ' . $e->getMessage();
    }
} else {
    $pesan = 'ID buku tidak diberikan.';
}

// Proses update buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id_buku = trim($_POST['id_buku']);
    $judul = trim($_POST['judul']);
    $penulis = trim($_POST['penulis']);
    $penerbit = trim($_POST['penerbit']);
    $tanggal_terbit = trim($_POST['tanggal_terbit']);
    $harga_regular = trim($_POST['harga_regular']);
    $diskon = !empty(trim($_POST['diskon'])) ? trim($_POST['diskon']) : 0;
    $stok = trim($_POST['stok']);
    $kategori = trim($_POST['kategori']);
    $deskripsi = trim($_POST['deskripsi']);

    // Hitung harga setelah diskon
    $harga_setelah_diskon = $harga_regular;
    if ($diskon > 0) {
        $harga_setelah_diskon = $harga_regular - ($harga_regular * $diskon / 100);
    }

    // Jika ada file baru diunggah
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $cover_buku = $_FILES['file']['name'];
        $target_dir = "../uploads/cover_buku/";
        $target_file = $target_dir . basename($cover_buku);
        
        // Validasi tipe file
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['file']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $pesan = "Hanya file gambar (JPEG, JPG, PNG, GIF, WEBP) yang diizinkan.";
        } else {
            if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                $pesan = "Gagal mengunggah file.";
            }
        }
    } else {
        // Jika tidak ada file baru, gunakan cover yang sudah ada
        $cover_buku = $_POST['cover_lama'];
    }

    // Validasi semua field wajib
    if ($id_buku === '' || $judul === '' || $penulis === '' || $penerbit === '' || $harga_regular === '' || $stok === '' || $kategori === '' || $deskripsi === '' || $cover_buku === '') {
        $pesan = 'Semua field wajib diisi.';
    } 
    // Validasi diskon antara 0 dan 100
    elseif ($diskon < 0 || $diskon > 100) {
        $pesan = 'Diskon harus antara 0 dan 100.';
    }
    else {
        try {
            $sql = "UPDATE buku SET 
                        judul = :judul, 
                        penulis = :penulis,
                        penerbit = :penerbit,
                        tanggal_terbit = :tanggal_terbit,
                        harga_regular = :harga_regular, 
                        diskon = :diskon,
                        harga_setelah_diskon = :harga_setelah_diskon,
                        stok = :stok, 
                        kategori = :kategori, 
                        deskripsi = :deskripsi, 
                        cover_buku = :cover_buku 
                    WHERE id_buku = :id_buku";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':judul' => $judul,
                ':penulis' => $penulis,
                ':penerbit' => $penerbit,
                ':tanggal_terbit' => $tanggal_terbit,
                ':harga_regular' => $harga_regular,
                ':diskon' => $diskon,
                ':harga_setelah_diskon' => $harga_setelah_diskon,
                ':stok' => $stok,
                ':kategori' => $kategori,
                ':deskripsi' => $deskripsi,
                ':cover_buku' => $cover_buku,
                ':id_buku' => $id_buku
            ]);

            header('Location: daftar_buku-admin.php');
            exit;
        } catch (PDOException $e) {
            $pesan = 'Gagal memperbarui data buku: ' . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Buku - Toko BUKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style-admin.css">
    <link rel="stylesheet" href="../CSS/style_buku-admin.css">
    <style>

        /* ===== FORM STYLES ===== */
        .btn-back {
            display: inline-block;
            margin: 1rem;
            padding: 0.75rem 1.5rem;
            gap: 0.5rem;
            text-decoration: none;
            color: #ffffffff;
            background-color: #6c757d;
            border-radius: 6px;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-weight: 500;
            max-width: max-content;
        }

        .btn-back:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .form-card {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #eaeaea;
        }

        .form-title {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-title i {
            color: #3498db;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #3498db;
            width: 16px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #eaeaea;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #eaeaea;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            min-height: 120px;
            resize: vertical;
            font-family: Arial, sans-serif;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-file {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px dashed #eaeaea;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background-color: #f8f9fa;
        }

        .form-file:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .current-file {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }

        .current-file img {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 0.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-error i {
            color: #dc3545;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .form-container {
                max-width: 100%;
                padding: 0 0.5rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .current-file img {
                width: 80px;
                height: 120px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .form-card {
                padding: 1rem;
            }

            .form-title {
                font-size: 1.25rem;
            }
        }
         .discount-preview {
            background-color: #e8f5e8;
            border: 1px solid #27ae60;
            border-radius: 4px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #155724;
        }
        
        .discount-preview.hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Konten Utama -->
        <div class="main-content">
            <!-- Header Konten -->
            <div class="content-header">
                <h2>Edit Buku</h2>
                <p>Perbarui informasi buku yang ada</p>
            </div>

            <a href="daftar_buku-admin.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Daftar
            </a>

            <main class="content-body">
                <div class="form-container">

                    <?php if ($pesan): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($pesan) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($id_buku !== '' && $judul !== ''): ?>
                        <div class="form-card">
                            <form action="" method="post" enctype="multipart/form-data">
                                <h2 style="text-align: center; margin-bottom: 1.5rem; color: #2c3e50;">
                                    <i class="fas fa-edit"></i> Edit Buku
                                </h2>

                                <input type="hidden" name="cover_lama" value="<?= htmlspecialchars($cover_buku) ?>">

                                <div class="form-group">
                                    <label for="id_buku" class="form-label">
                                        <i class="fas fa-hashtag"></i>
                                        ID Buku:
                                    </label>
                                    <input type="text" id="id_buku" name="id_buku" value="<?= htmlspecialchars($id_buku) ?>"
                                        class="form-input" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="judul" class="form-label">
                                        <i class="fas fa-book"></i>
                                        Judul Buku: <span class="required">*</span>
                                    </label>
                                    <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($judul) ?>"
                                        class="form-input" required>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="penulis" class="form-label">
                                            <i class="fas fa-user-edit"></i>
                                            Penulis: <span class="required">*</span>
                                        </label>
                                        <input type="text" id="penulis" name="penulis" value="<?= htmlspecialchars($penulis) ?>"
                                            class="form-input" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="penerbit" class="form-label">
                                            <i class="fas fa-building"></i>
                                            Penerbit: <span class="required">*</span>
                                        </label>
                                        <input type="text" id="penerbit" name="penerbit"
                                            value="<?= htmlspecialchars($penerbit) ?>" class="form-input" required>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="tanggal_terbit" class="form-label">
                                            <i class="fas fa-calendar-alt"></i>
                                            Tanggal Terbit:
                                        </label>
                                        <input type="date" id="tanggal_terbit" name="tanggal_terbit" class="form-input"
                                            value="<?= htmlspecialchars($tanggal_terbit) ?>">
                                        <div class="form-note">Opsional - format: YYYY-MM-DD</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="stok" class="form-label">
                                            <i class="fas fa-boxes"></i>
                                            Stok: <span class="required">*</span>
                                        </label>
                                        <input type="number" id="stok" name="stok" value="<?= htmlspecialchars($stok) ?>"
                                            class="form-input" required>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="harga_regular" class="form-label">
                                            <i class="fas fa-tag"></i>
                                            Harga Regular (Rp): <span class="required">*</span>
                                        </label>
                                        <input type="number" id="harga_regular" name="harga_regular" value="<?= htmlspecialchars($harga_regular) ?>"
                                            class="form-input" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="diskon" class="form-label">
                                            <i class="fas fa-percentage"></i>
                                            Diskon (%):
                                        </label>
                                        <input type="number" id="diskon" name="diskon" class="form-input"
                                            placeholder="Masukkan diskon dalam persentase" min="0" max="100"
                                            value="<?= htmlspecialchars($diskon) ?>">
                                        <div class="form-note">Opsional - antara 0 hingga 100</div>
                                        <div id="discountPreview" class="discount-preview <?= $diskon > 0 ? '' : 'hidden' ?>">
                                            <strong>Preview Harga:</strong><br>
                                            Harga Regular: Rp <span id="previewRegular"><?= number_format($harga_regular, 0, ',', '.') ?></span><br>
                                            Diskon: <span id="previewDiscount"><?= $diskon ?></span>%<br>
                                            Harga Setelah Diskon: Rp <span id="previewFinal"><?= number_format($harga_setelah_diskon ?? $harga_regular, 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="kategori" class="form-label">
                                        <i class="fas fa-tags"></i>
                                        Kategori: <span class="required">*</span>
                                    </label>
                                    <select id="kategori" name="kategori" class="form-select" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($kategori_list as $kategori_item): ?>
                                            <option value="<?= htmlspecialchars($kategori_item['nama_kategori']) ?>"
                                                <?= ($kategori == $kategori_item['nama_kategori']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($kategori_item['nama_kategori']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="deskripsi" class="form-label">
                                        <i class="fas fa-align-left"></i>
                                        Deskripsi: <span class="required">*</span>
                                    </label>
                                    <textarea id="deskripsi" name="deskripsi" class="form-textarea"
                                        required rows="5"><?= htmlspecialchars($deskripsi) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="file" class="form-label">
                                        <i class="fas fa-image"></i>
                                        Cover Buku:
                                    </label>
                                    <input type="file" id="file" name="file" class="form-file" accept="image/*">
                                    <div class="form-note">Biarkan kosong jika tidak ingin mengganti cover. Format: JPG, JPEG, PNG, GIF, WEBP (maks. 2MB)</div>

                                    <div class="current-file">
                                        <small><strong>Cover saat ini:</strong> <?= htmlspecialchars($cover_buku) ?></small>
                                        <?php if ($cover_buku): ?>
                                            <br>
                                            <img src="../uploads/cover_buku/<?= htmlspecialchars($cover_buku) ?>" 
                                                 alt="Cover <?= htmlspecialchars($judul) ?>"
                                                 onerror="this.style.display='none'"
                                                 style="max-width: 150px; margin-top: 0.5rem; border-radius: 4px;">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" name="edit" class="btn-primary">
                                        <i class="fas fa-save"></i>
                                        Simpan Perubahan
                                    </button>
                                    <a href="daftar_buku-admin.php" class="btn-secondary">
                                        <i class="fas fa-times"></i>
                                        Batal
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            Data buku tidak lengkap atau tidak ditemukan.
                        </div>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Footer -->
            <footer class="footer">
                &copy; <?= date('Y') ?> Toko BUKU. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
        // Fungsi untuk menghitung dan menampilkan preview diskon
        function updateDiscountPreview() {
            const hargaRegular = parseFloat(document.getElementById('harga_regular').value) || 0;
            const diskon = parseFloat(document.getElementById('diskon').value) || 0;
            const previewElement = document.getElementById('discountPreview');
            
            if (diskon > 0 && hargaRegular > 0) {
                const hargaFinal = hargaRegular - (hargaRegular * diskon / 100);
                
                document.getElementById('previewRegular').textContent = hargaRegular.toLocaleString('id-ID');
                document.getElementById('previewDiscount').textContent = diskon;
                document.getElementById('previewFinal').textContent = hargaFinal.toLocaleString('id-ID');
                
                previewElement.classList.remove('hidden');
            } else {
                previewElement.classList.add('hidden');
            }
        }

        // Event listeners untuk update real-time
        document.getElementById('harga_regular').addEventListener('input', updateDiscountPreview);
        document.getElementById('diskon').addEventListener('input', updateDiscountPreview);

        // Validasi form sebelum submit
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                const harga_regular = document.getElementById('harga_regular');
                const diskon = document.getElementById('diskon');
                const stok = document.getElementById('stok');
                const kategori = document.getElementById('kategori');

                if (harga_regular && harga_regular.value < 0) {
                    alert('Harga regular tidak boleh negatif');
                    e.preventDefault();
                }

                if (diskon && (diskon.value < 0 || diskon.value > 100)) {
                    alert('Diskon harus antara 0 dan 100');
                    e.preventDefault();
                }

                if (stok && stok.value < 0) {
                    alert('Stok tidak boleh negatif');
                    e.preventDefault();
                }

                if (kategori && kategori.value === '') {
                    alert('Pilih kategori buku');
                    e.preventDefault();
                }
            });
        }

        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDiscountPreview();
        });
        function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    const toggle = document.getElementById(id.replace('Dropdown', 'Toggle'));

    dropdown.classList.toggle('show');
    toggle.classList.toggle('active');
}

    </script>
</body>
</html>