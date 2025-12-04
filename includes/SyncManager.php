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
}
?>