<?php
// Genel ayarlar
if (!defined('SITE_NAME')) define('SITE_NAME', 'Pazaryeri Yönetim Paneli');
if (!defined('SITE_URL')) define('SITE_URL', 'https://poysi.com');
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'admin@panel.com');

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Hata raporlama (production'da kapatılmalı)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session ayarları (session başlatılmadan önce yapılmalı)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // HTTPS kullanıyorsanız 1 yapın
}

// Maksimum yükleme boyutu
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

// API zaman aşımı (saniye)
if (!defined('API_TIMEOUT')) define('API_TIMEOUT', 30);

// Sayfa başına ürün sayısı
if (!defined('PRODUCTS_PER_PAGE')) define('PRODUCTS_PER_PAGE', 50);
if (!defined('ORDERS_PER_PAGE')) define('ORDERS_PER_PAGE', 50);

// Log seviyeleri
if (!defined('LOG_ERROR')) define('LOG_ERROR', 'error');
if (!defined('LOG_WARNING')) define('LOG_WARNING', 'warning');
if (!defined('LOG_INFO')) define('LOG_INFO', 'info');
if (!defined('LOG_SUCCESS')) define('LOG_SUCCESS', 'success');

// Opencart ayarları
define('OPENCART_URL', 'http://localhost/opencart');
define('OPENCART_API_PATH', '/index.php?route=api/');

// Senkronizasyon ayarları
define('AUTO_SYNC_ENABLED', true);
define('SYNC_INTERVAL', 3600); // 1 saat (saniye cinsinden)

// Fotoğraf ayarları
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_IMAGE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
?>