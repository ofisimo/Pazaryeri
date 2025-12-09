<?php
/**
 * OpenCart Ürün Senkronizasyonu - KATEGORİ + DEBUG
 * Konum: pages/sync-products-debug.php (Test için)
 * 
 * KULLANIM: 
 * 1. Bunu pages/sync-products.php olarak yükleyin
 * 2. Senkronizasyon yapın
 * 3. PHP error_log'a bakın (genelde /var/log/apache2/error.log veya similar_web/error_log)
 * 4. "CATEGORY_DEBUG" veya "SYNC_" loglarını arayın
 */
session_start();

// Output buffering başlat - ekstra çıktıları yakala
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ImageHelper.php';
require_once __DIR__ . '/../api/OpencartAPI.php';

// Bufferi temizle - require'lardan gelen ekstra çıktıları sil
ob_end_clean();

// Yeni buffer başlat - sadece JSON çıktısı için
ob_start();

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    ob_end_flush();
    exit;
}

$source = $_GET['source'] ?? $_POST['source'] ?? '';
$direction = $_GET['direction'] ?? $_POST['direction'] ?? '';

if (empty($source) || empty($direction)) {
    echo json_encode(['success' => false, 'message' => 'Parametreler eksik']);
    ob_end_flush();
    exit;
}

