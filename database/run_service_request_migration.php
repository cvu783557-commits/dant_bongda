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
        "CREATE TABLE IF NOT EXISTS service_requests (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            booking_id INT NOT NULL,
            requested_by INT NOT NULL,
            status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
            customer_note VARCHAR(500) NULL,
            staff_note VARCHAR(500) NULL,
            handled_by INT NULL,
            handled_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_service_requests_status_created (status, created_at),
            INDEX idx_service_requests_booking (booking_id, created_at),
            CONSTRAINT fk_service_request_booking FOREIGN KEY (booking_id)
                REFERENCES bookings(id) ON DELETE CASCADE,
            CONSTRAINT fk_service_request_user FOREIGN KEY (requested_by)
                REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_service_request_handler FOREIGN KEY (handled_by)
                REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS service_request_items (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            request_id BIGINT UNSIGNED NOT NULL,
            service_id INT NOT NULL,
            service_name VARCHAR(150) NOT NULL,
            unit VARCHAR(30) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(12,0) NOT NULL,
            subtotal DECIMAL(12,0) NOT NULL,
            INDEX idx_service_request_items_request (request_id),
            CONSTRAINT fk_service_request_item_request FOREIGN KEY (request_id)
                REFERENCES service_requests(id) ON DELETE CASCADE,
            CONSTRAINT fk_service_request_item_service FOREIGN KEY (service_id)
                REFERENCES services(id) ON DELETE RESTRICT,
            CONSTRAINT chk_service_request_item_quantity CHECK (quantity > 0),
            CONSTRAINT chk_service_request_item_price CHECK (unit_price >= 0 AND subtotal >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    echo "[OK] Bảng service_requests và service_request_items đã sẵn sàng\n";
    echo "Hoàn tất migration yêu cầu dịch vụ cho {$database}.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
