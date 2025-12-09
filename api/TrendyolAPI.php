<?php
session_start();
require_once '../config/database.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

header('Content-Type: application/json');

$marketplace = $_POST['marketplace'] ?? '';

if (empty($marketplace)) {
    echo json_encode(['success' => false, 'message' => 'Pazaryeri belirtilmedi']);
    exit;
}

try {
    // Ayarları çek
    $stmt = $db->prepare("SELECT * FROM marketplace_settings WHERE marketplace = :marketplace");
    $stmt->execute([':marketplace' => $marketplace]);
    $settings = $stmt->fetch();
    
    if (!$settings) {
        echo json_encode(['success' => false, 'message' => 'Ayarlar bulunamadı. Lütfen önce kaydedin.']);
        exit;
    }
    
    // API sınıflarını yükle
    require_once '../api/TrendyolAPI.php';
    require_once '../api/HepsiburadaAPI.php';
    require_once '../api/N11API.php';
    
    $result = ['success' => false, 'message' => ''];
    
    // Test et
    if ($marketplace == 'trendyol') {
        $api = new TrendyolAPI(
            $settings['api_key'],
            $settings['api_secret'],
            $settings['merchant_id']
        );
        
        try {
            $response = $api->getBrands(0, 1);
            $result['success'] = true;
            $result['message'] = 'Trendyol bağlantısı başarılı!';
            $result['data'] = 'Toplam ' . ($response['totalElements'] ?? 0) . ' marka bulundu.';
        } catch (Exception $e) {
            $result['message'] = 'Bağlantı hatası: ' . $e->getMessage();
        }
    }
    
    elseif ($marketplace == 'hepsiburada') {
        $api = new HepsiburadaAPI(
            $settings['api_key'],
            $settings['api_secret'],
            $settings['merchant_id']
        );
        
        try {
            $response = $api->getProducts(0, 1);
            $result['success'] = true;
            $result['message'] = 'Hepsiburada bağlantısı başarılı!';
            $result['data'] = 'Toplam ' . ($response['totalCount'] ?? 0) . ' ürün bulundu.';
        } catch (Exception $e) {
            $result['message'] = 'Bağlantı hatası: ' . $e->getMessage();
        }
    }
    
    elseif ($marketplace == 'n11') {
        $api = new N11API(
            $settings['api_key'],
            $settings['api_secret']
        );
        
        try {
            $response = $api->getProducts(0, 1);
            $result['success'] = true;
            $result['message'] = 'N11 bağlantısı başarılı!';
            $result['data'] = 'API bağlantısı çalışıyor.';
        } catch (Exception $e) {
            $result['message'] = 'Bağlantı hatası: ' . $e->getMessage();
        }
    }
    
    // Loga kaydet
    $logStmt = $db->prepare("INSERT INTO sync_logs (marketplace, action, status, message) VALUES (:marketplace, 'connection_test', :status, :message)");
    $logStmt->execute([
        ':marketplace' => $marketplace,
        ':status' => $result['success'] ? 'success' : 'error',
        ':message' => $result['message']
    ]);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>