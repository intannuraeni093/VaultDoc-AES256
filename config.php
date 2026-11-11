<?php
// config.php - Konfigurasi Database & Enkripsi Simetris (AES-256-CBC)

$host = 'localhost';
$dbname = 'db_arsip_rahasia';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// KUNCI RAHASIA SIMETRIS (Wajib sama antara enkripsi dan dekripsi)
// Kunci ini bisa berupa string apa saja, pastikan aman dan dirahasiakan.
define('SECRET_KEY', 'KunciRahasiaPraktikumInformatika2026!');
define('AES_METHOD', 'aes-256-cbc');

// ------------------------------------------------------------------------
// FUNGSI ENKRIPSI & DEKRIPSI SIMETRIS (AES)
// ------------------------------------------------------------------------
function encryptData($data) {
    if (empty($data)) return '';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_METHOD));
    $encrypted = openssl_encrypt($data, AES_METHOD, SECRET_KEY, 0, $iv);
    // Gabungkan IV dan hasil enkripsi lalu encode base64
    return base64_encode($iv . $encrypted);
}

function decryptData($dataEncrypted) {
    if (empty($dataEncrypted)) return '[Gagal Dekripsi]';
    $decoded = base64_decode($dataEncrypted);
    $ivLength = openssl_cipher_iv_length(AES_METHOD);
    $iv = substr($decoded, 0, $ivLength);
    $encryptedData = substr($decoded, $ivLength);
    
    $decrypted = openssl_decrypt($encryptedData, AES_METHOD, SECRET_KEY, 0, $iv);
    return $decrypted !== false ? $decrypted : '[Password Salah atau Data Korup]';
}
?>