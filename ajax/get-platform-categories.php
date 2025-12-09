<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

header('Content-Type: application/json');

$platform = $_GET['platform'] ?? '';

if (empty($platform)) {
    echo json_encode(['success' => false, 'message' => 'Platform belirtilmedi']);
    exit;
}

try {
    $categories = [];
    
    if ($platform == 'opencart') {
        // OpenCart kategorilerini mapping'den çek
        $stmt = $db->query("
            SELECT 
                c.id,
                c.name,
                c.parent_id,
                cm.platform_category_id as platform_id,
                0 as level
            FROM categories c
            LEFT JOIN category_mappings cm ON c.id = cm.category_id AND cm.platform = 'opencart'
            WHERE cm.platform_category_id IS NOT NULL
            ORDER BY c.parent_id, c.sort_order, c.name
        ");
        $categories = $stmt->fetchAll();
        
        // Level hesapla (hiyerarşi için)
        foreach ($categories as &$cat) {
            $level = 0;
            $parentId = $cat['parent_id'];
            while ($parentId) {
                $level++;
                $parentStmt = $db->prepare("SELECT parent_id FROM categories WHERE id = :id");
                $parentStmt->execute([':id' => $parentId]);
                $parent = $parentStmt->fetch();
                $parentId = $parent ? $parent['parent_id'] : null;
            }
            $cat['level'] = $level;
        }
    } 
    else {
        // Diğer platformlar için (henüz eklenmedi)
        // Gelecekte Trendyol/Hepsiburada/N11 API'lerinden çekilecek
        echo json_encode([
            'success' => false, 
            'message' => ucfirst($platform) . ' kategorileri henüz desteklenmiyor. Yakında eklenecek.'
        ]);
        exit;
    }
    
    echo json_encode(['success' => true, 'categories' => $categories]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}