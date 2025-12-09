<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

header('Content-Type: application/json');

$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kategori ID']);
    exit;
}

try {
    // Alt kategorileri kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = :id");
    $stmt->execute([':id' => $category_id]);
    $childCount = $stmt->fetch()['count'];
    
    if ($childCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu kategorinin ' . $childCount . ' alt kategorisi var. Önce onları silin.']);
        exit;
    }
    
    // Kategoriye ait ürün var mı kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = :id");
    $stmt->execute([':id' => $category_id]);
    $productCount = $stmt->fetch()['count'];
    
    if ($productCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu kategoride ' . $productCount . ' ürün var. Önce ürünleri silin veya başka kategoriye taşıyın.']);
        exit;
    }
    
    // Kategoriyi sil (mapping'ler otomatik silinir - CASCADE)
    $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute([':id' => $category_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Kategori silindi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kategori bulunamadı']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}