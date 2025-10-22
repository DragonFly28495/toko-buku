<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beranda Admin - Toko BUKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style-admin.css">
    <style>
        /* Kartu & lainnya */
        .welcome-card {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            margin-bottom: 2rem;
        }

        .welcome-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .welcome-card p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 5rem;
        }

        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 2rem 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #eaeaea;
            text-decoration: none;
            color: inherit;
            height: 100%;
            min-height: 180px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stat-icon.books {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .stat-icon.orders {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }

        .stat-icon.messages {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .stat-number {
            font-size: 2.2rem;
            color: #2c3e50;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
            font-weight: 500;
        }

        /* ===== LOGOUT LINK ===== */
        .sidebar-link.logout {
            color: #e74c3c;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .sidebar-link.logout:hover {
            background-color: rgba(231, 76, 60, 0.1);
            border-left-color: #e74c3c;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== RESPONSIVE DESIGN ===== */
        /* Desktop Medium */
        @media (max-width: 1200px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Tablet */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .mobile-menu-btn {
                display: block;
                background: none;
                border: none;
                font-size: 1.5rem;
                color: #2c3e50;
                cursor: pointer;
                margin-right: 1rem;
                padding: 0.5rem;
            }

            .content-body {
                padding: 1.5rem 1rem;
                padding-bottom: 4rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
                margin-bottom: 2rem;
            }

            .welcome-card {
                padding: 2rem 1.5rem;
            }

            .welcome-card h3 {
                font-size: 1.5rem;
            }

            .stat-card {
                min-height: 160px;
                padding: 1.5rem;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
                margin-bottom: 0.8rem;
            }

            .stat-number {
                font-size: 1.8rem;
            }
        }

        /* Small Mobile */
        @media (max-width: 480px) {
            .stat-card {
                padding: 1.25rem;
                min-height: 140px;
            }
            
            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
                margin-bottom: 0.7rem;
            }
            
            .stat-number {
                font-size: 1.6rem;
                margin-bottom: 0.4rem;
            }
            
            .stat-label {
                font-size: 0.9rem;
            }
        }
    </style>

</head>

<body>
    <?php
    // Include config untuk koneksi database
    require_once '../config.php';

    // Query untuk mendapatkan total buku
    $sql_buku = "SELECT COUNT(*) as total_buku FROM buku";
    $stmt_buku = $pdo->query($sql_buku);
    $total_buku = $stmt_buku->fetch()['total_buku'];

    // Query untuk mendapatkan total pengguna terdaftar
    $sql_pengguna = "SELECT COUNT(*) as total_pengguna FROM daftar_pengguna";
    $stmt_pengguna = $pdo->query($sql_pengguna);
    $total_pengguna = $stmt_pengguna->fetch()['total_pengguna'];

    // Query untuk mendapatkan pesanan baru (status pending)
    $sql_pesanan = "SELECT COUNT(*) as pesanan_baru FROM transaksi WHERE status = 'pending'";
    $stmt_pesanan = $pdo->query($sql_pesanan);
    $pesanan_baru = $stmt_pesanan->fetch()['pesanan_baru'];

    // Query untuk mendapatkan pesan belum dibaca
    $sql_pesan = "SELECT COUNT(*) as pesan_belum_dibaca FROM pesan WHERE sudah_dibaca = 0 AND is_from_admin = 0";
    $stmt_pesan = $pdo->query($sql_pesan);
    $pesan_belum_dibaca = $stmt_pesan->fetch()['pesan_belum_dibaca'];
    ?>

    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <div class="content-header">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Selamat Datang Admin</h2>
                <p>Di Toko Buku - Kelola semua aktivitas toko dari sini</p>
            </div>

            <div class="content-body">
                <div class="welcome-card">
                    <h3>Selamat datang di Beranda Admin</h3>
                    <p>Anda dapat mengelola semua aspek toko buku dari sini. Pantau penjualan, kelola inventaris, dan
                        lihat statistik penting toko Anda.</p>
                </div>

                <div class="stats-container">
                    <a href="daftar_buku-admin.php" class="stat-card">
                        <div class="stat-icon books">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-number"><?= $total_buku ?></div>
                        <div class="stat-label">Total Buku</div>
                    </a>

                    <a href="daftar_pengguna-admin.php" class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?= $total_pengguna ?></div>
                        <div class="stat-label">Pengguna Terdaftar</div>
                    </a>

                    <a href="pesanan.php" class="stat-card">
                        <div class="stat-icon orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-number"><?= $pesanan_baru ?></div>
                        <div class="stat-label">Pesanan Baru</div>
                    </a>

                    <a href="pesan_pengguna.php" class="stat-card">
                        <div class="stat-icon messages">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-number"><?= $pesan_belum_dibaca ?></div>
                        <div class="stat-label">Pesan Belum Dibaca</div>
                    </a>
                </div>
            </div>

            <footer class="footer">
                &copy; <?= date('Y') ?> Toko BUKU. All rights reserved.
            </footer>
        </div>
    </div>

    <script>
        // Toggle dropdown dengan animasi
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const toggle = document.getElementById(id.replace('Dropdown', 'Toggle'));

            dropdown.classList.toggle('show');
            toggle.classList.toggle('active');
        }

        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        });

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('mobile-open');
                }
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-toggle')) {
                const dropdowns = document.querySelectorAll('.dropdown-content');
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });

                const toggles = document.querySelectorAll('.dropdown-toggle');
                toggles.forEach(toggle => {
                    toggle.classList.remove('active');
                });
            }
        });

        // Handle responsive behavior on window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        });
    </script>
</body>

</html>