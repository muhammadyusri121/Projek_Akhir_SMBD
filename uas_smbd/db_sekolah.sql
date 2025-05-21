-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 20, 2025 at 02:58 AM
-- Server version: 8.0.42-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sekolah`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_assign_guru_mapel` (IN `p_id_guru` INT, IN `p_id_mapel` INT)   BEGIN
    UPDATE mata_pelajaran
    SET id_guru = p_id_guru
    WHERE id_mapel = p_id_mapel$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_check_jadwal_conflict` (IN `p_id_kelas` INT, IN `p_id_guru` INT, IN `p_hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), IN `p_jam_mulai` TIME, IN `p_jam_selesai` TIME, OUT `p_conflict` BOOLEAN)   BEGIN
    DECLARE kelas_conflict INT$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_hitung_rata_rata` (IN `p_id_siswa` INT, IN `p_semester` ENUM('1','2'), IN `p_tahun_ajaran` VARCHAR(9), OUT `p_rata_rata` DECIMAL(5,2))   BEGIN
    SELECT AVG(nilai) INTO p_rata_rata
    FROM nilai
    WHERE id_siswa = p_id_siswa
    AND semester = p_semester
    AND tahun_ajaran = p_tahun_ajaran$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tambah_siswa` (IN `p_nis` VARCHAR(20), IN `p_nama_siswa` VARCHAR(100), IN `p_tanggal_lahir` DATE, IN `p_jenis_kelamin` ENUM('L','P'), IN `p_alamat` TEXT, IN `p_id_kelas` INT)   BEGIN
    DECLARE nis_exists INT$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id_guru` int NOT NULL,
  `nip` varchar(20) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `alamat` text NOT NULL,
  `no_telepon` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id_guru`, `nip`, `nama_guru`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `no_telepon`) VALUES
(1, '19800101001', 'Budi Santoso', '1980-01-01', 'L', 'Jl. Merdeka No. 1', '081234567890'),
(2, '19800102002', 'Siti Rahayu', '1980-01-02', 'P', 'Jl. Pahlawan No. 2', '081234567891'),
(3, '19800103003', 'Ahmad Hidayat', '1980-01-03', 'L', 'Jl. Sudirman No. 3', '081234567892'),
(4, '19800104004', 'Dewi Lestari', '1980-01-04', 'P', 'Jl. Gatot Subroto No. 4', '081234567893'),
(5, '19800105005', 'Eko Prasetyo', '1980-01-05', 'L', 'Jl. Diponegoro No. 5', '081234567894'),
(6, '19800106006', 'Fitriani', '1980-01-06', 'P', 'Jl. Ahmad Yani No. 6', '081234567895');

--
-- Triggers `guru`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_nip_before_insert` BEFORE INSERT ON `guru` FOR EACH ROW BEGIN
    IF LENGTH(NEW.nip) < 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'NIP harus minimal 5 karakter!'$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_validate_nip_before_update` BEFORE UPDATE ON `guru` FOR EACH ROW BEGIN
    IF LENGTH(NEW.nip) < 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'NIP harus minimal 5 karakter!'$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `id_mapel` int NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `id_kelas`, `id_guru`, `id_mapel`, `hari`, `jam_mulai`, `jam_selesai`) VALUES
(1, 1, 1, 1, 'Senin', '07:00:00', '08:30:00'),
(2, 1, 2, 2, 'Senin', '08:30:00', '10:00:00'),
(3, 1, 3, 3, 'Senin', '10:15:00', '11:45:00'),
(4, 1, 4, 4, 'Selasa', '07:00:00', '08:30:00'),
(5, 1, 5, 5, 'Selasa', '08:30:00', '10:00:00'),
(6, 1, 6, 6, 'Selasa', '10:15:00', '11:45:00'),
(7, 2, 1, 1, 'Rabu', '07:00:00', '08:30:00'),
(8, 2, 2, 2, 'Rabu', '08:30:00', '10:00:00'),
(9, 2, 3, 3, 'Rabu', '10:15:00', '11:45:00'),
(10, 2, 4, 4, 'Kamis', '07:00:00', '08:30:00'),
(11, 2, 5, 5, 'Kamis', '08:30:00', '10:00:00'),
(12, 2, 6, 6, 'Kamis', '10:15:00', '11:45:00');

