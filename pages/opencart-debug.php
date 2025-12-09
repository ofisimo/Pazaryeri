<?php
/**
 * OpenCart API Debug Sayfası
 * Bu dosyayı tarayıcıda açarak OpenCart'tan gelen veriyi görebilirsiniz
 * Örnek: http://yourdomain.com/pages/opencart-debug.php
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/OpencartAPI.php';

// Hata gösterimi
ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenCart API Debug</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            font-size: 20px;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .product-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .product-card h3 {
            margin-top: 0;
            color: #667eea;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .image-grid img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #e0e0e0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 OpenCart API Debug</h1>
            <p>OpenCart'tan gelen veri yapısını inceleyin</p>
        </div>
        
        <div class="content">
            <?php
            try {
                $opencartAPI = new OpencartAPI();
                
                if (!$opencartAPI->isActive()) {
                    echo '<div class="error">';
                    echo '<strong>❌ Hata:</strong> OpenCart entegrasyonu aktif değil. Settings sayfasından ayarları yapın.';
                    echo '</div>';
                    exit;
                }
                
                echo '<div class="success">';
                echo '<strong>✅ Başarılı:</strong> OpenCart API bağlantısı aktif.';
                echo '</div>';
                
                // İlk 3 ürünü çek
                echo '<div class="section">';
                echo '<h2>📦 OpenCart\'tan Ürünler Çekiliyor...</h2>';
                
                $response = $opencartAPI->getProducts(3, 1); // Sadece 3 ürün test için
                
                if (!isset($response['products']) || empty($response['products'])) {
                    echo '<div class="error">';
                    echo '<strong>❌ Hata:</strong> OpenCart\'tan ürün gelmedi.<br>';
                    echo '<strong>Yanıt:</strong><pre>' . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                    echo '</div>';
                    exit;
                }
                
                $products = $response['products'];
                echo '<div class="info-box">';
                echo '<strong>Toplam ' . count($products) . ' ürün çekildi (test için)</strong>';
                echo '</div>';
                echo '</div>';
                
                // Her ürün için detaylı bilgi
                foreach ($products as $index => $product) {
                    echo '<div class="section">';
                    echo '<div class="product-card">';
                    echo '<h3>Ürün #' . ($index + 1) . ': ' . htmlspecialchars($product['name']) . '</h3>';
                    
                    // Temel bilgiler
                    echo '<table>';
                    echo '<tr><th>Alan</th><th>Değer</th></tr>';
                    echo '<tr><td>Product ID</td><td>' . ($product['product_id'] ?? 'YOK') . '</td></tr>';
                    echo '<tr><td>SKU/Model</td><td>' . ($product['model'] ?? 'YOK') . '</td></tr>';
                    echo '<tr><td>Fiyat</td><td>' . ($product['price'] ?? 0) . '</td></tr>';
                    echo '<tr><td>Stok</td><td>' . ($product['quantity'] ?? 0) . '</td></tr>';
                    echo '</table>';
                    
                    // Resim bilgileri
                    echo '<h4 style="margin-top: 20px;">🖼️ Resim Bilgileri:</h4>';
                    
                    // Ana Resim
                    echo '<div style="margin: 15px 0; padding: 15px; background: #f0f0f0; border-radius: 5px;">';
                    echo '<strong>Ana Resim (image field):</strong><br>';
                    if (isset($product['image']) && !empty($product['image'])) {
                        echo '<span class="badge badge-success">✓ Var</span>';
                        echo '<pre style="margin-top: 10px;">' . htmlspecialchars($product['image']) . '</pre>';
                        
                        // Resmi göster
                        echo '<div style="margin-top: 10px;">';
                        echo '<img src="' . htmlspecialchars($product['image']) . '" style="max-width: 200px; border: 2px solid #667eea;" onerror="this.style.border=\'2px solid red\'; this.alt=\'Resim yüklenemedi\';">';
                        echo '</div>';
                    } else {
                        echo '<span class="badge badge-danger">✗ Yok</span>';
                    }
                    echo '</div>';
                    
                    // Ek Resimler
                    echo '<div style="margin: 15px 0; padding: 15px; background: #f0f0f0; border-radius: 5px;">';
                    echo '<strong>Ek Resimler (images field):</strong><br>';
                    if (isset($product['images'])) {
                        if (is_array($product['images']) && !empty($product['images'])) {
                            echo '<span class="badge badge-success">✓ Var (' . count($product['images']) . ' adet)</span>';
                            echo '<pre style="margin-top: 10px;">' . json_encode($product['images'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                            
                            // Resimleri göster
                            echo '<div class="image-grid">';
                            foreach ($product['images'] as $img) {
                                if (!empty($img)) {
                                    echo '<img src="' . htmlspecialchars($img) . '" onerror="this.style.border=\'2px solid red\'; this.alt=\'Yüklenemedi\';">';
                                }
                            }
                            echo '</div>';
                        } else {
                            echo '<span class="badge badge-danger">✗ Boş Array</span>';
                        }
                    } else {
                        echo '<span class="badge badge-danger">✗ Field Yok</span>';
                        echo '<div class="warning" style="margin-top: 10px;">';
                        echo '<strong>⚠️ SORUN BU!</strong><br>';
                        echo 'OpenCart API\'nizde "images" field\'i yok. OpenCart\'ta özel bir API controller oluşturmanız gerekiyor.';
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    // Tüm veri yapısı
                    echo '<details style="margin-top: 20px;">';
                    echo '<summary style="cursor: pointer; font-weight: 600; color: #667eea;">📋 Tüm Ürün Verisini Göster (JSON)</summary>';
                    echo '<pre style="margin-top: 10px;">' . json_encode($product, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                    echo '</details>';
                    
                    echo '</div>';
                    echo '</div>';
                }
                
                // Özet ve Çözüm Önerileri
                echo '<div class="section">';
                echo '<h2>💡 Analiz ve Çözüm Önerileri</h2>';
                
                $hasImageField = isset($products[0]['image']);
                $hasImagesArray = isset($products[0]['images']) && is_array($products[0]['images']) && !empty($products[0]['images']);
                
                if ($hasImageField && $hasImagesArray) {
                    echo '<div class="success">';
                    echo '<strong>✅ Harika!</strong> Hem ana resim hem de ek resimler geliyor. Sistem çalışmalı.';
                    echo '</div>';
                } elseif ($hasImageField && !$hasImagesArray) {
                    echo '<div class="warning">';
                    echo '<strong>⚠️ Kısmi Sorun:</strong> Ana resim geliyor ama ek resimler gelmiyor.<br><br>';
                    echo '<strong>ÇÖZÜM:</strong> OpenCart\'ın varsayılan API\'si ek resimleri döndürmez. Özel bir API controller oluşturmanız gerekiyor.<br><br>';
                    echo '<strong>Seçenekler:</strong><br>';
                    echo '1. OpenCart\'ta catalog/controller/api/product.php dosyasını düzenleyin<br>';
                    echo '2. Sadece ana resimle devam edin (ek resimler olmayacak)<br>';
                    echo '3. FTP ile resimleri manuel olarak kopyalayın';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<strong>❌ Ciddi Sorun:</strong> Hiç resim bilgisi gelmiyor.<br><br>';
                    echo '<strong>ÇÖZÜM:</strong> OpenCart API ayarlarınızı kontrol edin.';
                    echo '</div>';
                }
                
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>❌ Hata:</strong> ' . $e->getMessage();
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>