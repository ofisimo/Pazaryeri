<?php
/**
 * Ürün Varyantlarını Getir (BASİT VERSİYON)
 * Konum: ajax/get-product-variants.php
 * 
 * NOT: Bu versiyon variant_id olmadan çalışır
 * Ana ürün resmini gösterir
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

$productId = $_GET['id'] ?? 0;

// Debug log
error_log("VARIANT REQUEST: ID = " . var_export($productId, true));

if (!$productId || $productId == 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Geçersiz ürün ID',
        'debug' => ['received_id' => $productId]
    ]);
    exit;
}

try {
    // Ürün bilgisini al
    $stmt = $db->prepare("SELECT name, image_url FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        exit;
    }
    
    // Ana ürün resmini belirle
    $mainImageUrl = null;
    if (!empty($product['image_url'])) {
        if (strpos($product['image_url'], 'http') === 0) {
            $mainImageUrl = $product['image_url'];
        } else {
            $mainImageUrl = '/uploads/' . $product['image_url'];
        }
    }
    
    // Varyantları al
    $stmt = $db->prepare("
        SELECT * 
        FROM product_variants 
        WHERE parent_product_id = :product_id
        ORDER BY variant_name
    ");
    $stmt->execute([':product_id' => $productId]);
    $variants = $stmt->fetchAll();
    
    // Varyant yoksa
    if (empty($variants)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Bu ürünün varyantı bulunmuyor'
        ]);
        exit;
    }
    
    // Varyantları formatla
    $formattedVariants = [];
    foreach ($variants as $variant) {
        // Options JSON'u parse et
        $options = [];
        if (!empty($variant['options_json'])) {
            $options = json_decode($variant['options_json'], true);
            if (!$options) $options = [];
        }
        
        // Varyant için ana ürün resmini kullan
        // İleride variant_id eklenince her varyantın kendi resmi olabilir
        
        $formattedVariants[] = [
            'id' => $variant['id'],
            'sku' => $variant['variant_sku'],
            'name' => $variant['variant_name'],
            'price' => $variant['price'],
            'stock' => $variant['stock'],
            'barcode' => $variant['barcode'],
            'image_url' => $mainImageUrl, // Ana ürün resmi
            'variant_attributes' => json_encode($options),
            'options' => $options
        ];
    }
    
    echo json_encode([
        'success' => true,
        'product_name' => $product['name'],
        'variants' => $formattedVariants
    ]);
    
} catch (Exception $e) {
    error_log("VARIANT FETCH ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}