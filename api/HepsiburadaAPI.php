<?php
class HepsiburadaAPI {
    private $username;
    private $password;
    private $merchantId;
    private $baseUrl = 'https://mpop.hepsiburada.com/';

    public function __construct($username, $password, $merchantId) {
        $this->username = $username;
        $this->password = $password;
        $this->merchantId = $merchantId;
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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
        
        if ($httpCode >= 400) {
            throw new Exception("API Hatası ({$httpCode}): " . ($result['message'] ?? 'Bilinmeyen hata'));
        }

        return $result;
    }

    // Ürünleri getir
    public function getProducts($offset = 0, $limit = 100) {
        return $this->makeRequest("product/api/products/{$this->merchantId}?offset={$offset}&limit={$limit}");
    }

    // Ürün detayı
    public function getProduct($sku) {
        return $this->makeRequest("product/api/products/{$this->merchantId}/{$sku}");
    }

    // Ürün oluştur
    public function createProduct($productData) {
        return $this->makeRequest("product/api/products/{$this->merchantId}", 'POST', $productData);
    }

    // Ürün güncelle
    public function updateProduct($sku, $productData) {
        return $this->makeRequest("product/api/products/{$this->merchantId}/{$sku}", 'PUT', $productData);
    }

    // Stok ve fiyat güncelle
    public function updateInventory($listings) {
        $data = ['listings' => $listings];
        return $this->makeRequest("product/api/inventories/{$this->merchantId}", 'POST', $data);
    }

    // Siparişleri getir
    public function getOrders($beginDate, $endDate, $status = null) {
        $endpoint = "order/api/orders/{$this->merchantId}?beginDate={$beginDate}&endDate={$endDate}";
        if ($status) {
            $endpoint .= "&status={$status}";
        }
        return $this->makeRequest($endpoint);
    }

    // Sipariş detayı
    public function getOrderDetail($orderNumber) {
        return $this->makeRequest("order/api/orders/{$this->merchantId}/{$orderNumber}");
    }

    // Sipariş kabul et
    public function acceptOrder($orderNumber) {
        return $this->makeRequest("order/api/orders/{$this->merchantId}/{$orderNumber}/acknowledge", 'POST');
    }

    // Kargo bilgisi gönder
    public function createShipment($shipmentData) {
        return $this->makeRequest("order/api/shipments/{$this->merchantId}", 'POST', $shipmentData);
    }

    // Kategorileri getir
    public function getCategories() {
        return $this->makeRequest("product/api/categories/merchantid/{$this->merchantId}");
    }

    // Kategori özellikleri
    public function getCategoryAttributes($categoryId) {
        return $this->makeRequest("product/api/categories/{$this->merchantId}/{$categoryId}/attributes");
    }
}