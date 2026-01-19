-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Nov 2025 pada 05.40
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
-- Database: `toko_online`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `regency_id` int(11) NOT NULL,
  `detail` text DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `province_id`, `regency_id`, `detail`, `postal_code`) VALUES
(12, 3, 1, 1, 'Jl. Manduhara No. 18', '73111'),
(13, 3, 1, 1, 'Jl. Manduhara No. 18', '73111'),
(14, 3, 1, 1, 'Jl. Manduhara No. 18', '73111'),
(15, 3, 1, 1, 'Jl. Manduhara No. 18', '73111');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Baju', 'Baju Wawan Store'),
(2, 'Celana', 'Celana Wawan Store'),
(3, 'Sepatu', 'Sepatu Wawan Store'),
(4, 'Sandal', 'Sandal Wawan Store'),
(5, 'Tas', 'Tas Wawan Store');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','shipped','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `address_id`, `total_amount`, `status`, `created_at`) VALUES
(9, 'INV-20250917-5654', 3, 12, 2274000.00, 'completed', '2025-09-17 05:56:31'),
(10, 'INV-20250917-1490', 3, 13, 3020000.00, 'completed', '2025-09-17 06:07:46'),
(11, 'INV-20250917-6385', 3, 14, 1070000.00, 'completed', '2025-09-17 07:54:36'),
(12, 'INV-20250918-5929', 3, 15, 2270000.00, 'completed', '2025-09-18 02:00:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(7, 9, 5, 3, 300000.00),
(8, 9, 7, 3, 450000.00),
(9, 10, 4, 5, 600000.00),
(10, 11, 2, 2, 75000.00),
(11, 11, 3, 2, 450000.00),
(12, 12, 3, 5, 450000.00);

--
-- Trigger `order_items`
--
DELIMITER $$
CREATE TRIGGER `trg_reduce_stock` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET stock = stock - NEW.quantity
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_restock` AFTER DELETE ON `order_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET stock = stock + OLD.quantity
    WHERE id = OLD.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `method` enum('transfer','cod','ewallet') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','confirmed','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `proof` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `method`, `amount`, `status`, `paid_at`, `proof`, `notes`, `created_at`) VALUES
