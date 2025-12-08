-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 08:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pontaj_firma`
--

-- --------------------------------------------------------

--
-- Table structure for table `angajati`
--

CREATE TABLE `angajati` (
  `id` int(11) NOT NULL,
  `nume` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `parola_hash` varchar(255) NOT NULL,
  `departament` varchar(50) DEFAULT NULL,
  `data_angajare` date DEFAULT NULL,
  `este_admin` tinyint(1) DEFAULT 0,
  `tip_program_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `angajati`
--

INSERT INTO `angajati` (`id`, `nume`, `email`, `parola_hash`, `departament`, `data_angajare`, `este_admin`, `tip_program_id`) VALUES
(1, 'Admin Principal', 'admin@firma.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Management', '2024-01-01', 1, 2),
(2, 'Mihai Popescu', 'mihai@popescu.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'IT', '2024-01-15', 0, 1),
(3, 'Ana Ionescu', 'ana@ionescu.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Vanzari', '2024-02-01', 0, 2),
(4, 'craciun', 'awad@asdjn.com', '$2y$10$QfIekvc1pPfvpbiOpG/6qeXVQtQVKoOtW5zLQpUP8lIIHYeIirfl.', 'Vanzari', '2025-12-02', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `cereri_concediu`
--

CREATE TABLE `cereri_concediu` (
  `id` int(11) NOT NULL,
  `angajat_id` int(11) NOT NULL,
  `data_start` date NOT NULL,
  `data_end` date NOT NULL,
  `tip_concediu` varchar(50) DEFAULT NULL,
  `stare` enum('în așteptare','aprobat','respins') DEFAULT 'în așteptare',
  `motiv` text DEFAULT NULL,
  `data_cerere` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orar_angajati`
--

CREATE TABLE `orar_angajati` (
  `id` int(11) NOT NULL,
  `angajat_id` int(11) NOT NULL,
  `data_start` date NOT NULL,
  `tura_id` int(11) NOT NULL,
  `ora_start_efectiva` datetime DEFAULT NULL,
  `ora_end_efectiva` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orar_angajati`
--

INSERT INTO `orar_angajati` (`id`, `angajat_id`, `data_start`, `tura_id`, `ora_start_efectiva`, `ora_end_efectiva`) VALUES
(7, 2, '2025-11-24', 1, '2025-11-24 00:47:56', '2025-11-24 08:47:56'),
(8, 2, '2025-11-25', 1, '2025-11-25 00:47:56', '2025-11-25 08:47:56'),
(9, 2, '2025-11-26', 2, '2025-11-26 08:47:56', '2025-11-26 16:47:56'),
(10, 2, '2025-11-27', 1, '2025-11-27 00:47:56', '2025-11-27 08:47:56'),
(11, 2, '2025-11-28', 2, '2025-11-28 08:47:56', '2025-11-28 16:47:56'),
(12, 3, '2025-11-24', 4, '2025-11-24 01:47:56', '2025-11-24 13:47:56'),
(13, 3, '2025-11-25', 5, '2025-11-25 13:47:56', '2025-11-26 01:47:56'),
(14, 3, '2025-11-27', 4, '2025-11-27 01:47:56', '2025-11-27 13:47:56'),
(15, 1, '2025-11-24', 1, '2025-11-24 02:47:56', '2025-11-24 10:47:56'),
(17, 1, '2025-11-26', 1, '2025-11-26 02:47:56', '2025-11-26 10:47:56'),
(18, 3, '2025-11-27', 1, '2025-11-27 06:00:00', '2025-11-27 14:00:00'),
(19, 3, '2025-11-27', 1, '2025-11-27 06:00:00', '2025-11-27 14:00:00'),
(20, 3, '2025-11-27', 1, '2025-11-27 06:00:00', '2025-11-27 14:00:00'),
(21, 1, '2025-11-28', 3, '2025-11-28 22:00:00', '2025-11-29 06:00:00'),
(22, 3, '2025-12-01', 5, '2025-12-01 19:00:00', '2025-12-02 07:00:00'),
(23, 2, '2025-12-01', 4, '2025-12-01 07:00:00', '2025-12-01 19:00:00'),
(24, 1, '2025-12-01', 4, '2025-12-01 07:00:00', '2025-12-01 19:00:00'),
(25, 1, '2025-12-02', 2, '2025-12-02 14:00:00', '2025-12-02 22:00:00'),
(26, 1, '2025-11-29', 4, '2025-11-29 07:00:00', '2025-11-29 19:00:00'),
(27, 2, '2025-12-02', 4, '2025-12-02 07:00:00', '2025-12-02 19:00:00'),
(28, 2, '2025-12-03', 4, '2025-12-03 07:00:00', '2025-12-03 19:00:00'),
(29, 2, '2025-12-04', 4, '2025-12-04 07:00:00', '2025-12-04 19:00:00'),
(30, 2, '2025-12-05', 4, '2025-12-05 07:00:00', '2025-12-05 19:00:00'),
(31, 2, '2025-12-06', 4, '2025-12-06 07:00:00', '2025-12-06 19:00:00'),
(32, 2, '2025-12-07', 5, '2025-12-07 19:00:00', '2025-12-08 07:00:00'),
(33, 3, '2025-12-02', 2, '2025-12-02 14:00:00', '2025-12-02 22:00:00'),
(34, 3, '2025-12-03', 2, '2025-12-03 14:00:00', '2025-12-03 22:00:00'),
(35, 3, '2025-12-04', 1, '2025-12-04 06:00:00', '2025-12-04 14:00:00'),
(36, 3, '2025-12-05', 4, '2025-12-05 07:00:00', '2025-12-05 19:00:00'),
(37, 1, '2025-12-03', 4, '2025-12-03 07:00:00', '2025-12-03 19:00:00'),
(38, 3, '2025-12-08', 1, '2025-12-08 06:00:00', '2025-12-08 14:00:00'),
(39, 2, '2025-12-10', 5, '2025-12-10 19:00:00', '2025-12-11 07:00:00'),
(40, 3, '2025-12-11', 5, '2025-12-11 19:00:00', '2025-12-12 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `pontaje`
--

CREATE TABLE `pontaje` (
  `id` int(11) NOT NULL,
  `angajat_id` int(11) NOT NULL,
  `data_pontaj` datetime DEFAULT current_timestamp(),
  `tip` enum('intrare','iesire') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pontaje`
--

INSERT INTO `pontaje` (`id`, `angajat_id`, `data_pontaj`, `tip`) VALUES
(1, 1, '2025-11-28 02:29:55', 'intrare'),
(2, 1, '2025-11-28 02:29:57', 'iesire'),
(3, 2, '2025-11-24 05:55:00', 'intrare'),
(4, 2, '2025-11-24 14:05:00', 'iesire'),
(5, 2, '2025-11-25 05:50:00', 'intrare'),
(6, 2, '2025-11-25 14:10:00', 'iesire'),
(7, 2, '2025-11-26 13:55:00', 'intrare'),
(8, 2, '2025-11-26 22:05:00', 'iesire'),
(9, 3, '2025-11-24 06:55:00', 'intrare'),
(10, 3, '2025-11-24 19:05:00', 'iesire'),
(11, 3, '2025-11-25 18:55:00', 'intrare'),
(12, 3, '2025-11-26 07:05:00', 'iesire'),
(13, 1, '2025-11-24 08:00:00', 'intrare'),
(14, 1, '2025-11-24 16:00:00', 'iesire'),
(15, 1, '2025-11-25 08:05:00', 'intrare'),
(16, 1, '2025-11-25 16:10:00', 'iesire'),
(17, 2, '2025-11-28 03:10:47', 'intrare'),
(18, 2, '2025-11-28 03:10:49', 'iesire'),
(19, 1, '2025-11-28 15:08:18', 'intrare'),
(20, 1, '2025-11-28 15:08:19', 'iesire'),
(21, 2, '2025-11-30 15:18:00', 'intrare'),
(22, 2, '2025-11-30 15:18:01', 'iesire'),
(23, 1, '2025-11-30 15:37:54', 'intrare'),
(24, 1, '2025-11-30 15:37:56', 'iesire'),
(25, 1, '2025-11-30 15:38:03', 'intrare'),
(26, 1, '2025-11-30 15:38:04', 'iesire'),
(27, 2, '2025-11-30 15:39:49', 'intrare'),
(28, 2, '2025-11-30 15:39:50', 'iesire'),
(29, 1, '2025-12-01 22:54:46', 'intrare'),
(30, 1, '2025-12-01 22:54:47', 'iesire'),
(31, 1, '2025-12-01 08:00:00', 'intrare'),
(32, 1, '2025-12-01 16:30:00', 'iesire'),
(33, 1, '2025-12-02 16:20:47', 'intrare'),
(34, 1, '2025-12-02 16:20:53', 'iesire'),
(35, 2, '2025-12-02 18:39:53', 'intrare'),
(36, 2, '2025-12-02 18:40:32', 'iesire'),
(37, 2, '2025-12-07 23:31:17', 'intrare'),
(38, 2, '2025-12-07 23:31:28', 'iesire');

-- --------------------------------------------------------

--
-- Table structure for table `statistici_ore`
--

CREATE TABLE `statistici_ore` (
  `id` int(11) NOT NULL,
  `angajat_id` int(11) NOT NULL,
  `luna` int(11) NOT NULL,
  `an` int(11) NOT NULL,
  `ore_lucrate_total` decimal(6,2) DEFAULT 0.00,
  `ore_suplimentare` decimal(6,2) DEFAULT 0.00,
  `ore_noapte` decimal(6,2) DEFAULT 0.00,
  `ore_maxime_luna` decimal(6,2) DEFAULT 0.00,
  `zile_lucratoare_luna` int(11) DEFAULT 0,
  `data_ultima_actualizare` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipuri_program`
--

CREATE TABLE `tipuri_program` (
  `id` int(11) NOT NULL,
  `nume_program` varchar(50) NOT NULL,
  `ore_pe_zi` decimal(4,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipuri_program`
--

INSERT INTO `tipuri_program` (`id`, `nume_program`, `ore_pe_zi`) VALUES
(1, '8 ore', 8.00),
(2, '12 ore', 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `ture`
--

CREATE TABLE `ture` (
  `id` int(11) NOT NULL,
  `tip_program_id` int(11) NOT NULL,
  `nume_tura` varchar(20) NOT NULL,
  `ora_start` time NOT NULL,
  `ora_end` time NOT NULL,
  `trece_peste_zi` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ture`
--

INSERT INTO `ture` (`id`, `tip_program_id`, `nume_tura`, `ora_start`, `ora_end`, `trece_peste_zi`) VALUES
(1, 1, 'T1', '06:00:00', '14:00:00', 0),
(2, 1, 'T2', '14:00:00', '22:00:00', 0),
(3, 1, 'T3', '22:00:00', '06:00:00', 1),
(4, 2, 'T1', '07:00:00', '19:00:00', 0),
(5, 2, 'T2', '19:00:00', '07:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `zile_libere`
--

CREATE TABLE `zile_libere` (
  `id` int(11) NOT NULL,
  `nume_zi` varchar(100) NOT NULL,
  `data_zi` date NOT NULL,
  `an` int(11) NOT NULL,
  `este_recurent` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `angajati`
--
ALTER TABLE `angajati`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `tip_program_id` (`tip_program_id`);

--
-- Indexes for table `cereri_concediu`
--
ALTER TABLE `cereri_concediu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `angajat_id` (`angajat_id`);

--
-- Indexes for table `orar_angajati`
--
ALTER TABLE `orar_angajati`
  ADD PRIMARY KEY (`id`),
  ADD KEY `angajat_id` (`angajat_id`),
  ADD KEY `tura_id` (`tura_id`);

--
-- Indexes for table `pontaje`
--
ALTER TABLE `pontaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `angajat_id` (`angajat_id`);

--
-- Indexes for table `statistici_ore`
--
ALTER TABLE `statistici_ore`
  ADD PRIMARY KEY (`id`),
  ADD KEY `angajat_id` (`angajat_id`);

--
-- Indexes for table `tipuri_program`
--
ALTER TABLE `tipuri_program`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ture`
--
ALTER TABLE `ture`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tip_program_id` (`tip_program_id`);

--
-- Indexes for table `zile_libere`
--
ALTER TABLE `zile_libere`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `angajati`
--
ALTER TABLE `angajati`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cereri_concediu`
--
ALTER TABLE `cereri_concediu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orar_angajati`
--
ALTER TABLE `orar_angajati`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `pontaje`
--
ALTER TABLE `pontaje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `statistici_ore`
--
ALTER TABLE `statistici_ore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tipuri_program`
--
ALTER TABLE `tipuri_program`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ture`
--
ALTER TABLE `ture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `zile_libere`
--
ALTER TABLE `zile_libere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `angajati`
--
ALTER TABLE `angajati`
  ADD CONSTRAINT `angajati_ibfk_1` FOREIGN KEY (`tip_program_id`) REFERENCES `tipuri_program` (`id`);

--
-- Constraints for table `cereri_concediu`
--
ALTER TABLE `cereri_concediu`
  ADD CONSTRAINT `cereri_concediu_ibfk_1` FOREIGN KEY (`angajat_id`) REFERENCES `angajati` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orar_angajati`
--
ALTER TABLE `orar_angajati`
  ADD CONSTRAINT `orar_angajati_ibfk_1` FOREIGN KEY (`angajat_id`) REFERENCES `angajati` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orar_angajati_ibfk_2` FOREIGN KEY (`tura_id`) REFERENCES `ture` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pontaje`
--
ALTER TABLE `pontaje`
  ADD CONSTRAINT `pontaje_ibfk_1` FOREIGN KEY (`angajat_id`) REFERENCES `angajati` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `statistici_ore`
--
ALTER TABLE `statistici_ore`
  ADD CONSTRAINT `statistici_ore_ibfk_1` FOREIGN KEY (`angajat_id`) REFERENCES `angajati` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ture`
--
ALTER TABLE `ture`
  ADD CONSTRAINT `ture_ibfk_1` FOREIGN KEY (`tip_program_id`) REFERENCES `tipuri_program` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
