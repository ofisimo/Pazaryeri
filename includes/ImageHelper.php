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
        
        // Platform klasörünü oluştur
        $platformPath = self::$uploadBasePath . $platform . '/';
        if (!file_exists($platformPath)) {
            mkdir($platformPath, 0755, true);
        }
        
        // Dosya uzantısını al
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg';
        }
        
        // Benzersiz dosya adı oluştur
        $fileName = ($productSku ? $productSku . '_' : '') . uniqid() . '.' . $extension;
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
            
            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            fclose($fp);
            
            // İndirme başarılı mı kontrol et
            if ($success && $httpCode == 200 && file_exists($filePath) && filesize($filePath) > 0) {
                // Relatif path döndür
                return $platform . '/' . $fileName;
            } else {
                // Başarısız, dosyayı sil
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return false;
            }
        } catch (Exception $e) {
            error_log("Image download error: " . $e->getMessage());
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return false;
        }
    }
    
    /**
     * Ürün resimlerini kaydet
     */
    public static function saveProductImages($db, $productId, $images, $platform = 'local') {
        $saved = 0;
        $sortOrder = 0;
        
        foreach ($images as $imageUrl) {
            if (empty($imageUrl)) continue;
            
            // Resmi indir
            $imagePath = self::downloadImage($imageUrl, $platform, '');
            
            if ($imagePath) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO product_images (product_id, image_url, image_path, platform, sort_order, is_main)
                        VALUES (:product_id, :image_url, :image_path, :platform, :sort_order, :is_main)
                    ");
                    $stmt->execute([
                        ':product_id' => $productId,
                        ':image_url' => $imageUrl,
                        ':image_path' => $imagePath,
                        ':platform' => $platform,
                        ':sort_order' => $sortOrder,
                        ':is_main' => ($sortOrder == 0) ? 1 : 0 // İlk resim ana resim
                    ]);
                    $saved++;
                    $sortOrder++;
                } catch (Exception $e) {
                    error_log("Database save error: " . $e->getMessage());
                }
            }
        }
        
        return $saved;
    }
    
    /**
     * Resim URL'ini döndür
     */
    public static function getImageUrl($imagePath) {
        if (empty($imagePath)) {
            return '';
        }
        
        // Eğer tam URL ise olduğu gibi döndür
        if (strpos($imagePath, 'http') === 0) {
            return $imagePath;
        }
        
        // Relatif path ise tam URL'e çevir
        return '/uploads/' . $imagePath;
    }
    
    /**
     * Ürünün ana resmini getir
     */
    public static function getMainImage($db, $productId) {
        $stmt = $db->prepare("
            SELECT image_path FROM product_images 
            WHERE product_id = :product_id AND is_main = 1
            ORDER BY sort_order
            LIMIT 1
        ");
        $stmt->execute([':product_id' => $productId]);
        $result = $stmt->fetch();
        
        return $result ? self::getImageUrl($result['image_path']) : '';
    }
    
    /**
     * Ürünün tüm resimlerini getir
     */
    public static function getProductImages($db, $productId) {
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
    }
}