--
-- Triggers `jadwal`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_jadwal_before_insert` BEFORE INSERT ON `jadwal` FOR EACH ROW BEGIN
    DECLARE kelas_conflict INT$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL,
  `nama_kelas` varchar(20) NOT NULL,
  `tahun_ajaran` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `nama_kelas`, `tahun_ajaran`) VALUES
(1, 'X-A', '2023/2024'),
(2, 'X-B', '2023/2024'),
(3, 'XI-A', '2023/2024'),
(4, 'XI-B', '2023/2024'),
(5, 'XII-A', '2023/2024'),
(6, 'XII-B', '2023/2024');

--
-- Triggers `kelas`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_kelas_before_insert` BEFORE INSERT ON `kelas` FOR EACH ROW BEGIN
    DECLARE kelas_exists INT$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `mata_pelajaran`
--

CREATE TABLE `mata_pelajaran` (
  `id_mapel` int NOT NULL,
  `nama_mapel` varchar(50) NOT NULL,
  `deskripsi` text NOT NULL,
  `id_guru` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mata_pelajaran`
--

INSERT INTO `mata_pelajaran` (`id_mapel`, `nama_mapel`, `deskripsi`, `id_guru`) VALUES
(1, 'Matematika', 'Pelajaran tentang ilmu hitung dan logika', 1),
(2, 'Bahasa Indonesia', 'Pelajaran tentang bahasa dan sastra Indonesia', 2),
(3, 'Bahasa Inggris', 'Pelajaran tentang bahasa Inggris', 3),
(4, 'Fisika', 'Pelajaran tentang ilmu alam dan fenomena fisik', 4),
(5, 'Kimia', 'Pelajaran tentang ilmu kimia dan reaksi kimia', 5),
(6, 'Biologi', 'Pelajaran tentang ilmu hayati dan makhluk hidup', 6);

--
-- Triggers `mata_pelajaran`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_mapel_before_insert` BEFORE INSERT ON `mata_pelajaran` FOR EACH ROW BEGIN
    IF NEW.id_guru IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Mata pelajaran harus memiliki guru pengampu!'$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `nilai`
--

CREATE TABLE `nilai` (
  `id_nilai` int NOT NULL,
  `id_siswa` int NOT NULL,
  `id_mapel` int NOT NULL,
  `nilai` decimal(5,2) NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `tahun_ajaran` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nilai`
--

INSERT INTO `nilai` (`id_nilai`, `id_siswa`, `id_mapel`, `nilai`, `semester`, `tahun_ajaran`) VALUES
(1, 1, 1, 85.50, '1', '2023/2024'),
(2, 1, 2, 90.00, '1', '2023/2024'),
(3, 1, 3, 78.75, '1', '2023/2024'),
(4, 1, 4, 82.25, '1', '2023/2024'),
(5, 1, 5, 88.00, '1', '2023/2024'),
(6, 1, 6, 92.50, '1', '2023/2024'),
(7, 2, 1, 75.00, '1', '2023/2024'),
(8, 2, 2, 80.50, '1', '2023/2024'),
(9, 2, 3, 85.75, '1', '2023/2024'),
(10, 2, 4, 78.25, '1', '2023/2024'),
(11, 2, 5, 82.00, '1', '2023/2024'),
(12, 2, 6, 79.50, '1', '2023/2024'),
(13, 21, 4, 76.00, '1', '2023/2024'),
(14, 22, 4, 89.00, '1', '2023/2024'),
(15, 23, 4, 77.00, '1', '2023/2024'),
(16, 24, 4, 43.00, '1', '2023/2024'),
(17, 21, 6, 76.00, '2', '2023/2024'),
(18, 22, 6, 89.00, '2', '2023/2024'),
(19, 23, 6, 77.00, '2', '2023/2024'),
(20, 24, 6, 69.00, '2', '2023/2024');

--
-- Triggers `nilai`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_nilai_before_insert` BEFORE INSERT ON `nilai` FOR EACH ROW BEGIN
    IF NEW.nilai < 0 OR NEW.nilai > 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nilai harus berada dalam rentang 0-100!'$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_validate_nilai_before_update` BEFORE UPDATE ON `nilai` FOR EACH ROW BEGIN
    IF NEW.nilai < 0 OR NEW.nilai > 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nilai harus berada dalam rentang 0-100!'$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `alamat` text NOT NULL,
  `id_kelas` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nis`, `nama_siswa`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `id_kelas`) VALUES
(1, '20230001', 'Andi Wijaya', '2005-01-01', 'L', 'Jl. Mawar No. 1', 1),
(2, '20230002', 'Budi Setiawan', '2005-01-02', 'L', 'Jl. Melati No. 2', 1),
(3, '20230003', 'Citra Dewi', '2005-01-03', 'P', 'Jl. Anggrek No. 3', 1),
(4, '20230004', 'Dian Purnama', '2005-01-04', 'P', 'Jl. Dahlia No. 4', 1),
(5, '20230005', 'Eko Santoso', '2005-01-05', 'L', 'Jl. Kenanga No. 5', 2),
(6, '20230006', 'Fani Rahmawati', '2005-01-06', 'P', 'Jl. Tulip No. 6', 2),
(7, '20230007', 'Gilang Pratama', '2005-01-07', 'L', 'Jl. Kamboja No. 7', 2),
(8, '20230008', 'Hani Safitri', '2005-01-08', 'P', 'Jl. Teratai No. 8', 2),
(9, '20220001', 'Irfan Hakim', '2004-01-01', 'L', 'Jl. Cempaka No. 9', 3),
(10, '20220002', 'Jihan Putri', '2004-01-02', 'P', 'Jl. Seroja No. 10', 3),
(11, '20220003', 'Kevin Ramadhan', '2004-01-03', 'L', 'Jl. Flamboyan No. 11', 3),
(12, '20220004', 'Lina Mariana', '2004-01-04', 'P', 'Jl. Sakura No. 12', 3),
(13, '20220005', 'Maman Suparman', '2004-01-05', 'L', 'Jl. Lavender No. 13', 4),
(14, '20220006', 'Nina Anggraini', '2004-01-06', 'P', 'Jl. Lily No. 14', 4),
(15, '20220007', 'Oscar Pratama', '2004-01-07', 'L', 'Jl. Aster No. 15', 4),
(16, '20220008', 'Putri Handayani', '2004-01-08', 'P', 'Jl. Krisan No. 16', 4),
(17, '20210001', 'Qori Maulana', '2003-01-01', 'L', 'Jl. Begonia No. 17', 5),
(18, '20210002', 'Ratih Kusuma', '2003-01-02', 'P', 'Jl. Daisy No. 18', 5),
(19, '20210003', 'Surya Aditya', '2003-01-03', 'L', 'Jl. Iris No. 19', 5),
(20, '20210004', 'Tari Wulandari', '2003-01-04', 'P', 'Jl. Lotus No. 20', 5),
(21, '20210005', 'Umar Hidayat', '2003-01-05', 'L', 'Jl. Peony No. 21', 6),
(22, '20210006', 'Vina Septiani', '2003-01-06', 'P', 'Jl. Poppy No. 22', 6),
(23, '20210007', 'Wawan Kurniawan', '2003-01-07', 'L', 'Jl. Sunflower No. 23', 6),
(24, '20210008', 'Xena Paramita', '2003-01-08', 'P', 'Jl. Tulip No. 24', 6);

--
-- Triggers `siswa`
--
DELIMITER $$
CREATE TRIGGER `tr_validate_nis_before_insert` BEFORE INSERT ON `siswa` FOR EACH ROW BEGIN
    IF LENGTH(NEW.nis) < 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'NIS harus minimal 5 karakter!'$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_validate_nis_before_update` BEFORE UPDATE ON `siswa` FOR EACH ROW BEGIN
    IF LENGTH(NEW.nis) < 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'NIS harus minimal 5 karakter!'$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_guru_mapel`
-- (See below for the actual view)
--
CREATE TABLE `view_guru_mapel` (
`id_guru` int
,`nip` varchar(20)
,`nama_guru` varchar(100)
,`tanggal_lahir` date
,`jenis_kelamin` enum('L','P')
,`alamat` text
,`no_telepon` varchar(15)
,`id_mapel` int
,`nama_mapel` varchar(50)
,`deskripsi` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_jadwal_lengkap`
-- (See below for the actual view)
--
CREATE TABLE `view_jadwal_lengkap` (
`id_jadwal` int
,`hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')
,`jam_mulai` time
,`jam_selesai` time
,`id_kelas` int
,`nama_kelas` varchar(20)
,`id_mapel` int
,`nama_mapel` varchar(50)
,`id_guru` int
,`nama_guru` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_kelas_siswa`
-- (See below for the actual view)
--
CREATE TABLE `view_kelas_siswa` (
`id_kelas` int
,`nama_kelas` varchar(20)
,`tahun_ajaran` varchar(9)
,`jumlah_siswa` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_mapel_guru`
-- (See below for the actual view)
--
CREATE TABLE `view_mapel_guru` (
`id_mapel` int
,`nama_mapel` varchar(50)
,`deskripsi` text
,`id_guru` int
,`nama_guru` varchar(100)
,`nip` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_rapor_siswa`
-- (See below for the actual view)
--
CREATE TABLE `view_rapor_siswa` (
`id_nilai` int
,`nilai` decimal(5,2)
,`semester` enum('1','2')
,`tahun_ajaran` varchar(9)
,`id_siswa` int
,`nis` varchar(20)
,`nama_siswa` varchar(100)
,`id_kelas` int
,`nama_kelas` varchar(20)
,`id_mapel` int
,`nama_mapel` varchar(50)
,`id_guru` int
,`nama_guru` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_siswa_kelas`
-- (See below for the actual view)
--
CREATE TABLE `view_siswa_kelas` (
`id_siswa` int
,`nis` varchar(20)
,`nama_siswa` varchar(100)
,`tanggal_lahir` date
,`jenis_kelamin` enum('L','P')
,`alamat` text
,`id_kelas` int
,`nama_kelas` varchar(20)
,`tahun_ajaran` varchar(9)
);

-- --------------------------------------------------------

--
-- Structure for view `view_guru_mapel`
--
DROP TABLE IF EXISTS `view_guru_mapel`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_guru_mapel`  AS SELECT `g`.`id_guru` AS `id_guru`, `g`.`nip` AS `nip`, `g`.`nama_guru` AS `nama_guru`, `g`.`tanggal_lahir` AS `tanggal_lahir`, `g`.`jenis_kelamin` AS `jenis_kelamin`, `g`.`alamat` AS `alamat`, `g`.`no_telepon` AS `no_telepon`, `m`.`id_mapel` AS `id_mapel`, `m`.`nama_mapel` AS `nama_mapel`, `m`.`deskripsi` AS `deskripsi` FROM (`guru` `g` left join `mata_pelajaran` `m` on((`g`.`id_guru` = `m`.`id_guru`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_jadwal_lengkap`
--
DROP TABLE IF EXISTS `view_jadwal_lengkap`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_jadwal_lengkap`  AS SELECT `j`.`id_jadwal` AS `id_jadwal`, `j`.`hari` AS `hari`, `j`.`jam_mulai` AS `jam_mulai`, `j`.`jam_selesai` AS `jam_selesai`, `k`.`id_kelas` AS `id_kelas`, `k`.`nama_kelas` AS `nama_kelas`, `m`.`id_mapel` AS `id_mapel`, `m`.`nama_mapel` AS `nama_mapel`, `g`.`id_guru` AS `id_guru`, `g`.`nama_guru` AS `nama_guru` FROM (((`jadwal` `j` left join `kelas` `k` on((`j`.`id_kelas` = `k`.`id_kelas`))) left join `mata_pelajaran` `m` on((`j`.`id_mapel` = `m`.`id_mapel`))) left join `guru` `g` on((`j`.`id_guru` = `g`.`id_guru`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_kelas_siswa`
--
DROP TABLE IF EXISTS `view_kelas_siswa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_kelas_siswa`  AS SELECT `k`.`id_kelas` AS `id_kelas`, `k`.`nama_kelas` AS `nama_kelas`, `k`.`tahun_ajaran` AS `tahun_ajaran`, count(`s`.`id_siswa`) AS `jumlah_siswa` FROM (`kelas` `k` left join `siswa` `s` on((`k`.`id_kelas` = `s`.`id_kelas`))) GROUP BY `k`.`id_kelas` ;

-- --------------------------------------------------------

--
-- Structure for view `view_mapel_guru`
--
DROP TABLE IF EXISTS `view_mapel_guru`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_mapel_guru`  AS SELECT `m`.`id_mapel` AS `id_mapel`, `m`.`nama_mapel` AS `nama_mapel`, `m`.`deskripsi` AS `deskripsi`, `g`.`id_guru` AS `id_guru`, `g`.`nama_guru` AS `nama_guru`, `g`.`nip` AS `nip` FROM (`mata_pelajaran` `m` left join `guru` `g` on((`m`.`id_guru` = `g`.`id_guru`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_rapor_siswa`
--
DROP TABLE IF EXISTS `view_rapor_siswa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_rapor_siswa`  AS SELECT `n`.`id_nilai` AS `id_nilai`, `n`.`nilai` AS `nilai`, `n`.`semester` AS `semester`, `n`.`tahun_ajaran` AS `tahun_ajaran`, `s`.`id_siswa` AS `id_siswa`, `s`.`nis` AS `nis`, `s`.`nama_siswa` AS `nama_siswa`, `k`.`id_kelas` AS `id_kelas`, `k`.`nama_kelas` AS `nama_kelas`, `m`.`id_mapel` AS `id_mapel`, `m`.`nama_mapel` AS `nama_mapel`, `g`.`id_guru` AS `id_guru`, `g`.`nama_guru` AS `nama_guru` FROM ((((`nilai` `n` left join `siswa` `s` on((`n`.`id_siswa` = `s`.`id_siswa`))) left join `kelas` `k` on((`s`.`id_kelas` = `k`.`id_kelas`))) left join `mata_pelajaran` `m` on((`n`.`id_mapel` = `m`.`id_mapel`))) left join `guru` `g` on((`m`.`id_guru` = `g`.`id_guru`))) ;

-- --------------------------------------------------------

--
-- Structure for view `view_siswa_kelas`
--
DROP TABLE IF EXISTS `view_siswa_kelas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_siswa_kelas`  AS SELECT `s`.`id_siswa` AS `id_siswa`, `s`.`nis` AS `nis`, `s`.`nama_siswa` AS `nama_siswa`, `s`.`tanggal_lahir` AS `tanggal_lahir`, `s`.`jenis_kelamin` AS `jenis_kelamin`, `s`.`alamat` AS `alamat`, `k`.`id_kelas` AS `id_kelas`, `k`.`nama_kelas` AS `nama_kelas`, `k`.`tahun_ajaran` AS `tahun_ajaran` FROM (`siswa` `s` left join `kelas` `k` on((`s`.`id_kelas` = `k`.`id_kelas`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_guru` (`id_guru`),
  ADD KEY `id_mapel` (`id_mapel`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`);

--
-- Indexes for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  ADD PRIMARY KEY (`id_mapel`),
  ADD KEY `id_guru` (`id_guru`);

--
-- Indexes for table `nilai`
--
ALTER TABLE `nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `id_mapel` (`id_mapel`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id_guru` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  MODIFY `id_mapel` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id_nilai` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`),
  ADD CONSTRAINT `jadwal_ibfk_3` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id_mapel`);

--
-- Constraints for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  ADD CONSTRAINT `mata_pelajaran_ibfk_1` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`);

--
-- Constraints for table `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`),
  ADD CONSTRAINT `nilai_ibfk_2` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id_mapel`);

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
