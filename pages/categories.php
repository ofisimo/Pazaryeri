<?php
$success = '';
$error = '';

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
            $categoryMap = []; // OpenCart ID -> Panel ID eşleşmesi
            
            foreach ($categories as $ocCat) {
                // Kategori zaten var mı kontrol et
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
                    // Güncelle (parent_id'siz)
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
                    // Yeni ekle (parent_id'siz)
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
                    
                    // Eşleştirme kaydet
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
                
                // Map'e ekle
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

// Kategorileri getir
$stmt = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count,
           (SELECT COUNT(*) FROM category_mappings cm WHERE cm.category_id = c.id) as mapping_count
    FROM categories c
    ORDER BY c.sort_order, c.name
");
$categories = $stmt->fetchAll();
?>

<style>
.categories-page {
    animation: fadeIn 0.5s;
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

.mapping-badge.opencart {
    background: #e3f2fd;
    color: #2196f3;
}

.mapping-badge.trendyol {
    background: #fff5f0;
    color: #f27a1a;
}

.mapping-badge.hepsiburada {
    background: #fff4ed;
    color: #ff6000;
}

.mapping-badge.n11 {
    background: #f8f5fb;
    color: #7c3fb7;
}.empty-state {
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
}

.btn-edit {
    background: #3498db;
    color: white;
}

.btn-delete {
    background: #e74c3c;
    color: white;
}

.btn-action:hover {
    transform: scale(1.05);
}.bulk-actions {
    display: none;
    animation: slideDown 0.3s;
}
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

	 <!-- Toplu İşlemler -->
    <div class="bulk-actions" id="bulk-actions-categories" style="display: none;">
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
            <span id="selected-count-categories" style="font-weight: 600; color: #2c3e50;">0 kategori seçildi</span>
            <button class="btn btn-danger" onclick="bulkDeleteCategories()">
                <i class="fas fa-trash"></i> Seçilenleri Sil
            </button>
        </div>
    </div>
    <!-- Senkronizasyon Bölümü -->
    <div class="sync-section">
        <h3><i class="fas fa-sync"></i> Kategori Senkronizasyonu</h3>
        <p style="color: #7f8c8d; font-size: 14px; margin-top: 5px;">
            Kategorileri dış platformlardan çekin ve otomatik eşleştirin
        </p>
        <form method="POST" class="sync-buttons">
            <button type="submit" name="sync_opencart_categories" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> OpenCart'tan Çek
            </button>
            <button type="button" class="btn btn-warning" onclick="syncMarketplaceCategories('trendyol')">
                <i class="fas fa-shopping-bag"></i> Trendyol'dan Çek
            </button>
            <button type="button" class="btn btn-info" onclick="syncMarketplaceCategories('hepsiburada')">
                <i class="fas fa-shopping-basket"></i> Hepsiburada'dan Çek
            </button>
            <button type="button" class="btn btn-success" onclick="syncMarketplaceCategories('n11')">
                <i class="fas fa-store-alt"></i> N11'den Çek
            </button>
        </form>
    </div>

    <!-- Kategori Listesi -->
    <div class="categories-table">
        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>Henüz kategori bulunmuyor</h3>
                <p>Yukarıdaki butonları kullanarak kategorileri senkronize edebilirsiniz</p>
            </div>
        <?php else: ?>
            <table>
        <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all-categories" onclick="toggleSelectAllCategories()">
                        </th>
                        <th>Kategori Adı</th>
                        <th>Ürün Sayısı</th>
                        <th>Eşleştirmeler</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="category-checkbox" value="<?php echo $category['id']; ?>">
                            </td>
                       
                       <td>
                                <?php
                                // Üst kategori varsa hiyerarşiyi göster
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
                                <span class="badge badge-info">
                                    <?php echo $category['product_count']; ?> ürün
                                </span>
                            </td>
                            <td>
                                <div class="mapping-badges">
                                    <?php
                                    $stmt = $db->prepare("SELECT platform FROM category_mappings WHERE category_id = :id");
                                    $stmt->execute([':id' => $category['id']]);
                                    $mappings = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                    
                                    if (empty($mappings)) {
                                        echo '<span style="color: #999; font-size: 12px;">Eşleştirme yok</span>';
                                    } else {
                                        foreach ($mappings as $platform) {
                                            echo '<span class="mapping-badge ' . $platform . '">' . ucfirst($platform) . '</span>';
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
    // Eşleştirme detaylarını göster
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
            alert('✗ ' + data.message);
        }
    });
}// Tümünü seç/kaldır
function toggleSelectAllCategories() {
    const selectAll = document.getElementById('select-all-categories');
    const checkboxes = document.querySelectorAll('.category-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActionsCategories();
}

// Seçili kategori sayısını güncelle
function updateBulkActionsCategories() {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const count = checkboxes.length;
    const bulkActions = document.getElementById('bulk-actions-categories');
    const selectedCount = document.getElementById('selected-count-categories');
    
    if (count > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = count + ' kategori seçildi';
    } else {
        bulkActions.style.display = 'none';
        document.getElementById('select-all-categories').checked = false;
    }
}

// Checkbox'lara event listener ekle
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.category-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsCategories);
    });
});

// Toplu silme
function bulkDeleteCategories() {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Lütfen silmek için kategori seçin');
        return;
    }
    
    if (!confirm(`${ids.length} kategoriyi silmek istediğinize emin misiniz?\n\nNot: Alt kategorisi veya ürünü olan kategoriler silinemez.`)) {
        return;
    }
    
    let deleted = 0;
    let failed = 0;
    let errors = [];
    
    Promise.all(ids.map(id => 
        fetch('ajax/delete-category.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'category_id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                deleted++;
            } else {
                failed++;
                errors.push(data.message);
            }
        })
        .catch(() => failed++)
    ))
    .then(() => {
        let message = `✓ ${deleted} kategori silindi`;
        if (failed > 0) {
            message += `\n✗ ${failed} kategori silinemedi`;
            if (errors.length > 0) {
                message += '\n\nHatalar:\n' + errors.slice(0, 3).join('\n');
            }
        }
        alert(message);
        location.reload();
    });
}
</script>