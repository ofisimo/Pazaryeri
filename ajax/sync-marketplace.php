<?php
session_start();
require_once '../config/database.php';
require_once '../includes/SyncManager.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

header('Content-Type: application/json');

$marketplace = $_POST['marketplace'] ?? '';
$type = $_POST['type'] ?? '';

if (empty($marketplace)) {
    echo json_encode(['success' => false, 'message' => 'Pazaryeri belirtilmedi']);
    exit;
}

if (empty($type)) {
    echo json_encode(['success' => false, 'message' => 'İşlem tipi belirtilmedi']);
    exit;
}

try {
    $syncManager = new SyncManager($db);
    
    if ($type == 'orders') {
        $result = $syncManager->syncOrders($marketplace);
    } 
    elseif ($type == 'products') {
        // Panelden pazaryerine gönder
        $result = $syncManager->syncProducts($marketplace);
    }
    elseif ($type == 'products_import') {
        // Pazaryerinden panele çek (RESİMLERLE)
        $result = $syncManager->syncProductsFromMarketplace($marketplace);
    }
    else {
        $result = ['success' => false, 'message' => 'Geçersiz işlem'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>