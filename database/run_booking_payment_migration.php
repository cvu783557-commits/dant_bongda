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
        "CREATE TABLE IF NOT EXISTS booking_payment_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT NOT NULL,
            amount DECIMAL(12,0) NOT NULL,
            payment_method ENUM('cash','banking','momo','zalo','vnpay','other') NOT NULL,
            status ENUM('success','failed','refund') NOT NULL,
            reference_code VARCHAR(100) NULL,
            note VARCHAR(500) NULL,
            recorded_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_payment_tx_booking_created (booking_id, created_at),
            UNIQUE INDEX uq_payment_tx_booking_reference (booking_id, reference_code),
            CONSTRAINT fk_payment_tx_booking FOREIGN KEY (booking_id)
                REFERENCES bookings(id) ON DELETE CASCADE,
            CONSTRAINT fk_payment_tx_user FOREIGN KEY (recorded_by)
                REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT chk_payment_tx_amount CHECK (amount > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "[OK] Bảng booking_payment_transactions đã sẵn sàng\n";

    $pdo->exec(
        "INSERT INTO booking_payment_transactions
            (booking_id, amount, payment_method, status, reference_code, note, created_at)
         SELECT b.id, b.paid_amount, b.payment_method, 'success',
                CONCAT('LEGACY-BOOKING-', b.id),
                'Số dư thanh toán ghi nhận trước khi bật sổ giao dịch',
                COALESCE(b.updated_at, b.created_at, NOW())
         FROM bookings b
         WHERE b.paid_amount > 0
           AND NOT EXISTS (
               SELECT 1
               FROM booking_payment_transactions pt
               WHERE pt.booking_id = b.id
                 AND pt.reference_code = CONCAT('LEGACY-BOOKING-', b.id)
           )"
    );
    echo "[OK] Đã chuyển số dư thanh toán hiện có thành giao dịch lịch sử nếu cần\n";
    echo "Hoàn tất migration quản lý thanh toán cho {$database}.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
