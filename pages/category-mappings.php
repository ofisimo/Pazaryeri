<?php
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $platform = $_POST['platform'] ?? '';
    $platform_category_id = trim($_POST['platform_category_id'] ?? '');
    $platform_category_name = trim($_POST['platform_category_name'] ?? '');
    
    if ($platform && $platform_category_id && $platform_category_name) {
        try {
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
                ':platform_id' => $platform_category_id,
                ':platform_name' => $platform_category_name,
                ':platform_id2' => $platform_category_id,
                ':platform_name2' => $platform_category_name
            ]);
            
            $success = 'Eşleştirme kaydedildi';
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    } else {
        $error = 'Tüm alanları doldurun';
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

.platform-select {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.platform-btn {
    padding: 15px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.platform-btn:hover {
    border-color: #3498db;
    transform: translateY(-2px);
}

.platform-btn.selected {
    border-color: #3498db;
    background: #e3f2fd;
}

.mapping-list {
    margin-top: 30px;
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

    <!-- Yeni Eşleştirme Ekleme -->
    <div class="mapping-card">
        <h3><i class="fas fa-plus-circle"></i> Yeni Eşleştirme Ekle</h3>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Pazaryeri Seçin</label>
                <div class="platform-select">
                    <label class="platform-btn">
                        <input type="radio" name="platform" value="opencart" style="display: none;" onchange="selectPlatform(this)">
                        <i class="fas fa-shopping-cart" style="font-size: 24px; color: #2196f3;"></i>
                        <p style="margin-top: 5px; font-weight: 600;">OpenCart</p>
                    </label>
                    <label class="platform-btn">
                        <input type="radio" name="platform" value="trendyol" style="display: none;" onchange="selectPlatform(this)">
                        <i class="fas fa-shopping-bag" style="font-size: 24px; color: #f27a1a;"></i>
                        <p style="margin-top: 5px; font-weight: 600;">Trendyol</p>
                    </label>
                    <label class="platform-btn">
                        <input type="radio" name="platform" value="hepsiburada" style="display: none;" onchange="selectPlatform(this)">
                        <i class="fas fa-shopping-basket" style="font-size: 24px; color: #ff6000;"></i>
                        <p style="margin-top: 5px; font-weight: 600;">Hepsiburada</p>
                    </label>
                    <label class="platform-btn">
                        <input type="radio" name="platform" value="n11" style="display: none;" onchange="selectPlatform(this)">
                        <i class="fas fa-store-alt" style="font-size: 24px; color: #7c3fb7;"></i>
                        <p style="margin-top: 5px; font-weight: 600;">N11</p>
                    </label>
                </div>
            </div>

   <div class="form-group" id="category-selection" style="display: none;">
                <label id="category-label">Kategori Seçin</label>
                <select name="platform_category_select" id="platform-category-select" class="form-control" onchange="fillCategoryInfo()">
                    <option value="">Kategori seçin...</option>
                </select>
                <small id="category-help">Eşleştirilecek kategoriyi seçin</small>
            </div>

            <input type="hidden" name="platform_category_id" id="platform-category-id">
            <input type="hidden" name="platform_category_name" id="platform-category-name">

            <div id="selected-category-info" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin-top: 0;">Seçilen Kategori:</h4>
                <p style="margin: 0;"><strong id="selected-name"></strong></p>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">ID: <span id="selected-id"></span></p>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Eşleştirmeyi Kaydet
            </button>
        </form>
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
</div>

<script>
function selectPlatform(radio) {
    // Tüm butonlardan selected kaldır
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Seçili olan butona selected ekle
    radio.parentElement.classList.add('selected');
}
	let categoriesData = {
    opencart: [],
    trendyol: [],
    hepsiburada: [],
    n11: []
};

function selectPlatform(radio) {
    // Tüm butonlardan selected kaldır
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Seçili olan butona selected ekle
    radio.parentElement.classList.add('selected');
    
    const platform = radio.value;
    loadPlatformCategories(platform);
}

function loadPlatformCategories(platform) {
    const categorySelection = document.getElementById('category-selection');
    const categorySelect = document.getElementById('platform-category-select');
    const categoryLabel = document.getElementById('category-label');
    
    // Loading göster
    categorySelect.innerHTML = '<option value="">Yükleniyor...</option>';
    categorySelection.style.display = 'block';
    
    // Platform adını güncelle
    const platformNames = {
        'opencart': 'OpenCart',
        'trendyol': 'Trendyol',
        'hepsiburada': 'Hepsiburada',
        'n11': 'N11'
    };
    categoryLabel.textContent = platformNames[platform] + ' Kategorisi Seçin';
    
    // AJAX ile kategorileri çek
    fetch(`ajax/get-platform-categories.php?platform=${platform}`)
    .then(r => r.json())
    .then(data => {
        if (data.success && data.categories.length > 0) {
            categoriesData[platform] = data.categories;
            
            // Dropdown'ı doldur
            let options = '<option value="">Kategori seçin...</option>';
            data.categories.forEach(cat => {
                const indent = cat.level ? '— '.repeat(cat.level) : '';
                options += `<option value="${cat.id}" data-name="${cat.name}" data-platform-id="${cat.platform_id || cat.id}">
                    ${indent}${cat.name}
                </option>`;
            });
            
            categorySelect.innerHTML = options;
        } else {
            categorySelect.innerHTML = '<option value="">Kategori bulunamadı</option>';
            alert('Bu platform için kategori bulunamadı. Önce kategorileri senkronize edin.');
        }
    })
    .catch(err => {
        categorySelect.innerHTML = '<option value="">Hata oluştu</option>';
        alert('Kategoriler yüklenirken hata: ' + err);
    });
}

function fillCategoryInfo() {
    const select = document.getElementById('platform-category-select');
    const selectedOption = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('selected-category-info');
    
    if (selectedOption.value) {
        const categoryName = selectedOption.getAttribute('data-name');
        const platformId = selectedOption.getAttribute('data-platform-id');
        
        // Hidden inputları doldur
        document.getElementById('platform-category-id').value = platformId;
        document.getElementById('platform-category-name').value = categoryName;
        
        // Seçilen kategori bilgisini göster
        document.getElementById('selected-name').textContent = categoryName;
        document.getElementById('selected-id').textContent = platformId;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
</script>