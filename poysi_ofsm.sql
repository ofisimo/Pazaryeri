-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 09 Ara 2025, 15:55:58
-- Sunucu sürümü: 10.4.33-MariaDB
-- PHP Sürümü: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `poysi_ofsm`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(112, 'Ürün Kategori 1', NULL, NULL, 0, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(113, 'Ürün Kategori 2', NULL, NULL, 0, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(114, 'Ürün Kategori 3', NULL, NULL, 0, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(115, 'Alt Kategori 1', 112, NULL, 0, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:25'),
(116, 'Alt Kategori 2', 112, NULL, 0, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:25'),
(117, 'Alt Kategori 3', 112, NULL, 0, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:25'),
(118, 'Ürün Kategori 4', NULL, NULL, 0, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `category_mappings`
--

CREATE TABLE `category_mappings` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `platform_category_id` varchar(100) DEFAULT NULL,
  `platform_category_name` varchar(255) DEFAULT NULL,
  `platform_category_path` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `category_mappings`
--

INSERT INTO `category_mappings` (`id`, `category_id`, `platform`, `platform_category_id`, `platform_category_name`, `platform_category_path`, `created_at`, `updated_at`) VALUES
(114, 112, 'opencart', '62', 'Ürün Kategori 1', NULL, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(115, 113, 'opencart', '63', 'Ürün Kategori 2', NULL, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(116, 114, 'opencart', '64', 'Ürün Kategori 3', NULL, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(117, 115, 'opencart', '65', 'Alt Kategori 1', NULL, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(118, 116, 'opencart', '66', 'Alt Kategori 2', NULL, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(119, 117, 'opencart', '67', 'Alt Kategori 3', NULL, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(120, 118, 'opencart', '68', 'Ürün Kategori 4', NULL, '2025-12-09 20:55:23', '2025-12-09 20:55:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `marketplace_products`
--

CREATE TABLE `marketplace_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `marketplace` varchar(50) NOT NULL,
  `marketplace_product_id` varchar(100) DEFAULT NULL,
  `marketplace_sku` varchar(100) DEFAULT NULL,
  `marketplace_status` varchar(50) DEFAULT NULL,
  `last_sync` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `marketplace_settings`
--

CREATE TABLE `marketplace_settings` (
  `id` int(11) NOT NULL,
  `marketplace` varchar(50) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `merchant_id` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `opencart_settings`
--

CREATE TABLE `opencart_settings` (
  `id` int(11) NOT NULL,
  `store_url` varchar(255) NOT NULL,
  `api_token` varchar(255) DEFAULT NULL,
  `api_username` varchar(100) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `opencart_settings`
--

INSERT INTO `opencart_settings` (`id`, `store_url`, `api_token`, `api_username`, `api_key`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'https://kelebeksoft.com', '', 'ofisimo', 'jr8v1VVCKJE7FqWODtyhPRPZeiGHmb4w2hx07UAihM5Z6Z4MEChqTlaT1UgnkzYKq32E6CDf7kgMBVfRDWkTIH6chpG8JPDrypCGikyL8XawEZhMFBmP6zNsTPKQIXayykUXnj5WbewdZAgGWKbtVnoD1WYuJeLBg7sM78cCB2rwPgrzNIzcdenyaxlWW8rIfixBJtXtEUvvJ6KjYx1xRWfMNstds3hbiogThXDniA3z0ZmGJoQD6OYn4aslAHo', 1, '2025-12-02 08:21:43', '2025-12-02 18:02:40');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `marketplace` varchar(50) NOT NULL,
  `order_number` varchar(100) NOT NULL,
  `customer_name` varchar(200) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `cargo_company` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(500) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `opencart_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `has_variants` tinyint(1) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `barcode` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `opencart_id`, `parent_id`, `has_variants`, `sku`, `name`, `description`, `price`, `stock`, `barcode`, `category_id`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(753, 24, NULL, 1, '001', 'Demo ürün 1', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 310.00, 950, '', NULL, 'opencart/product_001_1765313723_69388cbb5f7f1.jpg', 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(754, 33, NULL, 1, '010', 'Demo ürün 10', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 110.00, 1, '', NULL, 'opencart/product_010_1765313723_69388cbb84a27.jpg', 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(755, 34, NULL, 1, '011', 'Demo ürün 11', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 140.00, 1, '', NULL, 'opencart/product_011_1765313723_69388cbbec899.jpg', 1, '2025-12-09 20:55:23', '2025-12-09 20:55:24'),
(756, 35, NULL, 1, '012', 'Demo ürün 12', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 190.00, 1, '', NULL, 'opencart/product_012_1765313724_69388cbc3d535.jpg', 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(757, 36, NULL, 1, '013', 'Demo ürün 13', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 710.00, 1, '', NULL, 'opencart/product_013_1765313724_69388cbc53ed2.jpg', 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(758, 37, NULL, 1, '014', 'Demo ürün 14', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 610.00, 1, '', NULL, 'opencart/product_014_1765313724_69388cbc638ab.jpg', 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(759, 38, NULL, 1, '015', 'Demo ürün 15', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 360.00, 1, '', NULL, 'opencart/product_015_1765313724_69388cbc77744.jpg', 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(760, 39, NULL, 1, '016', 'Demo ürün 16', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 160.00, 1, '', NULL, 'opencart/product_016_1765313724_69388cbcb82f3.jpg', 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(761, 25, NULL, 1, '002', 'Demo ürün 2', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 210.00, 1, '', NULL, 'opencart/product_002_1765313725_69388cbd069ea.jpg', 1, '2025-12-09 20:55:24', '2025-12-09 20:55:25'),
(762, 26, NULL, 1, '003', 'Demo ürün 3', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 260.00, 1, '', NULL, 'opencart/product_003_1765313725_69388cbd4b051.jpg', 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(763, 27, NULL, 1, '004', 'Demo ürün 4', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 280.00, 1, '', NULL, 'opencart/product_004_1765313725_69388cbd5cb17.jpg', 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(764, 28, NULL, 1, '005', 'Demo ürün 5', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 380.00, 1, '', NULL, 'opencart/product_005_1765313725_69388cbd9dc2c.jpg', 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(765, 29, NULL, 1, '006', 'Demo ürün 6', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 410.00, 1, '', NULL, 'opencart/product_006_1765313725_69388cbdada56.jpg', 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(766, 30, NULL, 1, '007', 'Demo ürün 7', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 460.00, 1, '', NULL, 'opencart/product_007_1765313725_69388cbdbd3a4.jpg', 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(767, 31, NULL, 1, '008', 'Demo ürün 8', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 560.00, 1, '', NULL, 'opencart/product_008_1765313725_69388cbdcc1d7.jpg', 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(768, 32, NULL, 1, '009', 'Demo ürün 9', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 510.00, 1, '', NULL, 'opencart/product_009_1765313725_69388cbddaebd.jpg', 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `product_categories`
--

INSERT INTO `product_categories` (`id`, `product_id`, `category_id`, `created_at`) VALUES
(1409, 753, 112, '2025-12-09 20:55:23'),
(1411, 753, 113, '2025-12-09 20:55:23'),
(1412, 753, 114, '2025-12-09 20:55:23'),
(1413, 753, 115, '2025-12-09 20:55:23'),
(1414, 753, 116, '2025-12-09 20:55:23'),
(1415, 753, 117, '2025-12-09 20:55:23'),
(1416, 753, 118, '2025-12-09 20:55:23'),
(1417, 754, 112, '2025-12-09 20:55:23'),
(1419, 754, 113, '2025-12-09 20:55:23'),
(1420, 754, 114, '2025-12-09 20:55:23'),
(1421, 754, 115, '2025-12-09 20:55:23'),
(1422, 754, 116, '2025-12-09 20:55:23'),
(1423, 754, 117, '2025-12-09 20:55:23'),
(1424, 754, 118, '2025-12-09 20:55:23'),
(1425, 755, 112, '2025-12-09 20:55:23'),
(1427, 755, 113, '2025-12-09 20:55:23'),
(1428, 755, 114, '2025-12-09 20:55:23'),
(1429, 755, 115, '2025-12-09 20:55:23'),
(1430, 755, 116, '2025-12-09 20:55:23'),
(1431, 755, 117, '2025-12-09 20:55:23'),
(1432, 755, 118, '2025-12-09 20:55:23'),
(1433, 756, 112, '2025-12-09 20:55:24'),
(1435, 756, 113, '2025-12-09 20:55:24'),
(1436, 756, 114, '2025-12-09 20:55:24'),
(1437, 756, 115, '2025-12-09 20:55:24'),
(1438, 756, 116, '2025-12-09 20:55:24'),
(1439, 756, 117, '2025-12-09 20:55:24'),
(1440, 756, 118, '2025-12-09 20:55:24'),
(1441, 757, 112, '2025-12-09 20:55:24'),
(1443, 757, 113, '2025-12-09 20:55:24'),
(1444, 757, 114, '2025-12-09 20:55:24'),
(1445, 757, 115, '2025-12-09 20:55:24'),
(1446, 757, 116, '2025-12-09 20:55:24'),
(1447, 757, 117, '2025-12-09 20:55:24'),
(1448, 757, 118, '2025-12-09 20:55:24'),
(1449, 758, 112, '2025-12-09 20:55:24'),
(1451, 758, 113, '2025-12-09 20:55:24'),
(1452, 758, 114, '2025-12-09 20:55:24'),
(1453, 758, 115, '2025-12-09 20:55:24'),
(1454, 758, 116, '2025-12-09 20:55:24'),
(1455, 758, 117, '2025-12-09 20:55:24'),
(1456, 758, 118, '2025-12-09 20:55:24'),
(1457, 759, 112, '2025-12-09 20:55:24'),
(1459, 759, 113, '2025-12-09 20:55:24'),
(1460, 759, 114, '2025-12-09 20:55:24'),
(1461, 759, 115, '2025-12-09 20:55:24'),
(1462, 759, 116, '2025-12-09 20:55:24'),
(1463, 759, 117, '2025-12-09 20:55:24'),
(1464, 759, 118, '2025-12-09 20:55:24'),
(1465, 760, 112, '2025-12-09 20:55:24'),
(1467, 760, 113, '2025-12-09 20:55:24'),
(1468, 760, 114, '2025-12-09 20:55:24'),
(1469, 760, 115, '2025-12-09 20:55:24'),
(1470, 760, 116, '2025-12-09 20:55:24'),
(1471, 760, 117, '2025-12-09 20:55:24'),
(1472, 760, 118, '2025-12-09 20:55:24'),
(1473, 761, 112, '2025-12-09 20:55:24'),
(1475, 761, 113, '2025-12-09 20:55:25'),
(1476, 761, 114, '2025-12-09 20:55:25'),
(1477, 761, 115, '2025-12-09 20:55:25'),
(1478, 761, 116, '2025-12-09 20:55:25'),
(1479, 761, 117, '2025-12-09 20:55:25'),
(1480, 761, 118, '2025-12-09 20:55:25'),
(1481, 762, 112, '2025-12-09 20:55:25'),
(1483, 762, 113, '2025-12-09 20:55:25'),
(1484, 762, 114, '2025-12-09 20:55:25'),
(1485, 762, 115, '2025-12-09 20:55:25'),
(1486, 762, 116, '2025-12-09 20:55:25'),
(1487, 762, 117, '2025-12-09 20:55:25'),
(1488, 762, 118, '2025-12-09 20:55:25'),
(1489, 763, 112, '2025-12-09 20:55:25'),
(1491, 763, 113, '2025-12-09 20:55:25'),
(1492, 763, 114, '2025-12-09 20:55:25'),
(1493, 763, 115, '2025-12-09 20:55:25'),
(1494, 763, 116, '2025-12-09 20:55:25'),
(1495, 763, 117, '2025-12-09 20:55:25'),
(1496, 763, 118, '2025-12-09 20:55:25'),
(1497, 764, 112, '2025-12-09 20:55:25'),
(1499, 764, 113, '2025-12-09 20:55:25'),
(1500, 764, 114, '2025-12-09 20:55:25'),
(1501, 764, 115, '2025-12-09 20:55:25'),
(1502, 764, 116, '2025-12-09 20:55:25'),
(1503, 764, 117, '2025-12-09 20:55:25'),
(1504, 764, 118, '2025-12-09 20:55:25'),
(1505, 765, 112, '2025-12-09 20:55:25'),
(1507, 765, 113, '2025-12-09 20:55:25'),
(1508, 765, 114, '2025-12-09 20:55:25'),
(1509, 765, 115, '2025-12-09 20:55:25'),
(1510, 765, 116, '2025-12-09 20:55:25'),
(1511, 765, 117, '2025-12-09 20:55:25'),
(1512, 765, 118, '2025-12-09 20:55:25'),
(1513, 766, 112, '2025-12-09 20:55:25'),
(1515, 766, 113, '2025-12-09 20:55:25'),
(1516, 766, 114, '2025-12-09 20:55:25'),
(1517, 766, 115, '2025-12-09 20:55:25'),
(1518, 766, 116, '2025-12-09 20:55:25'),
(1519, 766, 117, '2025-12-09 20:55:25'),
(1520, 766, 118, '2025-12-09 20:55:25'),
(1521, 767, 112, '2025-12-09 20:55:25'),
(1523, 767, 113, '2025-12-09 20:55:25'),
(1524, 767, 114, '2025-12-09 20:55:25'),
(1525, 767, 115, '2025-12-09 20:55:25'),
(1526, 767, 116, '2025-12-09 20:55:25'),
(1527, 767, 117, '2025-12-09 20:55:25'),
(1528, 767, 118, '2025-12-09 20:55:25'),
(1529, 768, 112, '2025-12-09 20:55:25'),
(1531, 768, 113, '2025-12-09 20:55:25'),
(1532, 768, 114, '2025-12-09 20:55:25'),
(1533, 768, 115, '2025-12-09 20:55:25'),
(1534, 768, 116, '2025-12-09 20:55:25'),
(1535, 768, 117, '2025-12-09 20:55:25'),
(1536, 768, 118, '2025-12-09 20:55:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `platform` varchar(50) DEFAULT 'local',
  `sort_order` int(11) DEFAULT 0,
  `is_main` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `image_path`, `platform`, `sort_order`, `is_main`, `created_at`) VALUES
(813, 753, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/9-315x315-500x500.jpg', 'opencart/product_001_1765313723_69388cbb5f7f1.jpg', 'opencart', 0, 1, '2025-12-09 20:55:23'),
(814, 753, 'https://kelebeksoft.com/image/cache/catalog/2148200026-500x500.jpg', 'opencart/product_001_1765313723_69388cbb6c040.jpg', 'opencart', 1, 0, '2025-12-09 20:55:23'),
(815, 754, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/8-660x660-500x500.jpg', 'opencart/product_010_1765313723_69388cbb84a27.jpg', 'opencart', 0, 1, '2025-12-09 20:55:23'),
(816, 754, 'https://kelebeksoft.com/image/cache/catalog/2147655964-500x500.jpg', 'opencart/product_010_1765313723_69388cbb96124.jpg', 'opencart', 1, 0, '2025-12-09 20:55:23'),
(817, 754, 'https://kelebeksoft.com/image/cache/catalog/user-500x500.jpg', 'opencart/product_010_1765313723_69388cbbabf85.jpg', 'opencart', 2, 0, '2025-12-09 20:55:23'),
(818, 755, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/5-315x315-500x500.jpg', 'opencart/product_011_1765313723_69388cbbec899.jpg', 'opencart', 0, 1, '2025-12-09 20:55:23'),
(819, 755, 'https://kelebeksoft.com/image/cache/catalog/2148200026-500x500.jpg', 'opencart/product_011_1765313723_69388cbbef507.jpg', 'opencart', 1, 0, '2025-12-09 20:55:23'),
(820, 755, 'https://kelebeksoft.com/image/cache/catalog/images-500x500.png', 'opencart/product_011_1765313723_69388cbbf2376.png', 'opencart', 2, 0, '2025-12-09 20:55:24'),
(821, 756, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/4-660x660-500x500.jpg', 'opencart/product_012_1765313724_69388cbc3d535.jpg', 'opencart', 0, 1, '2025-12-09 20:55:24'),
(822, 756, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/galeri/10-500x500.jpg', 'opencart/product_012_1765313724_69388cbc402b5.jpg', 'opencart', 1, 0, '2025-12-09 20:55:24'),
(823, 756, 'https://kelebeksoft.com/image/cache/catalog/demo/modules/oclms_counter-500x500.jpg', 'opencart/product_012_1765313724_69388cbc42dda.jpg', 'opencart', 2, 0, '2025-12-09 20:55:24'),
(824, 756, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/galeri/7-500x500.jpg', 'opencart/product_012_1765313724_69388cbc45ba3.jpg', 'opencart', 3, 0, '2025-12-09 20:55:24'),
(825, 757, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/2-660x660-500x500.jpg', 'opencart/product_013_1765313724_69388cbc53ed2.jpg', 'opencart', 0, 1, '2025-12-09 20:55:24'),
(826, 758, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/7-660x660-500x500.jpg', 'opencart/product_014_1765313724_69388cbc638ab.jpg', 'opencart', 0, 1, '2025-12-09 20:55:24'),
(827, 759, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/12-315x315-500x500.jpg', 'opencart/product_015_1765313724_69388cbc77744.jpg', 'opencart', 0, 1, '2025-12-09 20:55:24'),
(828, 760, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/10-315x315-500x500.jpg', 'opencart/product_016_1765313724_69388cbcb82f3.jpg', 'opencart', 0, 1, '2025-12-09 20:55:24'),
(829, 761, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/2-660x660-500x500.jpg', 'opencart/product_002_1765313725_69388cbd069ea.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25'),
(830, 761, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/13-660x660-500x500.jpg', 'opencart/product_002_1765313725_69388cbd09ee0.jpg', 'opencart', 1, 0, '2025-12-09 20:55:25'),
(831, 762, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/8-660x660-500x500.jpg', 'opencart/product_003_1765313725_69388cbd4b051.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25'),
(832, 762, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/9-315x315-500x500.jpg', 'opencart/product_003_1765313725_69388cbd4e35c.jpg', 'opencart', 1, 0, '2025-12-09 20:55:25'),
(833, 763, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/5-315x315-500x500.jpg', 'opencart/product_004_1765313725_69388cbd5cb17.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25'),
(834, 764, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/4-660x660-500x500.jpg', 'opencart/product_005_1765313725_69388cbd9dc2c.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25'),
(835, 765, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/10-315x315-500x500.jpg', 'opencart/product_006_1765313725_69388cbdada56.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25'),
(836, 766, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/13-660x660-500x500.jpg', 'opencart/product_007_1765313725_69388cbdbd3a4.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25'),
(837, 767, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/3-660x660-500x500.jpg', 'opencart/product_008_1765313725_69388cbdcc1d7.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25'),
(838, 768, 'https://kelebeksoft.com/image/cache/catalog/demoicerik/urunler/1-660x660-500x500.jpg', 'opencart/product_009_1765313725_69388cbddaebd.jpg', 'opencart', 0, 1, '2025-12-09 20:55:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `parent_product_id` int(11) NOT NULL,
  `variant_sku` varchar(100) DEFAULT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `barcode` varchar(100) DEFAULT NULL,
  `options_json` text DEFAULT NULL COMMENT 'Renk, beden vb. JSON',
  `opencart_id` int(11) DEFAULT NULL,
  `trendyol_id` varchar(50) DEFAULT NULL,
  `hepsiburada_id` varchar(50) DEFAULT NULL,
  `n11_id` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `product_variants`
--

INSERT INTO `product_variants` (`id`, `parent_product_id`, `variant_sku`, `variant_name`, `price`, `stock`, `barcode`, `options_json`, `opencart_id`, `trendyol_id`, `hepsiburada_id`, `n11_id`, `is_active`, `created_at`, `updated_at`) VALUES
(205, 753, '', '', 5.00, 20, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(206, 753, '', '', 0.00, 10, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(207, 753, '', '', 20.00, 15, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(208, 753, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(209, 753, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(210, 753, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(211, 754, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(212, 754, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(213, 754, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:23', '2025-12-09 20:55:23'),
(214, 755, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(215, 755, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(216, 755, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(217, 756, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(218, 756, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(219, 756, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(220, 757, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(221, 757, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(222, 757, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(223, 758, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(224, 758, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(225, 758, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(226, 759, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(227, 759, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(228, 759, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(229, 760, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(230, 760, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(231, 760, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:24', '2025-12-09 20:55:24'),
(232, 761, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(233, 761, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(234, 761, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(235, 762, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(236, 762, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(237, 762, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(238, 763, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(239, 763, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(240, 763, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(241, 764, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(242, 764, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(243, 764, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(244, 765, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(245, 765, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(246, 765, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(247, 766, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(248, 766, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(249, 766, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(250, 767, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(251, 767, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(252, 767, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(253, 768, '', '', 25.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(254, 768, '', '', 100.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25'),
(255, 768, '', '', 50.00, 100, '', '[]', NULL, NULL, NULL, NULL, 1, '2025-12-09 20:55:25', '2025-12-09 20:55:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sync_logs`
--

CREATE TABLE `sync_logs` (
  `id` int(11) NOT NULL,
  `marketplace` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL,
  `message` text DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_data`)),
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sync_settings`
--

CREATE TABLE `sync_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `sync_settings`
--

INSERT INTO `sync_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'source_platform', 'opencart', '2025-12-05 11:30:15', '2025-12-09 18:31:28'),
(2, 'target_platforms', '[\"hepsiburada\",\"n11\"]', '2025-12-05 11:30:15', '2025-12-09 18:31:28'),
(3, 'auto_sync', '0', '2025-12-05 11:30:15', '2025-12-05 11:30:15'),
(4, 'sync_images', '1', '2025-12-05 11:30:15', '2025-12-05 11:30:15'),
(5, 'sync_categories', '1', '2025-12-05 11:30:15', '2025-12-05 11:30:15'),
(6, 'sync_variants', '1', '2025-12-05 11:30:15', '2025-12-05 11:30:15');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `last_login`, `created_at`) VALUES
(1, 'admin', '482c811da5d5b4bc6d497ffa98491e38', 'admin@panel.com', '2025-12-09 18:35:36', '2025-12-02 08:21:43');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Tablo için indeksler `category_mappings`
--
ALTER TABLE `category_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mapping` (`category_id`,`platform`),
  ADD KEY `idx_platform` (`platform`),
  ADD KEY `idx_category` (`category_id`);

--
-- Tablo için indeksler `marketplace_products`
--
ALTER TABLE `marketplace_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_marketplace_product` (`product_id`,`marketplace`),
  ADD KEY `idx_marketplace` (`marketplace`),
  ADD KEY `idx_product` (`product_id`);

--
-- Tablo için indeksler `marketplace_settings`
--
ALTER TABLE `marketplace_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_marketplace` (`marketplace`),
  ADD KEY `idx_marketplace` (`marketplace`),
  ADD KEY `idx_active` (`is_active`);

--
-- Tablo için indeksler `opencart_settings`
--
ALTER TABLE `opencart_settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_marketplace` (`marketplace`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_date` (`order_date`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_opencart` (`opencart_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Tablo için indeksler `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_category` (`product_id`,`category_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Tablo için indeksler `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_main` (`is_main`);

--
-- Tablo için indeksler `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_product_id` (`parent_product_id`);

--
-- Tablo için indeksler `sync_logs`
--
ALTER TABLE `sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_marketplace` (`marketplace`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `sync_settings`
--
ALTER TABLE `sync_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`setting_key`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- Tablo için AUTO_INCREMENT değeri `category_mappings`
--
ALTER TABLE `category_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- Tablo için AUTO_INCREMENT değeri `marketplace_products`
--
ALTER TABLE `marketplace_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `marketplace_settings`
--
ALTER TABLE `marketplace_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `opencart_settings`
--
ALTER TABLE `opencart_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=769;

--
-- Tablo için AUTO_INCREMENT değeri `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1537;

--
-- Tablo için AUTO_INCREMENT değeri `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=839;

--
-- Tablo için AUTO_INCREMENT değeri `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- Tablo için AUTO_INCREMENT değeri `sync_logs`
--
ALTER TABLE `sync_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `sync_settings`
--
ALTER TABLE `sync_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `category_mappings`
--
ALTER TABLE `category_mappings`
  ADD CONSTRAINT `category_mappings_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `marketplace_products`
--
ALTER TABLE `marketplace_products`
  ADD CONSTRAINT `marketplace_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variant_product` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
