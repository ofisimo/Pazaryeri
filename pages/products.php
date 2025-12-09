<?php
require_once __DIR__ . '/../includes/SyncHelper.php';
$syncHelper = new SyncHelper($db);
// Filtreleme
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? ''; // YENİ: Kategori filtresi
$marketplace = $_GET['marketplace'] ?? 'all';
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Sorgu oluştur
$where = ["p.is_active = 1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE :search OR p.sku LIKE :search OR p.barcode LIKE :search)";
    $params[':search'] = "%{$search}%";
}

// YENİ: Kategori filtresi
if (!empty($category)) {
    $where[] = "EXISTS (
        SELECT 1 FROM product_categories pc 
        WHERE pc.product_id = p.id AND pc.category_id = :category
    )";
    $params[':category'] = $category;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Toplam ürün sayısı
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM products p {$whereClause}");
$countStmt->execute($params);
$totalProducts = $countStmt->fetch()['total'];
$totalPages = ceil($totalProducts / $perPage);

// Ürünleri getir
$params[':limit'] = $perPage;
$params[':offset'] = $offset;

$stmt = $db->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM marketplace_products mp WHERE mp.product_id = p.id) as marketplace_count,
           (SELECT COUNT(*) FROM product_variants pv WHERE pv.parent_product_id = p.id) as variant_count
    FROM products p
    {$whereClause}
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    if ($key == ':limit' || $key == ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}

$stmt->execute();
$products = $stmt->fetchAll();

