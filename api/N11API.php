<?php
class N11API {
    private $apiKey;
    private $apiSecret;
    private $baseUrl = 'https://api.n11.com/ws/';

    public function __construct($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function makeRequest($service, $method, $data = []) {
        $url = $this->baseUrl . $service;
        
        $auth = [
            'appKey' => $this->apiKey,
            'appSecret' => $this->apiSecret
        ];
        
        $requestData = array_merge(['auth' => $auth], $data);
        
        $xml = $this->arrayToXml($method, $requestData);
        
        $ch = curl_init();
        
        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen($xml)
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("CURL Hatası: " . $error);
        }
        
        curl_close($ch);

        return $this->xmlToArray($response);
    }

    private function arrayToXml($methodName, $data) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.n11.com/ws/schemas">';
        $xml .= '<SOAP-ENV:Body>';
        $xml .= '<ns1:' . $methodName . '>';
        $xml .= $this->dataToXml($data);
        $xml .= '</ns1:' . $methodName . '>';
        $xml .= '</SOAP-ENV:Body>';
        $xml .= '</SOAP-ENV:Envelope>';
        return $xml;
    }

    private function dataToXml($data) {
        $xml = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= '<' . $key . '>' . $this->dataToXml($value) . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '>' . htmlspecialchars($value) . '</' . $key . '>';
            }
        }
        return $xml;
    }

    private function xmlToArray($xml) {
        try {
            $obj = simplexml_load_string($xml);
            $json = json_encode($obj);
            return json_decode($json, true);
        } catch (Exception $e) {
            throw new Exception("XML Parse Hatası: " . $e->getMessage());
        }
    }

    // Ürünleri getir
    public function getProducts($page = 0, $pageSize = 100) {
        $data = [
            'pagingData' => [
                'currentPage' => $page,
                'pageSize' => $pageSize
            ]
        ];
        return $this->makeRequest('ProductService.wsdl', 'GetProductList', $data);
    }

    // Ürün detayı
    public function getProduct($productId) {
        $data = [
            'productId' => $productId
        ];
        return $this->makeRequest('ProductService.wsdl', 'GetProductByProductId', $data);
    }

    // Ürün oluştur
    public function saveProduct($productData) {
        $data = [
            'product' => $productData
        ];
        return $this->makeRequest('ProductService.wsdl', 'SaveProduct', $data);
    }

    // Ürün güncelle
    public function updateProduct($productData) {
        $data = [
            'product' => $productData
        ];
        return $this->makeRequest('ProductService.wsdl', 'UpdateProductBasic', $data);
    }

    // Stok güncelle
    public function updateStock($productId, $quantity) {
        $data = [
            'productSellerCode' => $productId,
            'quantity' => $quantity
        ];
        return $this->makeRequest('ProductService.wsdl', 'UpdateStockByStockSellerCode', $data);

    }

    // Siparişleri getir
    public function getOrders($page = 0, $pageSize = 100, $startDate = null, $endDate = null) {
        $data = [
            'pagingData' => [
                'currentPage' => $page,
                'pageSize' => $pageSize
            ]
        ];
        
        if ($startDate) {
            $data['searchData'] = [
                'productId' => '',
                'status' => '',
                'buyerName' => '',
                'orderNumber' => '',
                'productSellerCode' => '',
                'recipient' => '',
                'sameDayDelivery' => 0,
                'period' => [
                    'startDate' => $startDate,
                    'endDate' => $endDate
                ]
            ];
        }
        
        return $this->makeRequest('OrderService.wsdl', 'OrderList', $data);
    }

    // Sipariş detayı
    public function getOrderDetail($orderId) {
        $data = [
            'orderRequest' => [
                'id' => $orderId
            ]
        ];
        return $this->makeRequest('OrderService.wsdl', 'OrderDetail', $data);
    }

    // Kategorileri getir
    public function getCategories($parentId = 0) {
        $data = [
            'categoryId' => $parentId
        ];
        return $this->makeRequest('CategoryService.wsdl', 'GetSubCategories', $data);
    }

    // Kategori özellikleri
    public function getCategoryAttributes($categoryId) {
        $data = [
            'categoryId' => $categoryId
        ];
        return $this->makeRequest('CategoryService.wsdl', 'GetCategoryAttributes', $data);
    }
}