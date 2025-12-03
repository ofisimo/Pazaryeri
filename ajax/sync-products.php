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
        $imagesDownloaded = 0; // Resim sayacı ekledik
        
        foreach ($ocProducts as $ocProduct) {
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
                        has_variants = :has_variants,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':opencart_id' => $ocProduct['product_id'],
                    ':name' => $ocProduct['name'],
                    ':price' => $ocProduct['price'] ?? 0,
                    ':stock' => $ocProduct['quantity'] ?? 0,
                    ':has_variants' => $hasVariants ? 1 : 0,
                    ':id' => $existing['id']
                ]);
                $updated++;
                $productId = $existing['id'];
            } else {
                // Yeni ana ürün ekle
                $stmt = $db->prepare("
                    INSERT INTO products (opencart_id, sku, name, description, price, stock, barcode, has_variants)
                    VALUES (:opencart_id, :sku, :name, :description, :price, :stock, :barcode, :has_variants)
                ");
                $stmt->execute([
                    ':opencart_id' => $ocProduct['product_id'],
                    ':sku' => $ocProduct['model'],
                    ':name' => $ocProduct['name'],
                    ':description' => $ocProduct['description'] ?? '',
                    ':price' => $ocProduct['price'] ?? 0,
                    ':stock' => $ocProduct['quantity'] ?? 0,
                    ':barcode' => $ocProduct['ean'] ?? '',
                    ':has_variants' => $hasVariants ? 1 : 0
                ]);
                $imported++;
                $productId = $db->lastInsertId();
            }
            
            // ============ RESİM İNDİRME BAŞLANGIÇ ============
            // Ana resmi indir
            if (!empty($ocProduct['image'])) {
                $imageUrl = downloadProductImage($productId, $ocProduct['image'], 'opencart', $db);
                if ($imageUrl) {
                    $imagesDownloaded++;
                }
            }
            
            // Ek resimleri indir
            if (!empty($ocProduct['images']) && is_array($ocProduct['images'])) {
                foreach ($ocProduct['images'] as $image) {
                    if (!empty($image)) {
                        $result = downloadAdditionalImage($productId, $image, 'opencart', $db);
                        if ($result) {
                            $imagesDownloaded++;
                        }
                    }
                }
            }
            // ============ RESİM İNDİRME BİTİŞ ============
            
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
            'message' => "{$imported} ürün eklendi, {$updated} ürün güncellendi, {$variantsAdded} varyant ve {$imagesDownloaded} resim eklendi"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bu işlem henüz desteklenmiyor']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}

// ============ RESİM İNDİRME FONKSİYONLARI ============

/**
 * Ana ürün resmini indir
 */
function downloadProductImage($productId, $imageUrl, $platform, $db) {
    if (empty($imageUrl)) return null;
    
    try {
        // uploads/opencart klasörünü oluştur
        $uploadDir = __DIR__ . '/../uploads/' . $platform . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Tam URL oluştur (OpenCart için)
        if (strpos($imageUrl, 'http') !== 0) {
            // OpenCart image path'i
            $imageUrl = 'https://kelebeksoft.com/image/' . $imageUrl;
        }
        
        // Dosya adı
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension) || strlen($extension) > 4) {
            $extension = 'jpg';
        }
        $fileName = 'product_' . $productId . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Resmi indir
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $imageData && strlen($imageData) > 100) {
            file_put_contents($filePath, $imageData);
            
            $relativePath = 'uploads/' . $platform . '/' . $fileName;
            
            // Veritabanını güncelle
            $stmt = $db->prepare("UPDATE products SET image_url = :image_url WHERE id = :id");
            $stmt->execute([':id' => $productId, ':image_url' => $relativePath]);
            
            return $relativePath;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Resim indirme hatası: " . $e->getMessage());
        return null;
    }
}

/**
 * Ek resim indir
 */
function downloadAdditionalImage($productId, $imageUrl, $platform, $db) {
    if (empty($imageUrl)) return false;
    
    try {
        $uploadDir = __DIR__ . '/../uploads/' . $platform . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Tam URL oluştur
        if (strpos($imageUrl, 'http') !== 0) {
            $imageUrl = 'https://kelebeksoft.com/image/' . $imageUrl;
        }
        
        // Dosya adı
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension) || strlen($extension) > 4) {
            $extension = 'jpg';
        }
        $fileName = 'product_' . $productId . '_extra_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Resmi indir
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $imageData && strlen($imageData) > 100) {
            file_put_contents($filePath, $imageData);
            
            $relativePath = 'uploads/' . $platform . '/' . $fileName;
            
            // product_images tablosuna ekle
            $stmt = $db->prepare("
                INSERT INTO product_images (product_id, image_url, image_path, platform, sort_order, is_main)
                VALUES (:product_id, :image_url, :image_path, :platform, 1, 0)
            ");
            $stmt->execute([
                ':product_id' => $productId,
                ':image_url' => $relativePath,
                ':image_path' => $relativePath,
                ':platform' => $platform
            ]);
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Ek resim indirme hatası: " . $e->getMessage());
        return false;
    }
}
?>