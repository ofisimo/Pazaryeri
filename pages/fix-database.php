<?php
/**
 * VERİTABANI KONTROL VE DÜZELTME SAYFASI
 * 
 * Bu sayfa product_images tablosunu kontrol eder ve düzeltir
 * Konum: pages/fix-database.php
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Hata gösterimi
ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veritabanı Kontrol ve Düzeltme</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
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
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
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
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
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
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Veritabanı Kontrol ve Düzeltme</h1>
            <p>product_images tablosu kontrol aracı</p>
        </div>
        
        <div class="content">
            <?php
            // 1. Tablo varlığı kontrolü
            echo '<div class="section">';
            echo '<h2>1️⃣ Tablo Kontrolü</h2>';
            
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'product_images'");
                $tableExists = $stmt->rowCount() > 0;
                
                if ($tableExists) {
                    echo '<div class="success">✅ product_images tablosu mevcut</div>';
                } else {
                    echo '<div class="error">❌ product_images tablosu bulunamadı!</div>';
                    echo '<div class="info">';
                    echo '<strong>ÇÖZÜM:</strong> product_images_update.sql dosyasını phpMyAdmin\'de çalıştırın.';
                    echo '</div>';
                    exit;
                }
            } catch (Exception $e) {
                echo '<div class="error">Hata: ' . $e->getMessage() . '</div>';
                exit;
            }
            
            echo '</div>';
            
            // 2. Tablo yapısı kontrolü
            echo '<div class="section">';
            echo '<h2>2️⃣ Tablo Yapısı</h2>';
            
            $stmt = $db->query("DESCRIBE product_images");
            $columns = $stmt->fetchAll();
            
            $requiredColumns = ['id', 'product_id', 'image_url', 'image_path', 'platform', 'sort_order', 'is_main', 'created_at'];
            $missingColumns = [];
            $existingColumns = [];
            
            foreach ($columns as $col) {
                $existingColumns[] = $col['Field'];
            }
            
            foreach ($requiredColumns as $reqCol) {
                if (!in_array($reqCol, $existingColumns)) {
                    $missingColumns[] = $reqCol;
                }
            }
            
            if (empty($missingColumns)) {
                echo '<div class="success">✅ Tüm gerekli kolonlar mevcut</div>';
            } else {
                echo '<div class="error">❌ Eksik kolonlar: ' . implode(', ', $missingColumns) . '</div>';
                echo '<div class="info">';
                echo '<strong>ÇÖZÜM:</strong> product_images_update.sql dosyasını çalıştırın.';
                echo '</div>';
            }
            
            echo '<details style="margin-top: 15px;">';
            echo '<summary style="cursor: pointer; font-weight: 600;">Tablo Yapısını Göster</summary>';
            echo '<table>';
            echo '<tr><th>Kolon</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . $col['Field'] . '</td>';
                echo '<td>' . $col['Type'] . '</td>';
                echo '<td>' . $col['Null'] . '</td>';
                echo '<td>' . $col['Key'] . '</td>';
                echo '<td>' . $col['Default'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</details>';
            
            echo '</div>';
            
            // 3. Kayıt sayısı
            echo '<div class="section">';
            echo '<h2>3️⃣ Kayıt İstatistikleri</h2>';
            
            // Toplam kayıt
            $stmt = $db->query("SELECT COUNT(*) as count FROM product_images");
            $totalImages = $stmt->fetch()['count'];
            
            // Platform bazlı
            $stmt = $db->query("SELECT platform, COUNT(*) as count FROM product_images GROUP BY platform");
            $byPlatform = $stmt->fetchAll();
            
            // Ana resimler
            $stmt = $db->query("SELECT COUNT(*) as count FROM product_images WHERE is_main = 1");
            $mainImages = $stmt->fetch()['count'];
            
            echo '<table>';
            echo '<tr><th>İstatistik</th><th>Değer</th></tr>';
            echo '<tr><td>Toplam Resim</td><td><strong>' . $totalImages . '</strong></td></tr>';
            echo '<tr><td>Ana Resim</td><td><strong>' . $mainImages . '</strong></td></tr>';
            
            foreach ($byPlatform as $platform) {
                echo '<tr><td>Platform: ' . $platform['platform'] . '</td><td><strong>' . $platform['count'] . '</strong></td></tr>';
            }
            echo '</table>';
            
            if ($totalImages == 0) {
                echo '<div class="warning" style="margin-top: 15px;">';
                echo '<strong>⚠️ Veritabanında hiç resim yok!</strong><br>';
                echo 'Bu normal değil. Resimler indirildi ama veritabanına yazılmadı demektir.';
                echo '</div>';
            }
            
            echo '</div>';
            
            // 4. Son 10 kayıt
            echo '<div class="section">';
            echo '<h2>4️⃣ Son Kayıtlar</h2>';
            
            $stmt = $db->query("SELECT * FROM product_images ORDER BY id DESC LIMIT 10");
            $recentImages = $stmt->fetchAll();
            
            if (!empty($recentImages)) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Product ID</th><th>Path</th><th>Platform</th><th>Ana?</th><th>Tarih</th></tr>';
                foreach ($recentImages as $img) {
                    echo '<tr>';
                    echo '<td>' . $img['id'] . '</td>';
                    echo '<td>' . $img['product_id'] . '</td>';
                    echo '<td style="font-size: 11px;">' . substr($img['image_path'], 0, 30) . '...</td>';
                    echo '<td>' . $img['platform'] . '</td>';
                    echo '<td>' . ($img['is_main'] ? '⭐' : '-') . '</td>';
                    echo '<td style="font-size: 11px;">' . $img['created_at'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="warning">Henüz kayıt yok</div>';
            }
            
            echo '</div>';
            
            // 5. Fiziksel dosya kontrolü
            echo '<div class="section">';
            echo '<h2>5️⃣ Fiziksel Dosya Kontrolü</h2>';
            
            $uploadsDir = __DIR__ . '/../uploads/opencart/';
            
            if (file_exists($uploadsDir)) {
                $files = array_diff(scandir($uploadsDir), ['.', '..', '.htaccess']);
                $fileCount = count($files);
                
                echo '<div class="info">';
                echo '<strong>uploads/opencart/ klasöründe ' . $fileCount . ' dosya var</strong>';
                echo '</div>';
                
                if ($fileCount > 0 && $totalImages == 0) {
                    echo '<div class="error">';
                    echo '<strong>❌ SORUN BULUNDU!</strong><br>';
                    echo 'Resimler fiziksel olarak indirilmiş (' . $fileCount . ' dosya) ama veritabanına yazılmamış!<br><br>';
                    echo '<strong>NEDEN:</strong> ImageHelper sınıfında veritabanına yazma hatası var.';
                    echo '</div>';
                }
                
                // İlk 5 dosyayı göster
                if ($fileCount > 0) {
                    echo '<details style="margin-top: 15px;">';
                    echo '<summary style="cursor: pointer; font-weight: 600;">İlk 5 Dosyayı Göster</summary>';
                    echo '<ul>';
                    $count = 0;
                    foreach ($files as $file) {
                        if ($count >= 5) break;
                        echo '<li>' . $file . '</li>';
                        $count++;
                    }
                    echo '</ul>';
                    echo '</details>';
                }
            } else {
                echo '<div class="error">uploads/opencart/ klasörü bulunamadı!</div>';
            }
            
            echo '</div>';
            
            // 6. Test yazma
            echo '<div class="section">';
            echo '<h2>6️⃣ Manuel Yazma Testi</h2>';
            
            if (isset($_POST['test_write'])) {
                try {
                    // İlk ürünü al
                    $stmt = $db->query("SELECT id FROM products LIMIT 1");
                    $testProduct = $stmt->fetch();
                    
                    if ($testProduct) {
                        $testProductId = $testProduct['id'];
                        
                        // Test kaydı ekle
                        $stmt = $db->prepare("
                            INSERT INTO product_images (product_id, image_url, image_path, platform, sort_order, is_main)
                            VALUES (:product_id, :image_url, :image_path, :platform, :sort_order, :is_main)
                        ");
                        
                        $result = $stmt->execute([
                            ':product_id' => $testProductId,
                            ':image_url' => 'http://test.com/test.jpg',
                            ':image_path' => 'opencart/test_manual_' . time() . '.jpg',
                            ':platform' => 'test',
                            ':sort_order' => 0,
                            ':is_main' => 1
                        ]);
                        
                        if ($result) {
                            $insertedId = $db->lastInsertId();
                            echo '<div class="success">';
                            echo '✅ Test başarılı! Kayıt eklendi (ID: ' . $insertedId . ')<br>';
                            echo 'Bu demek ki veritabanı çalışıyor, sorun ImageHelper\'da.';
                            echo '</div>';
                            
                            // Test kaydını sil
                            $stmt = $db->prepare("DELETE FROM product_images WHERE id = :id");
                            $stmt->execute([':id' => $insertedId]);
                            echo '<div class="info">Test kaydı temizlendi.</div>';
                        } else {
                            $errorInfo = $stmt->errorInfo();
                            echo '<div class="error">';
                            echo '❌ Yazma başarısız!<br>';
                            echo 'SQL Error: ' . print_r($errorInfo, true);
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="error">Test için ürün bulunamadı.</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error">';
                    echo '❌ Hata: ' . $e->getMessage() . '<br>';
                    echo 'SQL State: ' . $e->getCode();
                    echo '</div>';
                }
            }
            
            echo '<form method="post">';
            echo '<button type="submit" name="test_write" class="btn">Manuel Yazma Testi Yap</button>';
            echo '</form>';
            
            echo '</div>';
            
            // 7. Çözüm önerileri
            echo '<div class="section" style="background: #e7f3ff; border-left-color: #007bff;">';
            echo '<h2>💡 Çözüm Önerileri</h2>';
            
            if ($fileCount > 0 && $totalImages == 0) {
                echo '<div class="error">';
                echo '<strong>ANA SORUN: Resimler indiriliyor ama veritabanına yazılmıyor</strong>';
                echo '</div>';
                
                echo '<ol>';
                echo '<li><strong>ImageHelper.php dosyasını güncelleyin:</strong><br>';
                echo 'ImageHelper-fixed.php dosyasını includes/ImageHelper.php olarak yükleyin';
                echo '</li>';
                echo '<li><strong>PHP error log\'larını kontrol edin:</strong><br>';
                echo 'Hosting panelinden error.log dosyasına bakın';
                echo '</li>';
                echo '<li><strong>Tekrar senkronizasyon yapın:</strong><br>';
                echo 'Önce mevcut ürünleri silin, sonra tekrar içe aktarın';
                echo '</li>';
                echo '</ol>';
            } elseif ($totalImages > 0) {
                echo '<div class="success">';
                echo '<strong>✅ Sistem çalışıyor!</strong><br>';
                echo 'Resimler hem indiriliyor hem de veritabanına yazılıyor.';
                echo '</div>';
            }
            
            echo '</div>';
            ?>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="../pages/products.php" class="btn">Ürünler Sayfasına Dön</a>
            </div>
        </div>
    </div>
</body>
</html>