<?php
// İstatistikleri çek
$stats = [
    'total_products' => 0,
    'total_orders' => 0,
    'pending_orders' => 0,
    'today_orders' => 0,
    'marketplaces' => [
        'trendyol' => ['products' => 0, 'orders' => 0, 'pending' => 0, 'active' => false],
        'hepsiburada' => ['products' => 0, 'orders' => 0, 'pending' => 0, 'active' => false],
        'n11' => ['products' => 0, 'orders' => 0, 'pending' => 0, 'active' => false]
    ]
];

try {
    // Toplam ürün sayısı
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $stats['total_products'] = $stmt->fetch()['count'];
    
    // Toplam sipariş sayısı
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $stmt->fetch()['count'];
    
    // Bekleyen siparişler
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('Approved', 'Created', 'Picking')");
    $stats['pending_orders'] = $stmt->fetch()['count'];
    
    // Bugünkü siparişler
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['today_orders'] = $stmt->fetch()['count'];
    
    // Pazaryeri bazlı istatistikler
    foreach (['trendyol', 'hepsiburada', 'n11'] as $marketplace) {
        // Aktif mi kontrol et
        $stmt = $db->prepare("SELECT is_active FROM marketplace_settings WHERE marketplace = :marketplace");
        $stmt->execute([':marketplace' => $marketplace]);
        $setting = $stmt->fetch();
        $stats['marketplaces'][$marketplace]['active'] = $setting && $setting['is_active'];
        
        // Ürün sayısı
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM marketplace_products WHERE marketplace = :marketplace");
        $stmt->execute([':marketplace' => $marketplace]);
        $stats['marketplaces'][$marketplace]['products'] = $stmt->fetch()['count'];
        
        // Sipariş sayısı
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE marketplace = :marketplace");
        $stmt->execute([':marketplace' => $marketplace]);
        $stats['marketplaces'][$marketplace]['orders'] = $stmt->fetch()['count'];
        
        // Bekleyen siparişler
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE marketplace = :marketplace AND status IN ('Approved', 'Created', 'Picking')");
        $stmt->execute([':marketplace' => $marketplace]);
        $stats['marketplaces'][$marketplace]['pending'] = $stmt->fetch()['count'];
    }
    
    // Son 7 gün sipariş grafiği için veri
    $stmt = $db->query("
        SELECT DATE(order_date) as date, COUNT(*) as count 
        FROM orders 
        WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ");
    $chartData = $stmt->fetchAll();
    
    // Son siparişler
    $stmt = $db->query("
        SELECT o.*, 
               (SELECT SUM(total) FROM order_items WHERE order_id = o.id) as items_total
        FROM orders o 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll();
    
    // Son senkronizasyon logları
    $stmt = $db->query("
        SELECT * FROM sync_logs 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentLogs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "İstatistikler yüklenirken hata oluştu: " . $e->getMessage();
}
?>

<style>
.dashboard {
    animation: fadeIn 0.5s;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-icon.blue { background: #e3f2fd; color: #2196f3; }
.stat-icon.green { background: #e8f5e9; color: #4caf50; }
.stat-icon.orange { background: #fff3e0; color: #ff9800; }
.stat-icon.red { background: #ffebee; color: #f44336; }

.stat-details h4 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
    color: #2c3e50;
}

.stat-details p {
    color: #7f8c8d;
    font-size: 14px;
    margin: 0;
}

.marketplace-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.marketplace-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s;
}

.marketplace-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.marketplace-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px solid;
}

.marketplace-header.trendyol { border-color: #f27a1a; background: linear-gradient(135deg, #fff 0%, #fff5f0 100%); }
.marketplace-header.hepsiburada { border-color: #ff6000; background: linear-gradient(135deg, #fff 0%, #fff4ed 100%); }
.marketplace-header.n11 { border-color: #7c3fb7; background: linear-gradient(135deg, #fff 0%, #f8f5fb 100%); }

.marketplace-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.marketplace-title i {
    font-size: 24px;
}

.marketplace-title.trendyol i { color: #f27a1a; }
.marketplace-title.hepsiburada i { color: #ff6000; }
.marketplace-title.n11 i { color: #7c3fb7; }

.marketplace-title h3 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.marketplace-body {
    padding: 20px;
}

.marketplace-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.mini-stat {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.mini-stat .number {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
}

.mini-stat .label {
    font-size: 12px;
    color: #7f8c8d;
    text-transform: uppercase;
}

.mini-stat .icon {
    font-size: 16px;
    margin-bottom: 5px;
    opacity: 0.7;
}

.marketplace-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.btn-sync {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-sync.trendyol { background: #f27a1a; color: white; }
.btn-sync.hepsiburada { background: #ff6000; color: white; }
.btn-sync.n11 { background: #7c3fb7; color: white; }

.btn-sync:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

.btn-sync:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.recent-orders {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
}

.recent-orders h3 {
    margin-bottom: 20px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-item {
    padding: 15px;
    border: 1px solid #e1e1e1;
    border-radius: 5px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.order-item:hover {
    background: #f8f9fa;
    border-color: #3498db;
}

.order-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #2c3e50;
}

.order-info p {
    margin: 0;
    font-size: 12px;
    color: #7f8c8d;
}

.order-meta {
    text-align: right;
}

.order-price {
    font-size: 16px;
    font-weight: 700;
    color: #27ae60;
    margin-bottom: 5px;
}

.sync-logs {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
}

.sync-logs h3 {
    margin-bottom: 20px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.log-item {
    padding: 12px;
    border-left: 4px solid;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 10px;
    font-size: 13px;
}

.log-item.success { border-color: #27ae60; }
.log-item.error { border-color: #e74c3c; }
.log-item.warning { border-color: #f39c12; }
.log-item.info { border-color: #3498db; }

.log-marketplace {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    color: #7f8c8d;
}

.log-time {
    font-size: 11px;
    color: #95a5a6;
    margin-top: 5px;
}

@media (max-width: 1200px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .marketplace-grid {
        grid-template-columns: 1fr;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .marketplace-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard">
    <div class="page-header" style="margin-bottom: 30px;">
        <h2><i class="fas fa-chart-line"></i> Dashboard</h2>
        <p style="color: #7f8c8d; margin-top: 5px;">Genel istatistikler ve son işlemler</p>
    </div>

    <!-- Genel İstatistikler -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-details">
                <h4><?php echo number_format($stats['total_products']); ?></h4>
                <p>Toplam Ürün</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-details">
                <h4><?php echo number_format($stats['total_orders']); ?></h4>
                <p>Toplam Sipariş</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h4><?php echo number_format($stats['pending_orders']); ?></h4>
                <p>Bekleyen Sipariş</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-details">
                <h4><?php echo number_format($stats['today_orders']); ?></h4>
                <p>Bugünkü Sipariş</p>
            </div>
        </div>
    </div>

    <!-- Pazaryeri Kartları -->
    <div class="marketplace-grid">
        <!-- Trendyol -->
        <div class="marketplace-card">
            <div class="marketplace-header trendyol">
                <div class="marketplace-title trendyol">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Trendyol</h3>
                </div>
                <span class="status-badge <?php echo $stats['marketplaces']['trendyol']['active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $stats['marketplaces']['trendyol']['active'] ? 'Aktif' : 'Pasif'; ?>
                </span>
            </div>
            <div class="marketplace-body">
                <div class="marketplace-stats">
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-box"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['trendyol']['products']); ?></div>
                        <div class="label">Ürün</div>
                    </div>
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['trendyol']['orders']); ?></div>
                        <div class="label">Sipariş</div>
                    </div>
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['trendyol']['pending']); ?></div>
                        <div class="label">Bekleyen</div>
                    </div>
                </div>
                <div class="marketplace-actions">
                    <button class="btn-sync trendyol" onclick="syncMarketplace('trendyol', 'products')" <?php echo !$stats['marketplaces']['trendyol']['active'] ? 'disabled' : ''; ?>>
                        <i class="fas fa-sync"></i> Ürün Senkronize Et
                    </button>
                    <button class="btn-sync trendyol" onclick="syncMarketplace('trendyol', 'orders')" <?php echo !$stats['marketplaces']['trendyol']['active'] ? 'disabled' : ''; ?>>
                        <i class="fas fa-download"></i> Sipariş Çek
                    </button>
                </div>
            </div>
        </div>

        <!-- Hepsiburada -->
        <div class="marketplace-card">
            <div class="marketplace-header hepsiburada">
                <div class="marketplace-title hepsiburada">
                    <i class="fas fa-shopping-basket"></i>
                    <h3>Hepsiburada</h3>
                </div>
                <span class="status-badge <?php echo $stats['marketplaces']['hepsiburada']['active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $stats['marketplaces']['hepsiburada']['active'] ? 'Aktif' : 'Pasif'; ?>
                </span>
            </div>
            <div class="marketplace-body">
                <div class="marketplace-stats">
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-box"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['hepsiburada']['products']); ?></div>
                        <div class="label">Ürün</div>
                    </div>
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['hepsiburada']['orders']); ?></div>
                        <div class="label">Sipariş</div>
                    </div>
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['hepsiburada']['pending']); ?></div>
                        <div class="label">Bekleyen</div>
                    </div>
                </div>
                <div class="marketplace-actions">
                    <button class="btn-sync hepsiburada" onclick="syncMarketplace('hepsiburada', 'products')" <?php echo !$stats['marketplaces']['hepsiburada']['active'] ? 'disabled' : ''; ?>>
                        <i class="fas fa-sync"></i> Ürün Senkronize Et
                    </button>
                    <button class="btn-sync hepsiburada" onclick="syncMarketplace('hepsiburada', 'orders')" <?php echo !$stats['marketplaces']['hepsiburada']['active'] ? 'disabled' : ''; ?>>
                        <i class="fas fa-download"></i> Sipariş Çek
                    </button>
                </div>
            </div>
        </div>

        <!-- N11 -->
        <div class="marketplace-card">
            <div class="marketplace-header n11">
                <div class="marketplace-title n11">
                    <i class="fas fa-store-alt"></i>
                    <h3>N11</h3>
                </div>
                <span class="status-badge <?php echo $stats['marketplaces']['n11']['active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $stats['marketplaces']['n11']['active'] ? 'Aktif' : 'Pasif'; ?>
                </span>
            </div>
            <div class="marketplace-body">
                <div class="marketplace-stats">
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-box"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['n11']['products']); ?></div>
                        <div class="label">Ürün</div>
                    </div>
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['n11']['orders']); ?></div>
                        <div class="label">Sipariş</div>
                    </div>
                    <div class="mini-stat">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <div class="number"><?php echo number_format($stats['marketplaces']['n11']['pending']); ?></div>
                        <div class="label">Bekleyen</div>
                    </div>
                </div>
                <div class="marketplace-actions">
                    <button class="btn-sync n11" onclick="syncMarketplace('n11', 'products')" <?php echo !$stats['marketplaces']['n11']['active'] ? 'disabled' : ''; ?>>
                        <i class="fas fa-sync"></i> Ürün Senkronize Et
                    </button>
                    <button class="btn-sync n11" onclick="syncMarketplace('n11', 'orders')" <?php echo !$stats['marketplaces']['n11']['active'] ? 'disabled' : ''; ?>>
                        <i class="fas fa-download"></i> Sipariş Çek
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alt Bölüm -->
    <div class="content-grid">
        <!-- Son Siparişler -->
        <div class="recent-orders">
            <h3><i class="fas fa-shopping-cart"></i> Son Siparişler</h3>
            <?php if (empty($recentOrders)): ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">Henüz sipariş bulunmuyor</p>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <h4>
                                <?php 
                                $icon = '';
                                if ($order['marketplace'] == 'trendyol') $icon = 'fa-shopping-bag';
                                elseif ($order['marketplace'] == 'hepsiburada') $icon = 'fa-shopping-basket';
                                elseif ($order['marketplace'] == 'n11') $icon = 'fa-store-alt';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </h4>
                            <p>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong> - 
                                <?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?>
                            </p>
                        </div>
                        <div class="order-meta">
                            <div class="order-price"><?php echo number_format($order['total_amount'], 2); ?> ₺</div>
                            <span class="badge badge-<?php echo $order['status'] == 'Approved' ? 'success' : 'warning'; ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Senkronizasyon Logları -->
        <div class="sync-logs">
            <h3><i class="fas fa-history"></i> Son İşlemler</h3>
            <?php if (empty($recentLogs)): ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">Henüz log bulunmuyor</p>
            <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                    <div class="log-item <?php echo $log['status']; ?>">
                        <div class="log-marketplace"><?php echo strtoupper($log['marketplace']); ?></div>
                        <div><?php echo htmlspecialchars($log['message']); ?></div>
                        <div class="log-time"><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function syncMarketplace(marketplace, type) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    if (!confirm(`${marketplace.toUpperCase()} ${type === 'products' ? 'ürünleri' : 'siparişleri'} senkronize edilsin mi?`)) {
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    
    fetch('ajax/sync-marketplace.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `marketplace=${marketplace}&type=${type}`
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Senkronizasyon sırasında bir hata oluştu: ' + error);
    });
}
</script>