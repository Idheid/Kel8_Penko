-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 10, 2025 at 03:00 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/* !40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/* !40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/* !40101 SET NAMES utf8mb4 */;

--
-- Database: `penko_atk`
--
CREATE DATABASE IF NOT EXISTS `penko_atk` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `penko_atk`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `role` ENUM('admin', 'staff') DEFAULT 'staff',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--
INSERT INTO `users` (`username`, `password`, `name`, `email`, `role`) VALUES
('admin', '$2y$10$3jafoZXoUfMdDyvROQfHOuFTbL5wU9a0De6estQ8a60gIatDlAzti', 'Admin Utama', 'admin@example.com', 'admin'),
('staff1', '$2y$10$3jafoZXoUfMdDyvROQfHOuFTbL5wU9a0De6estQ8a60gIatDlAzti', 'Pegawai Satu', 'staff1@example.com', 'staff'),
('staff2', '$2y$10$3jafoZXoUfMdDyvROQfHOuFTbL5wU9a0De6estQ8a60gIatDlAzti', 'Pegawai Dua', 'staff2@example.com', 'staff');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--
CREATE TABLE `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `contact_person` VARCHAR(100),
    `email` VARCHAR(100),
    `phone` VARCHAR(20),
    `address` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--
INSERT INTO `suppliers` (`name`, `contact_person`, `email`, `phone`, `address`) VALUES
('PT Stationery Jaya', 'Budi Santoso', 'budi@distritek.com', '021-12345678', 'Jl. Sudirman No. 10, Jakarta'),
('CV Alat Tulis Makmur', 'Siti Aminah', 'siti@soluskantor.com', '081234567890', 'Jl. Gatot Subroto No. 25, Bandung'),
('Toko ATK Sejahtera', 'Ahmad Yani', 'ahmad@furniturplus.com', '031-98765432', 'Jl. Diponegoro No. 5, Surabaya');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--
INSERT INTO `categories` (`name`, `description`) VALUES
('Peralatan Tulis', 'Alat tulis dasar untuk keperluan kantor seperti pulpen dan pensil'),
('Alat Tulis Kertas', 'Produk berbasis kertas untuk kebutuhan kantor'),
('Peralatan Penyimpanan dan Pengarsipan', 'Alat untuk penyimpanan dan pengarsipan dokumen'),
('Peralatan Front Desk', 'Peralatan untuk kebutuhan meja resepsionis'),
('Peralatan Cetak dan Fotokopi', 'Peralatan untuk mencetak dan menyalin dokumen');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `description` text,
  `category_id` INT,
  `supplier_id` INT,
  `current_stock` int NOT NULL DEFAULT 0,
  `unit` varchar(20) NOT NULL,
  `price` DECIMAL(15,2) DEFAULT 0.00,
  `image` VARCHAR(255),
  `min_stock` int NOT NULL DEFAULT 10,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO `products` (`id`, `name`, `description`, `category_id`, `supplier_id`, `current_stock`, `unit`, `price`, `image`, `min_stock`) VALUES
(1, 'Pulpen Biru', 'Pulpen tinta biru, isi 12 buah per pak', 1, 3, 200, 'pak', 20000.00, 'https://images.pexels.com/photos/4266249/pexels-photo-4266249.jpeg?auto=compress&cs=tinysrgb&w=800', 20),
(3, 'Pensil HB', 'Pensil HB, isi 12 buah per pak', 1, 3, 150, 'pak', 15000.00, 'https://images.pexels.com/photos/159752/pencil-eraser-ruler-school-159752.jpeg?auto=compress&cs=tinysrgb&w=800', 15),
(5, 'Spidol Whiteboard', 'Spidol whiteboard warna, isi 4 buah per pak', 1, 3, 80, 'pak', 25000.00, 'https://images.pexels.com/photos/261896/pexels-photo-261896.jpeg?auto=compress&cs=tinysrgb&w=800', 22),
(7, 'Buku Catatan A5', 'Buku catatan A5, 100 halaman, garis', 3, 3, 100, 'pcs', 15000.00, 'https://images.pexels.com/photos/207662/pexels-photo-207662.jpeg?auto=compress&cs=tinysrgb&w=800', 50),
(9, 'Highlighter', 'Pen highlighter warna neon, isi 6 buah per pak', 1, 3, 70, 'pak', 20000.00, 'https://images.pexels.com/photos/261896/pexels-photo-261896.jpeg?auto=compress&cs=tinysrgb&w=800', 30),
(11, 'Ordner', 'Ordner A4 untuk arsip, kapasitas 500 lembar', 5, 3, 30, 'pcs', 40000.00, 'https://images.pexels.com/photos/5905163/pexels-photo-5905163.jpeg?auto=compress&cs=tinysrgb&w=800', 12);
-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` int NOT NULL,
  `type` enum('masuk','keluar') NOT NULL,
  `quantity` int NOT NULL,
  `date` date NOT NULL,
  `user_id` int NOT NULL,
  `reference` varchar(100),
  `notes` text,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `transactions` (`id`, `product_id`, `type`, `quantity`, `date`, `user_id`, `reference`, `notes`) VALUES
-- Peralatan Tulis
(1, 1, 'masuk', 250, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 'PO-001', 'Stok awal pulpen'),
(3, 3, 'masuk', 200, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 'PO-001', 'Stok awal pensil'),
(5, 5, 'masuk', 100, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 'PO-001', 'Stok awal spidol'),
(7, 7, 'masuk', 150, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 'PO-001', 'Stok awal penghapus'),
(9, 9, 'masuk', 120, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 'PO-002', 'Stok awal penggaris'),
(11, 11, 'masuk', 200, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 'PO-002', 'Stok awal rautan'),
(13, 11, 'masuk', 20, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 'PO-002', 'Stok awal tip-X');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--
CREATE TABLE `requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `requests` (`id`, `user_id`, `product_id`, `quantity`, `purpose`, `status`, `created_at`, `processed_at`, `admin_id`, `notes`) VALUES
(1, 3, 7, 10, 'Pengajuan buku catatan A5 untuk kegiatan pelatihan staf', 'pending', CURRENT_TIMESTAMP, NULL, NULL, NULL),
(3, 3, 1, 5, 'Permintaan pulpen untuk rapat mingguan divisi keuangan', 'approved', '2025-06-09 10:30:00', '2025-06-10 08:00:00', 1, 'Sudah disetujui oleh admin'),
(5, 5, 5, 3, 'Pengajuan spidol whiteboard untuk ruang meeting', 'rejected', '2025-06-08 14:15:00', '2025-06-09 09:00:00', 2, 'Stok tidak mencukupi');

-- --------------------------------------------------------

--
-- Indexes for tables

ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `admin_id` (`admin_id`);

-- --------------------------------------------------------

--
-- AUTO_INCREMENT for tables
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


COMMIT;
