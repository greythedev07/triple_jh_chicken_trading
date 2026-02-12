-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 07:41 PM
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
-- Database: `commissioned_app_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'admin@admin.com', '$2y$10$88YcSAdxdU3aGh5.C9YIauffZ2rjxaStYoDBsNHgFg8UH5zE78njG', '2025-11-25 08:25:20');

-- --------------------------------------------------------

--
-- Table structure for table `admin_keys`
--

CREATE TABLE `admin_keys` (
  `id` int(11) NOT NULL,
  `admin_key` varchar(255) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_keys`
--

INSERT INTO `admin_keys` (`id`, `admin_key`, `used`, `created_at`) VALUES
(1, '80085', 0, '2025-11-25 08:07:48');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `variant_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_data`)),
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `driver_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'img/profile_pic/default.png',
  `vehicle_type` varchar(50) DEFAULT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `date_joined` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `driver_code`, `name`, `email`, `phone`, `password`, `profile_picture`, `vehicle_type`, `license_no`, `address`, `is_active`, `date_joined`, `created_at`, `updated_at`) VALUES
(1, 'DRV-20251125-8291', 'chickenboy', 'chickenboy@gmail.com', '09666338920', '$2y$10$kZa8Z/VNY1xBPdGoj1EZE.Qzkh4xn8mcvgHUR9RCuinGZjaGqxYSe', 'img/profile_pic/default.png', 'Motorcycle', '1982390182', 'Verde Heights, San Jose Del Monte', 1, '2025-11-25 20:30:09', '2025-11-25 20:30:09', '2025-11-25 20:30:09'),
(2, 'DRV-20251201-8739', 'greyvocals', 'taptapgawe@gmail.com', '09351625312', '$2y$10$2oGqrj8Gy0GdWcOECqUTxeCuU54ko7qNsnznfAlK3zKLdFcVO97da', 'img/profile_pic/default.png', 'Bike', '73642872364', 'There', 0, '2025-12-01 03:13:46', '2025-12-01 03:13:46', '2025-12-01 03:13:46');

-- --------------------------------------------------------

--
-- Table structure for table `gcash_qr_codes`
--

CREATE TABLE `gcash_qr_codes` (
  `id` int(11) NOT NULL,
  `qr_code_url` varchar(500) NOT NULL,
  `qr_code_image` varchar(500) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gcash_qr_codes`
--

