<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

try {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new Exception("Không đọc được schema.sql");
    }

    $pdo->exec($sql);

    // Verify
    $tables = ['pitches', 'time_slots', 'bookings'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("SELECT COUNT(*) AS c FROM `{$t}`");
        $r = $stmt->fetch();
        echo "OK - Bảng {$t}: {$r['c']} dòng\n";
    }
    echo "\nIMPORT THÀNH CÔNG CSDL `{$dbname}`!\n";
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
    exit(1);
}
