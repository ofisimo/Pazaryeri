<?php
/**
 * Platform Kategorilerini Getir
 * AJAX endpoint - Modal'da kategori dropdown'ını doldurmak için
 * Konum: pages/get-platform-categories.php
 */
session_start();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

$platform = $_GET['platform'] ?? '';

if (empty($platform)) {
    echo json_encode(['success' => false, 'message' => 'Platform parametresi eksik']);
    exit;
}

try {
    $categories = [];
    
    switch ($platform) {
        case 'opencart':
            require_once __DIR__ . '/../api/OpencartAPI.php';
            $api = new OpencartAPI();
            
            if ($api->isActive()) {
                $response = $api->getCategories();
                if (isset($response['categories'])) {
                    $categories = $response['categories'];
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'OpenCart aktif değil']);
                exit;
            }
            break;
            
        case 'trendyol':
            require_once __DIR__ . '/../api/TrendyolAPI.php';
            $api = new TrendyolAPI();
            
            if ($api->isActive()) {
                // Trendyol API'den kategorileri çek
                // NOT: Trendyol API metodunu eklemeniz gerekebilir
                $response = $api->getCategories();
                if (isset($response['categories'])) {
                    $categories = $response['categories'];
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Trendyol aktif değil']);
                exit;
            }
            break;
            
        case 'hepsiburada':
            require_once __DIR__ . '/../api/HepsiburadaAPI.php';
            $api = new HepsiburadaAPI();
            
            if ($api->isActive()) {
                // Hepsiburada API'den kategorileri çek
                $response = $api->getCategories();
                if (isset($response['categories'])) {
                    $categories = $response['categories'];
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Hepsiburada aktif değil']);
                exit;
            }
            break;
            
        case 'n11':
            require_once __DIR__ . '/../api/N11API.php';
            $api = new N11API();
            
            if ($api->isActive()) {
                // N11 API'den kategorileri çek
                $response = $api->getCategories();
                if (isset($response['categories'])) {
                    $categories = $response['categories'];
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'N11 aktif değil']);
                exit;
            }
            break;
            
        default:
            // Veritabanından önceden çekilmiş kategorileri getir
            $stmt = $db->prepare("
                SELECT DISTINCT 
                    platform_category_id as category_id,
                    platform_category_id as id,
                    platform_category_name as name
                FROM category_mappings
                WHERE platform = :platform
                ORDER BY platform_category_name
            ");
            $stmt->execute([':platform' => $platform]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Kategorileri alfabetik sıraya koy
    usort($categories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'count' => count($categories)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}