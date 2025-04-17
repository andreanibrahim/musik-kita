-- Adminer 4.8.1 MySQL 9.2.0 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `offline_transaction_details`;
CREATE TABLE `offline_transaction_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_transaction` int NOT NULL,
  `id_produk` int DEFAULT NULL,
  `jumlah` int NOT NULL,
  `subtotal` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_transaction` (`id_transaction`),
  KEY `id_produk` (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `offline_transaction_details` (`id`, `id_transaction`, `id_produk`, `jumlah`, `subtotal`) VALUES
(1,	1,	1,	1,	1500000),
(3,	7,	1,	1,	1500000),
(4,	8,	4,	1,	100000),
(5,	9,	4,	1,	100000),
(16,	20,	3,	1,	3000000),
(17,	21,	3,	1,	3000000),
(18,	22,	3,	1,	3000000),
(19,	23,	3,	1,	3000000),
(20,	24,	3,	1,	3000000),
(21,	25,	3,	1,	3000000),
(22,	26,	3,	1,	3000000),
(23,	27,	3,	1,	3000000),
(24,	28,	2,	1,	5000000),
(25,	29,	1,	1,	1500000),
(26,	30,	3,	1,	3000000),
(27,	31,	4,	1,	100000),
(28,	32,	2,	1,	5000000);

DROP TABLE IF EXISTS `offline_transactions`;
CREATE TABLE `offline_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_produk` int NOT NULL,
  `kasir_id` int NOT NULL,
  `total` int NOT NULL,
  `transaction_code` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` int NOT NULL,
  `nama_pelanggan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_produk` (`id_produk`),
  CONSTRAINT `offline_transactions_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `offline_transactions` (`id`, `id_produk`, `kasir_id`, `total`, `transaction_code`, `jumlah`, `subtotal`, `nama_pelanggan`, `tanggal`) VALUES
(6,	1,	2,	1500000,	'OFF-67FFAD106DB93',	1,	1500000,	NULL,	'2025-04-16 13:13:52'),
(7,	1,	2,	1500000,	'OFF-67FFAD84D0CFD',	1,	1500000,	NULL,	'2025-04-16 13:15:48'),
(8,	4,	2,	100000,	'OFF-67FFAD9D6449F',	1,	100000,	NULL,	'2025-04-16 13:16:13'),
(9,	4,	2,	100000,	'OFF-67FFAE543A240',	1,	100000,	NULL,	'2025-04-16 13:19:16'),
(10,	1,	2,	1500000,	'OFF-67FFAE573F049',	1,	1500000,	NULL,	'2025-04-16 13:19:19'),
(11,	1,	2,	1500000,	'OFF-67FFAEEE91375',	1,	1500000,	NULL,	'2025-04-16 13:21:50'),
(12,	3,	2,	3000000,	'OFF-67FFAEF13ED4C',	1,	3000000,	NULL,	'2025-04-16 13:21:53'),
(13,	3,	2,	3000000,	'OFF-67FFAEF4540BC',	1,	3000000,	NULL,	'2025-04-16 13:21:56'),
(14,	3,	2,	3000000,	'OFF-67FFAEF57941B',	1,	3000000,	NULL,	'2025-04-16 13:21:57'),
(15,	3,	2,	3000000,	'OFF-67FFAF048CAE7',	1,	3000000,	NULL,	'2025-04-16 13:22:12'),
(16,	3,	2,	3000000,	'OFF-67FFAF13E4665',	1,	3000000,	NULL,	'2025-04-16 13:22:27'),
(17,	3,	2,	3000000,	'OFF-67FFAF62B8E91',	1,	3000000,	NULL,	'2025-04-16 13:23:46'),
(18,	3,	2,	3000000,	'OFF-67FFB190D457C',	1,	3000000,	NULL,	'2025-04-16 13:33:04'),
(19,	3,	2,	3000000,	'OFF-67FFB29A49292',	1,	3000000,	NULL,	'2025-04-16 13:37:30'),
(20,	3,	2,	3000000,	'OFF-67FFB317DCE14',	1,	3000000,	NULL,	'2025-04-16 13:39:35'),
(21,	3,	2,	3000000,	'OFF-67FFB386370E3',	1,	3000000,	NULL,	'2025-04-16 13:41:26'),
(22,	3,	2,	3000000,	'OFF-67FFB395A7485',	1,	3000000,	NULL,	'2025-04-16 13:41:41'),
(23,	3,	2,	3000000,	'OFF-67FFB397BA78C',	1,	3000000,	NULL,	'2025-04-16 13:41:43'),
(24,	3,	2,	3000000,	'OFF-67FFB39ECE113',	1,	3000000,	NULL,	'2025-04-16 13:41:50'),
(25,	3,	2,	3000000,	'OFF-67FFB3BBA770B',	1,	3000000,	NULL,	'2025-04-16 13:42:19'),
(26,	3,	2,	3000000,	'OFF-67FFB43C4F402',	1,	3000000,	NULL,	'2025-04-16 13:44:28'),
(27,	3,	2,	3000000,	'OFF-67FFB442D0D2E',	1,	3000000,	NULL,	'2025-04-16 13:44:34'),
(28,	2,	2,	5000000,	'OFF-67FFB44C831C8',	1,	5000000,	NULL,	'2025-04-16 13:44:44'),
(29,	1,	2,	1500000,	'OFF-68004F7BA23A3',	1,	1500000,	NULL,	'2025-04-17 00:46:51'),
(30,	3,	2,	3000000,	'OFF-680058A19220C',	1,	3000000,	NULL,	'2025-04-17 01:25:53'),
(31,	4,	2,	100000,	'OFF-680058A90AA8E',	1,	100000,	NULL,	'2025-04-17 01:26:01'),
(32,	2,	2,	5000000,	'OFF-680058AE483EC',	1,	5000000,	NULL,	'2025-04-17 01:26:06');

DROP TABLE IF EXISTS `online_transactions`;
CREATE TABLE `online_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_pembeli` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email_pembeli` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `alamat_pembeli` text COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total` int NOT NULL,
  `status` enum('pending','diproses','selesai','dibatalkan') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `payment_method` enum('cod','transfer') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cod',
  `proof_of_payment` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `online_transactions` (`id`, `order_code`, `nama_pembeli`, `email_pembeli`, `alamat_pembeli`, `tanggal`, `total`, `status`, `payment_method`, `proof_of_payment`) VALUES
(1,	'ORD-67FF8E454DC3E',	'Andrean',	'andreanmaulanaibrahim@gmail.com',	'Jl. Raya Cimareme No 230/10',	'2025-04-16 11:02:29',	1500000,	'selesai',	'transfer',	'uploads/proof_ORD-67FF8E454DC3E.jpeg'),
(2,	'ORD-67FF8E454DC6O',	'Igo',	'igo@gmail.com',	'Jl. Raya Cimareme No 230/10',	'2025-04-17 11:02:29',	1500000,	'selesai',	'transfer',	'uploads/proof_ORD-67FF8E454DC3E.jpeg'),
(4,	'ORD-67FF8E454DC9W',	'Andrean',	'andreanmaulanaibrahim@gmail.com',	'Jl. Raya Cimareme No 230/10',	'2025-04-16 12:02:29',	5000000,	'selesai',	'transfer',	'uploads/proof_ORD-67FF8E454DC3E.jpeg');

DROP TABLE IF EXISTS `produk`;
CREATE TABLE `produk` (
  `id_produk` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kategori` enum('Gitar','Drum','Keyboard','Aksesoris') COLLATE utf8mb4_general_ci NOT NULL,
  `harga` int NOT NULL,
  `stok` int NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `produk` (`id_produk`, `nama`, `kategori`, `harga`, `stok`, `deskripsi`, `image`, `created_at`) VALUES
