<?php
class OpencartAPI {
    private $storeUrl;
    private $apiToken;
    private $apiUsername;
    private $apiKey;
    private $isActive = false;

    public function __construct() {
        global $db;
        
        // Ayarları veritabanından çek
        try {
            $stmt = $db->query("SELECT * FROM opencart_settings WHERE id = 1");
            $settings = $stmt->fetch();
            
            if ($settings && $settings['is_active']) {
                $this->storeUrl = rtrim($settings['store_url'], '/');
                $this->apiToken = $settings['api_token'];
                $this->apiUsername = $settings['api_username'];
                $this->apiKey = $settings['api_key'];
                $this->isActive = true;
            }
        } catch (PDOException $e) {
            error_log("OpenCart ayarları yüklenemedi: " . $e->getMessage());
        }
    }

    public function isActive() {
        return $this->isActive;
    }

    /**
     * OpenCart API isteği yap
     */
    private $sessionToken = null;

    /**
     * API session token al
     */
    private function getSessionToken() {
        if ($this->sessionToken) {
            return $this->sessionToken;
        }

        $url = $this->storeUrl . '/index.php?route=api/login';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'username' => $this->apiUsername,
            'key' => $this->apiKey
        ]));
        curl_setopt($ch, CURLOPT_HEADER, true); // Header'ı da al

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        curl_close($ch);

        // Cookie'den token al
        preg_match('/OCSESSID=([^;]+)/', $header, $matches);
        if (isset($matches[1])) {
            $this->sessionToken = $matches[1];
            return $this->sessionToken;
        }

        // JSON'dan token al
        $result = json_decode($body, true);
        
        if (isset($result['success'])) {
            // Token yerine session kullanıyoruz
            $this->sessionToken = 'session_active';
            return $this->sessionToken;
        }

        throw new Exception("API token alınamadı: " . ($result['error'] ?? json_encode($result)));
    }

    /**
     * OpenCart API isteği yap
     */
private function makeRequest($endpoint, $method = 'GET', $data = []) {
        if (!$this->isActive) {
            throw new Exception("OpenCart entegrasyonu aktif değil");
        }

        $url = $this->storeUrl . '/index.php?route=api/' . $endpoint;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Her istekte login bilgilerini gönder
        if ($method == 'POST') {
            $data['username'] = $this->apiUsername;
            $data['key'] = $this->apiKey;
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            $url .= (strpos($url, '?') !== false ? '&' : '?');
            $url .= 'username=' . urlencode($this->apiUsername);
            $url .= '&key=' . urlencode($this->apiKey);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("CURL Hatası: " . $error);
        }

        curl_close($ch);

        $result = json_decode($response, true);
        
        // JSON decode kontrolü
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON hatası: " . json_last_error_msg() . " | Response: " . substr($response, 0, 500));
        }

        if ($httpCode >= 400) {
            throw new Exception("OpenCart API Hatası ({$httpCode}): " . ($result['error'] ?? 'Bilinmeyen hata'));
        }

        return $result;
    }
