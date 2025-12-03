<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ürün ID']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT id, variant_name, variant_attributes, sku, price, stock, image_url
        FROM product_variants 
        WHERE parent_product_id = :product_id
        ORDER BY sort_order, id
    ");
    $stmt->execute([':product_id' => $product_id]);
    $variants = $stmt->fetchAll();
    
    // JSON attributes'ları decode et
    foreach ($variants as &$variant) {
        $variant['attributes'] = json_decode($variant['variant_attributes'], true);
    }
    
    echo json_encode(['success' => true, 'variants' => $variants]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}