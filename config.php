<?php
// ===== DEKOPANEL VERİTABANI AYARLARI =====
// Hostinger'dan aldığınız bilgileri buraya girin

define('DB_HOST', 'localhost');
define('DB_NAME', 'u137711758_dekopanel');
define('DB_USER', 'u137711758_yonetim');
define('DB_PASS', 'Dekopanel2025');
define('DB_CHARSET', 'utf8mb4');

// Görsel yükleme ayarları
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', '/uploads/');
define('PRODUCT_IMG_W', 600);
define('PRODUCT_IMG_H', 600);
define('BANNER_IMG_W', 1200);
define('BANNER_IMG_H', 500);

// Admin şifresi (SHA256 hash)
// Varsayılan şifre: dekopanel2025
define('ADMIN_PASS_HASH', hash('sha256', 'dekopanel2025'));
define('ADMIN_USER', 'admin');

// Hata ayıklama (canlıda false yapın)
define('DEBUG', false);

// ==========================================
// Bağlantı fonksiyonu
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            if (DEBUG) {
                die(json_encode(['error' => $e->getMessage()]));
            } else {
                die(json_encode(['error' => 'Veritabanı bağlantı hatası']));
            }
        }
    }
    return $pdo;
}
