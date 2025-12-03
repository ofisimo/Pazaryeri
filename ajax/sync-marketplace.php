<?php
session_start();
require_once '../config/database.php';
require_once '../includes/SyncManager.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

header('Content-Type: application/json');

$marketplace = $_POST['marketplace'] ?? '';
$type = $_POST['type'] ?? ''; // 'products' veya 'orders'

if (empty($marketplace) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Gerekli parametreler eksik']);
    exit;
}

try {
    $syncManager = new SyncManager($db);
    
    if ($type == 'orders') {
        $result = $syncManager->syncOrders($marketplace);
    } elseif ($type == 'products') {
        $result = $syncManager->syncProducts($marketplace);
    } else {
        $result = ['success' => false, 'message' => 'Geçersiz senkronizasyon tipi'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>