<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    Dotenv::createImmutable(__DIR__ . '/..')->load();

    $host = $_ENV['DB_HOST'] ?? null;
    $port = $_ENV['DB_PORT'] ?? null;
    $user = $_ENV['DB_USERNAME'] ?? null;
    $password = $_ENV['DB_PASSWORD'] ?? '';
    $database = $_ENV['DB_NAME'] ?? null;
    if (!$host || !$port || !$user || !$database) {
        throw new RuntimeException('Thiếu cấu hình kết nối cơ sở dữ liệu trong .env.');
    }

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $columnsToAdd = [
        'checkin_token' => 'CHAR(64) NULL',
        'checked_in_at' => 'DATETIME NULL',
        'checked_in_by' => 'INT NULL',
        'checkin_note' => 'VARCHAR(500) NULL',
    ];
    $columns = $pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `{$column}` {$definition}");
            echo "[OK] Đã thêm bookings.{$column}\n";
        } else {
            echo "[SKIP] bookings.{$column} đã tồn tại\n";
        }
    }

    $indexes = $pdo->query("SHOW INDEX FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN, 2);
    if (!in_array('uq_bookings_checkin_token', $indexes, true)) {
        $pdo->exec("CREATE UNIQUE INDEX `uq_bookings_checkin_token` ON `bookings` (`checkin_token`)");
        echo "[OK] Đã thêm chỉ mục uq_bookings_checkin_token\n";
    } else {
        echo "[SKIP] Chỉ mục uq_bookings_checkin_token đã tồn tại\n";
    }

    echo "Hoàn tất migration QR check-in cho {$database}.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
