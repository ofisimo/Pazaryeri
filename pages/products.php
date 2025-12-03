<?php
// Filtreleme
$search = $_GET['search'] ?? '';
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
}
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
    background: white;
    color: #3498db;
    border-color: #3498db;
}

.sync-btn.import:hover {
    background: #3498db;
    color: white;
}

.sync-btn.export {
    background: white;
    color: #27ae60;
    border-color: #27ae60;
}

.sync-btn.export:hover {
    background: #27ae60;
    color: white;
}

.sync-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 15px;
    align-items: end;
}

.products-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.products-table table {
    width: 100%;
}

.products-table thead {
    background: #f8f9fa;
}

.products-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    font-size: 13px;
    text-transform: uppercase;
}

.products-table td {
    padding: 15px;
    border-bottom: 1px solid #e1e1e1;
    vertical-align: middle;
}

.products-table tbody tr:hover {
    background: #f8f9fa;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
    border: 1px solid #e1e1e1;
}

.product-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
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

.pagination a:hover {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination span.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

@media (max-width: 768px) {
    .sync-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
}.product-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

#select-all {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.bulk-actions {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 5px;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}/* Modal */
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

    <!-- Senkronizasyon Butonları -->
    <div class="sync-buttons">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-sync"></i> Toplu Senkronizasyon</h3>
        <div class="sync-grid">
            <!-- OpenCart -->
            <button class="sync-btn import" onclick="syncProducts('opencart', 'import')">
                <i class="fas fa-download"></i> OpenCart'tan Çek
            </button>
            <button class="sync-btn export" onclick="syncProducts('opencart', 'export')">
                <i class="fas fa-upload"></i> OpenCart'a Gönder
            </button>
            
            <!-- Trendyol -->
            <button class="sync-btn import" onclick="syncProducts('trendyol', 'import')">
                <i class="fas fa-download"></i> Trendyol'dan Çek
            </button>
            <button class="sync-btn export" onclick="syncProducts('trendyol', 'export')">
                <i class="fas fa-upload"></i> Trendyol'a Gönder
            </button>
            
            <!-- Hepsiburada -->
            <button class="sync-btn import" onclick="syncProducts('hepsiburada', 'import')">
                <i class="fas fa-download"></i> Hepsiburada'dan Çek
            </button>
            <button class="sync-btn export" onclick="syncProducts('hepsiburada', 'export')">
                <i class="fas fa-upload"></i> Hepsiburada'ya Gönder
            </button>
            
            <!-- N11 -->
            <button class="sync-btn import" onclick="syncProducts('n11', 'import')">
                <i class="fas fa-download"></i> N11'den Çek
            </button>
            <button class="sync-btn export" onclick="syncProducts('n11', 'export')">
                <i class="fas fa-upload"></i> N11'e Gönder
            </button>
        </div>
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
    <!-- Filtreler -->
    <div class="filters">
        <form method="GET" action="">
            <input type="hidden" name="page" value="products">
            <div class="filter-row">
                <div>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Ürün adı, SKU veya barkod ile ara..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Ara
                </button>
            </div>
        </form>
    </div>

    <!-- Ürün Listesi -->
    <div class="products-table">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Henüz ürün bulunmuyor</h3>
                <p>Yukarıdaki butonları kullanarak ürün ekleyebilir veya senkronize edebilirsiniz.</p>
            </div>
        <?php else: ?>
            <table>
        <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" onclick="toggleSelectAll()">
                        </th>
                        <th>Görsel</th>
                        <th>Ürün Bilgisi</th>
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
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
                        <a href="?page=products&p=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=products&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=products&p=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
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
function syncProducts(source, direction) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    const action = direction === 'import' ? 'çekilsin' : 'gönderilsin';
    const sourceName = source.charAt(0).toUpperCase() + source.slice(1);
    
    if (!confirm(`${sourceName} ${direction === 'import' ? 'sisteminden ürünler' : 'sistemine ürünler'} ${action} mi?`)) {
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    
    fetch('ajax/sync-products.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'source=' + encodeURIComponent(source) + '&direction=' + encodeURIComponent(direction)
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
   if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            // Debug bilgisini de göster
            let msg = '✗ ' + data.message;
            if (data.debug) {
                msg += '\n\nDebug: ' + JSON.stringify(data.debug, null, 2);
            }
            alert(msg);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Senkronizasyon sırasında bir hata oluştu: ' + error);
    });
}

