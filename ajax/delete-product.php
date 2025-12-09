<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

try {
    // JSON verisi mi yoksa form-data mı kontrol et
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // TOPLU SİLME - JSON formatında gelen veriler
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
            echo json_encode(['success' => false, 'message' => 'Silinecek ürün seçilmedi']);
            exit;
        }
        
        $ids = array_map('intval', $data['ids']);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $db->beginTransaction();
        
        // İlişkili verileri sil
        $stmt = $db->prepare("DELETE FROM product_categories WHERE product_id IN ($placeholders)");
        $stmt->execute($ids);
        
        $stmt = $db->prepare("DELETE FROM product_images WHERE product_id IN ($placeholders)");
        $stmt->execute($ids);
        
        $stmt = $db->prepare("DELETE FROM product_variants WHERE parent_product_id IN ($placeholders)");
        $stmt->execute($ids);
        
        $stmt = $db->prepare("DELETE FROM marketplace_products WHERE product_id IN ($placeholders)");
        $stmt->execute($ids);
        
        // Ürünleri sil
        $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        $deletedCount = $stmt->rowCount();
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "$deletedCount ürün başarıyla silindi"
        ]);
        
    } else {
        // TEK ÜRÜN SİLME - Form-data formatında
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz ürün ID']);
            exit;
        }
        
        $db->beginTransaction();
        
        // İlişkili verileri sil
        $stmt = $db->prepare("DELETE FROM product_categories WHERE product_id = :id");
        $stmt->execute([':id' => $product_id]);
        
        $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = :id");
        $stmt->execute([':id' => $product_id]);
        
        $stmt = $db->prepare("DELETE FROM product_variants WHERE parent_product_id = :id");
        $stmt->execute([':id' => $product_id]);
        
        $stmt = $db->prepare("DELETE FROM marketplace_products WHERE product_id = :id");
        $stmt->execute([':id' => $product_id]);
        
        // Ürünü sil
        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        
        if ($stmt->rowCount() > 0) {
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Ürün başarıyla silindi']);
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        }
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}