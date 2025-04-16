-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2025 at 02:43 PM
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
-- Database: `musikkita_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `offline_transactions`
--

CREATE TABLE `offline_transactions` (
  `id` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `kasir_id` int(11) NOT NULL,
  `total` int(100) NOT NULL,
  `transaction_code` varchar(255) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offline_transaction_details`
--

CREATE TABLE `offline_transaction_details` (
  `id` int(11) NOT NULL,
  `id_transaction` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offline_transaction_details`
--

INSERT INTO `offline_transaction_details` (`id`, `id_transaction`, `id_produk`, `jumlah`, `subtotal`) VALUES
(1, 1, 1, 1, 1500000);

-- --------------------------------------------------------

--
-- Table structure for table `online_transactions`
--

CREATE TABLE `online_transactions` (
  `id` int(11) NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `email_pembeli` varchar(100) NOT NULL,
  `alamat_pembeli` text NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` int(11) NOT NULL,
  `status` enum('pending','diproses','selesai','dibatalkan') DEFAULT 'pending',
  `payment_method` enum('cod','transfer') NOT NULL DEFAULT 'cod',
  `proof_of_payment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `online_transactions`
--

INSERT INTO `online_transactions` (`id`, `order_code`, `nama_pembeli`, `email_pembeli`, `alamat_pembeli`, `tanggal`, `total`, `status`, `payment_method`, `proof_of_payment`) VALUES
(1, 'ORD-67FF8E454DC3E', 'Andrean', 'andreanmaulanaibrahim@gmail.com', 'Jl. Raya Cimareme No 230/10', '2025-04-16 11:02:29', 1500000, 'diproses', 'transfer', 'uploads/proof_ORD-67FF8E454DC3E.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kategori` enum('Gitar','Drum','Keyboard','Aksesoris') NOT NULL,
  `harga` int(11) NOT NULL,
  `stok` int(11) NOT NULL,
  `deskripsi` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id_produk`, `nama`, `kategori`, `harga`, `stok`, `deskripsi`, `image`, `created_at`) VALUES
(1, 'Gitar Akustik Yamaha', 'Gitar', 1500000, 9, '0', 'assets/images/67ff92c921d23.jpg', '2025-04-16 10:31:22'),
(2, 'Drum Set Pearl', 'Drum', 5000000, 5, '0', 'assets/images/67ff92e77d6b9.jpg', '2025-04-16 10:31:22'),
(3, 'Keyboard Roland', 'Keyboard', 3000000, 8, 'Keyboard Roland dengan 61 tuts, fitur canggih untuk musisi modern.', 'assets/images/keyboard_roland.jpg', '2025-04-16 10:31:22'),
(4, 'Senar Gitar DAddario', 'Aksesoris', 100000, 50, 'Senar gitar berkualitas dari DAddario, tahan lama dan suara jernih.', 'assets/images/senar_daddario.jpg', '2025-04-16 10:31:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir') NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$zXNVwTj7EQLSY.I8RmLHIeZrCMB.ho0.JXYgw27KXW3qQFEjGmLlG', 'admin', 'Admin MusikKita', 'admin@musikkita.com', '2025-04-16 10:31:22'),
(2, 'kasir', '$2y$10$BnODF3/3FwGWPGH.9KsX1OGQT/.4NBTKnJv1i/ML5wiFYoHK9AGTm', 'kasir', 'Kasir MusikKita', 'kasir@musikkita.com', '2025-04-16 10:31:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `offline_transactions`
--
ALTER TABLE `offline_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `offline_transaction_details`
--
ALTER TABLE `offline_transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_transaction` (`id_transaction`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `online_transactions`
--
ALTER TABLE `online_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `offline_transactions`
--
ALTER TABLE `offline_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `offline_transaction_details`
--
ALTER TABLE `offline_transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `online_transactions`
--
ALTER TABLE `online_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `offline_transactions`
--
ALTER TABLE `offline_transactions`
  ADD CONSTRAINT `offline_transactions_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Constraints for table `offline_transaction_details`
--
ALTER TABLE `offline_transaction_details`
  ADD CONSTRAINT `offline_transaction_details_ibfk_1` FOREIGN KEY (`id_transaction`) REFERENCES `online_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_transaction_details_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
