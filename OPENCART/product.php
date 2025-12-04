<?php
class ControllerApiProduct extends Controller {
    
    public function index() {
        $this->load->language('api/product');
        
        $json = array();

        $this->load->model('catalog/product');
        
        // Parametreleri al
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 100;
        $start = isset($this->request->get['start']) ? (int)$this->request->get['start'] : 0;
        
        $filter_data = array(
            'start' => $start,
            'limit' => $limit
        );
 // Ürün resimleri
            $this->load->model('catalog/product');
            $images = $this->model_catalog_product->getProductImages($product['product_id']);
            
            $product_images = array();
            if ($product['image']) {
                $product_images[] = HTTP_SERVER . 'image/' . $product['image'];
            }
            
            foreach ($images as $image) {
                $product_images[] = HTTP_SERVER . 'image/' . $image['image'];
            }
            
            $product_data = array(
                'product_id' => $product['product_id'],
                'name'       => $product['name'],
                'model'      => $product['model'],
                'sku'        => $product['sku'],
                'quantity'   => $product['quantity'],
                'price'      => $product['price'],
                'image'      => $product['image'] ? HTTP_SERVER . 'image/' . $product['image'] : '',
                'images'     => $product_images, // TÜM RESİMLER
                'ean'        => isset($product['ean']) ? $product['ean'] : '',
                'description' => strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')),
                'variants'    => array()
            );
        // Ürünleri getir
        $products = $this->model_catalog_product->getProducts($filter_data);
        
        $json['success'] = true;
        $json['products'] = array();
        
        foreach ($products as $product) {
            // Ürün bilgileri
            $product_data = array(
                'product_id' => $product['product_id'],
                'name'       => $product['name'],
                'model'      => $product['model'],
                'sku'        => $product['sku'],
                'quantity'   => $product['quantity'],
                'price'      => $product['price'],
                'image'      => $product['image'] ? HTTP_SERVER . 'image/' . $product['image'] : '',
                'ean'        => isset($product['ean']) ? $product['ean'] : '',
                'description' => strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')),
                'variants'    => array()
            );
            
            // Varyantları getir (OpenCart'ta ürün seçenekleri olarak)
            $this->load->model('catalog/product');
            $options = $this->model_catalog_product->getProductOptions($product['product_id']);
            
            if (!empty($options)) {
                foreach ($options as $option) {
                    if ($option['type'] == 'select' || $option['type'] == 'radio') {
                        foreach ($option['product_option_value'] as $option_value) {
                            $product_data['variants'][] = array(
                                'variant_name'  => $option['name'],
                                'variant_value' => $option_value['name'],
                                'price_prefix'  => $option_value['price_prefix'],
                                'price'         => $option_value['price'],
                                'quantity'      => $option_value['quantity'],
                                'sku'           => isset($option_value['sku']) ? $option_value['sku'] : '',
                                'image'         => $option_value['image'] ? HTTP_SERVER . 'image/' . $option_value['image'] : ''
                            );
                        }
                    }
                }
            }
            
            $json['products'][] = $product_data;
        }
        
        $json['total'] = $this->model_catalog_product->getTotalProducts();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}