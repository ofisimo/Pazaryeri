<?php
/**
 * Platform Sync Helper Functions
 * Senkronizasyon ayarlarını yönetir ve buton görünürlüğünü kontrol eder
 * 
 * Konum: includes/SyncHelper.php
 */

class SyncHelper {
    private $db;
    private $settings;
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    /**
     * Ayarları veritabanından yükle
     */
    private function loadSettings() {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM sync_settings");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->settings = [];
        foreach ($rows as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Varsayılan değerler
        if (!isset($this->settings['source_platform'])) {
            $this->settings['source_platform'] = 'opencart';
        }
        if (!isset($this->settings['target_platforms'])) {
            $this->settings['target_platforms'] = '[]';
        }
    }
    
    /**
     * Başlangıç platformunu al
     */
    public function getSourcePlatform() {
        return $this->settings['source_platform'] ?? 'opencart';
    }
    
    /**
     * Hedef platformları al
     */
    public function getTargetPlatforms() {
        $targets = json_decode($this->settings['target_platforms'] ?? '[]', true);
        return is_array($targets) ? $targets : [];
    }
    
    /**
     * Bir platform için "Çek" butonunu göster mi?
     */
    public function canPullFrom($platform) {
        return $platform === $this->getSourcePlatform();
    }
    
    /**
     * Bir platform için "Gönder" butonunu göster mi?
     */
    public function canPushTo($platform) {
        return in_array($platform, $this->getTargetPlatforms());
    }
    
    /**
     * Platform aktif mi?
     */
    public function isPlatformActive($platform) {
        $source = $this->getSourcePlatform();
        $targets = $this->getTargetPlatforms();
        
        return $platform === $source || in_array($platform, $targets);
    }
    
    /**
     * Ayar değeri al
     */
    public function getSetting($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Resimleri senkronize et mi?
     */
    public function shouldSyncImages() {
        return ($this->getSetting('sync_images', '1') === '1');
    }
    
    /**
     * Kategorileri senkronize et mi?
     */
    public function shouldSyncCategories() {
        return ($this->getSetting('sync_categories', '1') === '1');
    }
    
    /**
     * Varyantları senkronize et mi?
     */
    public function shouldSyncVariants() {
        return ($this->getSetting('sync_variants', '1') === '1');
    }
    
    /**
     * Otomatik senkronizasyon açık mı?
     */
    public function isAutoSyncEnabled() {
        return ($this->getSetting('auto_sync', '0') === '1');
    }
    
    /**
     * Platform bilgilerini al
     */
    public function getPlatformInfo() {
        return [
            'opencart' => ['name' => 'OpenCart', 'icon' => 'fa-shopping-cart', 'color' => '#2196f3'],
            'trendyol' => ['name' => 'Trendyol', 'icon' => 'fa-shopping-bag', 'color' => '#f27a1a'],
            'hepsiburada' => ['name' => 'Hepsiburada', 'icon' => 'fa-shopping-basket', 'color' => '#ff6000'],
            'n11' => ['name' => 'N11', 'icon' => 'fa-store-alt', 'color' => '#7c3fb7']
        ];
    }
    
    /**
     * Buton HTML'i oluştur
     */
    public function renderSyncButtons() {
        $source = $this->getSourcePlatform();
        $targets = $this->getTargetPlatforms();
        $platforms = $this->getPlatformInfo();
        
        $html = '<div class="sync-buttons-container">';
        
        // Başlangıç platform - Çek butonu
        if (isset($platforms[$source])) {
            $platform = $platforms[$source];
            $html .= sprintf(
                '<button class="btn btn-primary" onclick="syncFrom(\'%s\')">
                    <i class="fas %s"></i> %s\'tan Çek
                </button>',
                $source,
                $platform['icon'],
                $platform['name']
            );
        }
        
        // Hedef platformlar - Gönder butonları
        foreach ($targets as $target) {
            if (isset($platforms[$target])) {
                $platform = $platforms[$target];
                $html .= sprintf(
                    '<button class="btn btn-success" onclick="syncTo(\'%s\')">
                        <i class="fas %s"></i> %s\'a Gönder
                    </button>',
                    $target,
                    $platform['icon'],
                    $platform['name']
                );
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
