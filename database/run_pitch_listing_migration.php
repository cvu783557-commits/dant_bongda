<?php
/**
 * Bổ sung thông tin địa chỉ, giờ hoạt động và tiện ích cho danh sách sân.
 * Chạy: php database/run_pitch_listing_migration.php
 * An toàn khi chạy lại: chỉ thêm các cột còn thiếu, không xóa dữ liệu.
 */

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    Dotenv::createImmutable(__DIR__ . '/..')->load();

    $host = $_ENV['DB_HOST'] ?? null;
    $port = $_ENV['DB_PORT'] ?? null;
    $user = $_ENV['DB_USERNAME'] ?? null;
    $pass = $_ENV['DB_PASSWORD'] ?? null;
    $dbname = $_ENV['DB_NAME'] ?? null;
    if (!$host || !$port || !$user || !$dbname) {
        throw new RuntimeException('Thiếu cấu hình kết nối cơ sở dữ liệu trong .env.');
    }

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ]
    );

    $table = $pdo->query("SHOW TABLES LIKE 'pitches'")->fetchColumn();
    if (!$table) {
        throw new RuntimeException("Không tìm thấy bảng pitches trong cơ sở dữ liệu `{$dbname}`.");
    }

    $columns = [
        'address' => "VARCHAR(255) NULL",
        'opening_time' => "TIME NOT NULL DEFAULT '06:00:00'",
        'closing_time' => "TIME NOT NULL DEFAULT '23:00:00'",
        'grass_quality' => "VARCHAR(100) NULL",
        'has_lighting' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'has_changing_room' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'has_parking' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'extra_services' => 'TEXT NULL',
    ];

    foreach ($columns as $name => $definition) {
        $exists = $pdo->query("SHOW COLUMNS FROM `pitches` LIKE '{$name}'")->fetch();
        if ($exists) {
            echo "[SKIP] pitches.{$name} đã tồn tại\n";
            continue;
        }

        $pdo->exec("ALTER TABLE `pitches` ADD COLUMN `{$name}` {$definition}");
        echo "[OK] Đã thêm pitches.{$name}\n";
    }

    echo "Hoàn tất migration thông tin danh sách sân.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
