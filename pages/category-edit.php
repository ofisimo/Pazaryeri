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

// Güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Kategori adı gereklidir';
    } elseif ($parent_id === $category_id) {
        $error = 'Kategori kendi alt kategorisi olamaz';
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE categories 
                SET name = :name,
                    parent_id = :parent_id,
                    description = :description,
                    sort_order = :sort_order,
                    is_active = :is_active
                WHERE id = :id
            ");
            $stmt->execute([
                ':name' => $name,
                ':parent_id' => $parent_id,
                ':description' => $description,
                ':sort_order' => $sort_order,
                ':is_active' => $is_active,
                ':id' => $category_id
            ]);
            
            $success = 'Kategori güncellendi';
            
            // Kategori bilgisini yeniden yükle
            $stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
            $stmt->execute([':id' => $category_id]);
            $category = $stmt->fetch();
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
.edit-page {
    animation: fadeIn 0.5s;
}

.edit-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<div class="edit-page">
    <div class="page-header">
        <div>
            <h2><i class="fas fa-edit"></i> Kategori Düzenle</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Kategori bilgilerini güncelleyin</p>
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

    <div class="edit-card">
        <form method="POST" action="">
            <div class="form-group">
                <label>Kategori Adı *</label>
                <input type="text" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($category['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Üst Kategori</label>
                <select name="parent_id" class="form-control">
                    <option value="">— Ana Kategori —</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <?php if ($cat['id'] != $category_id): // Kendini gösterme ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $cat['id'] == $category['parent_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <small>Boş bırakırsanız ana kategori olur</small>
            </div>

            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($category['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Sıralama</label>
                <input type="number" name="sort_order" class="form-control" 
                       value="<?php echo $category['sort_order']; ?>">
                <small>Küçük değerler önce gösterilir</small>
            </div>

            <div class="form-group">
                <label class="checkbox-row">
                    <input type="checkbox" name="is_active" <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                    Kategori aktif
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Kaydet
            </button>
        </form>
    </div>
</div>