(9, 9, 'ewallet', 2274000.00, 'confirmed', '2025-09-17 05:57:06', 'proof_1758088626_9126.jpg', NULL, '2025-09-17 05:56:32'),
(10, 10, 'transfer', 3020000.00, 'confirmed', '2025-09-17 06:08:10', 'proof_1758089290_4721.jpg', NULL, '2025-09-17 06:07:46'),
(11, 11, 'transfer', 1070000.00, 'confirmed', '2025-09-17 07:55:04', 'proof_1758095704_8360.jpg', NULL, '2025-09-17 07:54:36'),
(12, 12, 'ewallet', 2270000.00, 'confirmed', '2025-09-18 02:02:46', 'proof_1758160966_2191.jpg', NULL, '2025-09-18 02:00:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `image`) VALUES
(1, 1, 'Baju Street Wear Hip Hop', 'Baju Street Wear Hip Hop\r\nBiru\r\nAll Size\r\nUnisex\r\nBahan Aman dan Lembut', 65000.00, 50, '1757910044_03a6814e.jpg'),
(2, 1, 'Baju Hitam Paradiso', 'Baju Hitam Paradiso\r\nHitam All Size\r\nUnisex\r\nBahan Nyaman dan Lembut sekali', 75000.00, 38, '1757910197_958864c4.jpg'),
(3, 2, 'Celana Chinos Pria', 'Celana Chinos Pria', 450000.00, 13, '1757914557_6084b6ac.jpg'),
(4, 2, 'Celana Johnwin Pria', 'Celana Johnwin', 600000.00, 25, '1757999424_c180b000.jpg'),
(5, 3, 'Sepatu Pria Duff Brezy', 'Sepatu Pria Duff Brezy', 300000.00, 17, '1757999469_cdc8e6a4.png'),
(6, 4, 'Sandal Gunung', 'Sandal Gunung', 50000.00, 20, '1757999514_3c16a3b4.png'),
(7, 3, 'Sepatu Hitam Mills', 'Sepatu Hitam Mills', 450000.00, 37, '1758001580_8c294917.png'),
(8, 4, 'Sendal Jepit Wanita', 'Sendal Jepit Wanita', 45000.00, 100, '1758001613_1a274e3c.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `provinces`
--

CREATE TABLE `provinces` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `provinces`
--

INSERT INTO `provinces` (`id`, `name`) VALUES
(1, 'Kalimantan Tengah'),
(2, 'Kalimantan Selatan'),
(3, 'Kalimantan Barat'),
(4, 'Kalimantan Timur'),
(5, 'Kalimantan Utara');

-- --------------------------------------------------------

--
-- Struktur dari tabel `regencies`
--

CREATE TABLE `regencies` (
  `id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `regencies`
--

INSERT INTO `regencies` (`id`, `province_id`, `name`, `postal_code`) VALUES
(1, 1, 'Palangka Raya', '73111'),
(2, 1, 'Kotawaringin Barat', '74111'),
(3, 1, 'Kotawaringin Timur', '74364'),
(4, 1, 'Kapuas', '73511'),
(5, 1, 'Sukamara', '74172'),
(6, 1, 'Lamandau', '74162');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` int(11) NOT NULL,
  `regency_id` int(11) NOT NULL,
  `courier` enum('JNE','POS Indonesia','JNT','SPX','TIKI') NOT NULL,
  `cost` decimal(12,2) NOT NULL,
  `estimated_days` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `shipping_rates`
--

INSERT INTO `shipping_rates` (`id`, `regency_id`, `courier`, `cost`, `estimated_days`) VALUES
(3, 1, 'JNT', 24000.00, '2-3 hari'),
(4, 1, 'SPX', 20000.00, '3-5 hari'),
(5, 1, 'TIKI', 26000.00, '2-4 hari'),
(6, 2, 'JNE', 30000.00, '3-5 hari'),
(7, 2, 'POS Indonesia', 28000.00, '3-6 hari'),
(8, 2, 'JNT', 29000.00, '3-5 hari'),
(9, 2, 'SPX', 25000.00, '4-6 hari'),
(10, 2, 'TIKI', 31000.00, '3-5 hari'),
(11, 3, 'JNE', 28000.00, '3-4 hari'),
(12, 3, 'POS Indonesia', 26000.00, '3-5 hari'),
(13, 3, 'JNT', 27000.00, '3-4 hari'),
(14, 3, 'SPX', 23000.00, '4-6 hari'),
(15, 3, 'TIKI', 29000.00, '3-5 hari'),
(16, 4, 'JNE', 35000.00, '4-6 hari'),
(17, 4, 'POS Indonesia', 32000.00, '4-7 hari'),
(18, 4, 'JNT', 34000.00, '4-6 hari'),
(19, 4, 'SPX', 30000.00, '5-7 hari'),
(20, 4, 'TIKI', 36000.00, '4-6 hari'),
(23, 1, 'JNE', 25000.00, '2-3 hari'),
(24, 1, 'POS Indonesia', 20000.00, '1-2 hari'),
(26, 6, 'JNE', 30000.00, '2-3 hari'),
(27, 6, 'POS Indonesia', 25000.00, '1-2 hari'),
(28, 6, 'JNT', 25000.00, '1-2 hari'),
(29, 6, 'SPX', 35000.00, '2-3 hari'),
(30, 6, 'TIKI', 30000.00, '4-5 hari'),
(31, 5, 'JNE', 40000.00, '2-3 hari'),
(32, 5, 'POS Indonesia', 25000.00, '1-2 hari'),
(33, 5, 'JNT', 30000.00, '2-3 hari'),
(34, 5, 'SPX', 40000.00, '4-5 hari'),
(35, 5, 'TIKI', 35000.00, '4-5 hari');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@toko.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', '2025-09-14 15:21:42'),
(2, 'Customer', 'user@toko.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'customer', '2025-09-14 15:21:42'),
(3, 'Tertu Akikkuti Jordan', 'tertuakikkutijordan@gmail.com', '$2y$10$YWzy4JC1znkwiFpHgdzfTeE5Dc4r.XcmuYe.gwiek9D/wy8PgxIMm', '082251548898', 'customer', '2025-09-14 16:06:36'),
(4, 'Wawan Suharmanto', 'wawanpos@gmail.com', '$2y$10$Jbgm9vXxEMyKtmhRMPz.u.nHSYZiOmCxjNQ.0S0VdokcCYBHQX0/u', '082251373438', 'admin', '2025-09-16 05:14:28');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `province_id` (`province_id`),
  ADD KEY `regency_id` (`regency_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_orders_order_number` (`order_number`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `idx_orders_user` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category_id`);

--
-- Indeks untuk tabel `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `regencies`
--
ALTER TABLE `regencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indeks untuk tabel `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shipping_regency` (`regency_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `regencies`
--
ALTER TABLE `regencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `shipping_rates`
--
ALTER TABLE `shipping_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `addresses_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `addresses_ibfk_3` FOREIGN KEY (`regency_id`) REFERENCES `regencies` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `regencies`
--
ALTER TABLE `regencies`
  ADD CONSTRAINT `regencies_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD CONSTRAINT `shipping_rates_ibfk_1` FOREIGN KEY (`regency_id`) REFERENCES `regencies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
