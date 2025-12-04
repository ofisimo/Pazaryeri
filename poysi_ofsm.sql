-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 03 Ara 2025, 16:22:31
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
(81, 24, NULL, 1, '001', 'Demo ürün 1', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 310.00, 950, '', NULL, 'uploads/opencart/product_81_1764795595.jpg', 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(82, 33, NULL, 1, '010', 'Demo ürün 10', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 110.00, 1, '', NULL, 'uploads/opencart/product_82_1764795595.jpg', 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(83, 34, NULL, 1, '011', 'Demo ürün 11', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 140.00, 1, '', NULL, 'uploads/opencart/product_83_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(84, 35, NULL, 1, '012', 'Demo ürün 12', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 190.00, 1, '', NULL, 'uploads/opencart/product_84_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(85, 36, NULL, 1, '013', 'Demo ürün 13', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 710.00, 1, '', NULL, 'uploads/opencart/product_85_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(86, 37, NULL, 1, '014', 'Demo ürün 14', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 610.00, 1, '', NULL, 'uploads/opencart/product_86_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(87, 38, NULL, 1, '015', 'Demo ürün 15', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 360.00, 1, '', NULL, 'uploads/opencart/product_87_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(88, 39, NULL, 1, '016', 'Demo ürün 16', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 160.00, 1, '', NULL, 'uploads/opencart/product_88_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(89, 25, NULL, 1, '002', 'Demo ürün 2', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 210.00, 1, '', NULL, 'uploads/opencart/product_89_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(90, 26, NULL, 1, '003', 'Demo ürün 3', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 260.00, 1, '', NULL, 'uploads/opencart/product_90_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(91, 27, NULL, 1, '004', 'Demo ürün 4', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 280.00, 1, '', NULL, 'uploads/opencart/product_91_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(92, 28, NULL, 1, '005', 'Demo ürün 5', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 380.00, 1, '', NULL, 'uploads/opencart/product_92_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(93, 29, NULL, 1, '006', 'Demo ürün 6', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 410.00, 1, '', NULL, 'uploads/opencart/product_93_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(94, 30, NULL, 1, '007', 'Demo ürün 7', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 460.00, 1, '', NULL, 'uploads/opencart/product_94_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(95, 31, NULL, 1, '008', 'Demo ürün 8', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 560.00, 1, '', NULL, 'uploads/opencart/product_95_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(96, 32, NULL, 1, '009', 'Demo ürün 9', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore and dolore magna aliqua. Ut enim ad minim veniam, quis nostrud egzersizi ullamco emek, en az bir ortak sonuç. velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, culpa qui officia deserunt mollit anim id est laborum. \"MÖ 45\'te Cicero tarafından yazılan \"de Finibus Bonorum et Malorum\" bölüm 1.10.32\"Her şeyle ilgili perspektif, her şeyden önce hata oturtmak voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis and quasi architecto beatae vitae dicta sunt explicabo. Sonuç olarak, büyük ölçüde hacimsel sekanslar, Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt to via and dolore magnam aliquam en quaerat. , quis nostrum practiceem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi resultatur?vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? \"H. Rackham tarafından 1914 çevirisibundan biraz avantaj elde etmek dışında mı?&nbsp;Ama can sıkıcı sonuçları olmayan bir zevkten zevk almayı seçen veya sonuçta hiçbir zevk üretmeyen bir acıdan kaçınan bir adamda kusur bulmaya kim hakkı var? \"', 510.00, 1, '', NULL, 'uploads/opencart/product_96_1764795596.jpg', 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56');

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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `parent_product_id` int(11) DEFAULT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `variant_value` varchar(255) DEFAULT NULL,
  `variant_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_attributes`)),
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `parent_product_id`, `variant_name`, `variant_value`, `variant_attributes`, `sku`, `barcode`, `price`, `stock`, `image_url`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(154, 81, 81, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '001-21cd43', NULL, 335.00, 100, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(155, 81, 81, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '001-56a4d7', NULL, 410.00, 100, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(156, 81, 81, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '001-09e196', NULL, 360.00, 100, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(157, 81, 81, 'Beden: S', NULL, '{\"Beden\":\"S\"}', '001-2104b8', NULL, 315.00, 20, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(158, 81, 81, 'Beden: L', NULL, '{\"Beden\":\"L\"}', '001-f11a73', NULL, 310.00, 10, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(159, 81, 81, 'Beden: XL', NULL, '{\"Beden\":\"XL\"}', '001-1c0f0c', NULL, 330.00, 15, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(160, 82, 82, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '010-21cd43', NULL, 135.00, 100, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(161, 82, 82, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '010-56a4d7', NULL, 210.00, 100, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(162, 82, 82, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '010-09e196', NULL, 160.00, 100, '', 0, 1, '2025-12-03 20:59:55', '2025-12-03 20:59:55'),
(163, 83, 83, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '011-21cd43', NULL, 165.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(164, 83, 83, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '011-56a4d7', NULL, 240.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(165, 83, 83, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '011-09e196', NULL, 190.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(166, 84, 84, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '012-21cd43', NULL, 215.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(167, 84, 84, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '012-56a4d7', NULL, 290.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(168, 84, 84, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '012-09e196', NULL, 240.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(169, 85, 85, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '013-21cd43', NULL, 735.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(170, 85, 85, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '013-56a4d7', NULL, 810.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(171, 85, 85, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '013-09e196', NULL, 760.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(172, 86, 86, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '014-21cd43', NULL, 635.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(173, 86, 86, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '014-56a4d7', NULL, 710.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(174, 86, 86, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '014-09e196', NULL, 660.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(175, 87, 87, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '015-21cd43', NULL, 385.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(176, 87, 87, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '015-56a4d7', NULL, 460.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(177, 87, 87, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '015-09e196', NULL, 410.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(178, 88, 88, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '016-21cd43', NULL, 185.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(179, 88, 88, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '016-56a4d7', NULL, 260.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(180, 88, 88, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '016-09e196', NULL, 210.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(181, 89, 89, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '002-21cd43', NULL, 235.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(182, 89, 89, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '002-56a4d7', NULL, 310.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(183, 89, 89, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '002-09e196', NULL, 260.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(184, 90, 90, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '003-21cd43', NULL, 285.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(185, 90, 90, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '003-56a4d7', NULL, 360.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(186, 90, 90, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '003-09e196', NULL, 310.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(187, 91, 91, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '004-21cd43', NULL, 305.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(188, 91, 91, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '004-56a4d7', NULL, 380.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(189, 91, 91, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '004-09e196', NULL, 330.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(190, 92, 92, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '005-21cd43', NULL, 405.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(191, 92, 92, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '005-56a4d7', NULL, 480.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(192, 92, 92, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '005-09e196', NULL, 430.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(193, 93, 93, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '006-21cd43', NULL, 435.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(194, 93, 93, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '006-56a4d7', NULL, 510.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(195, 93, 93, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '006-09e196', NULL, 460.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(196, 94, 94, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '007-21cd43', NULL, 485.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(197, 94, 94, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '007-56a4d7', NULL, 560.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(198, 94, 94, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '007-09e196', NULL, 510.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(199, 95, 95, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '008-21cd43', NULL, 585.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(200, 95, 95, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '008-56a4d7', NULL, 660.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(201, 95, 95, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '008-09e196', NULL, 610.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(202, 96, 96, 'Renk: Mavi', NULL, '{\"Renk\":\"Mavi\"}', '009-21cd43', NULL, 535.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(203, 96, 96, 'Renk: Beyaz', NULL, '{\"Renk\":\"Beyaz\"}', '009-56a4d7', NULL, 610.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56'),
(204, 96, 96, 'Renk: Kırmızı', NULL, '{\"Renk\":\"Kırmızı\"}', '009-09e196', NULL, 560.00, 100, '', 0, 1, '2025-12-03 20:59:56', '2025-12-03 20:59:56');

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
(1, 'admin', '482c811da5d5b4bc6d497ffa98491e38', 'admin@panel.com', '2025-12-03 13:48:59', '2025-12-02 08:21:43');

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
  ADD KEY `idx_parent` (`parent_product_id`),
  ADD KEY `idx_sku` (`sku`);

--
-- Tablo için indeksler `sync_logs`
--
ALTER TABLE `sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_marketplace` (`marketplace`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Tablo için AUTO_INCREMENT değeri `category_mappings`
--
ALTER TABLE `category_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- Tablo için AUTO_INCREMENT değeri `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Tablo için AUTO_INCREMENT değeri `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- Tablo için AUTO_INCREMENT değeri `sync_logs`
--
ALTER TABLE `sync_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Tablo kısıtlamaları `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
