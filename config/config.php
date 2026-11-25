<?php
/**
 * =====================================================================
 * FILE INI: KONFIGURASI SITUS
 * =====================================================================
 */

// Webhook Midtrans (api/notification.php) mendefinisikan NO_SESSION
// agar request server-ke-server tidak membuat file session sia-sia.
if (!defined('NO_SESSION') && session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL', '/geprek-mantul-erp');
// Otomatis memakai '/geprek-mantul-erp' di XAMPP dan '' (root) di Wasmer
$isLocal = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
define('BASE_URL', $isLocal ? '/geprek-mantul-erp' : '');

define('SITE_NAME', 'Geprek Mantul');
define('WHATSAPP_ADMIN', '6281234567890');
define('ONGKIR_DEFAULT', 8000);

date_default_timezone_set('Asia/Jakarta');

/**
 * =====================================================================
 * KONFIGURASI MIDTRANS
 * =====================================================================
 */
// Kunci Sandbox dari dashboard baru
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-XAjCAkeKwAtw6xV1isqELHiz'); 
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-iYb.slkhezygFd0w6cYaWWiA');

// WAJIB false karena kita pakai kunci SB- (Sandbox / Uji Coba)
define('MIDTRANS_IS_PRODUCTION', false); 

// Verifikasi sertifikat SSL saat memanggil API Midtrans.
define('MIDTRANS_SSL_VERIFY', false);

define('MIDTRANS_DEBUG', true);
define('MIDTRANS_EXPIRY_MINUTES', 60);

define('MIDTRANS_ENABLED_PAYMENTS', [
    'gopay', 'shopeepay', 'other_qris',
    'bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel', 'other_va',
]);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/midtrans.php';
