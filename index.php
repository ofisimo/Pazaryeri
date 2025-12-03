<?php
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'products', 'orders', 'settings', 'trendyol', 'hepsiburada', 'n11', 'categories', 'category-mappings', 'category-edit', 'category-add', 'product-add'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pazaryeri Yönetim Paneli</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-store"></i> Panel</h3>
            </div>
            <ul class="sidebar-menu">
                <li class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>">
                    <a href="?page=dashboard"><i class="fas fa-home"></i> Ana Sayfa</a>
                </li>
                <li class="<?php echo $page == 'products' ? 'active' : ''; ?>">
                    <a href="?page=products"><i class="fas fa-box"></i> Ürünler</a>
                </li>
				<li class="<?php echo $page == 'categories' ? 'active' : ''; ?>">
                    <a href="?page=categories"><i class="fas fa-folder-tree"></i> Kategoriler</a>
                </li>
                <li class="<?php echo $page == 'orders' ? 'active' : ''; ?>">
                    <a href="?page=orders"><i class="fas fa-shopping-cart"></i> Siparişler</a>
                </li>
                <li class="menu-title">Pazaryerleri</li>
                <li class="<?php echo $page == 'trendyol' ? 'active' : ''; ?>">
                    <a href="?page=trendyol"><i class="fas fa-shopping-bag"></i> Trendyol</a>
                </li>
                <li class="<?php echo $page == 'hepsiburada' ? 'active' : ''; ?>">
                    <a href="?page=hepsiburada"><i class="fas fa-shopping-basket"></i> Hepsiburada</a>
                </li>
                <li class="<?php echo $page == 'n11' ? 'active' : ''; ?>">
                    <a href="?page=n11"><i class="fas fa-store-alt"></i> N11</a>
                </li>
                <li class="menu-title">Ayarlar</li>
                <li class="<?php echo $page == 'settings' ? 'active' : ''; ?>">
                    <a href="?page=settings"><i class="fas fa-cog"></i> Ayarlar</a>
                </li>
            </ul>
        </nav>

        <!-- Ana İçerik -->
        <div class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h4>Hoş Geldiniz, <?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                </div>
                <div class="topbar-right">
                    <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
                </div>
            </div>

            <div class="content">
                <?php
                $page_file = "pages/{$page}.php";
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    echo "<div class='alert alert-danger'>Sayfa bulunamadı!</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>