<?php
class ImageHelper {
    private static $uploadBasePath = __DIR__ . '/../uploads/';
    
    /**
     * URL'den resim indir ve kaydet
     */
    public static function downloadImage($imageUrl, $platform = 'local', $productSku = '') {
        if (empty($imageUrl)) {
            return false;
        }
        
        // Platform klasörünü olustur
        $platformPath = self::$uploadBasePath . $platform . '/';
        if (!file_exists($platformPath)) {
            mkdir($platformPath, 0755, true);
        }
        
        // Dosya uzantisini al
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $extension = 'jpg';
        }
        
        // Benzersiz dosya adi olustur
        $fileName = 'product_' . ($productSku ? $productSku . '_' : '') . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $platformPath . $fileName;
        
        try {
            // Resmi indir
            $ch = curl_init($imageUrl);
            $fp = fopen($filePath, 'wb');
            
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            curl_close($ch);
            fclose($fp);
            
            if ($success && $httpCode == 200 && file_exists($filePath) && filesize($filePath) > 0) {
                $imageInfo = @getimagesize($filePath);
                if ($imageInfo !== false) {
                    return $platform . '/' . $fileName;
                } else {
                    unlink($filePath);
                    error_log("ImageHelper: Geçersiz resim formati: " . $imageUrl);
                    return false;
                }
            } else {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                error_log("ImageHelper: Resim indirilemedi: " . $imageUrl . " | HTTP: " . $httpCode);
                return false;
            }
        } catch (Exception $e) {
            error_log("ImageHelper: Download error: " . $e->getMessage());
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return false;
        }
    }
    
    /**
     * Ürün resimlerini kaydet
     */
    public static function saveProductImages($db, $productId, $images, $platform = 'local', $productSku = '') {
        $saved = 0;
        $sortOrder = 0;
        
        foreach ($images as $imageUrl) {
            if (empty($imageUrl)) {
                error_log("ImageHelper: Bos resim URL'si atlandi");
                continue;
            }
            
            // Resmi indir
            $imagePath = self::downloadImage($imageUrl, $platform, $productSku);
            
            if ($imagePath) {
                try {
                    // SQL hazirla ve çalistir
                    $stmt = $db->prepare("
                        INSERT INTO product_images (product_id, image_url, image_path, platform, sort_order, is_main)
                        VALUES (:product_id, :image_url, :image_path, :platform, :sort_order, :is_main)
                    ");
                    
                    $isMain = ($sortOrder == 0) ? 1 : 0;
                    
                    $result = $stmt->execute([
                        ':product_id' => (int)$productId,
                        ':image_url' => $imageUrl,
                        ':image_path' => $imagePath,
                        ':platform' => $platform,
                        ':sort_order' => (int)$sortOrder,
                        ':is_main' => $isMain
                    ]);
                    
                    if ($result) {
                        $saved++;
                        $sortOrder++;
                        error_log("ImageHelper: Resim kaydedildi - Product ID: {$productId}, Path: {$imagePath}, Main: {$isMain}");
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        error_log("ImageHelper: SQL execute failed - " . print_r($errorInfo, true));
                    }
                    
                } catch (PDOException $e) {
                    error_log("ImageHelper: PDO Exception - " . $e->getMessage());
                } catch (Exception $e) {
                    error_log("ImageHelper: Exception - " . $e->getMessage());
                }
            } else {
                error_log("ImageHelper: Resim indirilemedi - URL: {$imageUrl}");
            }
        }
        
        error_log("ImageHelper: Toplam {$saved} resim kaydedildi");
        return $saved;
    }
    
    /**
     * Resim URL'ini döndür
     */
    public static function getImageUrl($imagePath) {
        if (empty($imagePath)) {
            return '';
        }
        
        if (strpos($imagePath, 'http') === 0) {
            return $imagePath;
        }
        
        return '/uploads/' . $imagePath;
    }
    
    /**
     * Ürünün ana resmini getir
     */
    public static function getMainImage($db, $productId) {
        try {
            $stmt = $db->prepare("
                SELECT image_path FROM product_images 
                WHERE product_id = :product_id AND is_main = 1
                ORDER BY sort_order
                LIMIT 1
            ");
            $stmt->execute([':product_id' => $productId]);
            $result = $stmt->fetch();
            
            return $result ? self::getImageUrl($result['image_path']) : '';
        } catch (Exception $e) {
            error_log("ImageHelper: getMainImage error - " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Ürünün tüm resimlerini getir
     */
    public static function getProductImages($db, $productId) {
        try {
            $stmt = $db->prepare("
                SELECT id, image_path, platform, is_main, sort_order
                FROM product_images 
                WHERE product_id = :product_id
                ORDER BY sort_order
            ");
            $stmt->execute([':product_id' => $productId]);
            $images = $stmt->fetchAll();
            
            foreach ($images as &$image) {
                $image['url'] = self::getImageUrl($image['image_path']);
            }
            
            return $images;
        } catch (Exception $e) {
            error_log("ImageHelper: getProductImages error - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ürün resmini sil
     */
    public static function deleteProductImage($db, $imageId) {
        try {
            $stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = :id");
            $stmt->execute([':id' => $imageId]);
            $image = $stmt->fetch();
            
            if ($image && !empty($image['image_path'])) {
                $filePath = self::$uploadBasePath . $image['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                $stmt = $db->prepare("DELETE FROM product_images WHERE id = :id");
                $stmt->execute([':id' => $imageId]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("ImageHelper: deleteProductImage error - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ürünün tüm resimlerini sil
     */
    public static function deleteAllProductImages($db, $productId) {
        try {
            $images = self::getProductImages($db, $productId);
            
            foreach ($images as $image) {
                $filePath = self::$uploadBasePath . $image['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $productId]);
            
            return true;
        } catch (Exception $e) {
            error_log("ImageHelper: deleteAllProductImages error - " . $e->getMessage());
            return false;
        }
    }
}