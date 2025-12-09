<?php
/**
 * Kategorisiz Ürünleri Getir
 * Konum: pages/get-uncategorized-products.php
 */
session_start();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

try {
    // Kategorisiz ürünleri getir
    $stmt = $db->query("
        SELECT p.id, p.name, p.sku
        FROM products p
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        WHERE pc.id IS NULL
        ORDER BY p.name
        LIMIT 100
    ");
    
    $products = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}