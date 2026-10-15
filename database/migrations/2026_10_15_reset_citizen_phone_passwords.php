<?php
/**
 * Migrasi: acak password akun warga yang masih memakai nomor HP sebagai password
 * (default lama findOrCreateCitizen). Jalankan sekali via CLI:
 *   php database/migrations/2026_10_15_reset_citizen_phone_passwords.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$db = \App\Config\Database::getConnection();
$citizens = $db->query("SELECT id, phone, password_hash FROM users WHERE role = 'ROLE_CITIZEN' AND phone IS NOT NULL")->fetchAll();
$update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");

$reset = 0;
foreach ($citizens as $citizen) {
    if (password_verify((string)$citizen['phone'], (string)$citizen['password_hash'])) {
        $update->execute([password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT), $citizen['id']]);
        $reset++;
    }
}

echo "Akun warga diperiksa: " . count($citizens) . ", password nomor HP diacak: {$reset}" . PHP_EOL;
