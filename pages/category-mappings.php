<?php
/**
 * Gelişmiş Kategori Eşleştirme Sayfası - SYNTAX FIXED
 * Otomatik öneriler, isim benzerliği, toplu eşleştirme
 */

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    header('Location: ?page=categories');
    exit;
}

// Kategori bilgisini getir
$stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute([':id' => $category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: ?page=categories');
    exit;
}

$success = '';
$error = '';

// Eşleştirme kaydetme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'save_mapping') {
        $platform = $_POST['platform'] ?? '';
        $platform_category_id = trim($_POST['platform_category_id'] ?? '');
        $platform_category_name = trim($_POST['platform_category_name'] ?? '');
        
        if ($platform && $platform_category_id && $platform_category_name) {
            try {
                // Önce mevcut eşleştirmeyi kontrol et
                $stmt = $db->prepare("
                    SELECT id FROM category_mappings 
                    WHERE category_id = :category_id AND platform = :platform
                ");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':platform' => $platform
                ]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Güncelle
                    $stmt = $db->prepare("
                        UPDATE category_mappings 
                        SET platform_category_id = :platform_id,
                            platform_category_name = :platform_name
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':platform_id' => $platform_category_id,
                        ':platform_name' => $platform_category_name,
                        ':id' => $existing['id']
                    ]);
                } else {
                    // Yeni ekle
                    $stmt = $db->prepare("
                        INSERT INTO category_mappings (category_id, platform, platform_category_id, platform_category_name)
                        VALUES (:category_id, :platform, :platform_id, :platform_name)
                    ");
                    $stmt->execute([
                        ':category_id' => $category_id,
                        ':platform' => $platform,
                        ':platform_id' => $platform_category_id,
                        ':platform_name' => $platform_category_name
                    ]);
                }
                
                $success = 'Eşleştirme kaydedildi';
            } catch (Exception $e) {
                $error = 'Hata: ' . $e->getMessage();
            }
        } else {
            $error = 'Tüm alanları doldurun';
        }
    }
    
    // Otomatik eşleştirme
    elseif ($action == 'auto_match') {
        $platform = $_POST['platform'] ?? '';
        
        if ($platform) {
            try {
                // Platform kategorilerini çek
                require_once __DIR__ . '/../api/OpencartAPI.php';
                
                $platformCategories = [];
                
                if ($platform == 'opencart') {
                    $api = new OpencartAPI();
                    if ($api->isActive()) {
                        $response = $api->getCategories();
                        $platformCategories = $response['categories'] ?? [];
                    }
                }
                // Diğer platformlar için API çağrıları eklenebilir
                
                // İsim benzerliğine göre en uygun kategoriyi bul
                $bestMatch = null;
                $bestScore = 0;
                
                foreach ($platformCategories as $pCat) {
                    $score = similarity($category['name'], $pCat['name']);
                    if ($score > $bestScore && $score > 0.6) { // %60 benzerlik eşiği
                        $bestScore = $score;
                        $bestMatch = $pCat;
                    }
                }
                
                if ($bestMatch) {
                    // Eşleştirmeyi kaydet
                    $stmt = $db->prepare("
                        INSERT INTO category_mappings (category_id, platform, platform_category_id, platform_category_name)
                        VALUES (:category_id, :platform, :platform_id, :platform_name)
                        ON DUPLICATE KEY UPDATE 
                            platform_category_id = :platform_id2,
                            platform_category_name = :platform_name2
                    ");
                    $stmt->execute([
                        ':category_id' => $category_id,
                        ':platform' => $platform,
                        ':platform_id' => $bestMatch['category_id'],
                        ':platform_name' => $bestMatch['name'],
                        ':platform_id2' => $bestMatch['category_id'],
                        ':platform_name2' => $bestMatch['name']
                    ]);
                    
                    // FIXED: Önce hesaplama yap, sonra string'e ekle
                    $percentage = round($bestScore * 100);
                    $success = "Otomatik eşleştirme yapıldı: {$bestMatch['name']} (%{$percentage} benzerlik)";
                } else {
                    $error = 'Uygun eşleşme bulunamadı. Manuel olarak eşleştirin.';
                }
            } catch (Exception $e) {
                $error = 'Hata: ' . $e->getMessage();
            }
        }
    }
}

// Silme
if (isset($_GET['delete_mapping'])) {
    $mapping_id = (int)$_GET['delete_mapping'];
    try {
        $stmt = $db->prepare("DELETE FROM category_mappings WHERE id = :id AND category_id = :category_id");
        $stmt->execute([':id' => $mapping_id, ':category_id' => $category_id]);
        $success = 'Eşleştirme silindi';
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Mevcut eşleştirmeleri getir
$stmt = $db->prepare("SELECT * FROM category_mappings WHERE category_id = :id");
$stmt->execute([':id' => $category_id]);
$mappings = $stmt->fetchAll();

// String benzerliği hesaplama fonksiyonu
function similarity($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));
    
    // Levenshtein mesafesi
    $lev = levenshtein($str1, $str2);
    $maxLen = max(strlen($str1), strlen($str2));
    
    if ($maxLen == 0) return 1.0;
    
    return 1.0 - ($lev / $maxLen);
}
?>

