<?php
$success = '';
$error = '';

// Kategori ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Kategori adı gereklidir';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO categories (name, parent_id, description, sort_order, is_active)
                VALUES (:name, :parent_id, :description, :sort_order, :is_active)
            ");
            $stmt->execute([
                ':name' => $name,
                ':parent_id' => $parent_id,
                ':description' => $description,
                ':sort_order' => $sort_order,
                ':is_active' => $is_active
            ]);
            
            $categoryId = $db->lastInsertId();
            $success = 'Kategori eklendi! <a href="?page=category-mappings&id=' . $categoryId . '">Şimdi eşleştirme yapın</a>';
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    }
}

// Üst kategori listesi
$stmt = $db->query("SELECT id, name, parent_id FROM categories ORDER BY name");
$allCategories = $stmt->fetchAll();
?>

<style>
.add-page {
    animation: fadeIn 0.5s;
}

.add-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    max-width: 800px;
    margin: 0 auto;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="add-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-plus-circle"></i> Yeni Kategori Ekle</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Yeni bir kategori oluşturun</p>
        </div>
        <button class="btn btn-secondary" onclick="window.location.href='?page=categories'">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="add-card">
        <form method="POST" action="">
            <div class="form-group">
                <label>Kategori Adı *</label>
                <input type="text" name="name" class="form-control" placeholder="Örn: Elektronik" required autofocus>
                <small>Kategorinin görünen adı</small>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Üst Kategori</label>
                    <select name="parent_id" class="form-control">
                        <option value="">— Ana Kategori —</option>
                        <?php 
                        // Hiyerarşik gösterim için kategorileri sırala
                        function buildCategoryTree($categories, $parentId = null, $level = 0) {
                            $result = [];
                            foreach ($categories as $cat) {
                                if ($cat['parent_id'] == $parentId) {
                                    $cat['level'] = $level;
                                    $result[] = $cat;
                                    $children = buildCategoryTree($categories, $cat['id'], $level + 1);
                                    $result = array_merge($result, $children);
                                }
                            }
                            return $result;
                        }
                        
                        $tree = buildCategoryTree($allCategories);
                        foreach ($tree as $cat): 
                            $indent = str_repeat('— ', $cat['level']);
                        ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo $indent . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Boş bırakırsanız ana kategori olur</small>
                </div>

                <div class="form-group">
                    <label>Sıralama</label>
                    <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    <small>Küçük değerler önce gösterilir</small>
                </div>
            </div>

            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Kategori hakkında kısa açıklama (opsiyonel)"></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-row" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_active" checked style="width: 18px; height: 18px;">
                    <span>Kategori aktif</span>
                </label>
                <small style="margin-left: 28px; color: #999;">Pasif kategoriler ürün eklenemez</small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kategoriyi Kaydet
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=categories'">
                    <i class="fas fa-times"></i> İptal
                </button>
            </div>
        </form>
    </div>

    <!-- Bilgilendirme Kartı -->
    <div class="add-card" style="margin-top: 20px; background: #e3f2fd; border-left: 4px solid #2196f3;">
        <h4 style="margin-top: 0; color: #1976d2;">
            <i class="fas fa-info-circle"></i> Bilgi
        </h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Kategori oluşturduktan sonra pazaryerleriyle eşleştirme yapabilirsiniz</li>
            <li>Alt kategoriler için önce üst kategoriyi oluşturun</li>
            <li>Sıralama numarası küçük olan kategoriler listede önce görünür</li>
            <li>Pasif kategoriler ürün listesinde gösterilmez</li>
        </ul>
    </div>
</div>