INSERT INTO `gcash_qr_codes` (`id`, `qr_code_url`, `qr_code_image`, `amount`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'https://qr.gcash.com/sample', 'uploads/qr_codes/gcash_qr_sample.png', 0.00, 1, '2025-11-25 08:07:49', '2025-11-25 08:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `history_of_delivery`
--

CREATE TABLE `history_of_delivery` (
  `id` int(11) NOT NULL,
  `to_be_delivered_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `payment_received` decimal(10,2) DEFAULT NULL,
  `change_given` decimal(10,2) DEFAULT NULL,
  `delivery_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `proof_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `history_of_delivery`
--

INSERT INTO `history_of_delivery` (`id`, `to_be_delivered_id`, `driver_id`, `user_id`, `order_number`, `payment_method`, `delivery_address`, `payment_received`, `change_given`, `delivery_time`, `proof_image`, `created_at`) VALUES
(1, 1, 1, 1, 'TJH-20251125-0001', 'COD', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 1599.00, 599.00, '2025-11-25 13:40:08', 'uploads/deliveries/1764103220_whole-chicken.png', '2025-11-25 20:40:20'),
(2, 2, 1, 1, 'TJH-20251126-0001', 'GCash', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 200.00, 80.00, '2025-11-26 12:34:31', 'uploads/deliveries/1764185683_placeholder.jpg', '2025-11-26 19:34:43'),
(3, 3, 2, 1, 'TJH-20251201-0002', 'COD', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 800.00, 100.00, '2025-11-30 20:21:21', 'uploads/deliveries/1764559292_566544321_808954665470038_5867477141472599839_n.jpg', '2025-12-01 03:21:32'),
(4, 4, 2, 1, 'TJH-20251201-0003', 'COD', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 180.00, 30.00, '2025-11-30 20:23:57', 'uploads/deliveries/1764559461_566544321_808954665470038_5867477141472599839_n.jpg', '2025-12-01 03:24:21');

-- --------------------------------------------------------

--
-- Table structure for table `history_of_delivery_items`
--

CREATE TABLE `history_of_delivery_items` (
  `id` int(11) NOT NULL,
  `history_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `history_of_delivery_items`
--

INSERT INTO `history_of_delivery_items` (`id`, `history_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 5, 160.00),
(2, 1, 3, 1, 200.00),
(3, 2, 4, 1, 120.00),
(4, 3, 5, 5, 140.00),
(5, 4, 7, 1, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `weekly_analytics`
--

CREATE TABLE `weekly_analytics` (
  `id` int(11) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `total_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_products_sold` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_products`
--

CREATE TABLE `parent_products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_products`
--

INSERT INTO `parent_products` (`id`, `name`, `description`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Whole Chicken', 'Fresh whole chicken, perfect for roasting or grilling.', 'uploads/items/6925688034081.png', '2025-11-25 08:07:49', '2025-11-25 01:27:44'),
(2, 'Chicken Wings', 'Juicy chicken wings, ideal for frying or baking.', 'uploads/items/69256877b4b6a.png', '2025-11-25 08:07:49', '2025-11-25 01:27:35'),
(3, 'Chicken Breasts', 'Tender chicken breast fillets, great for stir-fries or salads.', 'uploads/items/6925686e29354.png', '2025-11-25 08:07:49', '2025-11-25 01:27:26');

-- --------------------------------------------------------

--
-- Table structure for table `pending_delivery`
--

CREATE TABLE `pending_delivery` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'COD',
  `payment_status` enum('pending','verified','failed') DEFAULT 'pending',
  `gcash_reference` varchar(100) DEFAULT NULL,
  `gcash_payment_screenshot` varchar(500) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `delivery_address` text NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `date_requested` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_delivery`
--

INSERT INTO `pending_delivery` (`id`, `order_number`, `user_id`, `driver_id`, `payment_method`, `payment_status`, `gcash_reference`, `gcash_payment_screenshot`, `status`, `delivery_address`, `landmark`, `total_amount`, `date_requested`) VALUES
(1, 'TJH-20251125-0001', 1, 1, 'COD', 'verified', '', NULL, 'delivered', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 1000.00, '2025-11-25 20:30:36'),
(2, 'TJH-20251126-0001', 1, 1, 'GCash', 'verified', '', '../uploads/gcash_screenshots/gcash_2025-11-26_20-31-05_6927557939fda.jpg', 'delivered', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 120.00, '2025-11-26 19:31:05'),
(3, 'TJH-20251201-0001', 1, 1, 'COD', 'verified', '', NULL, 'cancelled', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 500.00, '2025-12-01 03:15:27'),
(4, 'TJH-20251201-0002', 1, 2, 'COD', 'verified', '', NULL, 'delivered', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 700.00, '2025-12-01 03:15:48'),
(5, 'TJH-20251201-0003', 1, 2, 'COD', 'verified', '', NULL, 'delivered', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 150.00, '2025-12-01 03:22:23'),
(6, 'TJH-20251201-0004', 1, 1, 'COD', 'verified', '', NULL, 'pending', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 400.00, '2025-12-01 03:23:30'),
(7, 'TJH-20251201-0005', 1, 2, 'COD', 'verified', '', NULL, 'cancelled', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 160.00, '2025-12-01 03:25:02'),
(8, 'TJH-20251201-0006', 1, 2, 'COD', 'verified', '', NULL, 'cancelled', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 1000.00, '2025-12-01 03:40:20'),
(9, 'TJH-20251201-0007', 1, 1, 'COD', 'verified', '', NULL, 'to be delivered', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 160.00, '2025-12-01 03:42:15'),
(10, 'TJH-20251201-0008', 1, 1, 'GCash', 'verified', '', '../uploads/gcash_screenshots/gcash_2025-12-01_04-43-04_692d0ec885641.jpg', 'assigned', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 640.00, '2025-12-01 03:43:04'),
(11, 'TJH-20251203-0001', 1, 1, 'COD', 'verified', '', NULL, 'pending', 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, 200.00, '2025-12-03 18:37:07');

-- --------------------------------------------------------

--
-- Table structure for table `pending_delivery_items`
--

CREATE TABLE `pending_delivery_items` (
  `id` int(11) NOT NULL,
  `pending_delivery_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_delivery_items`
--

INSERT INTO `pending_delivery_items` (`id`, `pending_delivery_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 5, 160.00),
(2, 1, 3, 1, 200.00),
(3, 2, 4, 1, 120.00),
(5, 4, 5, 5, 140.00),
(6, 5, 7, 1, 150.00),
(7, 6, 9, 2, 200.00),
(8, 7, 1, 1, 160.00),
(9, 8, 3, 5, 200.00),
(10, 9, 1, 1, 160.00),
(11, 10, 1, 4, 160.00),
(12, 11, 3, 1, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT 'img/products/placeholder.jpg' COMMENT 'Path to product image'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `parent_id`, `name`, `description`, `price`, `weight`, `stock`, `is_active`, `created_at`, `updated_at`, `image`) VALUES
(1, 1, 'Whole Chicken (Small)', 'Approximately 1.2-1.5kg per piece.', 160.00, '1.2-1.5kg', 10, 1, '2025-11-25 08:07:49', '2025-12-01 03:43:04', 'img/products/placeholder.jpg'),
(2, 1, 'Whole Chicken (Medium)', 'Approximately 1.5-1.8kg per piece.', 180.00, '1.5-1.8kg', 20, 1, '2025-11-25 08:07:49', '2025-11-25 08:07:49', 'img/products/placeholder.jpg'),
(3, 1, 'Whole Chicken (Large)', 'Approximately 1.8-2.2kg per piece.', 200.00, '1.8-2.2kg', 8, 1, '2025-11-25 08:07:49', '2025-12-03 18:37:07', 'img/products/placeholder.jpg'),
(4, 2, 'Chicken Wings (Regular)', 'Pack of 1kg (about 10-12 pieces).', 120.00, '1kg', 14, 1, '2025-11-25 08:07:49', '2025-11-26 19:31:05', 'img/products/placeholder.jpg'),
(5, 2, 'Chicken Wings (Jumbo)', 'Pack of 1kg (about 8-10 pieces).', 140.00, '1kg', 5, 1, '2025-11-25 08:07:49', '2025-12-01 03:15:48', 'img/products/placeholder.jpg'),
(6, 2, 'Chicken Wings (Party Pack)', 'Pack of 3kg (about 30-36 pieces).', 330.00, '3kg', 5, 1, '2025-11-25 08:07:49', '2025-11-25 08:07:49', 'img/products/placeholder.jpg'),
(7, 3, 'Chicken Breasts (Regular)', 'Pack of 1kg (about 4-5 pieces).', 150.00, '1kg', 14, 1, '2025-11-25 08:07:49', '2025-12-01 03:22:23', 'img/products/placeholder.jpg'),
(8, 3, 'Chicken Breasts (Skinless)', 'Pack of 1kg (about 4-5 pieces), skin removed.', 170.00, '1kg', 10, 1, '2025-11-25 08:07:49', '2025-11-25 08:07:49', 'img/products/placeholder.jpg'),
(9, 3, 'Chicken Breasts (Boneless)', 'Pack of 1kg, boneless fillets.', 200.00, '', 8, 1, '2025-11-25 08:07:49', '2025-12-01 03:23:30', 'img/products/placeholder.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(20, 2, 1, 5, 'The BEST', '2025-11-26 20:49:01', '2025-12-01 03:24:48'),
(21, 4, 1, 5, 'gwapo naman ihihihih', '2025-11-26 21:12:39', '2025-11-26 21:12:39');

-- --------------------------------------------------------

--
-- Table structure for table `to_be_delivered`
--

CREATE TABLE `to_be_delivered` (
  `id` int(11) NOT NULL,
  `pending_delivery_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `delivery_address` text NOT NULL,
  `pickup_proof` varchar(500) DEFAULT NULL,
  `pickup_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(32) DEFAULT 'picked_up'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `to_be_delivered`
--

INSERT INTO `to_be_delivered` (`id`, `pending_delivery_id`, `driver_id`, `user_id`, `delivery_address`, `pickup_proof`, `pickup_time`, `status`) VALUES
(1, 1, 1, 1, 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 'uploads/pickups/1764103208_whole-chicken.png', '2025-11-25 20:40:08', 'delivered'),
(2, 2, 1, 1, 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, '2025-11-26 19:34:24', 'delivered'),
(3, 4, 2, 1, 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 'uploads/pickups/1764559281_566544321_808954665470038_5867477141472599839_n.jpg', '2025-12-01 03:21:21', 'delivered'),
(4, 5, 2, 1, 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 'uploads/pickups/1764559437_566544321_808954665470038_5867477141472599839_n.jpg', '2025-12-01 03:23:57', 'delivered'),
(5, 10, 1, 1, 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', NULL, '2025-12-01 03:43:30', 'pending'),
(6, 9, 1, 1, 'B14 L14 Verde Heights, Brgy. Gaya-Gaya, San Jose del Monte City, 3023 (Landmark: Club House)', 'uploads/pickups/1764787046_2025-11-28_22.40.01.png', '2025-12-03 18:37:26', 'picked_up');

-- --------------------------------------------------------

--
-- Table structure for table `to_be_delivered_items`
--

CREATE TABLE `to_be_delivered_items` (
  `id` int(11) NOT NULL,
  `to_be_delivered_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `to_be_delivered_items`
--

INSERT INTO `to_be_delivered_items` (`id`, `to_be_delivered_id`, `product_id`, `quantity`, `price`) VALUES
(6, 5, 1, 4, 160.00),
(7, 6, 1, 1, 160.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phonenumber` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `phonenumber`, `password`, `address`, `barangay`, `city`, `zipcode`, `landmark`, `created_at`) VALUES
(1, 'Graymar Clive', 'Gillado', 'greythedev07@gmail.com', '09763209335', '$2y$10$ftcZDG68m/UzOG2/swkzxOiYU2J57J/wh.ePD2HTo5VW2E7awMemG', 'B14 L14 Verde Heights', 'Gaya-Gaya', 'San Jose del Monte City', '3023', 'Club House', '2025-11-25 08:12:29'),
(2, 'Grey', 'Yes', 'greygrey@gmail.com', '387123712387', '$2y$10$PKxDzWe.ezpdRJ/yS9wdQ.h7sQaY8HWW0Q1QbM7zm9PJykgkL9iz6', 'B14 L14 Verde Heights', 'Gaya-Gaya', 'San Jose del Monte City', '3023', 'Club House', '2025-12-03 18:33:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admins_username` (`username`);

--
-- Indexes for table `admin_keys`
--
ALTER TABLE `admin_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_key` (`admin_key`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `idx_cart_product` (`product_id`),
  ADD KEY `idx_cart_parent_id` (`parent_id`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `driver_code` (`driver_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_drivers_email` (`email`);

--
-- Indexes for table `gcash_qr_codes`
--
ALTER TABLE `gcash_qr_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `history_of_delivery`
--
ALTER TABLE `history_of_delivery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `to_be_delivered_id` (`to_be_delivered_id`),
  ADD KEY `idx_history_delivery_user` (`user_id`),
  ADD KEY `idx_history_delivery_driver` (`driver_id`),
  ADD KEY `idx_history_delivery_order_number` (`order_number`),
  ADD KEY `idx_history_delivery_payment_method` (`payment_method`);

--
-- Indexes for table `history_of_delivery_items`
--
ALTER TABLE `history_of_delivery_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `history_id` (`history_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `weekly_analytics`
--
ALTER TABLE `weekly_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_weekly_analytics_week` (`week_start_date`,`week_end_date`),
  ADD KEY `idx_weekly_analytics_week_start` (`week_start_date`),
  ADD KEY `idx_weekly_analytics_week_end` (`week_end_date`);

--
-- Indexes for table `parent_products`
--
ALTER TABLE `parent_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pending_delivery`
--
ALTER TABLE `pending_delivery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pending_delivery_user` (`user_id`),
  ADD KEY `idx_pending_delivery_driver` (`driver_id`),
  ADD KEY `idx_pending_delivery_status` (`status`),
  ADD KEY `idx_pending_delivery_order_number` (`order_number`),
  ADD KEY `idx_pending_delivery_gcash_ref` (`gcash_reference`),
  ADD KEY `idx_pending_delivery_payment_status` (`payment_status`);

--
-- Indexes for table `pending_delivery_items`
--
ALTER TABLE `pending_delivery_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_delivery_id` (`pending_delivery_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_user_review` (`product_id`,`user_id`),
  ADD KEY `idx_product_reviews_product` (`product_id`),
  ADD KEY `idx_product_reviews_user` (`user_id`);

--
-- Indexes for table `to_be_delivered`
--
ALTER TABLE `to_be_delivered`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pending_delivery_id` (`pending_delivery_id`),
  ADD KEY `idx_to_be_delivered_driver` (`driver_id`),
  ADD KEY `idx_to_be_delivered_user` (`user_id`),
  ADD KEY `idx_to_be_delivered_status` (`status`);

--
-- Indexes for table `to_be_delivered_items`
--
ALTER TABLE `to_be_delivered_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `to_be_delivered_id` (`to_be_delivered_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_keys`
--
ALTER TABLE `admin_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gcash_qr_codes`
--
ALTER TABLE `gcash_qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `history_of_delivery`
--
ALTER TABLE `history_of_delivery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `history_of_delivery_items`
--
ALTER TABLE `history_of_delivery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `weekly_analytics`
--
ALTER TABLE `weekly_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parent_products`
--
ALTER TABLE `parent_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pending_delivery`
--
ALTER TABLE `pending_delivery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pending_delivery_items`
--
ALTER TABLE `pending_delivery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `to_be_delivered`
--
ALTER TABLE `to_be_delivered`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `to_be_delivered_items`
--
ALTER TABLE `to_be_delivered_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_parent_product` FOREIGN KEY (`parent_id`) REFERENCES `parent_products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `history_of_delivery`
--
ALTER TABLE `history_of_delivery`
  ADD CONSTRAINT `history_of_delivery_ibfk_1` FOREIGN KEY (`to_be_delivered_id`) REFERENCES `to_be_delivered` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_of_delivery_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_of_delivery_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `history_of_delivery_items`
--
ALTER TABLE `history_of_delivery_items`
  ADD CONSTRAINT `history_of_delivery_items_ibfk_1` FOREIGN KEY (`history_id`) REFERENCES `history_of_delivery` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_of_delivery_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_delivery`
--
ALTER TABLE `pending_delivery`
  ADD CONSTRAINT `pending_delivery_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pending_delivery_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pending_delivery_items`
--
ALTER TABLE `pending_delivery_items`
  ADD CONSTRAINT `pending_delivery_items_ibfk_1` FOREIGN KEY (`pending_delivery_id`) REFERENCES `pending_delivery` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pending_delivery_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `to_be_delivered`
--
ALTER TABLE `to_be_delivered`
  ADD CONSTRAINT `to_be_delivered_ibfk_1` FOREIGN KEY (`pending_delivery_id`) REFERENCES `pending_delivery` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `to_be_delivered_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `to_be_delivered_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `to_be_delivered_items`
--
ALTER TABLE `to_be_delivered_items`
  ADD CONSTRAINT `to_be_delivered_items_ibfk_1` FOREIGN KEY (`to_be_delivered_id`) REFERENCES `to_be_delivered` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `to_be_delivered_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