<style>
.mapping-page {
    animation: fadeIn 0.5s;
}

.mapping-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.platform-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.platform-card {
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
}

.platform-card:hover {
    border-color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.platform-card.mapped {
    border-color: #27ae60;
    background: #d4edda;
}

.platform-card.not-mapped {
    border-color: #e74c3c;
    background: #f8d7da;
}

.platform-icon {
    font-size: 36px;
    margin-bottom: 10px;
}

.platform-icon.opencart { color: #2196f3; }
.platform-icon.trendyol { color: #f27a1a; }
.platform-icon.hepsiburada { color: #ff6000; }
.platform-icon.n11 { color: #7c3fb7; }

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

.suggestion-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin: 20px 0;
    border-radius: 5px;
}

.suggestion-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: white;
    border-radius: 5px;
    margin-bottom: 10px;
}

.similarity-badge {
    background: #3498db;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.mapping-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
}

.mapping-info {
    flex: 1;
}

.platform-badge-large {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 15px;
    font-weight: 600;
    margin-bottom: 5px;
}

.mapping-badge.opencart { background: #e3f2fd; color: #2196f3; }
.mapping-badge.trendyol { background: #fff5f0; color: #f27a1a; }
.mapping-badge.hepsiburada { background: #fff4ed; color: #ff6000; }
.mapping-badge.n11 { background: #f8f5fb; color: #7c3fb7; }

.quick-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.category-select-container {
    position: relative;
    margin: 20px 0;
}

.category-dropdown {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    font-size: 14px;
}

.category-dropdown:focus {
    outline: none;
    border-color: #3498db;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    background: #667eea;
    color: white;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.modal-close:hover {
    color: #ddd;
}

.modal-body {
    padding: 20px;
}
</style>

<div class="mapping-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-link"></i> Kategori Eşleştirme</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">
                <strong><?php echo htmlspecialchars($category['name']); ?></strong> kategorisini pazaryerleriyle eşleştirin
            </p>
        </div>
        <button class="btn btn-secondary" onclick="window.location.href='?page=categories'">
            <i class="fas fa-arrow-left"></i> Geri Dön
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

    <!-- Platform Durumu -->
    <div class="mapping-card">
        <h3><i class="fas fa-chart-pie"></i> Platform Eşleştirme Durumu</h3>
        
        <div class="platform-grid">
            <?php
            $platforms = ['opencart', 'trendyol', 'hepsiburada', 'n11'];
            $icons = [
                'opencart' => 'fa-shopping-cart',
                'trendyol' => 'fa-shopping-bag',
                'hepsiburada' => 'fa-shopping-basket',
                'n11' => 'fa-store-alt'
            ];
            
            foreach ($platforms as $platform) {
                $isMapped = false;
                $mappedName = '';
                
                foreach ($mappings as $mapping) {
                    if ($mapping['platform'] == $platform) {
                        $isMapped = true;
                        $mappedName = $mapping['platform_category_name'];
                        break;
                    }
                }
                
                $cardClass = $isMapped ? 'mapped' : 'not-mapped';
                ?>
                <div class="platform-card <?php echo $cardClass; ?>" onclick="openMappingModal('<?php echo $platform; ?>')">
                    <div class="platform-icon <?php echo $platform; ?>">
                        <i class="fas <?php echo $icons[$platform]; ?>"></i>
                    </div>
                    <h4><?php echo ucfirst($platform); ?></h4>
                    <?php if ($isMapped): ?>
                        <span class="status-badge mapped">✓ Eşleştirilmiş</span>
                        <p style="font-size: 12px; color: #666; margin-top: 8px;">
                            <?php echo htmlspecialchars($mappedName); ?>
                        </p>
                    <?php else: ?>
                        <span class="status-badge not-mapped">✗ Eşleştirilmemiş</span>
                    <?php endif; ?>
                    
                    <div class="quick-actions">
                        <?php if (!$isMapped): ?>
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); autoMatch('<?php echo $platform; ?>')">
                                <i class="fas fa-magic"></i> Otomatik
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openMappingModal('<?php echo $platform; ?>')">
                            <i class="fas fa-edit"></i> Manuel
                        </button>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <!-- Mevcut Eşleştirmeler -->
    <?php if (!empty($mappings)): ?>
    <div class="mapping-card">
        <h3><i class="fas fa-list"></i> Mevcut Eşleştirmeler</h3>
        
        <div class="mapping-list">
            <?php foreach ($mappings as $mapping): ?>
                <div class="mapping-item">
                    <div class="mapping-info">
                        <span class="platform-badge-large mapping-badge <?php echo $mapping['platform']; ?>">
                            <?php echo ucfirst($mapping['platform']); ?>
                        </span>
                        <div style="margin-top: 8px;">
                            <strong><?php echo htmlspecialchars($mapping['platform_category_name']); ?></strong>
                            <br>
                            <small style="color: #999;">ID: <?php echo htmlspecialchars($mapping['platform_category_id']); ?></small>
                        </div>
                    </div>
                    <div>
                        <button class="btn btn-warning btn-sm" onclick="openMappingModal('<?php echo $mapping['platform']; ?>')" style="margin-right: 5px;">
                            <i class="fas fa-edit"></i> Düzenle
                        </button>
                        <a href="?page=category-mappings&id=<?php echo $category_id; ?>&delete_mapping=<?php echo $mapping['id']; ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Bu eşleştirmeyi silmek istediğinize emin misiniz?')">
                            <i class="fas fa-trash"></i> Sil
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Eşleştirme İpucu -->
    <div class="mapping-card" style="background: #e3f2fd; border-left: 4px solid #2196f3;">
        <h4 style="margin-top: 0;"><i class="fas fa-info-circle"></i> Eşleştirme Nasıl Çalışır?</h4>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Otomatik:</strong> Sistem, kategori isimlerine göre en uygun eşleşmeyi bulur</li>
            <li><strong>Manuel:</strong> Platform kategorilerinden kendiniz seçersiniz</li>
            <li><strong>Ürün Gönderimi:</strong> Ürünler gönderilirken eşleştirilen kategoriler kullanılır</li>
            <li><strong>Çift Yönlü:</strong> Hem gönderim hem çekim sırasında çalışır</li>
        </ul>
    </div>
</div>

<!-- Eşleştirme Modal -->
<div id="mappingModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Kategori Eşleştir</h3>
            <span class="modal-close" onclick="closeMappingModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_mapping">
                <input type="hidden" name="platform" id="modal-platform">
                
                <div class="category-select-container">
                    <label><strong id="platform-label">Platform</strong> Kategorisi Seçin:</label>
                    <select name="platform_category_select" id="platform-category-select" class="category-dropdown" onchange="updateHiddenFields()">
                        <option value="">Kategori seçin...</option>
                    </select>
                </div>
                
                <input type="hidden" name="platform_category_id" id="modal-category-id">
                <input type="hidden" name="platform_category_name" id="modal-category-name">
                
                <div id="loading-message" style="display: none; padding: 20px; text-align: center;">
                    <i class="fas fa-spinner fa-spin"></i> Kategoriler yükleniyor...
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" id="save-button">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeMappingModal()">
                        İptal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openMappingModal(platform) {
    document.getElementById('mappingModal').style.display = 'block';
    document.getElementById('modal-platform').value = platform;
    document.getElementById('modalTitle').textContent = platform.charAt(0).toUpperCase() + platform.slice(1) + ' Kategori Eşleştir';
    document.getElementById('platform-label').textContent = platform.charAt(0).toUpperCase() + platform.slice(1);
    
    loadPlatformCategories(platform);
}

function closeMappingModal() {
    document.getElementById('mappingModal').style.display = 'none';
}

function loadPlatformCategories(platform) {
    const select = document.getElementById('platform-category-select');
    const loading = document.getElementById('loading-message');
    const saveButton = document.getElementById('save-button');
    
    select.style.display = 'none';
    loading.style.display = 'block';
    saveButton.disabled = true;
    
    fetch('get-platform-categories.php?platform=' + platform)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Kategori seçin...</option>';
            
            if (data.success && data.categories) {
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = JSON.stringify({id: cat.category_id || cat.id, name: cat.name});
                    option.textContent = cat.name + ' (ID: ' + (cat.category_id || cat.id) + ')';
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">Kategori bulunamadı</option>';
            }
            
            loading.style.display = 'none';
            select.style.display = 'block';
            saveButton.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            loading.style.display = 'none';
            select.style.display = 'block';
            select.innerHTML = '<option value="">Hata oluştu</option>';
            saveButton.disabled = false;
        });
}

function updateHiddenFields() {
    const select = document.getElementById('platform-category-select');
    const selectedValue = select.value;
    
    if (selectedValue) {
        const data = JSON.parse(selectedValue);
        document.getElementById('modal-category-id').value = data.id;
        document.getElementById('modal-category-name').value = data.name;
    }
}

function autoMatch(platform) {
    if (!confirm('Bu platform için otomatik eşleştirme yapılsın mı?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="auto_match">
        <input type="hidden" name="platform" value="${platform}">
    `;
    document.body.appendChild(form);
    form.submit();
}

window.onclick = function(event) {
    const modal = document.getElementById('mappingModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>