// Her ürün için pazaryeri durumlarını al
foreach ($products as &$product) {
    $mpStmt = $db->prepare("
        SELECT marketplace, marketplace_status, last_sync 
        FROM marketplace_products 
        WHERE product_id = :product_id
    ");
    $mpStmt->execute([':product_id' => $product['id']]);
    $product['marketplaces'] = $mpStmt->fetchAll(PDO::FETCH_GROUP);
    
    // YENİ: Kategorileri çek
    $catStmt = $db->prepare("
        SELECT c.id, c.name
        FROM categories c
        INNER JOIN product_categories pc ON c.id = pc.category_id
        WHERE pc.product_id = :product_id
        ORDER BY c.name
    ");
    $catStmt->execute([':product_id' => $product['id']]);
    $product['categories'] = $catStmt->fetchAll();
}

// YENİ: Kategori listesi (filtre için)
$categoriesStmt = $db->query("
    SELECT c.id, c.name, c.parent_id,
           COUNT(pc.product_id) as product_count
    FROM categories c
    LEFT JOIN product_categories pc ON c.id = pc.category_id
    GROUP BY c.id, c.name, c.parent_id
    HAVING product_count > 0
    ORDER BY c.name
");
$allCategories = $categoriesStmt->fetchAll();
?>

<style>
.products-page {
    animation: fadeIn 0.5s;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

/* YENİ: Kategori Filtresi Stilleri */
.filter-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 15px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.form-group input,
.form-group select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.form-group select {
    cursor: pointer;
}

.filter-button {
    padding: 10px 20px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.filter-button:hover {
    background: #2980b9;
    transform: translateY(-2px);

}

.clear-filter {
    padding: 10px 20px;
    background: #95a5a6;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    margin-left: 10px;
}

.clear-filter:hover {
    background: #7f8c8d;
}

.sync-buttons {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.sync-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.sync-btn {
    padding: 12px 20px;
    border: 2px solid;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.sync-btn.import {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.sync-btn.export {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.sync-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.search-bar input {
    flex: 1;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.search-bar button {
    padding: 12px 24px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}

.filter-tags {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-tag {
    padding: 8px 15px;
    background: #ecf0f1;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-tag:hover {
    background: #3498db;
    color: white;
}

.filter-tag.active {
    background: #3498db;
    color: white;
}

.products-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tbody tr {
    border-bottom: 1px solid #ecf0f1;
    transition: all 0.3s;
}

tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
}

td {
    padding: 15px;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e1e1e1;
}

.product-info h4 {
    margin: 0 0 8px 0;
    font-size: 15px;
    color: #2c3e50;
}

.product-info p {
    margin: 0;
    font-size: 12px;
    color: #7f8c8d;
}

.stock-badge {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
}

.stock-high {
    background: #d4edda;
    color: #155724;
}

.stock-medium {
    background: #fff3cd;
    color: #856404;
}

.stock-low {
    background: #f8d7da;
    color: #721c24;
}

/* YENİ: Kategori Badge Stilleri */
.category-badges {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.category-badge {
    padding: 4px 10px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.category-badge:hover {
    background: #1976d2;
    color: white;
    cursor: pointer;
}

.no-category {
    color: #999;
    font-style: italic;
    font-size: 12px;
}

.marketplace-icons {
    display: flex;
    gap: 5px;
    align-items: center;
}

.mp-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.mp-icon.active {
    opacity: 1;
}

.mp-icon.inactive {
    opacity: 0.3;
    filter: grayscale(100%);
}

.mp-icon.opencart {
    background: #e3f2fd;
    color: #2196f3;
}

.mp-icon.trendyol {
    background: #fff5f0;
    color: #f27a1a;
}

.mp-icon.hepsiburada {
    background: #fff4ed;
    color: #ff6000;
}

.mp-icon.n11 {
    background: #f8f5fb;
    color: #7c3fb7;
}

.mp-icon:hover {
    transform: scale(1.2);
}

.mp-icon .tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 11px;
    white-space: nowrap;
    display: none;
    margin-bottom: 5px;
}

.mp-icon:hover .tooltip {
    display: block;
}

.action-buttons {
    display: flex;
    gap: 5px;
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

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #555;
}

.pagination .active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 5px;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    animation: fadeIn 0.3s;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    border-radius: 10px;
    width: 90%;
    max-width: 900px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
    animation: slideDown 0.3s;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 20px;
}

.modal-close {
    font-size: 32px;
    font-weight: 300;
    color: #999;
    cursor: pointer;
    line-height: 1;
    transition: color 0.3s;
}

.modal-close:hover {
    color: #e74c3c;
}

.modal-body {
    padding: 30px;
    max-height: 500px;
    overflow-y: auto;
}

.variant-table {
    width: 100%;
    border-collapse: collapse;
}

.variant-table thead {
    background: #f8f9fa;
    position: sticky;
    top: 0;
}

.variant-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid #dee2e6;
    font-size: 13px;
    text-transform: uppercase;
}

.variant-table td {
    padding: 12px;
    border-bottom: 1px solid #ecf0f1;
    font-size: 14px;
}

.variant-table tbody tr:hover {
    background: #f8f9fa;
}

.variant-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #e1e1e1;
}

.variant-attributes {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.variant-attr-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.variant-stock-high {
    color: #27ae60;
    font-weight: 600;
}

.variant-stock-low {
    color: #e74c3c;
    font-weight: 600;
}

.variant-price {
    color: #27ae60;
    font-weight: 700;
    font-size: 15px;
}.sync-section {
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
}

.sync-buttons .btn {
    width: 100%;
    padding: 12px 20px;
    font-weight: 600;
}
</style>

<div class="products-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-box"></i> Ürün Yönetimi</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Tüm ürünlerinizi tek yerden yönetin</p>
        </div>
        <button class="btn btn-primary" onclick="window.location.href='?page=product-add'">
            <i class="fas fa-plus"></i> Yeni Ürün Ekle
        </button>
    </div>

    <!-- YENİ: Filtreleme Bölümü -->
    <div class="filter-section">
        <form method="GET" action="">
            <input type="hidden" name="page" value="products">
            <div class="filter-grid">
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Ürün Ara</label>
                    <input type="text" name="search" placeholder="Ürün adı, SKU veya barkod..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-folder"></i> Kategori</label>
                    <select name="category">
                        <option value="">Tüm Kategoriler</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['product_count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="filter-button">
                    <i class="fas fa-filter"></i> Filtrele
                </button>
                <?php if (!empty($search) || !empty($category)): ?>
                    <a href="?page=products" class="clear-filter">
                        <i class="fas fa-times"></i> Filtreyi Temizle
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
	
	
	
<!-- Senkronizasyon Butonları -->
<div class="sync-section">
    <h3>
        <i class="fas fa-sync-alt"></i> Senkronizasyon
        <a href="?page=platform-sync-settings" class="btn btn-sm btn-secondary" style="float: right;">
            <i class="fas fa-cog"></i> Ayarları Değiştir
        </a>
    </h3>
    
    <?php
    $source = $syncHelper->getSourcePlatform();
    $targets = $syncHelper->getTargetPlatforms();
    $hasConfig = !empty($source) && !empty($targets);
    ?>
    
    <!-- UYARI 1: Hiç ayar yapılmamış -->
    <?php if (!$hasConfig): ?>
        <div class="alert alert-warning" style="margin-top: 15px;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Platform ayarları yapılmamış!</strong>
            <p style="margin: 10px 0 0 0;">
                Ürünleri senkronize etmek için önce başlangıç platformu ve hedef platformları belirlemelisiniz.
                <a href="?page=platform-sync-settings" style="font-weight: bold;">
                    Şimdi ayarları yapın →
                </a>
            </p>
        </div>
    <?php else: ?>
        
        <!-- UYARI 2: Sadece başlangıç var, hedef yok -->
        <?php if (empty($targets)): ?>
            <div class="alert alert-info" style="margin-top: 15px;">
                <i class="fas fa-info-circle"></i>
                Başlangıç platform: <strong><?php echo ucfirst($source); ?></strong> 
                ancak henüz hedef platform seçilmemiş.
                <a href="?page=platform-sync-settings">Hedef platform ekleyin</a>
            </div>
        <?php endif; ?>
        
        <!-- Butonlar -->
        <div class="sync-buttons" style="margin-top: 15px;">
            <?php
            $platforms = [
                'opencart' => ['name' => 'OpenCart', 'icon' => 'fa-shopping-cart'],
                'trendyol' => ['name' => 'Trendyol', 'icon' => 'fa-shopping-bag'],
                'hepsiburada' => ['name' => 'Hepsiburada', 'icon' => 'fa-shopping-basket'],
                'n11' => ['name' => 'N11', 'icon' => 'fa-store-alt']
            ];
            
            foreach ($platforms as $key => $platform):
                // Çek butonu
                if ($syncHelper->canPullFrom($key)):
            ?>
                <button class="btn btn-primary" onclick="syncProducts('<?php echo $key; ?>', 'import')">
                    <i class="fas <?php echo $platform['icon']; ?>"></i>
                    <?php echo $platform['name']; ?>'tan Çek
                </button>
            <?php 
                endif;
                
                // Gönder butonu
                if ($syncHelper->canPushTo($key)):
            ?>
                <button class="btn btn-success" onclick="syncProducts('<?php echo $key; ?>', 'export')">
                    <i class="fas <?php echo $platform['icon']; ?>"></i>
                    <?php echo $platform['name']; ?>'a Gönder
                </button>
            <?php 
                endif;
            endforeach;
            ?>
        </div>
        
        <!-- Ayar Bilgisi -->
        <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 12px; color: #666;">
            <i class="fas fa-info-circle"></i>
            Başlangıç: <strong><?php echo ucfirst($source); ?></strong>
            <?php if (!empty($targets)): ?>
                → Hedefler: <strong><?php echo implode(', ', array_map('ucfirst', $targets)); ?></strong>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
</div>


    <!-- Toplu İşlemler -->
    <div class="bulk-actions" id="bulk-actions" style="display: none;">
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
            <span id="selected-count" style="font-weight: 600; color: #2c3e50;">0 ürün seçildi</span>
            <button class="btn btn-danger" onclick="bulkDelete()">
                <i class="fas fa-trash"></i> Seçilenleri Sil
            </button>
        </div>
    </div>

    <!-- Ürün Listesi -->
    <div class="products-table">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Ürün bulunamadı</h3>
                <p>Arama kriterlerinizi değiştirin veya yeni ürün ekleyin</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" onclick="toggleSelectAll()">
                        </th>
                        <th>Resim</th>
                        <th>Ürün Bilgileri</th>
                        <th>Kategoriler</th> <!-- YENİ KOLON -->
                        <th>Stok</th>
                        <th>Fiyat</th>
                        <th>Pazaryerleri</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>">
                            </td>
                            <td>
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php 
                                        // ImageHelper kullanarak URL'i düzelt
                                        if (strpos($product['image_url'], 'http') === 0) {
                                            echo htmlspecialchars($product['image_url']);
                                        } else {
                                            echo '/uploads/' . htmlspecialchars($product['image_url']);
                                        }
                                    ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="product-image" style="background: #e1e1e1; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image" style="color: #999;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-info">
                                    <h4>
                                        <a href="#" onclick="showProductVariants(<?php echo $product['id']; ?>); return false;" 
                                           style="color: #2c3e50; text-decoration: none;">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                            <?php if ($product['variant_count'] > 0): ?>
                                                <i class="fas fa-angle-down" style="font-size: 14px; color: #999;"></i>
                                            <?php endif; ?>
                                        </a>
                                    </h4>
                                    <p>
                                        <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?>
                                        <?php if ($product['barcode']): ?>
                                            | <strong>Barkod:</strong> <?php echo htmlspecialchars($product['barcode']); ?>
                                        <?php endif; ?>
                                        <?php if ($product['variant_count'] > 0): ?>
                                            <br>
                                            <span class="badge badge-info">
                                                <i class="fas fa-th"></i> <?php echo $product['variant_count']; ?> Varyant
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                            <!-- YENİ: Kategoriler Kolonu -->
                            <td>
                                <div class="category-badges">
                                    <?php if (!empty($product['categories'])): ?>
                                        <?php foreach ($product['categories'] as $cat): ?>
                                            <span class="category-badge" 
                                                  onclick="filterByCategory(<?php echo $cat['id']; ?>)" 
                                                  title="Bu kategoriye göre filtrele">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="no-category">Kategorisiz</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $stockClass = 'stock-high';
                                if ($product['stock'] <= 0) $stockClass = 'stock-low';
                                elseif ($product['stock'] < 10) $stockClass = 'stock-medium';
                                ?>
                                <span class="stock-badge <?php echo $stockClass; ?>">
                                    <?php echo $product['stock']; ?> adet
                                </span>
                            </td>
                            <td>
                                <strong style="font-size: 16px; color: #27ae60;">
                                    <?php echo number_format($product['price'], 2); ?> ₺
                                </strong>
                            </td>
                            <td>
                                <div class="marketplace-icons">
                                    <!-- OpenCart -->
                                    <div class="mp-icon opencart <?php echo $product['opencart_id'] ? 'active' : 'inactive'; ?>" 
                                         title="OpenCart">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span class="tooltip">OpenCart</span>
                                    </div>
                                    
                                    <!-- Trendyol -->
                                    <div class="mp-icon trendyol <?php echo isset($product['marketplaces']['trendyol']) ? 'active' : 'inactive'; ?>" 
                                         title="Trendyol">
                                        <i class="fas fa-shopping-bag"></i>
                                        <span class="tooltip">Trendyol</span>
                                    </div>
                                    
                                    <!-- Hepsiburada -->
                                    <div class="mp-icon hepsiburada <?php echo isset($product['marketplaces']['hepsiburada']) ? 'active' : 'inactive'; ?>" 
                                         title="Hepsiburada">
                                        <i class="fas fa-shopping-basket"></i>
                                        <span class="tooltip">Hepsiburada</span>
                                    </div>
                                    
                                    <!-- N11 -->
                                    <div class="mp-icon n11 <?php echo isset($product['marketplaces']['n11']) ? 'active' : 'inactive'; ?>" 
                                         title="N11">
                                        <i class="fas fa-store-alt"></i>
                                        <span class="tooltip">N11</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Sayfalama -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=products&p=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=products&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=products&p=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Varyant Modal -->
<div id="variantModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalProductName">Ürün Varyantları</h3>
            <span class="modal-close" onclick="closeVariantModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalVariantList">
            <!-- Varyantlar buraya gelecek -->
        </div>
    </div>
</div>

<script>
// Kategoriye göre filtreleme
function filterByCategory(categoryId) {
    window.location.href = '?page=products&category=' + categoryId;
}

// Senkronizasyon
function syncProducts(platform, direction) {
    if (!confirm(`${platform} platformu ile senkronizasyon başlatılsın mı?`)) {
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    
    fetch('pages/sync-products.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `source=${platform}&direction=${direction}`
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('✗ Hata: ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('✗ Hata: ' + error);
    });
}

// Ürün düzenle
function editProduct(id) {
    window.location.href = '?page=product-add&id=' + id;
}

// Ürün sil
function deleteProduct(id) {
    if (!confirm('Bu ürünü silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('ajax/delete-product.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ürün başarıyla silindi');
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    });
}

// Toplu seçim
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const selectAll = document.getElementById('select-all');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const checked = document.querySelectorAll('.product-checkbox:checked').length;
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    
    if (checked > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = checked + ' ürün seçildi';
    } else {
        bulkActions.style.display = 'none';
    }
}

// Checkbox değişikliklerini dinle
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });
});

// Toplu silme
function bulkDelete() {
    const checked = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
    
    if (!confirm(`${checked.length} ürünü silmek istediğinizden emin misiniz?`)) {
        return;
    }
    
    fetch('ajax/delete-product.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ids: checked })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ürünler başarıyla silindi');
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    });
}

// Varyant modalını göster (RESİMSİZ VERSİYON)
function showProductVariants(productId) {
    console.log('Varyantlar yükleniyor, Product ID:', productId);
    
    // Loading göster
    document.getElementById('modalProductName').textContent = 'Yükleniyor...';
    document.getElementById('modalVariantList').innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px;color:#3498db;"></i></div>';
    document.getElementById('variantModal').style.display = 'block';
    
    // Varyantları getir
    fetch('ajax/get-product-variants.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            console.log('Varyant data:', data);
            
            if (data.success) {
                document.getElementById('modalProductName').textContent = data.product_name + ' - Varyantlar';
                
                let html = '<table class="variant-table">';
                html += '<thead><tr>';
                html += '<th style="width: 25%;">Varyant Adı</th>';
                html += '<th style="width: 30%;">Özellikler</th>';
                html += '<th style="width: 15%;">SKU</th>';
                html += '<th style="width: 15%;">Stok</th>';
                html += '<th style="width: 15%;">Fiyat</th>';
                html += '</tr></thead>';
                html += '<tbody>';
                
                data.variants.forEach(variant => {
                    html += '<tr>';
                    
                    // Varyant Adı
                    html += '<td><strong style="color:#2c3e50;font-size:14px;">' + (variant.name || 'İsimsiz Varyant') + '</strong></td>';
                    
                    // Özellikler (options)
                    html += '<td><div class="variant-attributes">';
                    if (variant.options && Object.keys(variant.options).length > 0) {
                        for (let key in variant.options) {
                            html += '<span class="variant-attr-badge">' + key + ': ' + variant.options[key] + '</span>';
                        }
                    } else {
                        html += '<span style="color:#999;font-size:12px;">Özellik yok</span>';
                    }
                    html += '</div></td>';
                    
                    // SKU
                    html += '<td><code style="background:#f0f0f0;padding:4px 8px;border-radius:4px;font-size:12px;">' + (variant.sku || 'N/A') + '</code></td>';
                    
                    // Stok
                    let stockClass = variant.stock > 0 ? 'variant-stock-high' : 'variant-stock-low';
                    let stockIcon = variant.stock > 0 ? 'fa-check-circle' : 'fa-times-circle';
                    html += '<td class="' + stockClass + '"><i class="fas ' + stockIcon + '"></i> ' + variant.stock + ' adet</td>';
                    
                    // Fiyat
                    html += '<td class="variant-price">' + parseFloat(variant.price).toFixed(2) + ' ₺</td>';
                    
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                
                document.getElementById('modalVariantList').innerHTML = html;
            } else {
                // Hata durumu
                document.getElementById('modalProductName').textContent = 'Bilgi';
                document.getElementById('modalVariantList').innerHTML = 
                    '<div style="text-align:center;padding:40px;color:#7f8c8d;">' +
                    '<i class="fas fa-info-circle" style="font-size:48px;margin-bottom:20px;"></i>' +
                    '<p style="font-size:16px;">' + data.message + '</p>' +
                    '</div>';
            }
        })
        .catch(error => {
            console.error('Varyant yükleme hatası:', error);
            document.getElementById('modalProductName').textContent = 'Hata';
            document.getElementById('modalVariantList').innerHTML = 
                '<div style="text-align:center;padding:40px;color:#e74c3c;">' +
                '<i class="fas fa-exclamation-circle" style="font-size:48px;margin-bottom:20px;"></i>' +
                '<p style="font-size:16px;">Varyantlar yüklenirken bir hata oluştu.</p>' +
                '<p style="font-size:14px;color:#999;">' + error.message + '</p>' +
                '</div>';
        });
}

// Modalı kapat
function closeVariantModal() {
    document.getElementById('variantModal').style.display = 'none';
}

// Modal dışına tıklanınca kapat
window.onclick = function(event) {
    const modal = document.getElementById('variantModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>