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
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS booking_extensions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            amount DECIMAL(12,0) NOT NULL DEFAULT 0,
            recorded_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_booking_extensions_booking (booking_id, id),
            CONSTRAINT fk_booking_extensions_booking FOREIGN KEY (booking_id)
                REFERENCES bookings(id) ON DELETE CASCADE,
            CONSTRAINT fk_booking_extensions_user FOREIGN KEY (recorded_by)
                REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT chk_booking_extensions_amount CHECK (amount >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    echo "[OK] Bảng booking_extensions đã sẵn sàng.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
