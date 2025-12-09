<?php
/**
 * Yardımcı Fonksiyonlar
 */

/**
 * Güvenli HTML çıktısı
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Tarih formatla
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Para formatla
 */
function formatPrice($price, $currency = '₺') {
    return number_format($price, 2, ',', '.') . ' ' . $currency;
}

/**
 * Durum rengini al
 */
function getStatusColor($status) {
    $colors = [
        'Approved' => 'warning',
        'Created' => 'warning',
        'Picking' => 'info',
        'Shipped' => 'info',
        'Delivered' => 'success',
        'Cancelled' => 'danger',
        'Returned' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

/**
 * Pazaryeri ikonunu al
 */
function getMarketplaceIcon($marketplace) {
    $icons = [
        'trendyol' => 'fa-shopping-bag',
        'hepsiburada' => 'fa-shopping-basket',
        'n11' => 'fa-store-alt'
    ];
    return $icons[$marketplace] ?? 'fa-store';
}

/**
 * Başarı mesajı göster
 */
function showSuccess($message) {
    return '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . h($message) . '</div>';
}

/**
 * Hata mesajı göster
 */
function showError($message) {
    return '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . h($message) . '</div>';
}

/**
 * Uyarı mesajı göster
 */
function showWarning($message) {
    return '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ' . h($message) . '</div>';
}

/**
 * Bilgi mesajı göster
 */
function showInfo($message) {
    return '<div class="alert alert-info"><i class="fas fa-info-circle"></i> ' . h($message) . '</div>';
}

/**
 * Log kaydet
 */
function logMessage($message, $file = 'app.log') {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/' . $file;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Sayfalama linkleri oluştur
 */
function pagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Önceki sayfa
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&p=' . ($currentPage - 1) . '"><i class="fas fa-chevron-left"></i></a>';
    }
    
    // Sayfa numaraları
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '&p=' . $i . '">' . $i . '</a>';
        }
    }
    
    // Sonraki sayfa
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&p=' . ($currentPage + 1) . '"><i class="fas fa-chevron-right"></i></a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Güvenli yönlendirme
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * CSRF token oluştur
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token doğrula
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Dosya boyutunu formatla
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Güvenli dosya yükleme
 */
function uploadFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Geçersiz dosya');
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('Dosya boyutu çok büyük');
        default:
            throw new Exception('Dosya yükleme hatası');
    }
    
    if ($file['size'] > 5242880) { // 5MB
        throw new Exception('Dosya boyutu 5MB\'dan küçük olmalıdır');
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Geçersiz dosya türü');
    }
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Dosya yüklenemedi');
    }
    
    return $fileName;
}

/**
 * Metin kısalt
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * JSON yanıt gönder
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>