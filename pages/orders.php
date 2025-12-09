<?php
// Filtreleme
$marketplace = $_GET['marketplace'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Sorgu oluştur
$where = [];
$params = [];

if ($marketplace != 'all') {
    $where[] = "o.marketplace = :marketplace";
    $params[':marketplace'] = $marketplace;
}

if ($status != 'all') {
    $where[] = "o.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where[] = "(o.order_number LIKE :search OR o.customer_name LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Toplam sipariş sayısı
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM orders o {$whereClause}");
$countStmt->execute($params);
$totalOrders = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Siparişleri getir
$params[':limit'] = $perPage;
$params[':offset'] = $offset;

$stmt = $db->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.total) as items_total
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    {$whereClause}
    GROUP BY o.id
    ORDER BY o.order_date DESC
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
$orders = $stmt->fetchAll();

// Durum sayıları
$statusCounts = [];
$statusStmt = $db->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
");
while ($row = $statusStmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<style>
.orders-page {
    animation: fadeIn 0.5s;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
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
    grid-template-columns: 200px 200px 1fr auto;
    gap: 15px;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
    font-weight: 600;
    color: #555;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.status-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.status-tab {
    padding: 10px 20px;
    background: white;
    border: 2px solid #e1e1e1;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    color: #555;
    font-size: 14px;
    font-weight: 600;
}

.status-tab:hover {
    border-color: #3498db;
    color: #3498db;
}

.status-tab.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.status-tab .count {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    margin-left: 5px;
}

.orders-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.orders-table table {
    width: 100%;
}

.orders-table thead {
    background: #f8f9fa;
}

.orders-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    font-size: 13px;
    text-transform: uppercase;
}

.orders-table td {
    padding: 15px;
    border-bottom: 1px solid #e1e1e1;
}

.orders-table tbody tr:hover {
    background: #f8f9fa;
}

.marketplace-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
}

.marketplace-badge.trendyol {
    background: #fff5f0;
    color: #f27a1a;
}

.marketplace-badge.hepsiburada {
    background: #fff4ed;
    color: #ff6000;
}

.marketplace-badge.n11 {
    background: #f8f5fb;
    color: #7c3fb7;
}

.order-number {
    font-weight: 600;
    color: #2c3e50;
    text-decoration: none;
}

.order-number:hover {
    color: #3498db;
}

.customer-info {
    font-size: 13px;
}

.customer-name {
    font-weight: 600;
    color: #2c3e50;
}

.customer-details {
    color: #7f8c8d;
    font-size: 12px;
}

.order-price {
    font-size: 16px;
    font-weight: 700;
    color: #27ae60;
}

.order-items {
    font-size: 12px;
    color: #7f8c8d;
}

.order-date {
    font-size: 13px;
    color: #555;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.Approved,
.status-badge.Created {
    background: #fff3cd;
    color: #856404;
}

.status-badge.Picking,
.status-badge.Shipped {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge.Delivered {
    background: #d4edda;
    color: #155724;
}

.status-badge.Cancelled {
    background: #f8d7da;
    color: #721c24;
}

.order-actions {
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

.btn-action:hover {
    transform: scale(1.05);
}

.btn-view {
    background: #3498db;
    color: white;
}

.btn-ship {
    background: #27ae60;
    color: white;
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

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .orders-table {
        overflow-x: auto;
    }
}
</style>

<div class="orders-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-shopping-cart"></i> Sipariş Yönetimi</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Tüm pazaryeri siparişlerinizi tek yerden yönetin</p>
        </div>
        <button class="btn btn-primary" onclick="syncAllOrders()">
            <i class="fas fa-sync"></i> Tüm Siparişleri Güncelle
        </button>
    </div>

    <!-- Durum Sekmeleri -->
    <div class="status-tabs">
        <a href="?page=orders&status=all&marketplace=<?php echo $marketplace; ?>" 
           class="status-tab <?php echo $status == 'all' ? 'active' : ''; ?>">
            Tümü <span class="count"><?php echo $totalOrders; ?></span>
        </a>
        <?php foreach ($statusCounts as $statusName => $count): ?>
            <a href="?page=orders&status=<?php echo urlencode($statusName); ?>&marketplace=<?php echo $marketplace; ?>" 
               class="status-tab <?php echo $status == $statusName ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($statusName); ?> <span class="count"><?php echo $count; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtreler -->
    <div class="filters">
        <form method="GET" action="">
            <input type="hidden" name="page" value="orders">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Pazaryeri</label>
                    <select name="marketplace" onchange="this.form.submit()">
                        <option value="all" <?php echo $marketplace == 'all' ? 'selected' : ''; ?>>Tümü</option>
                        <option value="trendyol" <?php echo $marketplace == 'trendyol' ? 'selected' : ''; ?>>Trendyol</option>
                        <option value="hepsiburada" <?php echo $marketplace == 'hepsiburada' ? 'selected' : ''; ?>>Hepsiburada</option>
                        <option value="n11" <?php echo $marketplace == 'n11' ? 'selected' : ''; ?>>N11</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Durum</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>Tümü</option>
                        <?php foreach ($statusCounts as $statusName => $count): ?>
                            <option value="<?php echo htmlspecialchars($statusName); ?>" 
                                    <?php echo $status == $statusName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($statusName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Ara</label>
                    <input type="text" name="search" placeholder="Sipariş no veya müşteri adı..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Sipariş Listesi -->
    <div class="orders-table">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Sipariş bulunamadı</h3>
                <p>Seçilen filtrelere uygun sipariş bulunmuyor.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Pazaryeri</th>
                        <th>Sipariş No</th>
                        <th>Müşteri</th>
                        <th>Ürün Sayısı</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <?php
                                $mpClass = strtolower($order['marketplace']);
                                $mpIcon = '';
                                if ($mpClass == 'trendyol') $mpIcon = 'fa-shopping-bag';
                                elseif ($mpClass == 'hepsiburada') $mpIcon = 'fa-shopping-basket';
                                elseif ($mpClass == 'n11') $mpIcon = 'fa-store-alt';
                                ?>
                                <span class="marketplace-badge <?php echo $mpClass; ?>">
                                    <i class="fas <?php echo $mpIcon; ?>"></i>
                                    <?php echo ucfirst($order['marketplace']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?page=order-detail&id=<?php echo $order['id']; ?>" class="order-number">
                                    <?php echo htmlspecialchars($order['order_number']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="customer-info">
                                    <div class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <?php if ($order['customer_phone']): ?>
                                        <div class="customer-details">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="order-items">
                                    <i class="fas fa-box"></i> <?php echo $order['item_count']; ?> ürün
                                </span>
                            </td>
                            <td>
                                <div class="order-price"><?php echo number_format($order['total_amount'], 2); ?> ₺</div>
                            </td>
                            <td>
                                <div class="order-date"><?php echo date('d.m.Y', strtotime($order['order_date'])); ?></div>
                                <div class="customer-details"><?php echo date('H:i', strtotime($order['order_date'])); ?></div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="order-actions">
                                    <button class="btn-action btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)" 
                                            title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($order['status'] == 'Approved'): ?>
                                        <button class="btn-action btn-ship" onclick="shipOrder(<?php echo $order['id']; ?>)"
                                                title="Kargoya Ver">
                                            <i class="fas fa-shipping-fast"></i>
                                        </button>
                                    <?php endif; ?>
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
                        <a href="?page=orders&p=<?php echo $page - 1; ?>&marketplace=<?php echo $marketplace; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=orders&p=<?php echo $i; ?>&marketplace=<?php echo $marketplace; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=orders&p=<?php echo $page + 1; ?>&marketplace=<?php echo $marketplace; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function viewOrder(orderId) {
    window.location.href = '?page=order-detail&id=' + orderId;
}

function shipOrder(orderId) {
    if (confirm('Bu siparişi kargoya vermek istediğinize emin misiniz?')) {
        // Kargo modal'ı açılacak
        alert('Kargo bilgileri modal sayfası hazırlanacak');
    }
}

function syncAllOrders() {
    if (!confirm('Tüm pazaryerlerinden siparişler çekilsin mi?')) {
        return;
    }
    
    const marketplaces = ['trendyol', 'hepsiburada', 'n11'];
    let completed = 0;
    
    marketplaces.forEach(marketplace => {
        fetch('ajax/sync-marketplace.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `marketplace=${marketplace}&type=orders`
        })
        .then(response => response.json())
        .then(data => {
            completed++;
            if (completed === marketplaces.length) {
                alert('Tüm siparişler güncellendi!');
                location.reload();
            }
        });
    });
}
</script>