function processCategoryFromPlatform($db, $platformCategoryId, $platformCategoryName, $platform, $platformParentId = 0, &$categoryMap) {
    error_log("📁 CATEGORY: Processing $platformCategoryName (ID: $platformCategoryId, Platform: $platform, Parent: $platformParentId)");
    
    if (isset($categoryMap[$platformCategoryId])) {
        error_log("✅ CATEGORY: Already in map -> " . $categoryMap[$platformCategoryId]);
        return $categoryMap[$platformCategoryId];
    }
    
    $stmt = $db->prepare("
        SELECT c.id FROM categories c
        INNER JOIN category_mappings cm ON c.id = cm.category_id
        WHERE cm.platform = :platform AND cm.platform_category_id = :platform_id
    ");
    $stmt->execute([':platform' => $platform, ':platform_id' => $platformCategoryId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $categoryId = $existing['id'];
        error_log("✅ CATEGORY: Exists in DB -> Local ID: $categoryId");
        $stmt = $db->prepare("UPDATE categories SET name = :name WHERE id = :id");
        $stmt->execute([':name' => $platformCategoryName, ':id' => $categoryId]);
    } else {
        $stmt = $db->prepare("INSERT INTO categories (name, parent_id) VALUES (:name, NULL)");
        $stmt->execute([':name' => $platformCategoryName]);
        $categoryId = $db->lastInsertId();
        error_log("✨ CATEGORY: Created -> Local ID: $categoryId");
        
        $stmt = $db->prepare("
            INSERT INTO category_mappings (category_id, platform, platform_category_id, platform_category_name)
            VALUES (:category_id, :platform, :platform_id, :platform_name)
        ");
        $stmt->execute([
            ':category_id' => $categoryId,
            ':platform' => $platform,
            ':platform_id' => $platformCategoryId,
            ':platform_name' => $platformCategoryName
        ]);
    }
    
    $categoryMap[$platformCategoryId] = $categoryId;
    return $categoryId;
}

function updateCategoryHierarchy($db, $categoryMap, $platformParentMap) {
    error_log("🔗 HIERARCHY: Updating " . count($platformParentMap) . " parent relationships");
    foreach ($platformParentMap as $platformCatId => $platformParentId) {
        if ($platformParentId > 0 && isset($categoryMap[$platformCatId]) && isset($categoryMap[$platformParentId])) {
            $categoryId = $categoryMap[$platformCatId];
            $parentId = $categoryMap[$platformParentId];
            $stmt = $db->prepare("UPDATE categories SET parent_id = :parent_id WHERE id = :id");
            $stmt->execute([':parent_id' => $parentId, ':id' => $categoryId]);
            error_log("🔗 HIERARCHY: Category $categoryId -> Parent $parentId");
        }
    }
}

function linkProductToCategories($db, $productId, $categoryIds) {
    error_log("🔗 LINKING: Product $productId -> " . count($categoryIds) . " categories");
    $stmt = $db->prepare("DELETE FROM product_categories WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    
    $linked = 0;
    foreach ($categoryIds as $categoryId) {
        try {
            $stmt = $db->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)");
            $stmt->execute([':product_id' => $productId, ':category_id' => $categoryId]);
            $linked++;
            error_log("✅ LINKED: Product $productId <-> Category $categoryId");
        } catch (PDOException $e) {
            if ($e->getCode() != 23000) {
                error_log("❌ LINK ERROR: " . $e->getMessage());
            }
        }
    }
    return $linked;
}

try {
    if ($direction == 'import' && $source == 'opencart') {
        error_log("🚀 SYNC START: OpenCart import");
        
        $opencartAPI = new OpencartAPI();
        if (!$opencartAPI->isActive()) {
            echo json_encode(['success' => false, 'message' => 'OpenCart aktif değil']);
            ob_end_flush();
            exit;
        }
        
        $uploadsDir = __DIR__ . '/../uploads/opencart/';
        if (!file_exists($uploadsDir)) mkdir($uploadsDir, 0755, true);
        
        $response = $opencartAPI->getProducts(1000, 1);
        if (!isset($response['products']) || empty($response['products'])) {
            echo json_encode(['success' => false, 'message' => 'OpenCart\'tan ürün gelmedi']);
            ob_end_flush();
            exit;
        }
        
        $ocProducts = $response['products'];
        error_log("📦 PRODUCTS: Received " . count($ocProducts) . " products");
        
        // İlk ürünün yapısını logla (debug için)
        if (!empty($ocProducts[0])) {
            $firstProduct = $ocProducts[0];
            error_log("🔍 FIRST PRODUCT STRUCTURE:");
            error_log("  - product_id: " . ($firstProduct['product_id'] ?? 'N/A'));
            error_log("  - name: " . ($firstProduct['name'] ?? 'N/A'));
            error_log("  - Has 'variants' key: " . (isset($firstProduct['variants']) ? 'YES' : 'NO'));
            error_log("  - Has 'options' key: " . (isset($firstProduct['options']) ? 'YES' : 'NO'));
            if (isset($firstProduct['variants'])) {
                error_log("  - Variants count: " . count($firstProduct['variants']));
                error_log("  - Variants structure: " . json_encode(array_slice($firstProduct['variants'], 0, 1)));
            }
            if (isset($firstProduct['options'])) {
                error_log("  - Options count: " . count($firstProduct['options']));
                error_log("  - Options structure: " . json_encode(array_slice($firstProduct['options'], 0, 1)));
            }
        }
        
        $imported = 0; $updated = 0; $variantsAdded = 0; $imagesDownloaded = 0;
        $categoriesProcessed = 0; $categoriesLinked = 0;
        $categoryMap = []; $platformParentMap = [];
        
        foreach ($ocProducts as $ocProduct) {
            // Ürünü kaydet
            $stmt = $db->prepare("SELECT id FROM products WHERE sku = :sku OR opencart_id = :opencart_id");
            $stmt->execute([':sku' => $ocProduct['model'], ':opencart_id' => $ocProduct['product_id']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $db->prepare("UPDATE products SET opencart_id = :opencart_id, name = :name, price = :price, stock = :stock, has_variants = :has_variants, updated_at = NOW() WHERE id = :id");
                $stmt->execute([
                    ':opencart_id' => $ocProduct['product_id'], ':name' => $ocProduct['name'],
                    ':price' => $ocProduct['price'] ?? 0, ':stock' => $ocProduct['quantity'] ?? 0,
                    ':has_variants' => !empty($ocProduct['variants']) ? 1 : 0, ':id' => $existing['id']
                ]);
                $updated++; $productId = $existing['id'];
            } else {
                $stmt = $db->prepare("INSERT INTO products (opencart_id, sku, name, description, price, stock, barcode, has_variants) VALUES (:opencart_id, :sku, :name, :description, :price, :stock, :barcode, :has_variants)");
                $stmt->execute([
                    ':opencart_id' => $ocProduct['product_id'], ':sku' => $ocProduct['model'],
                    ':name' => $ocProduct['name'], ':description' => $ocProduct['description'] ?? '',
                    ':price' => $ocProduct['price'] ?? 0, ':stock' => $ocProduct['quantity'] ?? 0,
                    ':barcode' => $ocProduct['ean'] ?? '', ':has_variants' => !empty($ocProduct['variants']) ? 1 : 0
                ]);
                $imported++; $productId = $db->lastInsertId();
            }
            
            // KATEGORİLERİ İŞLE
            $productCategoryIds = []; $productCategories = [];
            
            // Format 1: category_id
            if (isset($ocProduct['category_id']) && $ocProduct['category_id'] > 0) {
                error_log("📁 FOUND: category_id={$ocProduct['category_id']} for product $productId");
                $productCategories[] = [
                    'category_id' => $ocProduct['category_id'],
                    'name' => $ocProduct['category_name'] ?? 'Kategori ' . $ocProduct['category_id'],
                    'parent_id' => $ocProduct['category_parent_id'] ?? 0
                ];
            }
            
            // Format 2: categories array
            if (isset($ocProduct['categories']) && is_array($ocProduct['categories'])) {
                error_log("📁 FOUND: categories array with " . count($ocProduct['categories']) . " items for product $productId");
                foreach ($ocProduct['categories'] as $cat) {
                    $productCategories[] = [
                        'category_id' => $cat['category_id'] ?? $cat['id'] ?? 0,
                        'name' => $cat['name'] ?? 'Kategori',
                        'parent_id' => $cat['parent_id'] ?? 0
                    ];
                }
            }
            
            // Kategorileri işle
            if (empty($productCategories)) {
                error_log("⚠️  WARNING: No categories for product $productId ({$ocProduct['name']})");
            } else {
                foreach ($productCategories as $cat) {
                    if (empty($cat['category_id'])) continue;
                    $categoryId = processCategoryFromPlatform($db, $cat['category_id'], $cat['name'], 'opencart', $cat['parent_id'], $categoryMap);
                    $productCategoryIds[] = $categoryId;
                    $categoriesProcessed++;
                    if ($cat['parent_id'] > 0) $platformParentMap[$cat['category_id']] = $cat['parent_id'];
                }
            }
            
            // Ürün-kategori ilişkisi
            if (!empty($productCategoryIds)) {
                $linked = linkProductToCategories($db, $productId, $productCategoryIds);
                $categoriesLinked += $linked;
            }
            
            // Resimler (kısaltılmış)
            $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $productId]);
            
            $allImages = [];
            if (!empty($ocProduct['image'])) $allImages[] = $ocProduct['image'];
            if (!empty($ocProduct['images']) && is_array($ocProduct['images'])) {
                foreach ($ocProduct['images'] as $img) {
                    if (!empty($img) && !in_array($img, $allImages)) $allImages[] = $img;
                }
            }
            
            if (!empty($allImages)) {
                try {
                    $savedCount = ImageHelper::saveProductImages($db, $productId, $allImages, 'opencart', $ocProduct['model']);
                    $imagesDownloaded += $savedCount;
                    if ($savedCount > 0) {
                        $stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = :product_id AND is_main = 1 LIMIT 1");
                        $stmt->execute([':product_id' => $productId]);
                        $mainImageRecord = $stmt->fetch();
                        if ($mainImageRecord && !empty($mainImageRecord['image_path'])) {
                            $stmt = $db->prepare("UPDATE products SET image_url = :image_path WHERE id = :id");
                            $stmt->execute([':image_path' => $mainImageRecord['image_path'], ':id' => $productId]);
                        }
                    }
                } catch (Exception $e) {
                    error_log("❌ IMAGE ERROR: Product $productId - " . $e->getMessage());
                }
            }
            
            // Varyantlar
            $hasVariants = false;
            $variantData = [];
            
            // Format 1: variants array (direkt)
            if (!empty($ocProduct['variants']) && is_array($ocProduct['variants'])) {
                error_log("🎨 VARIANTS FORMAT 1: Direct variants array");
                $variantData = $ocProduct['variants'];
                $hasVariants = true;
            }
            // Format 2: options array (OpenCart klasik format)
            elseif (!empty($ocProduct['options']) && is_array($ocProduct['options'])) {
                error_log("🎨 VARIANTS FORMAT 2: OpenCart options array");
                // OpenCart options'ı variants'a çevir
                foreach ($ocProduct['options'] as $option) {
                    if (isset($option['product_option_value'])) {
                        foreach ($option['product_option_value'] as $optionValue) {
                            $variantData[] = [
                                'sku' => $optionValue['sku'] ?? null,
                                'model' => $optionValue['model'] ?? null,
                                'price' => $optionValue['price'] ?? 0,
                                'quantity' => $optionValue['quantity'] ?? 0,
                                'name' => ($option['name'] ?? '') . ': ' . ($optionValue['name'] ?? ''),
                                'options' => [
                                    $option['name'] => $optionValue['name']
                                ]
                            ];
                        }
                    }
                }
                $hasVariants = !empty($variantData);
            }
            
            if ($hasVariants && !empty($variantData)) {
                error_log("🎨 VARIANTS: Found " . count($variantData) . " variants for product $productId");
                
                // Önce mevcut varyantları sil
                $stmt = $db->prepare("DELETE FROM product_variants WHERE parent_product_id = :parent_id");
                $stmt->execute([':parent_id' => $productId]);
                
                foreach ($variantData as $variant) {
                    try {
                        // Varyant bilgileri
                        $variantSku = $variant['sku'] ?? $variant['model'] ?? ($ocProduct['model'] . '-VAR' . rand(1000, 9999));
                        $variantName = $variant['name'] ?? '';
                        $variantPrice = $variant['price'] ?? $ocProduct['price'] ?? 0;
                        $variantStock = $variant['quantity'] ?? $variant['stock'] ?? 0;
                        $variantBarcode = $variant['ean'] ?? $variant['barcode'] ?? '';
                        
                        // Option bilgileri (renk, beden vb.)
                        $options = [];
                        if (isset($variant['options']) && is_array($variant['options'])) {
                            $options = $variant['options'];
                        }
                        
                        // Varyantı kaydet
                        $stmt = $db->prepare("
                            INSERT INTO product_variants 
                            (parent_product_id, variant_sku, variant_name, price, stock, barcode, options_json, opencart_id)
                            VALUES 
                            (:parent_id, :sku, :name, :price, :stock, :barcode, :options, :opencart_id)
                        ");
                        
                        $stmt->execute([
                            ':parent_id' => $productId,
                            ':sku' => $variantSku,
                            ':name' => $variantName,
                            ':price' => $variantPrice,
                            ':stock' => $variantStock,
                            ':barcode' => $variantBarcode,
                            ':options' => json_encode($options),
                            ':opencart_id' => $variant['product_id'] ?? $variant['id'] ?? null
                        ]);
                        
                        $variantId = $db->lastInsertId();
                        $variantsAdded++;
                        
                        error_log("✅ VARIANT: Added variant $variantId ($variantSku) for product $productId");
                        
                        // Varyant resimleri
                        if (!empty($variant['image'])) {
                            try {
                                $variantImages = [];
                                if (is_string($variant['image'])) {
                                    $variantImages[] = $variant['image'];
                                } elseif (is_array($variant['image'])) {
                                    $variantImages = $variant['image'];
                                }
                                
                                if (!empty($variantImages)) {
                                    $savedCount = ImageHelper::saveProductImages($db, $productId, $variantImages, 'opencart', $variantSku, $variantId);
                                    error_log("🖼️ VARIANT IMAGE: Saved $savedCount images for variant $variantId");
                                }
                            } catch (Exception $e) {
                                error_log("❌ VARIANT IMAGE ERROR: " . $e->getMessage());
                            }
                        }
                        
                    } catch (PDOException $e) {
                        error_log("❌ VARIANT ERROR: " . $e->getMessage());
                    }
                }
            } else {
                error_log("ℹ️ NO VARIANTS: Product $productId ({$ocProduct['name']}) has no variants");
                // has_variants flag'ini güncelle
                $stmt = $db->prepare("UPDATE products SET has_variants = 0 WHERE id = :id");
                $stmt->execute([':id' => $productId]);
            }
        }
        
        // Parent ilişkileri
        if (!empty($platformParentMap)) {
            updateCategoryHierarchy($db, $categoryMap, $platformParentMap);
        }
        
        error_log("✅ SYNC COMPLETE: Imported=$imported, Updated=$updated, CategoriesLinked=$categoriesLinked");
        
        $message = "{$imported} ürün eklendi, {$updated} ürün güncellendi, {$variantsAdded} varyant eklendi, {$imagesDownloaded} resim indirildi";
        if ($categoriesLinked > 0) {
            $message .= ", {$categoriesLinked} kategori ilişkilendirildi";
        } else {
            error_log("WARNING: NO CATEGORIES LINKED!");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'stats' => [
                'imported' => $imported, 'updated' => $updated, 'variants' => $variantsAdded,
                'images_downloaded' => $imagesDownloaded, 'categories_processed' => $categoriesProcessed,
                'categories_linked' => $categoriesLinked
            ]
        ]);
        
        // Bufferi temizle ve çıkış yap
        ob_end_flush();
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Bu işlem desteklenmiyor']);
        ob_end_flush();
        exit;
    }
} catch (Exception $e) {
    error_log("SYNC ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    ob_end_flush();
    exit;
}
