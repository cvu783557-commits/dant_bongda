<?php
/**
 * Tạo bảng lưu các giao dịch bán dịch vụ lẻ đã thanh toán.
 * Chạy: php database/run_service_sales_migration.php
 */

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
        "CREATE TABLE IF NOT EXISTS service_sales (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            service_id INT NOT NULL,
            service_name VARCHAR(150) NOT NULL,
            unit VARCHAR(30) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(12,0) NOT NULL,
            total_amount DECIMAL(12,0) NOT NULL,
            customer_name VARCHAR(150) NULL,
            customer_phone VARCHAR(30) NULL,
            payment_method ENUM('cash','banking') NOT NULL DEFAULT 'cash',
            recorded_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_service_sales_created (created_at, id),
            CONSTRAINT fk_service_sale_service FOREIGN KEY (service_id)
                REFERENCES services(id) ON DELETE RESTRICT,
            CONSTRAINT fk_service_sale_user FOREIGN KEY (recorded_by)
                REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT chk_service_sale_quantity CHECK (quantity > 0),
            CONSTRAINT chk_service_sale_price CHECK (unit_price >= 0 AND total_amount >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "ALTER TABLE service_sales
         MODIFY payment_method ENUM('cash','banking') NOT NULL DEFAULT 'cash'"
    );

    echo "[OK] Bảng service_sales đã sẵn sàng.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
