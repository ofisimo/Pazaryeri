<?php
/**
 * Platform Senkronizasyon Ayarları
 * FIXED VERSION - Event listener ile çalışan
 */

$success = '';
$error = '';

// Ayarları yükle
function getSyncSetting($db, $key, $default = null) {
    $stmt = $db->prepare("SELECT setting_value FROM sync_settings WHERE setting_key = :key");
    $stmt->execute([':key' => $key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

// Ayar kaydet
function saveSyncSetting($db, $key, $value) {
    $stmt = $db->prepare("
        INSERT INTO sync_settings (setting_key, setting_value)
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE setting_value = :value2
    ");
    $stmt->execute([':key' => $key, ':value' => $value, ':value2' => $value]);
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $source_platform = $_POST['source_platform'] ?? 'opencart';
        $target_platforms = $_POST['target_platforms'] ?? [];
        $auto_sync = isset($_POST['auto_sync']) ? '1' : '0';
        $sync_images = isset($_POST['sync_images']) ? '1' : '0';
        $sync_categories = isset($_POST['sync_categories']) ? '1' : '0';
        $sync_variants = isset($_POST['sync_variants']) ? '1' : '0';
        
        // Kaydet
        saveSyncSetting($db, 'source_platform', $source_platform);
        saveSyncSetting($db, 'target_platforms', json_encode($target_platforms));
        saveSyncSetting($db, 'auto_sync', $auto_sync);
        saveSyncSetting($db, 'sync_images', $sync_images);
        saveSyncSetting($db, 'sync_categories', $sync_categories);
        saveSyncSetting($db, 'sync_variants', $sync_variants);
        
        $success = 'Platform ayarları başarıyla kaydedildi!';
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Mevcut ayarları yükle
$source_platform = getSyncSetting($db, 'source_platform', 'opencart');
$target_platforms = json_decode(getSyncSetting($db, 'target_platforms', '[]'), true);
$auto_sync = getSyncSetting($db, 'auto_sync', '0') == '1';
$sync_images = getSyncSetting($db, 'sync_images', '1') == '1';
$sync_categories = getSyncSetting($db, 'sync_categories', '1') == '1';
$sync_variants = getSyncSetting($db, 'sync_variants', '1') == '1';

// Platformları al
$platforms = [
    'opencart' => ['name' => 'OpenCart', 'icon' => 'fa-shopping-cart', 'color' => '#2196f3'],
    'trendyol' => ['name' => 'Trendyol', 'icon' => 'fa-shopping-bag', 'color' => '#f27a1a'],
    'hepsiburada' => ['name' => 'Hepsiburada', 'icon' => 'fa-shopping-basket', 'color' => '#ff6000'],
    'n11' => ['name' => 'N11', 'icon' => 'fa-store-alt', 'color' => '#7c3fb7']
];
?>

<style>
.sync-settings-page {
    animation: fadeIn 0.5s;
}

.settings-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.visual-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.platform-option {
    border: 3px solid #e1e1e1;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.platform-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.platform-option.selected {
    border-color: #27ae60;
    background: linear-gradient(135deg, #d4edda 0%, #e8f5e9 100%);
}

.platform-option.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.platform-option input[type="radio"],
.platform-option input[type="checkbox"] {
    display: none;
}

.platform-icon-large {
    font-size: 48px;
    margin-bottom: 15px;
}

.platform-icon-large.opencart { color: #2196f3; }
.platform-icon-large.trendyol { color: #f27a1a; }
.platform-icon-large.hepsiburada { color: #ff6000; }
.platform-icon-large.n11 { color: #7c3fb7; }

.platform-badge-check {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #27ae60;
    color: white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.platform-option.selected .platform-badge-check {
    display: flex;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 10px;
}

.status-badge.mapped {
    background: #27ae60;
    color: white;
}

.status-badge.not-mapped {
    background: #e74c3c;
    color: white;
}

.flow-diagram {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin: 30px 0;
    padding: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
}

.flow-box {
    background: rgba(255,255,255,0.2);
    padding: 20px;
    border-radius: 8px;
    min-width: 150px;
    text-align: center;
    border: 2px solid rgba(255,255,255,0.3);
}

.flow-arrow {
    font-size: 32px;
}

.option-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.option-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.option-card label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 600;
}

.option-card input[type="checkbox"] {
    width: 20px;
    height: 20px;
}

.info-box {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.warning-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.success-preview {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 2px solid #27ae60;
}

.button-preview {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin: 15px 0;
}

.preview-btn {
    padding: 10px 20px;
    border-radius: 6px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-btn.active {
    background: #27ae60;
    color: white;
}

.preview-btn.inactive {
    background: #e1e1e1;
    color: #999;
    text-decoration: line-through;
}
</style>

<div class="sync-settings-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-sync-alt"></i> Platform Senkronizasyon Ayarları</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Başlangıç platformu ve hedef platformları belirleyin</p>
        </div>
        <button class="btn btn-secondary" onclick="window.location.href='?page=settings'">
            <i class="fas fa-cog"></i> API Ayarları
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

    <form method="POST" id="syncForm">
        <!-- Başlangıç Platformu -->
        <div class="settings-card">
            <h3><i class="fas fa-home"></i> Başlangıç Platformu</h3>
            <p style="color: #7f8c8d; margin-bottom: 20px;">
                Ürünlerinizin ana kaynağı hangisi? Buradan diğer platformlara ürün gönderilecek.
            </p>

            <div class="visual-selector" id="sourceSelector">
                <?php foreach ($platforms as $key => $platform): ?>
                    <div class="platform-option <?php echo $source_platform == $key ? 'selected' : ''; ?>" 
                         data-platform="<?php echo $key; ?>"
                         data-type="source">
                        <input type="radio" name="source_platform" value="<?php echo $key; ?>" 
                               <?php echo $source_platform == $key ? 'checked' : ''; ?>>
                        <div class="platform-badge-check">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="platform-icon-large <?php echo $key; ?>">
                            <i class="fas <?php echo $platform['icon']; ?>"></i>
                        </div>
                        <h4 style="margin: 0;"><?php echo $platform['name']; ?></h4>
                        <p style="color: #999; font-size: 12px; margin-top: 5px;">Ana Kaynak</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hedef Platformlar -->
        <div class="settings-card">
            <h3><i class="fas fa-bullseye"></i> Hedef Platformlar</h3>
            <p style="color: #7f8c8d; margin-bottom: 20px;">
                Ürünlerinizin gönderileceği platformları seçin. Birden fazla seçebilirsiniz.
            </p>

            <div class="visual-selector" id="targetSelector">
                <?php foreach ($platforms as $key => $platform): ?>
                    <?php 
                    $isSource = ($source_platform == $key);
                    $isTarget = in_array($key, $target_platforms);
                    ?>
                    <div class="platform-option <?php echo $isTarget && !$isSource ? 'selected' : ''; ?> <?php echo $isSource ? 'disabled' : ''; ?>" 
                         data-platform="<?php echo $key; ?>"
                         data-type="target">
                        <input type="checkbox" name="target_platforms[]" value="<?php echo $key; ?>" 
                               <?php echo $isTarget ? 'checked' : ''; ?>
                               <?php echo $isSource ? 'disabled' : ''; ?>>
                        <div class="platform-badge-check">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="platform-icon-large <?php echo $key; ?>">
                            <i class="fas <?php echo $platform['icon']; ?>"></i>
                        </div>
                        <h4 style="margin: 0;"><?php echo $platform['name']; ?></h4>
                        <p style="color: #999; font-size: 12px; margin-top: 5px;">
                            <?php echo $isSource ? 'Başlangıç Platform' : 'Hedef Platform'; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($source_platform): ?>
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Not:</strong>
                Başlangıç platformu (<?php echo $platforms[$source_platform]['name']; ?>) hedef olamaz. 
                Ürünler bu platformdan diğerlerine gönderilir.
            </div>
            <?php endif; ?>
        </div>

        <!-- Akış Diyagramı -->
        <div class="flow-diagram" id="flow-diagram">
            <div class="flow-box">
                <i class="fas <?php echo $platforms[$source_platform]['icon']; ?>" style="font-size: 36px;"></i>
                <h4 style="margin: 10px 0 0 0;"><?php echo $platforms[$source_platform]['name']; ?></h4>
                <small>Başlangıç</small>
            </div>
            <div class="flow-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
            <div class="flow-box">
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <?php if (empty($target_platforms)): ?>
                        <small>Hedef platform seçilmedi</small>
                    <?php else: ?>
                        <?php foreach ($target_platforms as $target): ?>
                            <i class="fas <?php echo $platforms[$target]['icon']; ?>" style="font-size: 24px;" title="<?php echo $platforms[$target]['name']; ?>"></i>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <h4 style="margin: 10px 0 0 0;">Hedefler</h4>
                <small><?php echo count($target_platforms); ?> platform</small>
            </div>
        </div>

        <!-- Senkronizasyon Seçenekleri -->
        <div class="settings-card">
            <h3><i class="fas fa-sliders-h"></i> Senkronizasyon Seçenekleri</h3>
            <p style="color: #7f8c8d; margin-bottom: 20px;">
                Hangi verilerin senkronize edileceğini seçin
            </p>

            <div class="option-grid">
                <div class="option-card">
                    <label>
                        <input type="checkbox" name="sync_images" <?php echo $sync_images ? 'checked' : ''; ?>>
                        <div>
                            <i class="fas fa-images"></i> Resimleri Senkronize Et
                            <p style="font-size: 12px; color: #999; margin: 5px 0 0 30px;">Ürün resimlerini otomatik aktar</p>
                        </div>
                    </label>
                </div>

                <div class="option-card">
                    <label>
                        <input type="checkbox" name="sync_categories" <?php echo $sync_categories ? 'checked' : ''; ?>>
                        <div>
                            <i class="fas fa-folder-tree"></i> Kategorileri Senkronize Et
                            <p style="font-size: 12px; color: #999; margin: 5px 0 0 30px;">Kategori eşleştirmelerini kullan</p>
                        </div>
                    </label>
                </div>

                <div class="option-card">
                    <label>
                        <input type="checkbox" name="sync_variants" <?php echo $sync_variants ? 'checked' : ''; ?>>
                        <div>
                            <i class="fas fa-layer-group"></i> Varyantları Senkronize Et
                            <p style="font-size: 12px; color: #999; margin: 5px 0 0 30px;">Renk, beden gibi seçenekleri aktar</p>
                        </div>
                    </label>
                </div>

                <div class="option-card" style="border-left-color: #ffc107;">
                    <label>
                        <input type="checkbox" name="auto_sync" <?php echo $auto_sync ? 'checked' : ''; ?>>
                        <div>
                            <i class="fas fa-bolt"></i> Otomatik Senkronizasyon
                            <p style="font-size: 12px; color: #999; margin: 5px 0 0 30px;">Ürün eklenince otomatik gönder</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Buton Önizlemesi -->
        <div class="settings-card">
            <h3><i class="fas fa-eye"></i> Ürünler Sayfasında Görünecek Butonlar</h3>
            <p style="color: #7f8c8d; margin-bottom: 20px;">
                Bu ayarlara göre ürünler sayfasında şu butonlar görünecek:
            </p>

            <div class="success-preview">
                <h4 style="margin-top: 0;">Aktif Butonlar</h4>
                <div class="button-preview">
                    <!-- Başlangıç platform: Çek butonu -->
                    <div class="preview-btn active">
                        <i class="fas <?php echo $platforms[$source_platform]['icon']; ?>"></i>
                        <?php echo $platforms[$source_platform]['name']; ?>'tan Çek
                    </div>

                    <!-- Hedef platformlar: Gönder butonları -->
                    <?php foreach ($target_platforms as $target): ?>
                        <div class="preview-btn active">
                            <i class="fas <?php echo $platforms[$target]['icon']; ?>"></i>
                            <?php echo $platforms[$target]['name']; ?>'a Gönder
                        </div>
                    <?php endforeach; ?>
                </div>

                <h4 style="margin-top: 20px;">Gizli Butonlar</h4>
                <div class="button-preview">
                    <!-- Başlangıç platform: Gönder butonu gizli -->
                    <div class="preview-btn inactive">
                        <i class="fas <?php echo $platforms[$source_platform]['icon']; ?>"></i>
                        <?php echo $platforms[$source_platform]['name']; ?>'a Gönder (Gizli)
                    </div>

                    <!-- Hedef platformlar: Çek butonları gizli -->
                    <?php foreach ($target_platforms as $target): ?>
                        <div class="preview-btn inactive">
                            <i class="fas <?php echo $platforms[$target]['icon']; ?>"></i>
                            <?php echo $platforms[$target]['name']; ?>'dan Çek (Gizli)
                        </div>
                    <?php endforeach; ?>

                    <!-- Seçilmeyen platformlar: Tüm butonlar gizli -->
                    <?php foreach ($platforms as $key => $platform): ?>
                        <?php if ($key != $source_platform && !in_array($key, $target_platforms)): ?>
                            <div class="preview-btn inactive">
                                <i class="fas <?php echo $platform['icon']; ?>"></i>
                                <?php echo $platform['name']; ?> (Tüm Butonlar Gizli)
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Kaydet Butonu -->
        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                <i class="fas fa-save"></i> Ayarları Kaydet
            </button>
        </div>
    </form>
</div>

<script>
// Event Listener ile Çalışan Versiyon - GARANTİLİ ÇALIŞIR
document.addEventListener('DOMContentLoaded', function() {
    
    // Başlangıç platform seçimi
    const sourcePlatforms = document.querySelectorAll('#sourceSelector .platform-option');
    sourcePlatforms.forEach(option => {
        option.addEventListener('click', function() {
            const platform = this.getAttribute('data-platform');
            const radio = this.querySelector('input[type="radio"]');
            
            // Tüm seçimleri temizle
            sourcePlatforms.forEach(opt => opt.classList.remove('selected'));
            
            // Bu platformu seç
            this.classList.add('selected');
            radio.checked = true;
            
            // Hedef platformlardan çıkar
            const targetCheckbox = document.querySelector(`#targetSelector input[value="${platform}"]`);
            if (targetCheckbox) {
                targetCheckbox.checked = false;
                targetCheckbox.closest('.platform-option').classList.remove('selected');
            }
            
            // Disabled durumlarını güncelle
            updateDisabledStates(platform);
        });
    });
    
    // Hedef platform seçimi
    const targetPlatforms = document.querySelectorAll('#targetSelector .platform-option');
    targetPlatforms.forEach(option => {
        option.addEventListener('click', function() {
            // Disabled ise işlem yapma
            if (this.classList.contains('disabled')) {
                return;
            }
            
            const checkbox = this.querySelector('input[type="checkbox"]');
            
            // Toggle
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected');
        });
    });
    
    // Disabled durumlarını güncelle
    function updateDisabledStates(sourcePlatform) {
        targetPlatforms.forEach(option => {
            const platform = option.getAttribute('data-platform');
            const checkbox = option.querySelector('input[type="checkbox"]');
            
            if (platform === sourcePlatform) {
                option.classList.add('disabled');
                checkbox.disabled = true;
                checkbox.checked = false;
                option.classList.remove('selected');
            } else {
                option.classList.remove('disabled');
                checkbox.disabled = false;
            }
        });
    }
    
    // Sayfa yüklendiğinde disabled durumunu ayarla
    const selectedSource = document.querySelector('#sourceSelector .platform-option.selected');
    if (selectedSource) {
        const platform = selectedSource.getAttribute('data-platform');
        updateDisabledStates(platform);
    }
});
</script>