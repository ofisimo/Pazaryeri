<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
        exit;
    }
    
    $marketplace = $_POST['marketplace'] ?? '';
    
    if (empty($marketplace)) {
        echo json_encode(['success' => false, 'message' => 'Pazaryeri belirtilmedi']);
        exit;
    }
    
    $result = ['success' => false, 'message' => 'Bilinmeyen hata'];
    
    // Her pazaryeri için ayrı ayrı try-catch
    if ($marketplace == 'opencart') {
        try {
            require_once '../api/OpencartAPI.php';
            $api = new OpencartAPI();
            $result = $api->testConnection();
        } catch (Throwable $e) {
            $result = ['success' => false, 'message' => 'OpenCart hatası: ' . $e->getMessage()];
        }
    }
    
    elseif ($marketplace == 'trendyol') {
        try {
            require_once '../api/TrendyolAPI.php';
            
            $stmt = $db->prepare("SELECT * FROM marketplace_settings WHERE marketplace = 'trendyol'");
            $stmt->execute();
            $settings = $stmt->fetch();
            
            if (!$settings || !$settings['is_active']) {
                $result = ['success' => false, 'message' => 'Trendyol aktif değil'];
            } else {
                $api = new TrendyolAPI($settings['api_key'], $settings['api_secret'], $settings['merchant_id']);
                $response = $api->getBrands(0, 1);
                $result = ['success' => true, 'message' => 'Trendyol bağlantısı başarılı!'];
            }
        } catch (Throwable $e) {
            $result = ['success' => false, 'message' => 'Trendyol hatası: ' . $e->getMessage()];
        }
    }
    
    elseif ($marketplace == 'hepsiburada') {
        try {
            require_once '../api/HepsiburadaAPI.php';
            
            $stmt = $db->prepare("SELECT * FROM marketplace_settings WHERE marketplace = 'hepsiburada'");
            $stmt->execute();
            $settings = $stmt->fetch();
            
            if (!$settings || !$settings['is_active']) {
                $result = ['success' => false, 'message' => 'Hepsiburada aktif değil'];
            } else {
                $api = new HepsiburadaAPI($settings['api_key'], $settings['api_secret'], $settings['merchant_id']);
                $response = $api->getProducts(0, 1);
                $result = ['success' => true, 'message' => 'Hepsiburada bağlantısı başarılı!'];
            }
        } catch (Throwable $e) {
            $result = ['success' => false, 'message' => 'Hepsiburada hatası: ' . $e->getMessage()];
        }
    }
    
    elseif ($marketplace == 'n11') {
        try {
            require_once '../api/N11API.php';
            
            $stmt = $db->prepare("SELECT * FROM marketplace_settings WHERE marketplace = 'n11'");
            $stmt->execute();
            $settings = $stmt->fetch();
            
            if (!$settings || !$settings['is_active']) {
                $result = ['success' => false, 'message' => 'N11 aktif değil'];
            } else {
                $api = new N11API($settings['api_key'], $settings['api_secret']);
                $response = $api->getProducts(0, 1);
                $result = ['success' => true, 'message' => 'N11 bağlantısı başarılı!'];
            }
        } catch (Throwable $e) {
            $result = ['success' => false, 'message' => 'N11 hatası: ' . $e->getMessage()];
        }
    }
    
    echo json_encode($result);
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Genel hata: ' . $e->getMessage(),
        'line' => $e->getLine()
    ]);
}