/**
     * Ürünleri getir
     */
    public function getProducts($limit = 100, $page = 1) {
        try {
            // Custom endpoint'imizi çağır
            $result = $this->makeRequest('product&limit=' . $limit . '&start=' . (($page - 1) * $limit));
            
            // Debug - ne geldi?
            error_log("OpenCart Response: " . print_r($result, true));
            
            // Yanıtı kontrol et
            if (!$result) {
                return ['products' => [], 'error' => 'Boş yanıt geldi', 'raw' => null];
            }
            
            if (!isset($result['products'])) {
                return ['products' => [], 'error' => 'products key yok', 'raw' => $result];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("OpenCart getProducts Error: " . $e->getMessage());
            return ['products' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Tek ürün getir
     */
    public function getProduct($productId) {
        return $this->makeRequest("product/product&product_id={$productId}");
    }

    /**
     * Ürün stok güncelle
     */
    public function updateStock($productId, $quantityChange) {
        // OpenCart'ta direkt stok güncellemesi için custom API endpoint'i gerekebilir
        // Burada örnek bir yapı gösterilmiştir
        
        try {
            // Önce mevcut stoğu al
            $product = $this->getProduct($productId);
            $currentStock = $product['quantity'] ?? 0;
            
            // Yeni stok miktarını hesapla
            $newStock = max(0, $currentStock + $quantityChange);
            
            // Stoğu güncelle
            return $this->makeRequest("product/product", 'PUT', [
                'product_id' => $productId,
                'quantity' => $newStock
            ]);
        } catch (Exception $e) {
            throw new Exception("Stok güncelleme hatası: " . $e->getMessage());
        }
    }

    /**
     * Ürün ekle
     */
    public function addProduct($productData) {
        $data = [
            'model' => $productData['sku'],
            'name' => [$productData['name']],
            'description' => [$productData['description'] ?? ''],
            'price' => $productData['price'],
            'quantity' => $productData['stock'],
            'sku' => $productData['sku'],
            'status' => 1,
            'sort_order' => 0
        ];

        if (!empty($productData['barcode'])) {
            $data['ean'] = $productData['barcode'];
        }

        return $this->makeRequest('product/product', 'POST', $data);
    }

    /**
     * Ürün güncelle
     */
    public function updateProduct($productId, $productData) {
        $data = [
            'product_id' => $productId
        ];

        if (isset($productData['name'])) {
            $data['name'] = [$productData['name']];
        }

        if (isset($productData['description'])) {
            $data['description'] = [$productData['description']];
        }

        if (isset($productData['price'])) {
            $data['price'] = $productData['price'];
        }

        if (isset($productData['stock'])) {
            $data['quantity'] = $productData['stock'];
        }

        return $this->makeRequest('product/product', 'PUT', $data);
    }

   /**
     * Kategorileri getir
     */
    public function getCategories() {
        try {
            $result = $this->makeRequest('category');
            
            if (!$result) {
                return ['categories' => [], 'error' => 'Boş yanıt'];
            }
            
            return $result;
        } catch (Exception $e) {
            return ['categories' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Siparişleri getir
     */
    public function getOrders($limit = 100, $page = 1) {
        $start = ($page - 1) * $limit;
        return $this->makeRequest("sale/order&limit={$limit}&start={$start}");
    }

    /**
     * Sipariş detayı
     */
    public function getOrder($orderId) {
        return $this->makeRequest("sale/order&order_id={$orderId}");
    }

    /**
     * Sipariş durumu güncelle
     */
    public function updateOrderStatus($orderId, $status) {
        return $this->makeRequest('sale/order', 'PUT', [
            'order_id' => $orderId,
            'order_status_id' => $status
        ]);
    }

   /**
     * API bağlantısını test et
     */
    public function testConnection() {
        if (!$this->isActive) {
            return [
                'success' => false,
                'message' => 'OpenCart entegrasyonu aktif değil'
            ];
        }
        
        if (empty($this->storeUrl)) {
            return [
                'success' => false,
                'message' => 'OpenCart Store URL belirtilmemiş'
            ];
        }
        
        // Basit bağlantı testi - sadece URL'in erişilebilir olduğunu kontrol et
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->storeUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200 || $httpCode == 301 || $httpCode == 302) {
                return [
                    'success' => true,
                    'message' => 'OpenCart sitesine erişim başarılı! (HTTP ' . $httpCode . ')'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'OpenCart sitesine erişilemiyor (HTTP ' . $httpCode . ')'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Bağlantı hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Toplu stok senkronizasyonu
     */
    public function syncStockFromPanel($products) {
        $syncedCount = 0;
        $errors = [];

        foreach ($products as $product) {
            try {
                if ($product['opencart_id']) {
                    $this->updateProduct($product['opencart_id'], [
                        'stock' => $product['stock'],
                        'price' => $product['price']
                    ]);
                    $syncedCount++;
                }
            } catch (Exception $e) {
                $errors[] = "SKU {$product['sku']}: " . $e->getMessage();
            }
        }

        return [
            'synced' => $syncedCount,
            'errors' => $errors
        ];
    }

    /**
     * OpenCart'tan ürünleri panele çek
     */
    public function syncProductsToPanel($db) {
        try {
            $products = $this->getProducts(1000, 1);
            $syncedCount = 0;

            foreach ($products['products'] ?? [] as $ocProduct) {
                // Ürün var mı kontrol et
                $stmt = $db->prepare("SELECT id FROM products WHERE sku = :sku");
                $stmt->execute([':sku' => $ocProduct['model']]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Güncelle
                    $stmt = $db->prepare("
                        UPDATE products 
                        SET opencart_id = :opencart_id, 
                            name = :name, 
                            price = :price, 
                            stock = :stock,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':opencart_id' => $ocProduct['product_id'],
                        ':name' => $ocProduct['name'],
                        ':price' => $ocProduct['price'],
                        ':stock' => $ocProduct['quantity'],
                        ':id' => $existing['id']
                    ]);
                } else {
                    // Yeni ekle
                    $stmt = $db->prepare("
                        INSERT INTO products (
                            opencart_id, sku, name, description, price, stock, barcode
                        ) VALUES (
                            :opencart_id, :sku, :name, :description, :price, :stock, :barcode
                        )
                    ");
                    $stmt->execute([
                        ':opencart_id' => $ocProduct['product_id'],
                        ':sku' => $ocProduct['model'],
                        ':name' => $ocProduct['name'],
                        ':description' => $ocProduct['description'] ?? '',
                        ':price' => $ocProduct['price'],
                        ':stock' => $ocProduct['quantity'],
                        ':barcode' => $ocProduct['ean'] ?? ''
                    ]);
                }

                $syncedCount++;
            }

            return [
                'success' => true,
                'message' => "{$syncedCount} ürün senkronize edildi"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
}