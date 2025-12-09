<?php
$success = '';
$error = '';

// Toplu otomatik eşleştirme
if (isset($_POST['auto_match_all'])) {
    $platform = $_POST['platform'] ?? '';
    
    if ($platform) {
        try {
            require_once 'api/OpencartAPI.php';
            
            $matched = 0;
            $skipped = 0;
            
            // Tüm kategorileri al
            $stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1");
            $allCategories = $stmt->fetchAll();
            
            // Platform kategorilerini çek
            $platformCategories = [];
            if ($platform == 'opencart') {
                $api = new OpencartAPI();
                if ($api->isActive()) {
                    $response = $api->getCategories();
                    $platformCategories = $response['categories'] ?? [];
                }
            }
            
            foreach ($allCategories as $cat) {
                // Zaten eşleştirilmiş mi?
                $stmt = $db->prepare("SELECT id FROM category_mappings WHERE category_id = :id AND platform = :platform");
                $stmt->execute([':id' => $cat['id'], ':platform' => $platform]);
                if ($stmt->fetch()) {
                    $skipped++;
                    continue;
                }
                
                // En iyi eşleşmeyi bul
                $bestMatch = null;
                $bestScore = 0;
                
                foreach ($platformCategories as $pCat) {
                    $score = similarity($cat['name'], $pCat['name']);
                    if ($score > $bestScore && $score > 0.6) {
                        $bestScore = $score;
                        $bestMatch = $pCat;
                    }
                }
                
                if ($bestMatch) {
                    $stmt = $db->prepare("
                        INSERT INTO category_mappings (category_id, platform, platform_category_id, platform_category_name)
                        VALUES (:category_id, :platform, :platform_id, :platform_name)
                    ");
                    $stmt->execute([
                        ':category_id' => $cat['id'],
                        ':platform' => $platform,
                        ':platform_id' => $bestMatch['category_id'],
                        ':platform_name' => $bestMatch['name']
                    ]);
                    $matched++;
                }
            }
            
            $success = "{$matched} kategori otomatik eşleştirildi, {$skipped} zaten eşleştirilmişti";
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    }
}

// OpenCart'tan kategori çekme
if (isset($_POST['sync_opencart_categories'])) {
    try {
        require_once 'api/OpencartAPI.php';
        $opencartAPI = new OpencartAPI();
        
        if ($opencartAPI->isActive()) {
            $response = $opencartAPI->getCategories();
            $categories = $response['categories'] ?? [];
            
            // İlk aşama: Tüm kategorileri ekle (parent_id olmadan)
            $imported = 0;
            $categoryMap = [];
            
            foreach ($categories as $ocCat) {
                $stmt = $db->prepare("
                    SELECT c.id 
                    FROM categories c
                    INNER JOIN category_mappings cm ON c.id = cm.category_id
                    WHERE cm.platform = 'opencart' 
                    AND cm.platform_category_id = :opencart_id
                ");
                $stmt->execute([':opencart_id' => $ocCat['category_id']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $db->prepare("
                        UPDATE categories 
                        SET name = :name, 
                            description = :description, 
                            sort_order = :sort_order
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $ocCat['name'],
                        ':description' => $ocCat['description'] ?? '',
                        ':sort_order' => $ocCat['sort_order'] ?? 0,
                        ':id' => $existing['id']
                    ]);
                    $categoryId = $existing['id'];
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO categories (name, description, sort_order) 
                        VALUES (:name, :description, :sort_order)
                    ");
                    $stmt->execute([
                        ':name' => $ocCat['name'],
                        ':description' => $ocCat['description'] ?? '',
                        ':sort_order' => $ocCat['sort_order'] ?? 0
                    ]);
                    $categoryId = $db->lastInsertId();
                    
                    $stmt = $db->prepare("
                        INSERT INTO category_mappings (category_id, platform, platform_category_id, platform_category_name)
                        VALUES (:category_id, 'opencart', :platform_id, :platform_name)
                    ");
                    $stmt->execute([
                        ':category_id' => $categoryId,
                        ':platform_id' => $ocCat['category_id'],
                        ':platform_name' => $ocCat['name']
                    ]);
                }
                
                $categoryMap[$ocCat['category_id']] = $categoryId;
                $imported++;
            }
            
            // İkinci aşama: parent_id ilişkilerini kur
            foreach ($categories as $ocCat) {
                if ($ocCat['parent_id'] > 0 && isset($categoryMap[$ocCat['parent_id']])) {
                    $panelCategoryId = $categoryMap[$ocCat['category_id']];
                    $panelParentId = $categoryMap[$ocCat['parent_id']];
                    
                    $stmt = $db->prepare("
                        UPDATE categories 
                        SET parent_id = :parent_id
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':parent_id' => $panelParentId,
                        ':id' => $panelCategoryId
                    ]);
                }
            }
            
            $success = "{$imported} kategori OpenCart'tan çekildi ve eşleştirildi";
        } else {
            $error = 'OpenCart entegrasyonu aktif değil';
        }
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Eşleştirme istatistiklerini hesapla
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT c.id) as total_categories,
        COUNT(DISTINCT CASE WHEN cm.id IS NOT NULL THEN c.id END) as mapped_categories,
        COUNT(DISTINCT CASE WHEN cm.id IS NULL THEN c.id END) as unmapped_categories,
        COUNT(DISTINCT CASE WHEN cm.platform = 'opencart' THEN c.id END) as opencart_mapped,
        COUNT(DISTINCT CASE WHEN cm.platform = 'trendyol' THEN c.id END) as trendyol_mapped,
        COUNT(DISTINCT CASE WHEN cm.platform = 'hepsiburada' THEN c.id END) as hepsiburada_mapped,
        COUNT(DISTINCT CASE WHEN cm.platform = 'n11' THEN c.id END) as n11_mapped
    FROM categories c
    LEFT JOIN category_mappings cm ON c.id = cm.category_id
");
$stats = $stmt->fetch();

// Kategorileri getir
$stmt = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM category_mappings cm WHERE cm.category_id = c.id) as mapping_count,
           (SELECT COUNT(DISTINCT pc.product_id) FROM product_categories pc WHERE pc.category_id = c.id) as product_count
    FROM categories c
    ORDER BY c.sort_order, c.name
");
$categories = $stmt->fetchAll();

// String benzerliği fonksiyonu
function similarity($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));
    $lev = levenshtein($str1, $str2);
    $maxLen = max(strlen($str1), strlen($str2));
    if ($maxLen == 0) return 1.0;
    return 1.0 - ($lev / $maxLen);
}
?>

<style>
.categories-page {
    animation: fadeIn 0.5s;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.stat-card.primary { border-color: #3498db; }
.stat-card.success { border-color: #27ae60; }
.stat-card.warning { border-color: #f39c12; }
.stat-card.opencart { border-color: #2196f3; }
.stat-card.trendyol { border-color: #f27a1a; }
.stat-card.hepsiburada { border-color: #ff6000; }
.stat-card.n11 { border-color: #7c3fb7; }

.stat-number {
    font-size: 32px;
    font-weight: 700;
    margin: 10px 0;
}

.stat-label {
    color: #7f8c8d;
    font-size: 13px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.sync-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.sync-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.bulk-match-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.bulk-match-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.bulk-match-btn {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
}

.bulk-match-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.categories-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.mapping-badges {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.mapping-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.mapping-badge.opencart { background: #e3f2fd; color: #2196f3; }
.mapping-badge.trendyol { background: #fff5f0; color: #f27a1a; }
.mapping-badge.hepsiburada { background: #fff4ed; color: #ff6000; }
.mapping-badge.n11 { background: #f8f5fb; color: #7c3fb7; }

.no-mapping {
    background: #ffebee;
    color: #c62828;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.category-row.unmapped {
    background: #fffde7;
}

.quick-match-btn {
    padding: 4px 8px;
    background: #9c27b0;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.3s;
}

.quick-match-btn:hover {
    background: #7b1fa2;
    transform: scale(1.05);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
    margin: 0 2px;
}

.btn-edit { background: #3498db; color: white; }
.btn-delete { background: #e74c3c; color: white; }
.btn-primary { background: #9c27b0; color: white; }
.btn-action:hover { transform: scale(1.05); }
</style>

<div class="categories-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-folder-tree"></i> Kategori Yönetimi</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Kategorileri yönetin ve pazaryerleriyle eşleştirin</p>
        </div>
        <button class="btn btn-primary" onclick="window.location.href='?page=category-add'">
            <i class="fas fa-plus"></i> Yeni Kategori
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Eşleştirme İstatistikleri -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-label">Toplam Kategori</div>
            <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Eşleştirilmiş</div>
            <div class="stat-number"><?php echo $stats['mapped_categories']; ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">Eşleştirilmemiş</div>
            <div class="stat-number"><?php echo $stats['unmapped_categories']; ?></div>
        </div>
        <div class="stat-card opencart">
            <div class="stat-label"><i class="fas fa-shopping-cart"></i> OpenCart</div>
            <div class="stat-number"><?php echo $stats['opencart_mapped']; ?></div>
        </div>
        <div class="stat-card trendyol">
            <div class="stat-label"><i class="fas fa-shopping-bag"></i> Trendyol</div>
            <div class="stat-number"><?php echo $stats['trendyol_mapped']; ?></div>
        </div>
        <div class="stat-card hepsiburada">
            <div class="stat-label"><i class="fas fa-shopping-basket"></i> Hepsiburada</div>
            <div class="stat-number"><?php echo $stats['hepsiburada_mapped']; ?></div>
        </div>
        <div class="stat-card n11">
            <div class="stat-label"><i class="fas fa-store-alt"></i> N11</div>
            <div class="stat-number"><?php echo $stats['n11_mapped']; ?></div>
        </div>
    </div>

    <!-- Toplu Otomatik Eşleştirme -->
    <?php if ($stats['unmapped_categories'] > 0): ?>
    <div class="bulk-match-section">
        <h3 style="margin: 0 0 10px 0;"><i class="fas fa-magic"></i> Toplu Otomatik Eşleştirme</h3>
        <p style="margin: 0 0 15px 0; opacity: 0.9;">
            <?php echo $stats['unmapped_categories']; ?> kategorinin tümünü otomatik olarak eşleştirin
        </p>
        <form method="POST" style="display: inline;">
            <div class="bulk-match-buttons">
                <button type="submit" name="auto_match_all" value="1" class="bulk-match-btn" 
                        onclick="this.form.platform.value='opencart'; return confirm('OpenCart için tüm kategoriler otomatik eşleştirilsin mi?')">
                    <i class="fas fa-shopping-cart"></i> OpenCart'a Eşleştir
                </button>
                <button type="submit" name="auto_match_all" value="1" class="bulk-match-btn"
                        onclick="this.form.platform.value='trendyol'; return confirm('Trendyol için tüm kategoriler otomatik eşleştirilsin mi?')">
                    <i class="fas fa-shopping-bag"></i> Trendyol'a Eşleştir
                </button>
                <button type="submit" name="auto_match_all" value="1" class="bulk-match-btn"
                        onclick="this.form.platform.value='hepsiburada'; return confirm('Hepsiburada için tüm kategoriler otomatik eşleştirilsin mi?')">
                    <i class="fas fa-shopping-basket"></i> Hepsiburada'ya Eşleştir
                </button>
                <button type="submit" name="auto_match_all" value="1" class="bulk-match-btn"
                        onclick="this.form.platform.value='n11'; return confirm('N11 için tüm kategoriler otomatik eşleştirilsin mi?')">
                    <i class="fas fa-store-alt"></i> N11'e Eşleştir
                </button>
            </div>
            <input type="hidden" name="platform" value="">
        </form>
    </div>
    <?php endif; ?>

    <!-- Senkronizasyon Bölümü -->
    <div class="sync-section">
        <h3><i class="fas fa-sync"></i> Kategori Senkronizasyonu</h3>
        <p style="color: #7f8c8d; margin-bottom: 15px;">Pazaryerlerinden kategorileri çekin ve otomatik eşleştirin</p>
        
        <form method="POST" style="display: inline;">
            <div class="sync-buttons">
                <button type="submit" name="sync_opencart_categories" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-shopping-cart"></i> OpenCart'tan Çek
                </button>
                <button type="button" class="btn btn-secondary" style="width: 100%;" onclick="syncMarketplaceCategories('trendyol')">
                    <i class="fas fa-shopping-bag"></i> Trendyol'dan Çek
                </button>
                <button type="button" class="btn btn-secondary" style="width: 100%;" onclick="syncMarketplaceCategories('hepsiburada')">
                    <i class="fas fa-shopping-basket"></i> Hepsiburada'dan Çek
                </button>
                <button type="button" class="btn btn-secondary" style="width: 100%;" onclick="syncMarketplaceCategories('n11')">
                    <i class="fas fa-store-alt"></i> N11'den Çek
                </button>
            </div>
        </form>
    </div>

    <!-- Kategoriler Tablosu -->
    <div class="categories-table">
        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>Henüz kategori yok</h3>
                <p>Yeni kategori eklemek için yukarıdaki butonu kullanın</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Ürün Sayısı</th>
                        <th>Platform Eşleştirmeleri</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr class="<?php echo $category['mapping_count'] == 0 ? 'unmapped' : ''; ?>">
                            <td>
                                <?php
                                $hierarchy = '';
                                if ($category['parent_id']) {
                                    $parentStmt = $db->prepare("SELECT name FROM categories WHERE id = :id");
                                    $parentStmt->execute([':id' => $category['parent_id']]);
                                    $parent = $parentStmt->fetch();
                                    if ($parent) {
                                        $hierarchy = '<span style="color: #999;">' . htmlspecialchars($parent['name']) . ' → </span>';
                                    }
                                }
                                ?>
                                <?php echo $hierarchy; ?>
                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                            </td>
                            <td>
                                <?php if ($category['product_count'] > 0): ?>
                                    <span class="badge badge-info" style="cursor: pointer;" 
                                          onclick="window.location.href='?page=products&category=<?php echo $category['id']; ?>'">
                                        <?php echo $category['product_count']; ?> ürün
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">0 ürün</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="mapping-badges">
                                    <?php
                                    $stmt = $db->prepare("SELECT platform, platform_category_name FROM category_mappings WHERE category_id = :id");
                                    $stmt->execute([':id' => $category['id']]);
                                    $mappings = $stmt->fetchAll();
                                    
                                    if (empty($mappings)) {
                                        echo '<span class="no-mapping">Eşleştirme yok</span>';
                                        echo '<button class="quick-match-btn" onclick="quickMatch(' . $category['id'] . ')" title="Hızlı Eşleştir"><i class="fas fa-magic"></i></button>';
                                    } else {
                                        foreach ($mappings as $mapping) {
                                            echo '<span class="mapping-badge ' . $mapping['platform'] . '" title="' . htmlspecialchars($mapping['platform_category_name']) . '">';
                                            echo ucfirst($mapping['platform']);
                                            echo '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($category['is_active']): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick="editCategory(<?php echo $category['id']; ?>)" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-primary" onclick="showMappings(<?php echo $category['id']; ?>)" title="Eşleştirmeler">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteCategory(<?php echo $category['id']; ?>)" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function syncMarketplaceCategories(platform) {
    if (!confirm(platform.charAt(0).toUpperCase() + platform.slice(1) + ' kategorileri çekilsin mi?')) {
        return;
    }
    alert('Bu özellik yakında eklenecek: ' + platform + ' kategori senkronizasyonu');
}

function editCategory(categoryId) {
    window.location.href = '?page=category-edit&id=' + categoryId;
}

function showMappings(categoryId) {
    window.location.href = '?page=category-mappings&id=' + categoryId;
}

function quickMatch(categoryId) {
    if (!confirm('Bu kategori için otomatik eşleştirme yapılsın mı?')) {
        return;
    }
    window.location.href = '?page=category-mappings&id=' + categoryId;
}

function deleteCategory(categoryId) {
    if (!confirm('Bu kategoriyi silmek istediğinize emin misiniz?')) {
        return;
    }
    
    fetch('ajax/delete-category.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'category_id=' + categoryId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ Kategori silindi');
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    });
}
</script>