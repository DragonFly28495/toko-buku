-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Okt 2025 pada 11.52
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_buku`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admins`
--

INSERT INTO `admins` (`id`, `nama`, `password`) VALUES
(1, 'admin', '$2y$10$tChd1RNT9NnAIQdwGSBMJOEUi4g96S/xpqnbVsqwgkIbI0ZoCtM2a');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku`
--

CREATE TABLE `buku` (
  `id_buku` int(6) UNSIGNED NOT NULL,
  `judul` varchar(300) NOT NULL,
  `penulis` varchar(100) NOT NULL,
  `penerbit` varchar(100) NOT NULL,
  `tanggal_terbit` varchar(300) NOT NULL,
  `harga_regular` int(12) NOT NULL,
  `diskon` int(12) NOT NULL,
  `harga_setelah_diskon` int(12) DEFAULT NULL,
  `stok` int(12) NOT NULL,
  `kategori` varchar(300) NOT NULL,
  `deskripsi` varchar(1500) NOT NULL,
  `cover_buku` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `buku`
--

INSERT INTO `buku` (`id_buku`, `judul`, `penulis`, `penerbit`, `tanggal_terbit`, `harga_regular`, `diskon`, `harga_setelah_diskon`, `stok`, `kategori`, `deskripsi`, `cover_buku`) VALUES
(2, 'Madilog', 'Tan Malaka', 'Narasi', '2025-10-18', 110000, 0, 110000, 9, 'Non Fiksi', 'Membahas tiga pilar utama pemikiran: Materialisme, Dialektika, dan Logika, yang disajikan sebagai alat untuk membebaskan bangsa Indonesia dari \"logika mistika\"—pemikiran irasional, tahayul, dan mitos yang dianggap menghambat kemajuan', 'Buku_Madilog.avif'),
(3, 'Animal Farm', 'George Orwell', 'Norris Book', '2025-10-18', 500000, 0, 500000, 31, 'Non Fiksi', 'Novel Animal Farm karya George Orwell adalah sebuah alegori politik yang menceritakan pemberontakan hewan di sebuah peternakan terhadap pemilik manusia mereka yang menindas, dengan tujuan menciptakan masyarakat yang setara dan adil. Namun, pemberontakan ini kemudian berubah menjadi rezim totaliter di mana babi-babi yang awalnya memimpin, justru membangun sistem tirani yang lebih buruk daripada sebelumnya, mencerminkan sejarah rezim Stalin di Uni Soviet.', 'Buku_Animal Farm.avif'),
(4, 'The Boy, the Mole, the Fox and the Horse', 'Charlie Mackeys', 'Ronebook', '2025-10-18', 210000, 10, 189000, 14, 'Novel', 'The Boy, the Mole, the Fox and the Horse menceritakan kisah menyentuh tentang perjalanan seorang anak laki-laki yang penuh rasa ingin tahu. Dalam perjalanannya, ia bertemu dengan Tikus Tanah yang bijaksana, dan keduanya menjalin persahabatan yang kokoh.', 'Buku_The Boy,the Mole, the Fox and the Horse.avif'),
(5, 'Atomic Habits', 'James Clear', 'Penguin Us', '2025-10-18', 100000, 0, 100000, 95, 'Non Fiksi', 'Menjelaskan bagaimana perubahan kecil yang konsisten dapat menciptakan dampak besar dalam hidup, bukan dengan fokus pada tujuan besar, melainkan pada sistem kebiasaan kecil dan bertahap', 'Buku_Atomic Habits.jpg'),
(8, 'Sapiens', 'Yuval Noah Harari', 'Kepustakaan Populer', '2025-10-18', 150000, 0, 150000, 30, 'Sejarah', 'Selama dua setengah juta tahun, berbagai spesies manusia hidup dan punah di Bumi. Sampai akhirnya tersisa satu yaitu Homo sapiens—Manusia Bijaksana—sejak seratusan ribu tahun lalu. Namun spesies ini bisa menyebar ke seluruh dunia dan beranak-pinak hingga berjumlah 7 miliar, dan kini menjadi kekuatan alam yang dapat mengubah kondisi planet. Apa penyebabnya?', 'buku_sapiens.avif'),
(12, 'Filosofi Teras', 'Henry Manampiring', 'Narasi', '2025-10-18', 2000000, 0, 2000000, 40, 'Non Fiksi', 'menceritakan tentang penerapan filsafat Stoisisme atau filsafat Stoa kuno untuk membantu mengatasi emosi negatif dan menjadi pribadi yang tangguh dalam menghadapi kehidupan.', 'Filosofi Teras.png'),
(13, 'The Little Prince', 'Antoine de Saint-Exupéry', 'Norris Book', '2025-10-18', 200000, 0, 200000, 3, 'Novel', 'Bercerita tentang seorang pangeran dari planet kecil yang melakukan perjalanan ke berbagai planet untuk mencari pemahaman tentang kehidupan, cinta, persahabatan, dan kehilangan. Cerita ini mengisahkan pertemuannya dengan berbagai karakter dewasa yang aneh di berbagai planet, sebelum akhirnya tiba di Bumi.', 'buku_The_Little_Prince.jpeg'),
(14, 'Il Principe (Sang Pangeran)', 'Niccolo Machiavelli', 'Cakrawala Sketsa Mandiri', '2025-10-18', 75000, 0, 75000, 19, 'Filsafat', 'Buku Il Principe karya Niccolò Machiavelli adalah risalah politik yang membahas cara memperoleh dan mempertahankan kekuasaan, sering kali dengan menganjurkan tindakan pragmatis seperti kelicikan dan kekejaman jika diperlukan untuk stabilitas negara. Buku ini dianggap sebagai panduan praktis bagi para penguasa untuk meraih, mempertahankan, dan memperluas kekuasaan mereka dengan cara yang realistis, berdasarkan pengalaman Machiavelli sendiri dan kajian sejarah.', 'Il Principe.jpg'),
(15, 'Wonder', 'R.J. Palacio', 'Penguin Us', '2025-10-18', 300000, 0, 300000, 19, 'Novel', 'Buku Wonder karya R.J. Palacio menceritakan tentang August \"Auggie\" Pullman, seorang anak laki-laki yang lahir dengan kelainan wajah (Mandibulofacial Dysostosis) dan harus menjalani puluhan operasi. Buku ini berfokus pada tahun pertamanya di sekolah umum, Beecher Prep, saat ia harus beradaptasi dengan lingkungan sosial barunya yang penuh tantangan, sekaligus mengajarkan nilai-nilai kebaikan, penerimaan, dan empati kepada pembaca.', 'buku_Wonder.jpg'),
(16, 'The Wealth of Nations', 'Adam Smith', 'Norris Book', '2025-10-18', 200000, 0, 200000, 30, 'Filsafat', 'Buku The Wealth of Nations adalah mahakarya dari Adam Smith, seorang ekonom dan filsuf moral asal Skotlandia. Buku ini menjelaskan bagaimana interaksi antara penawaran dan permintaan, serta motivasi pribadi dalam perdagangan, dapat menghasilkan keseimbangan ekonomi yang menguntungkan masyarakat secara keseluruhan—konsep yang dikenal sebagai “invisible hand” atau tangan tak terlihat. Ia juga mengkritik sistem merkantilisme dan menekankan pentingnya kebebasan ekonomi sebagai pendorong kemakmuran bangsa.', 'buku_the_wealth_of_nation.png'),
(17, 'Emotional Intelligence', 'Daniel Goleman', 'Ronebook', '2025-10-18', 300000, 0, 300000, 50, 'Non Fiksi', 'Buku Emotional Intelligence membahas tentang kecerdasan emosional, yang meliputi lima komponen utama: kesadaran diri, pengelolaan diri, motivasi, empati, dan keterampilan sosial', 'Emotional_Intelligence.png'),
(18, 'll', 'll', 'll', '2025-10-20', 100000, 75, 25000, 100, 'mm', 'mm', 'buku_The_Little_Prince.jpeg'),
(19, 'Dasar Jaringan', 'Alberto', 'berto', '2025-10-20', 100000, 0, 100000, 50, 'Komputer', 'Sebuah buku tentang ilmu-ilmu dasar jaringan dan komputer.', 'Il Principe.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar_pengguna`
--

