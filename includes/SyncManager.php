<?php
require_once __DIR__ . '/../api/TrendyolAPI.php';
require_once __DIR__ . '/../api/HepsiburadaAPI.php';
require_once __DIR__ . '/../api/N11API.php';
require_once __DIR__ . '/../api/OpencartAPI.php';

class SyncManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Pazaryeri siparişlerini senkronize et
     */
    public function syncOrders($marketplace) {
        try {
            // Pazaryeri ayarlarını al
            $settings = $this->getMarketplaceSettings($marketplace);
            if (!$settings || !$settings['is_active']) {
                return ['success' => false, 'message' => 'Pazaryeri aktif değil'];
            }
            
            $orders = [];
            $api = $this->getMarketplaceAPI($marketplace, $settings);
            
            // Siparişleri çek
            if ($marketplace == 'trendyol') {
                $response = $api->getOrders(0, 200);
                $orders = $response['content'] ?? [];
            } 
            elseif ($marketplace == 'hepsiburada') {
                $endDate = date('Y-m-d\TH:i:s');
                $beginDate = date('Y-m-d\TH:i:s', strtotime('-30 days'));
                $response = $api->getOrders($beginDate, $endDate);
                $orders = $response['items'] ?? [];
            }
            elseif ($marketplace == 'n11') {
                $endDate = date('d/m/Y');
                $startDate = date('d/m/Y', strtotime('-30 days'));
                $response = $api->getOrders(0, 200, $startDate, $endDate);
                $orders = $response['orderList'] ?? [];
            }
            
            $syncedCount = 0;
            $updatedCount = 0;
            
            foreach ($orders as $orderData) {
                $result = $this->processOrder($marketplace, $orderData);
                if ($result['action'] == 'inserted') {
                    $syncedCount++;
                } elseif ($result['action'] == 'updated') {
                    $updatedCount++;
                }
            }
            
            // Log kaydet
            $this->logSync($marketplace, 'order_sync', 'success', 
                "Toplam {$syncedCount} yeni sipariş, {$updatedCount} güncelleme yapıldı");
            
            return [
                'success' => true, 
                'message' => "{$syncedCount} yeni sipariş alındı, {$updatedCount} sipariş güncellendi"
            ];
            
        } catch (Exception $e) {
            $this->logSync($marketplace, 'order_sync', 'error', $e->getMessage());
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Sipariş işle ve veritabanına kaydet
     */
    private function processOrder($marketplace, $orderData) {
        // Siparişi normalize et
        $order = $this->normalizeOrder($marketplace, $orderData);
        
        // Sipariş var mı kontrol et
        $stmt = $this->db->prepare("SELECT id, status FROM orders WHERE order_number = :order_number");
        $stmt->execute([':order_number' => $order['order_number']]);
        $existingOrder = $stmt->fetch();
        
        if ($existingOrder) {
            // Durum değişmişse güncelle
            if ($existingOrder['status'] != $order['status']) {
                $stmt = $this->db->prepare("
                    UPDATE orders 
                    SET status = :status, updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':status' => $order['status'],
                    ':id' => $existingOrder['id']
                ]);
                return ['action' => 'updated', 'order_id' => $existingOrder['id']];
            }
            return ['action' => 'skipped', 'order_id' => $existingOrder['id']];
        }
        
        // Yeni sipariş ekle
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                marketplace, order_number, customer_name, customer_email, 
                customer_phone, shipping_address, total_amount, status, order_date
            ) VALUES (
                :marketplace, :order_number, :customer_name, :customer_email, 
                :customer_phone, :shipping_address, :total_amount, :status, :order_date
            )
        ");
        
        $stmt->execute([
            ':marketplace' => $marketplace,
            ':order_number' => $order['order_number'],
            ':customer_name' => $order['customer_name'],
            ':customer_email' => $order['customer_email'],
            ':customer_phone' => $order['customer_phone'],
            ':shipping_address' => $order['shipping_address'],
            ':total_amount' => $order['total_amount'],
            ':status' => $order['status'],
            ':order_date' => $order['order_date']
        ]);
        
        $orderId = $this->db->lastInsertId();
        
        // Sipariş ürünlerini ekle
        if (!empty($order['items'])) {
            $this->addOrderItems($orderId, $order['items']);
        }
        
        // Stok düşür
        $this->updateStockForOrder($order['items']);
        
        return ['action' => 'inserted', 'order_id' => $orderId];
    }
    
    /**
     * Sipariş verisini normalize et
     */
    private function normalizeOrder($marketplace, $data) {
        $order = [];
        
        if ($marketplace == 'trendyol') {
            $order['order_number'] = $data['orderNumber'] ?? '';
            $order['customer_name'] = $data['customerFirstName'] . ' ' . $data['customerLastName'];
            $order['customer_email'] = $data['customerEmail'] ?? '';
            $order['customer_phone'] = $data['shipmentAddress']['phone'] ?? '';
            $order['shipping_address'] = ($data['shipmentAddress']['address'] ?? '') . ' ' . 
                                        ($data['shipmentAddress']['district'] ?? '') . ' ' .
                                        ($data['shipmentAddress']['city'] ?? '');
            $order['total_amount'] = $data['grossAmount'] ?? 0;
            $order['status'] = $data['status'] ?? 'Unknown';
            $order['order_date'] = date('Y-m-d H:i:s', $data['orderDate'] / 1000);
            
            $order['items'] = [];
            foreach ($data['lines'] ?? [] as $line) {
                $order['items'][] = [
                    'sku' => $line['merchantSku'] ?? '',
                    'barcode' => $line['barcode'] ?? '',
                    'product_name' => $line['productName'] ?? '',
                    'quantity' => $line['quantity'] ?? 1,
                    'price' => $line['price'] ?? 0,
                    'total' => $line['amount'] ?? 0
                ];
            }
        }
        
        elseif ($marketplace == 'hepsiburada') {
            $order['order_number'] = $data['orderNumber'] ?? '';
            $order['customer_name'] = $data['shippingAddress']['firstName'] . ' ' . $data['shippingAddress']['lastName'];
            $order['customer_email'] = $data['customer']['email'] ?? '';
            $order['customer_phone'] = $data['shippingAddress']['phoneNumber'] ?? '';
            $order['shipping_address'] = ($data['shippingAddress']['address'] ?? '') . ' ' . 
                                        ($data['shippingAddress']['district'] ?? '') . ' ' .
                                        ($data['shippingAddress']['city'] ?? '');
            $order['total_amount'] = $data['totalPrice'] ?? 0;
            $order['status'] = $data['status'] ?? 'Unknown';
            $order['order_date'] = date('Y-m-d H:i:s', strtotime($data['orderDate']));
            
            $order['items'] = [];
            foreach ($data['items'] ?? [] as $item) {
                $order['items'][] = [
                    'sku' => $item['merchantSku'] ?? '',
                    'barcode' => $item['hbSku'] ?? '',
                    'product_name' => $item['productName'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'total' => $item['totalPrice'] ?? 0
                ];
            }
        }
        
        elseif ($marketplace == 'n11') {
            $order['order_number'] = $data['id'] ?? '';
            $order['customer_name'] = $data['buyer']['fullName'] ?? '';
            $order['customer_email'] = $data['buyer']['email'] ?? '';
            $order['customer_phone'] = $data['buyer']['phoneNumber'] ?? '';
            $order['shipping_address'] = ($data['shippingAddress']['address'] ?? '') . ' ' . 
                                        ($data['shippingAddress']['district'] ?? '') . ' ' .
                                        ($data['shippingAddress']['city'] ?? '');
            $order['total_amount'] = $data['totalAmount'] ?? 0;
            $order['status'] = $data['status'] ?? 'Unknown';
            $order['order_date'] = date('Y-m-d H:i:s', strtotime($data['orderDate']));
            
            $order['items'] = [];
            foreach ($data['items'] ?? [] as $item) {
                $order['items'][] = [
                    'sku' => $item['productSellerCode'] ?? '',
                    'barcode' => $item['productId'] ?? '',
                    'product_name' => $item['productName'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'total' => $item['totalAmount'] ?? 0
                ];
            }
        }
        
        return $order;
    }
    
    /**
     * Sipariş ürünlerini ekle
     */
    private function addOrderItems($orderId, $items) {
        $stmt = $this->db->prepare("
            INSERT INTO order_items (
                order_id, product_id, product_name, sku, quantity, price, total
            ) VALUES (
                :order_id, :product_id, :product_name, :sku, :quantity, :price, :total
            )
        ");
        
        foreach ($items as $item) {
            // Ürünü SKU'ya göre bul
            $productStmt = $this->db->prepare("SELECT id FROM products WHERE sku = :sku OR barcode = :barcode");
            $productStmt->execute([':sku' => $item['sku'], ':barcode' => $item['barcode']]);
            $product = $productStmt->fetch();
            
            $stmt->execute([
                ':order_id' => $orderId,
                ':product_id' => $product ? $product['id'] : null,
                ':product_name' => $item['product_name'],
                ':sku' => $item['sku'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price'],
                ':total' => $item['total']
            ]);
        }
    }
    
    /**
     * Stok düşür (hem panel hem OpenCart)
     */
    private function updateStockForOrder($items) {
        foreach ($items as $item) {
            // Panel stok düşür
            $stmt = $this->db->prepare("
                UPDATE products 
                SET stock = stock - :quantity 
                WHERE (sku = :sku OR barcode = :barcode) AND stock >= :quantity
            ");
            $stmt->execute([
                ':quantity' => $item['quantity'],
                ':sku' => $item['sku'],
                ':barcode' => $item['barcode']
            ]);
            
            // OpenCart stok düşür
            try {
                $opencartAPI = new OpencartAPI();
                if ($opencartAPI->isActive()) {
                    $productStmt = $this->db->prepare("SELECT opencart_id FROM products WHERE sku = :sku OR barcode = :barcode");
                    $productStmt->execute([':sku' => $item['sku'], ':barcode' => $item['barcode']]);
                    $product = $productStmt->fetch();
                    
                    if ($product && $product['opencart_id']) {
                        $opencartAPI->updateStock($product['opencart_id'], -$item['quantity']);
                    }
                }
            } catch (Exception $e) {
                // OpenCart hatası loglanabilir ama sipariş işlemi devam etmeli
                error_log("OpenCart stok güncelleme hatası: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Ürünleri senkronize et
     */
    public function syncProducts($marketplace) {
        try {
            $settings = $this->getMarketplaceSettings($marketplace);
            if (!$settings || !$settings['is_active']) {
                return ['success' => false, 'message' => 'Pazaryeri aktif değil'];
            }
            
            $api = $this->getMarketplaceAPI($marketplace, $settings);
            
            // Yerel ürünleri pazaryerine gönder
            $stmt = $this->db->query("SELECT * FROM products WHERE is_active = 1");
            $products = $stmt->fetchAll();
            
            $syncedCount = 0;
            foreach ($products as $product) {
                try {
                    // Ürün pazaryerinde var mı kontrol et
                    $mpStmt = $this->db->prepare("
                        SELECT * FROM marketplace_products 
                        WHERE product_id = :product_id AND marketplace = :marketplace
                    ");
                    $mpStmt->execute([
                        ':product_id' => $product['id'],
                        ':marketplace' => $marketplace
                    ]);
                    $mpProduct = $mpStmt->fetch();
                    
                    if ($mpProduct) {
                        // Stok ve fiyat güncelle
                        $this->updateMarketplaceProduct($marketplace, $api, $product, $mpProduct);
                    } else {
                        // Yeni ürün ekle
                        $this->createMarketplaceProduct($marketplace, $api, $product);
                    }
                    
                    $syncedCount++;
                } catch (Exception $e) {
                    error_log("Ürün senkronizasyon hatası: " . $e->getMessage());
                }
            }
            
            $this->logSync($marketplace, 'product_sync', 'success', 
                "Toplam {$syncedCount} ürün senkronize edildi");
            
            return ['success' => true, 'message' => "{$syncedCount} ürün senkronize edildi"];
            
        } catch (Exception $e) {
            $this->logSync($marketplace, 'product_sync', 'error', $e->getMessage());
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Pazaryerinde ürün güncelle
     */
    private function updateMarketplaceProduct($marketplace, $api, $product, $mpProduct) {
        if ($marketplace == 'trendyol') {
            $api->updateStock([
                [
                    'barcode' => $product['barcode'],
                    'quantity' => $product['stock'],
                    'salePrice' => $product['price'],
                    'listPrice' => $product['price']
                ]
            ]);
        }
        // Diğer pazaryerleri için de benzer şekilde...
        
        // Son senkronizasyon zamanını güncelle
        $stmt = $this->db->prepare("
            UPDATE marketplace_products 
            SET last_sync = NOW() 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $mpProduct['id']]);
    }
    
    /**
     * Pazaryerine yeni ürün ekle
     */
    private function createMarketplaceProduct($marketplace, $api, $product) {
        // Bu kısım her pazaryerinin kendi ürün ekleme formatına göre düzenlenmelidir
        // Örnek olarak Trendyol için:
        if ($marketplace == 'trendyol') {
            $productData = [
                'barcode' => $product['barcode'],
                'title' => $product['name'],
                'productMainId' => $product['sku'],
                'brandId' => 1, // Brand ID ayarlardan alınmalı
                'categoryId' => $product['category_id'],
                'quantity' => $product['stock'],
                'salePrice' => $product['price'],
                'listPrice' => $product['price'],
                'description' => $product['description'],
                'images' => [
                    ['url' => $product['image_url']]
                ]
            ];
            
            $result = $api->createProduct($productData);
            
            // Eşleştirme kaydet
            $stmt = $this->db->prepare("
                INSERT INTO marketplace_products (
                    product_id, marketplace, marketplace_product_id, marketplace_sku, last_sync
                ) VALUES (
                    :product_id, :marketplace, :marketplace_product_id, :marketplace_sku, NOW()
                )
            ");
            $stmt->execute([
                ':product_id' => $product['id'],
                ':marketplace' => $marketplace,
                ':marketplace_product_id' => $result['batchRequestId'] ?? '',
                ':marketplace_sku' => $product['sku']
            ]);
        }
    }
    
    /**
     * Pazaryeri ayarlarını getir
     */
    private function getMarketplaceSettings($marketplace) {
        $stmt = $this->db->prepare("SELECT * FROM marketplace_settings WHERE marketplace = :marketplace");
        $stmt->execute([':marketplace' => $marketplace]);
        return $stmt->fetch();
    }
    
    /**
     * Pazaryeri API'sini başlat
     */
    private function getMarketplaceAPI($marketplace, $settings) {
        if ($marketplace == 'trendyol') {
            return new TrendyolAPI(
                $settings['api_key'],
                $settings['api_secret'],
                $settings['merchant_id']
            );
        } 
        elseif ($marketplace == 'hepsiburada') {
            return new HepsiburadaAPI(
                $settings['api_key'],
                $settings['api_secret'],
                $settings['merchant_id']
            );
        }
        elseif ($marketplace == 'n11') {
            return new N11API(
                $settings['api_key'],
                $settings['api_secret']
            );
        }
        
        throw new Exception("Geçersiz pazaryeri: {$marketplace}");
    }
    
    /**
     * İşlem logla
     */
    private function logSync($marketplace, $action, $status, $message) {
        $stmt = $this->db->prepare("
            INSERT INTO sync_logs (marketplace, action, status, message) 
            VALUES (:marketplace, :action, :status, :message)
        ");
        $stmt->execute([
            ':marketplace' => $marketplace,
            ':action' => $action,
            ':status' => $status,
            ':message' => $message
        ]);
    }
	/**
     * Pazaryerinden ürünleri çek (RESİMLERLE BİRLİKTE)
     */
    public function syncProductsFromMarketplace($marketplace) {
        try {
            $settings = $this->getMarketplaceSettings($marketplace);
            if (!$settings || !$settings['is_active']) {
                return ['success' => false, 'message' => "$marketplace entegrasyonu aktif değil"];
            }
            
            $api = $this->getMarketplaceAPI($marketplace, $settings);
            
            // Pazaryerinden ürünleri çek
            $products = [];
            if ($marketplace == 'trendyol') {
                $response = $api->getProducts(0, 200);
                $products = $response['content'] ?? [];
            } 
            elseif ($marketplace == 'hepsiburada') {
                $response = $api->getProducts(0, 200);
                $products = $response['listings'] ?? [];
            }
            elseif ($marketplace == 'n11') {
                $response = $api->getProducts(0, 200);
                $products = $response['products']['product'] ?? [];
            }
            
            $syncedCount = 0;
            $imageCount = 0;
            
            foreach ($products as $productData) {
                try {
                    // Ürünü normalize et
                    $normalizedProduct = $this->normalizeProductData($marketplace, $productData);
                    if (!$normalizedProduct) continue;
                    
                    // Ürünü kaydet
                    $productId = $this->saveProductData($normalizedProduct);
                    
                    // Pazaryeri eşleştirmesini kaydet
                    $this->saveMarketplaceMappingData($productId, $marketplace, $normalizedProduct);
                    
                    // Ana resmi indir
                    if (!empty($normalizedProduct['image_url'])) {
                        $downloaded = $this->downloadProductImage($productId, $normalizedProduct['image_url'], $marketplace);
                        if ($downloaded) $imageCount++;
                    }
                    
                    // Ek resimleri indir
                    if (!empty($normalizedProduct['additional_images'])) {
                        $this->downloadAdditionalImages($productId, $normalizedProduct['additional_images'], $marketplace);
                        $imageCount += count($normalizedProduct['additional_images']);
                    }
                    
                    $syncedCount++;
                } catch (Exception $e) {
                    error_log("Ürün senkronizasyon hatası: " . $e->getMessage());
                }
            }
            
            $this->logSync($marketplace, 'product_import', 'success', 
                "{$syncedCount} ürün, {$imageCount} resim içe aktarıldı");
            
            return [
                'success' => true, 
                'message' => "{$syncedCount} ürün ve {$imageCount} resim içe aktarıldı"
            ];
            
        } catch (Exception $e) {
            $this->logSync($marketplace, 'product_import', 'error', $e->getMessage());
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
    
    /**
     * Ürünü normalize et
     */
    private function normalizeProductData($marketplace, $data) {
        if ($marketplace == 'trendyol') {
            $images = [];
            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $img) {
                    if (!empty($img['url'])) $images[] = $img['url'];
                }
            }
            
            return [
                'marketplace_product_id' => $data['id'] ?? '',
                'marketplace_sku' => $data['productCode'] ?? '',
                'sku' => $data['stockCode'] ?? $data['barcode'] ?? '',
                'barcode' => $data['barcode'] ?? '',
                'name' => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'price' => $data['salePrice'] ?? 0,
                'stock' => $data['quantity'] ?? 0,
                'image_url' => $images[0] ?? '',
                'additional_images' => array_slice($images, 1)
            ];
        }
        elseif ($marketplace == 'hepsiburada') {
            $images = $data['images'] ?? [];
            
            return [
                'marketplace_product_id' => $data['hepsiburadaSku'] ?? '',
                'marketplace_sku' => $data['merchantSku'] ?? '',
                'sku' => $data['merchantSku'] ?? '',
                'barcode' => $data['barcode'] ?? '',
                'name' => $data['productName'] ?? '',
                'description' => $data['productDescription'] ?? '',
                'price' => $data['price'] ?? 0,
                'stock' => $data['availableStock'] ?? 0,
                'image_url' => $images[0] ?? '',
                'additional_images' => array_slice($images, 1)
            ];
        }
        elseif ($marketplace == 'n11') {
            $images = [];
            if (!empty($data['images']['image']) && is_array($data['images']['image'])) {
                foreach ($data['images']['image'] as $img) {
                    if (!empty($img['url'])) $images[] = $img['url'];
                }
            }
            
            return [
                'marketplace_product_id' => $data['id'] ?? '',
                'marketplace_sku' => $data['productSellerCode'] ?? '',
                'sku' => $data['productSellerCode'] ?? '',
                'barcode' => $data['barcode'] ?? '',
                'name' => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'price' => $data['salePrice'] ?? 0,
                'stock' => $data['stockItems']['stockItem']['quantity'] ?? 0,
                'image_url' => $images[0] ?? '',
                'additional_images' => array_slice($images, 1)
            ];
        }
        
        return null;
    }
    
    /**
     * Ürünü kaydet veya güncelle
     */
    private function saveProductData($productData) {
        // Ürün var mı kontrol et
        $stmt = $this->db->prepare("SELECT id FROM products WHERE sku = :sku OR barcode = :barcode");
        $stmt->execute([':sku' => $productData['sku'], ':barcode' => $productData['barcode']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Güncelle
            $stmt = $this->db->prepare("
                UPDATE products 
                SET name = :name, description = :description, price = :price, stock = :stock, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $existing['id'],
                ':name' => $productData['name'],
                ':description' => strip_tags($productData['description']),
                ':price' => $productData['price'],
                ':stock' => $productData['stock']
            ]);
            return $existing['id'];
        } else {
            // Yeni ekle
            $stmt = $this->db->prepare("
                INSERT INTO products (sku, barcode, name, description, price, stock, is_active)
                VALUES (:sku, :barcode, :name, :description, :price, :stock, 1)
            ");
            $stmt->execute([
                ':sku' => $productData['sku'],
                ':barcode' => $productData['barcode'],
                ':name' => $productData['name'],
                ':description' => strip_tags($productData['description']),
                ':price' => $productData['price'],
                ':stock' => $productData['stock']
            ]);
            return $this->db->lastInsertId();
        }
    }
    
    /**
     * Pazaryeri eşleştirmesini kaydet
     */
    private function saveMarketplaceMappingData($productId, $marketplace, $productData) {
        $stmt = $this->db->prepare("
            INSERT INTO marketplace_products (product_id, marketplace, marketplace_product_id, marketplace_sku, last_sync)
            VALUES (:product_id, :marketplace, :marketplace_product_id, :marketplace_sku, NOW())
            ON DUPLICATE KEY UPDATE 
                marketplace_product_id = :marketplace_product_id,
                marketplace_sku = :marketplace_sku,
                last_sync = NOW()
        ");
        $stmt->execute([
            ':product_id' => $productId,
            ':marketplace' => $marketplace,
            ':marketplace_product_id' => $productData['marketplace_product_id'],
            ':marketplace_sku' => $productData['marketplace_sku']
        ]);
    }
    
    /**
     * Ürün resmini indir ve kaydet
     */
    private function downloadProductImage($productId, $imageUrl, $marketplace) {
        if (empty($imageUrl)) {
            return null;
        }
        
        try {
            // Platform klasörünü oluştur
            $uploadDir = __DIR__ . '/../uploads/' . $marketplace . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Dosya adı oluştur
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension) || strlen($extension) > 4) {
                $extension = 'jpg';
            }
            $fileName = 'product_' . $productId . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            // Resmi indir
            $ch = curl_init($imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200 && $imageData && strlen($imageData) > 100) {
                file_put_contents($filePath, $imageData);
                
                $relativePath = 'uploads/' . $marketplace . '/' . $fileName;
                
                // Veritabanını güncelle
                $stmt = $this->db->prepare("
                    UPDATE products 
                    SET image_url = :image_url 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id' => $productId,
                    ':image_url' => $relativePath
                ]);
                
                return $relativePath;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Resim indirme hatası: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ürün için ek resimleri indir
     */
    private function downloadAdditionalImages($productId, $images, $marketplace) {
        if (empty($images) || !is_array($images)) {
            return;
        }
        
        $sortOrder = 1;
        foreach ($images as $imageUrl) {
            if (empty($imageUrl)) continue;
            
            try {
                $uploadDir = __DIR__ . '/../uploads/' . $marketplace . '/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (empty($extension) || strlen($extension) > 4) {
                    $extension = 'jpg';
                }
                $fileName = 'product_' . $productId . '_extra_' . $sortOrder . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                
                $ch = curl_init($imageUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode == 200 && $imageData && strlen($imageData) > 100) {
                    file_put_contents($filePath, $imageData);
                    
                    $relativePath = 'uploads/' . $marketplace . '/' . $fileName;
                    
                    $stmt = $this->db->prepare("
                        INSERT INTO product_images (product_id, image_url, image_path, platform, sort_order, is_main)
                        VALUES (:product_id, :image_url, :image_path, :platform, :sort_order, 0)
                    ");
                    $stmt->execute([
                        ':product_id' => $productId,
                        ':image_url' => $relativePath,
                        ':image_path' => $relativePath,
                        ':platform' => $marketplace,
                        ':sort_order' => $sortOrder
                    ]);
                    
                    $sortOrder++;
                }
            } catch (Exception $e) {
                error_log("Ek resim indirme hatası: " . $e->getMessage());
            }
        }
    }
}
?>