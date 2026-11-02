-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Okt 2026 pada 15.09
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `foundit_web`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admins`
--

CREATE TABLE `admins` (
  `id` varchar(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admins`
--

INSERT INTO `admins` (`id`, `nama`, `email`, `password`, `no_telp`, `created_at`) VALUES
('ADM001', 'Admin Utama', 'admin1@example.com', 'HASH_ADMIN_DUMMY_001', '082100000001', '2026-10-29 13:56:30'),
('ADM002', 'Admin Kedua', 'admin2@example.com', 'HASH_ADMIN_DUMMY_002', '082100000002', '2026-10-29 13:56:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` varchar(10) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `nama_kategori`) VALUES
('CAT003', 'Aksesoris'),
('CAT006', 'Alat Tulis'),
('CAT008', 'Buku'),
('CAT002', 'Dokumen'),
('CAT001', 'Elektronik'),
('CAT009', 'Kartu'),
('CAT004', 'Kunci'),
('CAT010', 'Lainnya'),
('CAT007', 'Pakaian'),
('CAT005', 'Tas');

-- --------------------------------------------------------

--
-- Struktur dari tabel `claims`
--

CREATE TABLE `claims` (
  `id` varchar(10) NOT NULL,
  `bukti_kepemilikan` varchar(1000) NOT NULL,
  `lampiran_foto` varchar(255) DEFAULT NULL,
  `status_klaim` enum('Menunggu','Disetujui','Ditolak','Dibatalkan') NOT NULL DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `items_id` varchar(10) NOT NULL,
  `users_id` varchar(10) NOT NULL,
  `admins_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `claims`
--

INSERT INTO `claims` (`id`, `bukti_kepemilikan`, `lampiran_foto`, `status_klaim`, `created_at`, `items_id`, `users_id`, `admins_id`) VALUES
('CLM001', 'Dapat menyebutkan ciri khusus flashdisk yang tidak tercantum pada deskripsi publik.', 'bukti_clm001.jpg', 'Menunggu', '2026-10-29 13:56:30', 'ITM001', 'USR001', NULL),
('CLM002', 'Menjelaskan bentuk gantungan kunci dan merek kendaraan pemilik.', 'bukti_clm002.jpg', 'Disetujui', '2026-10-29 13:56:30', 'ITM002', 'USR002', 'ADM001'),
('CLM003', 'Menyebutkan isi dompet dan ciri khusus bagian dalamnya.', 'bukti_clm003.jpg', 'Ditolak', '2026-10-29 13:56:30', 'ITM003', 'USR003', 'ADM002');

-- --------------------------------------------------------

--
-- Struktur dari tabel `items`
--

CREATE TABLE `items` (
  `id` varchar(10) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` varchar(1000) NOT NULL,
  `tipe` enum('lost','found') NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `tanggal_kejadian` date NOT NULL,
  `foto` varchar(255) NOT NULL,
  `status` enum('Pending','Terbit','Selesai','Ditolak','Arsip') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `categories_id` varchar(10) NOT NULL,
  `nama_penemu` varchar(100) DEFAULT NULL,
  `no_telp_penemu` varchar(20) DEFAULT NULL,
  `users_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `items`
--

INSERT INTO `items` (`id`, `judul`, `deskripsi`, `tipe`, `lokasi`, `tanggal_kejadian`, `foto`, `status`, `created_at`, `categories_id`, `nama_penemu`, `no_telp_penemu`, `users_id`) VALUES
('ITM001', 'Flashdisk 64GB', 'Flashdisk warna hitam ditemukan di ruang laboratorium.', 'found', 'Lab Komputer Gedung C', '2026-09-09', 'flashdisk_64gb.jpg', 'Terbit', '2026-10-29 13:56:30', 'CAT001', 'Andi', '081300000001', NULL),
('ITM002', 'Kunci Motor', 'Satu kunci motor dengan gantungan berwarna biru.', 'found', 'Parkiran Fakultas', '2026-09-10', 'kunci_motor.jpg', 'Terbit', '2026-10-29 13:56:30', 'CAT004', 'Budi', '081300000002', NULL),
('ITM003', 'Dompet Cokelat', 'Dompet kulit cokelat ditemukan di kantin kampus.', 'found', 'Kantin Kampus', '2026-09-11', 'dompet_cokelat.jpg', 'Terbit', '2026-10-29 13:56:30', 'CAT003', 'Sinta', '081300000003', NULL),
('ITM004', 'Kartu Mahasiswa', 'Kartu mahasiswa ditemukan di sekitar perpustakaan.', 'found', 'Perpustakaan', '2026-09-12', 'kartu_mahasiswa.jpg', 'Pending', '2026-10-29 13:56:30', 'CAT009', 'Reno', NULL, NULL),
('ITM005', 'Buku Catatan', 'Buku catatan sampul hijau tertinggal di kelas.', 'found', 'Ruang Kelas B204', '2026-09-13', 'buku_catatan.jpg', 'Terbit', '2026-10-29 13:56:30', 'CAT008', 'Mira', '081300000005', NULL),
('ITM006', 'Laptop Abu-abu', 'Laptop tertinggal di ruang diskusi.', 'lost', 'Ruang Diskusi Perpustakaan', '2026-09-14', 'laptop_abu.jpg', 'Pending', '2026-10-29 13:56:30', 'CAT001', NULL, NULL, 'USR001'),
('ITM007', 'Buku Pemrograman', 'Buku pemrograman sampul biru hilang setelah perkuliahan.', 'lost', 'Gedung Kuliah A', '2026-09-15', 'buku_programming.jpg', 'Terbit', '2026-10-29 13:56:30', 'CAT008', NULL, NULL, 'USR002'),
('ITM008', 'Kartu ATM', 'Kartu ATM hilang di sekitar area parkir.', 'lost', 'Parkiran Utama', '2026-09-16', 'kartu_atm.jpg', 'Ditolak', '2026-10-29 13:56:30', 'CAT009', NULL, NULL, 'USR003'),
('ITM009', 'Tas Hitam', 'Tas hitam tertinggal di halte kampus.', 'lost', 'Halte Kampus', '2026-09-17', 'tas_hitam.jpg', 'Selesai', '2026-10-29 13:56:30', 'CAT005', NULL, NULL, 'USR004'),
('ITM010', 'Pulpen dan Tempat Pensil', 'Tempat pensil berisi beberapa alat tulis hilang.', 'lost', 'Gedung Kuliah B', '2026-09-18', 'tempat_pensil.jpg', 'Arsip', '2026-10-29 13:56:30', 'CAT006', NULL, NULL, 'USR005');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` varchar(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nomor_identitas` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `nomor_identitas`, `email`, `password`, `no_telp`, `created_at`, `role`) VALUES
('USR001', 'Dina Putri', '25071100001', 'dina.putri@example.com', 'HASH_DUMMY_001', '081200000001', '2026-10-29 13:56:30', 'user'),
('USR002', 'Rafi Pratama', '25071100002', 'rafi.pratama@example.com', 'HASH_DUMMY_002', '081200000002', '2026-10-29 13:56:30', 'user'),
('USR003', 'Nadia Safitri', '25071100003', 'nadia.safitri@example.com', 'HASH_DUMMY_003', '081200000003', '2026-10-29 13:56:30', 'user'),
('USR004', 'Fajar Ramadhan', '25071100004', 'fajar.ramadhan@example.com', 'HASH_DUMMY_004', '081200000004', '2026-10-29 13:56:30', 'user'),
('USR005', 'Salsa Maharani', '25071100005', 'salsa.maharani@example.com', 'HASH_DUMMY_005', '081200000005', '2026-10-29 13:56:30', 'user');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indeks untuk tabel `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `claims_items_fk` (`items_id`),
  ADD KEY `claims_users_fk` (`users_id`),
  ADD KEY `claims_admins_fk` (`admins_id`);

--
-- Indeks untuk tabel `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `items_categories_fk` (`categories_id`),
  ADD KEY `items_users_fk` (`users_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nomor_identitas` (`nomor_identitas`);

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `claims_admins_fk` FOREIGN KEY (`admins_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `claims_items_fk` FOREIGN KEY (`items_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `claims_users_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_categories_fk` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `items_users_fk` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