CREATE TABLE `daftar_pengguna` (
  `id_pengguna` int(13) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(8) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `no_telp` varchar(100) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `daftar_pengguna`
--

INSERT INTO `daftar_pengguna` (`id_pengguna`, `username`, `password`, `email`, `alamat`, `no_telp`, `foto_profil`) VALUES
(11, 'Salamuddin', 'udin321', 'salamuddin123@gmail.com', 'Jl. Kutilang 2', '0895383212639', 'profil_11_1760933988.png'),
(12, 'Indra Rizqi', '12345', 'indora@gmail.com', '', '', 'profil_12_1760340062.jpg'),
(13, 'Bambang', '12345', 'bambang@gmail.com', '', '', NULL),
(14, 'Budi', '12345', 'budi@gmail.com', '', '', 'profil_14_1760928256.png'),
(17, 'user', '54321', 'user12@gmail.com', 'Jl. Kutilang 2', '0895383212639', 'profil_17_1760936284.png'),
(18, 'wahidin', '12345', 'user1@gmail.com', '', '', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` varchar(30) NOT NULL,
  `nama_kategori` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
('1', 'Fiksi'),
('11', 'Filsafat'),
('12', 'Pendidikan'),
('13', 'Sains'),
('15', 'Bisnis'),
('2', 'Drama'),
('3', 'Non Fiksi'),
('4', 'Komputer'),
('40', 'Novel'),
('45', 'Alam'),
('5', 'mm'),
('8', 'Sejarah');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `id_keranjang` int(100) NOT NULL,
  `id_pengguna` int(100) NOT NULL,
  `username` varchar(300) NOT NULL,
  `jumlah` int(100) NOT NULL,
  `id_buku` int(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `keranjang`
--

INSERT INTO `keranjang` (`id_keranjang`, `id_pengguna`, `username`, `jumlah`, `id_buku`) VALUES
(20, 13, 'Bambang', 1, 4),
(21, 13, 'Bambang', 1, 2),
(81, 14, 'Budi', 1, 15),
(86, 18, 'wahidin', 2, 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `percakapan`
--

CREATE TABLE `percakapan` (
  `id_percakapan` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `subjek` varchar(255) DEFAULT NULL,
  `pesan_terakhir_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `percakapan`
--

INSERT INTO `percakapan` (`id_percakapan`, `id_pengguna`, `subjek`, `pesan_terakhir_pada`, `dibuat_pada`) VALUES
(1, 11, 'Pertanyaan Pengguna', '2025-10-20 03:21:33', '2025-10-07 04:47:18'),
(2, 12, 'Pertanyaan Pengguna', '2025-10-13 07:06:28', '2025-10-07 05:56:56'),
(3, 13, 'Pertanyaan Pengguna', '2025-10-07 23:47:59', '2025-10-07 07:01:03'),
(4, 14, 'Pertanyaan Pengguna', '2025-10-16 10:48:53', '2025-10-09 15:31:28'),
(6, 17, 'Pertanyaan Pengguna', '2025-10-20 04:59:17', '2025-10-20 04:58:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesan`
--

CREATE TABLE `pesan` (
  `id_pesan` int(11) NOT NULL,
  `id_percakapan` int(11) NOT NULL,
  `id_pengirim` int(11) DEFAULT NULL,
  `teks_pesan` text NOT NULL,
  `sudah_dibaca` tinyint(1) DEFAULT 0,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_from_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesan`
--

INSERT INTO `pesan` (`id_pesan`, `id_percakapan`, `id_pengirim`, `teks_pesan`, `sudah_dibaca`, `dibuat_pada`, `is_from_admin`) VALUES
(49, 3, 13, 'MIn stok buku ready enggak?', 1, '2025-10-07 07:01:22', 0),
(50, 3, NULL, 'Ready bang', 1, '2025-10-07 07:01:33', 1),
(51, 3, 13, 'oke', 1, '2025-10-07 07:05:27', 0),
(52, 3, 13, 'Pesan buku 1', 1, '2025-10-07 08:42:12', 0),
(53, 3, NULL, 'siap', 1, '2025-10-07 08:42:22', 1),
(54, 3, 13, 'Pesan Sekarang', 1, '2025-10-07 23:47:52', 0),
(55, 3, NULL, 'siap', 1, '2025-10-07 23:47:59', 1),
(57, 2, 12, 'Min', 1, '2025-10-08 04:53:48', 0),
(58, 2, NULL, 'Ada yang bisa saya bantu', 1, '2025-10-08 04:54:05', 1),
(59, 2, 12, 'Stok ready?', 1, '2025-10-08 05:50:21', 0),
(62, 2, NULL, 'Halo , Ada yang bisa saya bantu', 1, '2025-10-09 00:14:49', 1),
(63, 2, 12, 'Saya ingin mau beli buku Madilog Ke alamat papua', 1, '2025-10-09 00:15:37', 0),
(64, 2, 12, 'Min', 1, '2025-10-09 15:15:41', 0),
(65, 2, NULL, 'Ada yang bisa saya bantu', 1, '2025-10-09 15:16:03', 1),
(66, 2, 12, 'Tidak ada', 1, '2025-10-09 15:16:12', 0),
(67, 4, 14, 'Min', 1, '2025-10-09 15:37:27', 0),
(68, 4, NULL, 'Ada yang bisa saya bantu?', 1, '2025-10-09 15:37:59', 1),
(69, 2, 12, 'MIn', 1, '2025-10-13 07:05:53', 0),
(70, 2, NULL, 'Ada yang bisa saya bantu', 1, '2025-10-13 07:06:28', 1),
(72, 4, 14, 'bang', 1, '2025-10-16 10:48:53', 0),
(73, 1, 11, 'p', 1, '2025-10-18 06:52:30', 0),
(74, 1, 11, 'mas', 1, '2025-10-18 12:37:08', 0),
(75, 1, 11, 'p', 1, '2025-10-18 12:37:16', 0),
(76, 1, 11, 'oi', 1, '2025-10-18 13:04:52', 0),
(77, 1, 11, 'oi', 1, '2025-10-18 13:05:06', 0),
(78, 1, 11, 'oi', 1, '2025-10-18 13:05:07', 0),
(79, 1, 11, 'oi', 1, '2025-10-18 13:05:08', 0),
(80, 1, 11, 'po', 1, '2025-10-18 13:06:57', 0),
(81, 1, 11, 'bang', 1, '2025-10-18 13:07:00', 0),
(89, 1, 11, 'min', 1, '2025-10-20 03:21:24', 0),
(90, 1, NULL, 'oke', 1, '2025-10-20 03:21:33', 1),
(91, 6, 17, 'Halo', 1, '2025-10-20 04:59:03', 0),
(92, 6, NULL, 'Ada yang bisa saya bantu', 1, '2025-10-20 04:59:17', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `kd_transaksi` varchar(100) NOT NULL,
  `id_buku` int(100) NOT NULL,
  `id_pengguna` int(30) NOT NULL,
  `username` varchar(233) NOT NULL,
  `jumlah` int(100) NOT NULL,
  `total_harga` int(11) DEFAULT NULL,
  `metode_pembayaran` varchar(300) NOT NULL,
  `status` varchar(20) DEFAULT 'menunggu_validasi',
  `judul` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`kd_transaksi`, `id_buku`, `id_pengguna`, `username`, `jumlah`, `total_harga`, `metode_pembayaran`, `status`, `judul`, `created_at`) VALUES
('TRX1759816071', 4, 11, 'Salamuddin', 1, 210000, 'VA', 'selesai', 'The Boy, the Mole, the Fox and the Horse', '2025-10-08 14:15:27'),
('TRX1759820524', 5, 13, 'Bambang', 5, 500000, 'Bank Transfer', 'selesai', 'Atomic Habits', '2025-10-08 14:15:27'),
('TRX1759914695', 4, 11, 'Salamuddin', 1, 210000, 'VA', 'selesai', 'The Boy, the Mole, the Fox and the Horse', '2025-10-08 14:15:27'),
('TRX1759933317', 2, 12, 'Indora', 1, 110000, 'COD', 'selesai', 'Madilog', '2025-10-08 14:23:56'),
('TRX1759933317', 4, 12, 'Indora', 2, 420000, 'COD', 'selesai', 'The Boy, the Mole, the Fox and the Horse', '2025-10-08 14:23:56'),
('TRX1759933471', 3, 12, 'Indora', 1, 50000, 'Bank Transfer', 'selesai', 'Animal Farm', '2025-10-08 14:25:08'),
('TRX1759933880', 3, 11, 'Salamuddin', 1, 50000, 'COD', 'selesai', 'Animal Farm', '2025-10-08 14:38:07'),
('TRX1759934343', 2, 11, 'Salamuddin', 1, 110000, 'Bank Transfer', 'ditolak', 'Madilog', '2025-10-08 14:49:11'),
('TRX1759968776', 5, 12, 'Indora', 5, 500000, 'COD', 'ditolak', 'Atomic Habits', '2025-10-09 00:19:39'),
('TRX1760112792', 3, 14, 'Budi', 1, 50000, 'Bank Transfer', 'ditolak', 'Animal Farm', '2025-10-14 03:57:58'),
('TRX1760908356', 3, 16, 'Asep', 1, 500000, 'VA', 'selesai', 'Animal Farm', '2025-10-19 21:13:20'),
('TRX1760908453', 15, 16, 'Asep', 1, 300000, 'Bank Transfer', 'selesai', 'Wonder', '2025-10-19 21:14:32'),
('TRX1760927064', 4, 14, 'Budi', 1, 189000, 'Bank Transfer', 'ditolak', 'The Boy, the Mole, the Fox and the Horse', '2025-10-20 04:56:23'),
('TRX1760927064', 2, 14, 'Budi', 2, 220000, 'Bank Transfer', 'ditolak', 'Madilog', '2025-10-20 04:56:23'),
('TRX1760927064', 15, 14, 'Budi', 2, 600000, 'Bank Transfer', 'ditolak', 'Wonder', '2025-10-20 04:56:23'),
('TRX1760927064', 3, 14, 'Budi', 1, 500000, 'Bank Transfer', 'ditolak', 'Animal Farm', '2025-10-20 04:56:23'),
('TRX1760930110', 3, 11, 'Salamuddin', 2, 1000000, 'VA', 'selesai', 'Animal Farm', '2025-10-20 03:15:47'),
('TRX1760930110', 4, 11, 'Salamuddin', 1, 189000, 'VA', 'selesai', 'The Boy, the Mole, the Fox and the Horse', '2025-10-20 03:15:47'),
('TRX1760936310', 2, 17, 'user', 1, 110000, 'COD', 'selesai', 'Madilog', '2025-10-20 04:58:45'),
('TRX1760936310', 14, 17, 'user', 1, 75000, 'COD', 'selesai', 'Il Principe (Sang Pangeran)', '2025-10-20 04:58:45'),
('TRX1760951651', 13, 18, 'wahidin', 1, 200000, 'COD', 'selesai', 'The Little Prince', '2025-10-20 09:14:53');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id_buku`);

--
-- Indeks untuk tabel `daftar_pengguna`
--
ALTER TABLE `daftar_pengguna`
  ADD PRIMARY KEY (`id_pengguna`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id_keranjang`);

--
-- Indeks untuk tabel `percakapan`
--
ALTER TABLE `percakapan`
  ADD PRIMARY KEY (`id_percakapan`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indeks untuk tabel `pesan`
--
ALTER TABLE `pesan`
  ADD PRIMARY KEY (`id_pesan`),
  ADD KEY `id_percakapan` (`id_percakapan`),
  ADD KEY `id_pengirim` (`id_pengirim`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `buku`
--
ALTER TABLE `buku`
  MODIFY `id_buku` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `daftar_pengguna`
--
ALTER TABLE `daftar_pengguna`
  MODIFY `id_pengguna` int(13) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id_keranjang` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT untuk tabel `percakapan`
--
ALTER TABLE `percakapan`
  MODIFY `id_percakapan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pesan`
--
ALTER TABLE `pesan`
  MODIFY `id_pesan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `percakapan`
--
ALTER TABLE `percakapan`
  ADD CONSTRAINT `percakapan_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `daftar_pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesan`
--
ALTER TABLE `pesan`
  ADD CONSTRAINT `pesan_ibfk_1` FOREIGN KEY (`id_percakapan`) REFERENCES `percakapan` (`id_percakapan`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
