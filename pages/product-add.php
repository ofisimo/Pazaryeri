<?php
$success = '';
$error = '';

// Kategorileri getir
$stmt = $db->query("SELECT id, name, parent_id FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Ürün adı gereklidir';
    } elseif (empty($sku)) {
        $error = 'SKU gereklidir';
    } else {
        try {
            // SKU benzersizliği kontrol et
            $checkStmt = $db->prepare("SELECT id FROM products WHERE sku = :sku");
            $checkStmt->execute([':sku' => $sku]);
            if ($checkStmt->fetch()) {
                $error = 'Bu SKU zaten kullanılıyor!';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO products (name, sku, barcode, price, stock, category_id, description, is_active)
                    VALUES (:name, :sku, :barcode, :price, :stock, :category_id, :description, :is_active)
                ");
                $stmt->execute([
                    ':name' => $name,
                    ':sku' => $sku,
                    ':barcode' => $barcode,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':category_id' => $category_id,
                    ':description' => $description,
                    ':is_active' => $is_active
                ]);
                
                $productId = $db->lastInsertId();
                $success = 'Ürün eklendi! <a href="?page=product-edit&id=' . $productId . '">Düzenle</a> | <a href="?page=products">Listeye dön</a>';
            }
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    }
}

// Hiyerarşik kategori ağacı
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

$categoryTree = buildCategoryTree($categories);
?>

<style>
.product-add-page {
    animation: fadeIn 0.5s;
}

.form-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    max-width: 900px;
    margin: 0 auto;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 2px solid #f0f0f0;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h3 {
    margin-bottom: 20px;
    color: #2c3e50;
    font-size: 18px;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

@media (max-width: 768px) {
    .form-grid-2, .form-grid-3 {
        grid-template-columns: 1fr;
    }
}

.input-with-icon {
    position: relative;
}

.input-with-icon i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.input-with-icon input {
    padding-left: 40px;
}

.quick-stock {
    display: flex;
    gap: 5px;
    margin-top: 8px;
}

.quick-stock button {
    padding: 5px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.quick-stock button:hover {
    background: #f0f0f0;
}
</style>

<div class="product-add-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-plus-circle"></i> Yeni Ürün Ekle</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Yeni bir ürün oluşturun</p>
        </div>
        <button class="btn btn-secondary" onclick="window.location.href='?page=products'">
            <i class="fas fa-arrow-left"></i> Ürün Listesi
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

    <div class="form-card">
        <form method="POST" action="">
            <!-- Temel Bilgiler -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Temel Bilgiler</h3>
                
                <div class="form-group">
                    <label>Ürün Adı *</label>
                    <input type="text" name="name" class="form-control" 
                           placeholder="Örn: iPhone 15 Pro Max 256GB" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                           required autofocus>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>SKU (Stok Kodu) *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-barcode"></i>
                            <input type="text" name="sku" class="form-control" 
                                   placeholder="Örn: IPH15PM256" 
                                   value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" 
                                   required>
                        </div>
                        <small>Benzersiz olmalıdır</small>
                    </div>

                    <div class="form-group">
                        <label>Barkod</label>
                        <div class="input-with-icon">
                            <i class="fas fa-qrcode"></i>
                            <input type="text" name="barcode" class="form-control" 
                                   placeholder="Örn: 1234567890123" 
                                   value="<?php echo htmlspecialchars($_POST['barcode'] ?? ''); ?>">
                        </div>
                        <small>EAN / UPC kodu</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Kategori Seç —</option>
                        <?php foreach ($categoryTree as $cat): 
                            $indent = str_repeat('— ', $cat['level']);
                        ?>
                            <option value="<?php echo $cat['id']; ?>"
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo $indent . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Kategorisiz ürünler pazaryerlerine gönderilemez</small>
                </div>
            </div>

            <!-- Fiyat ve Stok -->
            <div class="form-section">
                <h3><i class="fas fa-tag"></i> Fiyat ve Stok</h3>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Fiyat (₺) *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lira-sign"></i>

                            <input type="number" name="price" class="form-control" 
                                   step="0.01" min="0" 
                                   placeholder="0.00" 
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Stok Miktarı *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-boxes"></i>
                            <input type="number" name="stock" id="stock-input" class="form-control" 
                                   min="0" 
                                   placeholder="0" 
                                   value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>" 
                                   required>
                        </div>
                        <div class="quick-stock">
                            <button type="button" onclick="setStock(10)">+10</button>
                            <button type="button" onclick="setStock(50)">+50</button>
                            <button type="button" onclick="setStock(100)">+100</button>
                            <button type="button" onclick="setStock(0)">Sıfırla</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Açıklama -->
            <div class="form-section">
                <h3><i class="fas fa-align-left"></i> Ürün Açıklaması</h3>
                
                <div class="form-group">
                    <textarea name="description" class="form-control" rows="6" 
                              placeholder="Ürün hakkında detaylı açıklama yazın..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <small>Pazaryerlerine gönderilirken kullanılacaktır</small>
                </div>
            </div>

            <!-- Ayarlar -->
            <div class="form-section">
                <h3><i class="fas fa-cog"></i> Ayarlar</h3>
                
                <div class="form-group">
                    <label class="checkbox-row" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active" 
                               <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>
                               style="width: 18px; height: 18px;">
                        <span>Ürün aktif</span>
                    </label>
                    <small style="margin-left: 28px; color: #999;">Pasif ürünler listede gösterilmez</small>
                </div>
            </div>

            <!-- Butonlar -->
            <div style="display: flex; gap: 10px; justify-content: space-between;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Ürünü Kaydet
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=products'">
                    <i class="fas fa-times"></i> İptal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function setStock(amount) {
    const input = document.getElementById('stock-input');
    if (amount === 0) {
        input.value = 0;
    } else {
        input.value = parseInt(input.value || 0) + amount;
    }
}
</script>