(1,	'Gitar Akustik Yamaha',	'Gitar',	1500000,	99,	'0',	'assets/images/67ff92c921d23.jpg',	'2025-04-16 10:31:22'),
(2,	'Drum Set Pearl',	'Drum',	5000000,	98,	'0',	'assets/images/67ff92e77d6b9.jpg',	'2025-04-16 10:31:22'),
(3,	'Keyboard Roland',	'Keyboard',	3000000,	91,	'Keyboard Roland dengan 61 tuts, fitur canggih untuk musisi modern.',	'assets/images/keyboard_roland.jpg',	'2025-04-16 10:31:22'),
(4,	'Senar Gitar DAddario',	'Aksesoris',	100000,	47,	'Senar gitar berkualitas dari DAddario, tahan lama dan suara jernih.',	'assets/images/senar_daddario.jpg',	'2025-04-16 10:31:22');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','kasir') COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama`, `email`, `created_at`) VALUES
(1,	'admin',	'$2y$10$zXNVwTj7EQLSY.I8RmLHIeZrCMB.ho0.JXYgw27KXW3qQFEjGmLlG',	'admin',	'Admin MusikKita',	'admin@musikkita.com',	'2025-04-16 10:31:22'),
(2,	'kasir',	'$2y$10$BnODF3/3FwGWPGH.9KsX1OGQT/.4NBTKnJv1i/ML5wiFYoHK9AGTm',	'kasir',	'Kasir MusikKita',	'kasir@musikkita.com',	'2025-04-16 10:31:22');

-- 2025-04-17 01:29:01