function editProduct(productId) {
    window.location.href = '?page=product-edit&id=' + productId;
}

function deleteProduct(productId) {
    if (!confirm('Bu ürünü silmek istediğinize emin misiniz?')) {
        return;
    }
    
    fetch('ajax/delete-product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Ürün silindi');
            location.reload();
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        alert('Silme işlemi sırasında bir hata oluştu: ' + error);
    });
}
	// Tümünü seç/kaldır
function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

// Seçili ürün sayısını güncelle
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const count = checkboxes.length;
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    
    if (count > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = count + ' ürün seçildi';
    } else {
        bulkActions.style.display = 'none';
        document.getElementById('select-all').checked = false;
    }
}

// Checkbox'lara event listener ekle
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
});

// Toplu silme
function bulkDelete() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Lütfen silmek için ürün seçin');
        return;
    }
    
    if (!confirm(`${ids.length} ürünü silmek istediğinize emin misiniz?`)) {
        return;
    }
    
    // Her ürünü sırayla sil
    let deleted = 0;
    let failed = 0;
    
    Promise.all(ids.map(id => 
        fetch('ajax/delete-product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'product_id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) deleted++;
            else failed++;
        })
        .catch(() => failed++)
    ))
    .then(() => {
        alert(`✓ ${deleted} ürün silindi${failed > 0 ? ', ' + failed + ' hata' : ''}`);
        location.reload();
    });
}

// Ürün varyantlarını modal'da göster
function showProductVariants(productId) {
    const modal = document.getElementById('variantModal');
    const modalBody = document.getElementById('modalVariantList');
    
    modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    modal.style.display = 'block';
    
    fetch('ajax/get-product-variants.php?product_id=' + productId)
    .then(r => r.json())
    .then(data => {
        if (data.success && data.variants.length > 0) {
            let html = '<table class="variant-table">';
            html += '<thead><tr>';
            html += '<th>Görsel</th>';
            html += '<th>Varyant</th>';
            html += '<th>Özellikler</th>';
            html += '<th>SKU</th>';
            html += '<th>Fiyat</th>';
            html += '<th>Stok</th>';
            html += '</tr></thead>';
            html += '<tbody>';
            
            data.variants.forEach(v => {
                html += '<tr>';
                
                // Görsel
                html += '<td>';
                if (v.image_url) {
                    html += '<img src="' + v.image_url + '" class="variant-image" alt="' + v.variant_name + '">';
                } else {
                    html += '<div class="variant-image" style="background: #e1e1e1; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image" style="color: #999;"></i></div>';
                }
                html += '</td>';
                
                // Varyant adı
                html += '<td><strong>' + v.variant_name + '</strong></td>';
                
                // Özellikler (badges)
                html += '<td><div class="variant-attributes">';
                if (v.attributes) {
                    for (let key in v.attributes) {
                        html += '<span class="variant-attr-badge">' + key + ': ' + v.attributes[key] + '</span>';
                    }
                }
                html += '</div></td>';
                
                // SKU
                html += '<td><code>' + v.sku + '</code></td>';
                
                // Fiyat
                html += '<td class="variant-price">' + parseFloat(v.price).toFixed(2) + ' ₺</td>';
                
                // Stok
                let stockClass = v.stock > 10 ? 'variant-stock-high' : 'variant-stock-low';
                html += '<td class="' + stockClass + '">' + v.stock + ' adet</td>';
                
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            modalBody.innerHTML = html;
        } else {
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;"><i class="fas fa-box-open fa-3x" style="margin-bottom: 15px;"></i><p>Bu üründe varyant bulunmuyor</p></div>';
        }
    })
    .catch(err => {
        modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #e74c3c;"><i class="fas fa-exclamation-circle fa-3x" style="margin-bottom: 15px;"></i><p>Hata: ' + err + '</p></div>';
    });
}

// Modal'ı kapat
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