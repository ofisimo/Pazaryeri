<?php
session_start();
require_once '../config/database.php';
require_once '../includes/ImageHelper.php';
require_once '../api/OpencartAPI.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

// GET veya POST'tan al
$source = $_GET['source'] ?? $_POST['source'] ?? '';
$direction = $_GET['direction'] ?? $_POST['direction'] ?? '';

if (empty($source) || empty($direction)) {
    echo json_encode(['success' => false, 'message' => 'Parametreler eksik']);
    exit;
}

try {
    if ($direction == 'import' && $source == 'opencart') {
        $opencartAPI = new OpencartAPI();
        
        if (!$opencartAPI->isActive()) {
            echo json_encode(['success' => false, 'message' => 'OpenCart aktif değil']);
            exit;
        }
        
        $response = $opencartAPI->getProducts(1000, 1);
        
        // DEBUG - Yanıtı görelim
        if (!isset($response['products']) || empty($response['products'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'OpenCart\'tan ürün gelmedi',
                'debug' => $response
            ]);
            exit;
        }
        
		
		
        $ocProducts = $response['products'] ?? [];
        
        $imported = 0;
        $updated = 0;
        $variantsAdded = 0;
        
        foreach ($ocProducts as $ocProduct) {
			     $imported++;
                    $productId = $db->lastInsertId();
                    
                    // Resimleri indir ve kaydet
                    if (!empty($ocProduct['images'])) {
                        ImageHelper::saveProductImages($db, $productId, $ocProduct['images'], 'opencart');
                    }
            // Ana ürün kontrolü
            $stmt = $db->prepare("SELECT id FROM products WHERE sku = :sku OR opencart_id = :opencart_id");
            $stmt->execute([
                ':sku' => $ocProduct['model'],
                ':opencart_id' => $ocProduct['product_id']
            ]);
            $existing = $stmt->fetch();
            
            // Varyant var mı kontrol et
            $hasVariants = !empty($ocProduct['variants']);
            
            if ($existing) {
                // Ana ürünü güncelle
                $stmt = $db->prepare("
                    UPDATE products 
                    SET opencart_id = :opencart_id, 
                        name = :name, 
                        price = :price, 
                        stock = :stock,
                        image_url = :image,
                        has_variants = :has_variants,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':opencart_id' => $ocProduct['product_id'],
                    ':name' => $ocProduct['name'],
                    ':price' => $ocProduct['price'] ?? 0,
                    ':stock' => $ocProduct['quantity'] ?? 0,
                    ':image' => $ocProduct['image'] ?? '',
                    ':has_variants' => $hasVariants ? 1 : 0,
                    ':id' => $existing['id']
                ]);
                $updated++;
                $productId = $existing['id'];
            } else {
                // Yeni ana ürün ekle
                $stmt = $db->prepare("
                    INSERT INTO products (opencart_id, sku, name, description, price, stock, barcode, image_url, has_variants)
                    VALUES (:opencart_id, :sku, :name, :description, :price, :stock, :barcode, :image, :has_variants)
                ");
                $stmt->execute([
                    ':opencart_id' => $ocProduct['product_id'],
                    ':sku' => $ocProduct['model'],
                    ':name' => $ocProduct['name'],
                    ':description' => $ocProduct['description'] ?? '',
                    ':price' => $ocProduct['price'] ?? 0,
                    ':stock' => $ocProduct['quantity'] ?? 0,
                    ':barcode' => $ocProduct['ean'] ?? '',
                    ':image' => $ocProduct['image'] ?? '',
                    ':has_variants' => $hasVariants ? 1 : 0
                ]);
                $imported++;
                $productId = $db->lastInsertId();
            }
            
            // Varyantları işle
            if ($hasVariants) {
                // Önce eski varyantları sil
                $stmt = $db->prepare("DELETE FROM product_variants WHERE parent_product_id = :parent_id");
                $stmt->execute([':parent_id' => $productId]);
                
                // Varyantları grupla (aynı kombinasyonları birleştir)
                $variantGroups = [];
                foreach ($ocProduct['variants'] as $variant) {
                    // Varyant key oluştur (örn: "Renk:Mavi")
                    $key = $variant['variant_name'] . ':' . $variant['variant_value'];
                    
                    if (!isset($variantGroups[$key])) {
                        $variantGroups[$key] = [
                            'attributes' => [],
                            'price' => $ocProduct['price'],
                            'stock' => $variant['quantity'] ?? 0,
                            'sku' => $variant['sku'],
                            'image' => $variant['image']
                        ];
                    }
                    
                    // Attribute ekle
                    $variantGroups[$key]['attributes'][$variant['variant_name']] = $variant['variant_value'];
                    
                    // Fiyatı hesapla
                    if ($variant['price_prefix'] == '+') {
                        $variantGroups[$key]['price'] += $variant['price'];
                    } elseif ($variant['price_prefix'] == '-') {
                        $variantGroups[$key]['price'] -= $variant['price'];
                    }
                }
                
                // Her varyant grubunu kaydet
                foreach ($variantGroups as $vGroup) {
                    // Attributes'ı JSON'a çevir
                    $attributesJson = json_encode($vGroup['attributes'], JSON_UNESCAPED_UNICODE);
                    
                    // Varyant adı oluştur (Renk: Mavi, Beden: L)
                    $variantName = implode(', ', array_map(
                        function($k, $v) { return "$k: $v"; },
                        array_keys($vGroup['attributes']),
                        array_values($vGroup['attributes'])
                    ));
                    
                    // SKU yoksa oluştur
                    $variantSku = $vGroup['sku'] ?: ($ocProduct['model'] . '-' . substr(md5($variantName), 0, 6));
                    
                    $stmt = $db->prepare("
                        INSERT INTO product_variants (
                            product_id, parent_product_id, variant_name, variant_attributes,
                            sku, price, stock, image_url
                        ) VALUES (
                            :product_id, :parent_id, :variant_name, :variant_attributes,
                            :sku, :price, :stock, :image
                        )
                    ");
                    $stmt->execute([
                        ':product_id' => $productId,
                        ':parent_id' => $productId,
                        ':variant_name' => $variantName,
                        ':variant_attributes' => $attributesJson,
                        ':sku' => $variantSku,
                        ':price' => $vGroup['price'],
                        ':stock' => $vGroup['stock'],
                        ':image' => $vGroup['image'] ?? ''
                    ]);
                    $variantsAdded++;
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "{$imported} ürün eklendi, {$updated} ürün güncellendi, {$variantsAdded} varyant eklendi"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bu işlem henüz desteklenmiyor']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}