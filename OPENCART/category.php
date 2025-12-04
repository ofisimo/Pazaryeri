<?php
class ControllerApiCategory extends Controller {
    
    public function index() {
        $json = array();
        
        // Direkt veritabanından tüm kategorileri çek
        $query = $this->db->query("
            SELECT 
                cd.category_id,
                cd.name,
                c.parent_id,
                c.sort_order,
                c.status,
                c.image
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd 
                ON (c.category_id = cd.category_id)
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY c.parent_id, c.sort_order, cd.name
        ");
        
        $json['success'] = true;
        $json['categories'] = array();
        
        foreach ($query->rows as $row) {
            $json['categories'][] = array(
                'category_id' => $row['category_id'],
                'name'        => $row['name'],
                'parent_id'   => $row['parent_id'],
                'sort_order'  => $row['sort_order'],
                'status'      => $row['status'],
                'image'       => !empty($row['image']) ? HTTP_SERVER . 'image/' . $row['image'] : ''
            );
        }
        
        $json['total'] = count($json['categories']);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}