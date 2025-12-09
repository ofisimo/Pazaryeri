<?php
$success = '';
$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marketplace = $_POST['marketplace'] ?? '';
    
    if ($marketplace == 'trendyol') {
        $api_key = trim($_POST['trendyol_api_key'] ?? '');
        $api_secret = trim($_POST['trendyol_api_secret'] ?? '');
        $supplier_id = trim($_POST['trendyol_supplier_id'] ?? '');
        $is_active = isset($_POST['trendyol_active']) ? 1 : 0;
        
        try {
            // Önce var mı kontrol et
            $checkStmt = $db->prepare("SELECT id FROM marketplace_settings WHERE marketplace = 'trendyol'");
            $checkStmt->execute();
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                // Güncelle
                $stmt = $db->prepare("UPDATE marketplace_settings SET api_key = :api_key, api_secret = :api_secret, merchant_id = :supplier_id, is_active = :is_active WHERE marketplace = 'trendyol'");
            } else {
                // Yeni ekle
                $stmt = $db->prepare("INSERT INTO marketplace_settings (marketplace, api_key, api_secret, merchant_id, is_active) VALUES ('trendyol', :api_key, :api_secret, :supplier_id, :is_active)");
            }
            
            $stmt->execute([
                ':api_key' => $api_key,
                ':api_secret' => $api_secret,
                ':supplier_id' => $supplier_id,
                ':is_active' => $is_active
            ]);
            
            $success = 'Trendyol ayarları başarıyla kaydedildi!';
        } catch (PDOException $e) {
            $error = 'Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage();
        }
    }
    
    elseif ($marketplace == 'hepsiburada') {
        // HEPSİBURADA: Tek servis anahtarı - hem username hem password olarak kaydediyoruz
        $service_key = trim($_POST['hb_service_key'] ?? '');
        $merchant_id = trim($_POST['hb_merchant_id'] ?? '');
        $is_active = isset($_POST['hb_active']) ? 1 : 0;
        
        try {
            $checkStmt = $db->prepare("SELECT id FROM marketplace_settings WHERE marketplace = 'hepsiburada'");
            $checkStmt->execute();
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                // GÜNCELLEME: api_username ve api_password alanlarına aynı servis anahtarını yazıyoruz
                $stmt = $db->prepare("
                    UPDATE marketplace_settings SET 
                        api_username = :service_key,
                        api_password = :service_key,
                        merchant_id = :merchant_id,
                        is_active = :is_active 
                    WHERE marketplace = 'hepsiburada'
                ");
            } else {
                // YENİ KAYIT: api_username ve api_password alanlarına aynı servis anahtarını yazıyoruz
                $stmt = $db->prepare("
                    INSERT INTO marketplace_settings 
                    (marketplace, api_username, api_password, merchant_id, is_active) 
                    VALUES ('hepsiburada', :service_key, :service_key, :merchant_id, :is_active)
                ");
            }
            
            $stmt->execute([
                ':service_key' => $service_key,
                ':merchant_id' => $merchant_id,
                ':is_active' => $is_active
            ]);
            
            $success = 'Hepsiburada ayarları başarıyla kaydedildi!';
        } catch (PDOException $e) {
            $error = 'Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage();
        }
    }
    
    elseif ($marketplace == 'n11') {
        $api_key = trim($_POST['n11_api_key'] ?? '');
        $api_secret = trim($_POST['n11_api_secret'] ?? '');
        $is_active = isset($_POST['n11_active']) ? 1 : 0;
        
        try {
            $checkStmt = $db->prepare("SELECT id FROM marketplace_settings WHERE marketplace = 'n11'");
            $checkStmt->execute();
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                $stmt = $db->prepare("UPDATE marketplace_settings SET api_key = :api_key, api_secret = :api_secret, is_active = :is_active WHERE marketplace = 'n11'");
            } else {
                $stmt = $db->prepare("INSERT INTO marketplace_settings (marketplace, api_key, api_secret, is_active) VALUES ('n11', :api_key, :api_secret, :is_active)");
            }
            
            $stmt->execute([
                ':api_key' => $api_key,
                ':api_secret' => $api_secret,
                ':is_active' => $is_active
            ]);
            
            $success = 'N11 ayarları başarıyla kaydedildi!';
        } catch (PDOException $e) {
            $error = 'Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage();
        }
    } elseif ($marketplace == 'opencart') {
        $store_url = trim($_POST['opencart_url'] ?? '');
        $api_token = trim($_POST['opencart_token'] ?? '');
        $api_username = trim($_POST['opencart_username'] ?? '');
        $api_key = trim($_POST['opencart_key'] ?? '');
        $is_active = isset($_POST['opencart_active']) ? 1 : 0;
        
        try {
            $checkStmt = $db->prepare("SELECT id FROM opencart_settings WHERE id = 1");
            $checkStmt->execute();
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                $stmt = $db->prepare("UPDATE opencart_settings SET store_url = :store_url, api_token = :api_token, api_username = :api_username, api_key = :api_key, is_active = :is_active WHERE id = 1");
            } else {
                $stmt = $db->prepare("INSERT INTO opencart_settings (store_url, api_token, api_username, api_key, is_active) VALUES (:store_url, :api_token, :api_username, :api_key, :is_active)");
            }
            
            $stmt->execute([
                ':store_url' => $store_url,
                ':api_token' => $api_token,
                ':api_username' => $api_username,
                ':api_key' => $api_key,
                ':is_active' => $is_active
            ]);
            
            $success = 'OpenCart ayarları başarıyla kaydedildi!';
        } catch (PDOException $e) {
            $error = 'Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Mevcut ayarları çek
$settings = [];
try {
    $stmt = $db->query("SELECT * FROM marketplace_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['marketplace']] = $row;
    }
    
    // OpenCart ayarlarını da çek
    $stmt = $db->query("SELECT * FROM opencart_settings WHERE id = 1");
    $settings['opencart'] = $stmt->fetch() ?: [];
    
} catch (PDOException $e) {
    $error = 'Ayarlar yüklenirken bir hata oluştu.';
}
?>

<style>
.settings-page {
    padding: 20px;
}

.settings-header {
    margin-bottom: 30px;
}

.settings-header h2 {
    color: #333;
    margin-bottom: 5px;
}

.settings-header p {
    color: #666;
    font-size: 14px;
}

.marketplace-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #e1e1e1;
}

.tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    color: #666;
    font-size: 15px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
}

.tab-btn:hover {
    color: #667eea;
}

.tab-btn.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.settings-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-row {
    margin-bottom: 20px;
}

.form-row label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-row input[type="text"],
.form-row input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e1e1;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-row input:focus {
    outline: none;
    border-color: #667eea;
}

.form-row small {
    display: block;
    margin-top: 5px;
    color: #999;
    font-size: 12px;
}

.checkbox-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}

.checkbox-row input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-row label {
    margin: 0;
    cursor: pointer;
}

.btn-save {
    padding: 12px 30px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-save:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.btn-test {
    padding: 12px 30px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    margin-left: 10px;
    transition: all 0.3s;
}

.btn-test:hover {
    background: #218838;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
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
	/* Hepsiburada için özel stil */
.info-box {
    background: #e8f4fd;
    border-left: 4px solid #3498db;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.info-box h4 {
    margin: 0 0 10px 0;
    color: #2980b9;
    font-size: 14px;
}

.info-box ol {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    color: #555;
}

.info-box ol li {
    margin-bottom: 5px;
}

.info-box code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    color: #e74c3c;
}
</style>

<div class="settings-page">
    <div class="settings-header">
        <h2><i class="fas fa-cog"></i> Pazaryeri Ayarları</h2>
        <p>Pazaryeri API bilgilerinizi buradan yönetebilirsiniz</p>
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

    <div class="marketplace-tabs">
        <button class="tab-btn active" onclick="openTab('trendyol')">
            <i class="fas fa-shopping-bag"></i> Trendyol
            <?php if (isset($settings['trendyol']) && $settings['trendyol']['is_active']): ?>
                <span class="status-badge status-active">Aktif</span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" onclick="openTab('hepsiburada')">
            <i class="fas fa-shopping-basket"></i> Hepsiburada
            <?php if (isset($settings['hepsiburada']) && $settings['hepsiburada']['is_active']): ?>
                <span class="status-badge status-active">Aktif</span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" onclick="openTab('n11')">
            <i class="fas fa-store-alt"></i> N11
            <?php if (isset($settings['n11']) && $settings['n11']['is_active']): ?>
                <span class="status-badge status-active">Aktif</span>
            <?php endif; ?>
        </button>
		 <button class="tab-btn" onclick="openTab('opencart')">
            <i class="fas fa-shopping-cart"></i> OpenCart
            <?php if (isset($settings['opencart']) && $settings['opencart']['is_active']): ?>
                <span class="status-badge status-active">Aktif</span>
            <?php endif; ?>
        </button>
    </div>

    <!-- TRENDYOL -->
    <div id="trendyol" class="tab-content active">
        <div class="settings-card">
            <h3><i class="fas fa-shopping-bag"></i> Trendyol API Ayarları</h3>
            <form method="POST" action="">
                <input type="hidden" name="marketplace" value="trendyol">
                
                <div class="form-row">
                    <label for="trendyol_api_key">API Key</label>
                    <input type="text" id="trendyol_api_key" name="trendyol_api_key" 
                           value="<?php echo htmlspecialchars($settings['trendyol']['api_key'] ?? ''); ?>" required>
                    <small>Trendyol Seller Portal'dan alacağınız API Key</small>
                </div>

                <div class="form-row">
                    <label for="trendyol_api_secret">API Secret</label>
                    <input type="password" id="trendyol_api_secret" name="trendyol_api_secret" 
                           value="<?php echo htmlspecialchars($settings['trendyol']['api_secret'] ?? ''); ?>" required>
                    <small>Trendyol Seller Portal'dan alacağınız API Secret</small>
                </div>

                <div class="form-row">
                    <label for="trendyol_supplier_id">Supplier ID</label>
                    <input type="text" id="trendyol_supplier_id" name="trendyol_supplier_id" 
                           value="<?php echo htmlspecialchars($settings['trendyol']['merchant_id'] ?? ''); ?>" required>
                    <small>Trendyol Tedarikçi ID'niz</small>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" id="trendyol_active" name="trendyol_active" 
                           <?php echo (isset($settings['trendyol']) && $settings['trendyol']['is_active']) ? 'checked' : ''; ?>>
                    <label for="trendyol_active">Trendyol entegrasyonunu aktif et</label>
                </div>

                <div>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    <button type="button" class="btn-test" onclick="testConnection('trendyol')">
                        <i class="fas fa-plug"></i> Bağlantıyı Test Et
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- HEPSİBURADA - YENİ FORM -->
    <div id="hepsiburada" class="tab-content">
        <div class="settings-card">
            <h3><i class="fas fa-shopping-basket"></i> Hepsiburada API Ayarları</h3>
            
            <!-- Bilgilendirme kutusu -->
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Hepsiburada API Bilgilerini Nasıl Alırım?</h4>
                <ol>
                    <li><strong>merchant.hepsiburada.com</strong> adresine giriş yapın</li>
                    <li>Sağ üstte mağaza adınıza tıklayın → <strong>Bilgilerim</strong></li>
                    <li>Sol menüden <strong>Entegrasyon</strong> → <strong>Entegratör Bilgileri</strong></li>
                    <li><strong>"Yeni Entegratör Ekle"</strong> butonuna tıklayın</li>
                    <li>1-2 saat bekleyin (aktivasyon için)</li>
                    <li><strong>Mağaza ID</strong> ve <strong>Servis Anahtarı</strong>nı kopyalayın</li>
                </ol>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="marketplace" value="hepsiburada">
                
                <div class="form-row">
                    <label for="hb_service_key">
                        <i class="fas fa-key"></i> Servis Anahtarı
                    </label>
                    <input type="text" id="hb_service_key" name="hb_service_key" 
                           value="<?php echo htmlspecialchars($settings['hepsiburada']['api_username'] ?? ''); ?>" 
                           placeholder="hb_sk_xxxxxxxxxxxx" required>
                    <small>
                        <strong>Entegratör Bilgileri</strong> sayfasında "Servis Anahtarı" linkine tıklayarak alabilirsiniz
                    </small>
                </div>

                <div class="form-row">
                    <label for="hb_merchant_id">
                        <i class="fas fa-id-card"></i> Merchant ID (Mağaza ID)
                    </label>
                    <input type="text" id="hb_merchant_id" name="hb_merchant_id" 
                           value="<?php echo htmlspecialchars($settings['hepsiburada']['merchant_id'] ?? ''); ?>" 
                           placeholder="12345678" required>
                    <small>
                        <strong>Entegratör Bilgileri</strong> sayfasındaki "Mağaza ID" numaranız
                    </small>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" id="hb_active" name="hb_active" 
                           <?php echo (isset($settings['hepsiburada']) && $settings['hepsiburada']['is_active']) ? 'checked' : ''; ?>>
                    <label for="hb_active">Hepsiburada entegrasyonunu aktif et</label>
                </div>

                <div>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    <button type="button" class="btn-test" onclick="testConnection('hepsiburada')">
                        <i class="fas fa-plug"></i> Bağlantıyı Test Et
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- N11 -->
    <div id="n11" class="tab-content">
        <div class="settings-card">
            <h3><i class="fas fa-store-alt"></i> N11 API Ayarları</h3>
            <form method="POST" action="">
                <input type="hidden" name="marketplace" value="n11">
                
                <div class="form-row">
                    <label for="n11_api_key">API Key</label>
                    <input type="text" id="n11_api_key" name="n11_api_key" 
                           value="<?php echo htmlspecialchars($settings['n11']['api_key'] ?? ''); ?>" required>
                    <small>N11 Mağaza Yönetim Paneli'nden alacağınız API Key</small>
                </div>

                <div class="form-row">
                    <label for="n11_api_secret">API Secret</label>
                    <input type="password" id="n11_api_secret" name="n11_api_secret" 
                           value="<?php echo htmlspecialchars($settings['n11']['api_secret'] ?? ''); ?>" required>
                    <small>N11 Mağaza Yönetim Paneli'nden alacağınız API Secret</small>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" id="n11_active" name="n11_active" 
                           <?php echo (isset($settings['n11']) && $settings['n11']['is_active']) ? 'checked' : ''; ?>>
                    <label for="n11_active">N11 entegrasyonunu aktif et</label>
                </div>

                <div>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    <button type="button" class="btn-test" onclick="testConnection('n11')">
                        <i class="fas fa-plug"></i> Bağlantıyı Test Et
                    </button>
                </div>
            </form>
        </div>
    </div>
	
	
	
<!-- OPENCART -->
    <div id="opencart" class="tab-content">
        <div class="settings-card">
            <h3><i class="fas fa-shopping-cart"></i> OpenCart API Ayarları</h3>
            <form method="POST" action="">
                <input type="hidden" name="marketplace" value="opencart">
                
                <div class="form-row">
                    <label for="opencart_url">OpenCart Site URL</label>
                    <input type="text" id="opencart_url" name="opencart_url" 
                           value="<?php echo htmlspecialchars($settings['opencart']['store_url'] ?? ''); ?>" 
                           placeholder="https://siteniz.com" required>
                    <small>OpenCart mağazanızın tam adresi (https:// ile)</small>
                </div>

                <div class="form-row">
                    <label for="opencart_token">API Token</label>
                    <input type="text" id="opencart_token" name="opencart_token" 
                           value="<?php echo htmlspecialchars($settings['opencart']['api_token'] ?? ''); ?>">
                    <small>OpenCart API token (opsiyonel)</small>
                </div>

                <div class="form-row">
                    <label for="opencart_username">API Username</label>
                    <input type="text" id="opencart_username" name="opencart_username" 
                           value="<?php echo htmlspecialchars($settings['opencart']['api_username'] ?? ''); ?>">
                    <small>API kullanıcı adı (opsiyonel)</small>
                </div>

                <div class="form-row">
                    <label for="opencart_key">API Key</label>
                    <input type="password" id="opencart_key" name="opencart_key" 
                           value="<?php echo htmlspecialchars($settings['opencart']['api_key'] ?? ''); ?>">
                    <small>API anahtarı (opsiyonel)</small>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" id="opencart_active" name="opencart_active" 
                           <?php echo (isset($settings['opencart']) && $settings['opencart']['is_active']) ? 'checked' : ''; ?>>
                    <label for="opencart_active">OpenCart entegrasyonunu aktif et</label>
                </div>

                <div>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    <button type="button" class="btn-test" onclick="testConnection('opencart')">
                        <i class="fas fa-plug"></i> Bağlantıyı Test Et
                    </button>
                </div>
            </form>
        </div>
    </div>
	
	
</div>

<script>
function openTab(tabName) {
    // Tüm tabları gizle
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => tab.classList.remove('active'));
    
    // Tüm butonların active sınıfını kaldır
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));
    
    // Seçili tabı göster
    document.getElementById(tabName).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
}

function testConnection(marketplace) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test ediliyor...';
    
    fetch('ajax/test-connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'marketplace=' + marketplace
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✓ ' + data.message + '\n' + (data.data || ''));
        } else {
            alert('✗ ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Bağlantı testi sırasında bir hata oluştu: ' + error);
    });
}
</script>