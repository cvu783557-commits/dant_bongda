<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/..')->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];
$database = $_ENV['DB_NAME'];
$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$pdo->exec("CREATE TABLE IF NOT EXISTS booking_series (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    pitch_id INT NOT NULL,
    booking_month DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_price DECIMAL(12,0) NOT NULL DEFAULT 0,
    deposit DECIMAL(12,0) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(12,0) NOT NULL DEFAULT 0,
    payment_method ENUM('vnpay') NOT NULL DEFAULT 'vnpay',
    payment_status ENUM('unpaid','deposit','partial','paid') NOT NULL DEFAULT 'unpaid',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_series_customer (customer_id),
    INDEX idx_booking_series_pitch_month (pitch_id, booking_month),
    CONSTRAINT fk_booking_series_customer FOREIGN KEY (customer_id)
        REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_series_pitch FOREIGN KEY (pitch_id)
        REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT chk_booking_series_time CHECK (start_time < end_time),
    CONSTRAINT chk_booking_series_total CHECK (total_price >= 0),
    CONSTRAINT chk_booking_series_deposit CHECK (deposit >= 0),
    CONSTRAINT chk_booking_series_paid CHECK (paid_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$columns = [
    'series_id' => 'INT NULL',
    'vnpay_txn_ref' => 'VARCHAR(100) NULL',
    'vnpay_transaction_no' => 'VARCHAR(15) NULL',
    'vnpay_transaction_date' => 'CHAR(14) NULL',
];
$existingColumns = $pdo->query('SHOW COLUMNS FROM bookings')->fetchAll(PDO::FETCH_COLUMN);
foreach ($columns as $name => $definition) {
    if (!in_array($name, $existingColumns, true)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN `{$name}` {$definition}");
        echo "Added bookings.{$name}\n";
    }
}

$bookingIndexes = $pdo->query('SHOW INDEX FROM bookings')->fetchAll(PDO::FETCH_COLUMN, 2);
if (!in_array('idx_booking_series', $bookingIndexes, true)) {
    $pdo->exec('CREATE INDEX idx_booking_series ON bookings (series_id)');
    echo "Added index idx_booking_series\n";
}

$seriesForeignKey = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'bookings'
       AND CONSTRAINT_NAME = 'fk_bookings_series'
       AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
);
$seriesForeignKey->execute();
if ((int)$seriesForeignKey->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE bookings
        ADD CONSTRAINT fk_bookings_series FOREIGN KEY (series_id)
        REFERENCES booking_series(id) ON DELETE SET NULL");
    echo "Added foreign key fk_bookings_series\n";
}

$indexes = $pdo->query('SHOW INDEX FROM bookings')->fetchAll(PDO::FETCH_COLUMN, 2);
if (!in_array('idx_booking_vnpay_txn_ref', $indexes, true)) {
    $pdo->exec('CREATE INDEX idx_booking_vnpay_txn_ref ON bookings (vnpay_txn_ref)');
    echo "Added index idx_booking_vnpay_txn_ref\n";
}

$pdo->exec("CREATE TABLE IF NOT EXISTS booking_cancellation_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    customer_user_id INT NOT NULL,
    request_reason VARCHAR(500) NOT NULL,
    status ENUM('pending','processing','approved','refunded','rejected','manual_review') NOT NULL DEFAULT 'pending',
    refund_amount DECIMAL(12,0) NOT NULL DEFAULT 0,
    refund_request_id VARCHAR(32) NULL,
    refund_response_code VARCHAR(14) NULL,
    refund_message VARCHAR(500) NULL,
    reviewed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    INDEX idx_cancel_request_status (status, created_at),
    INDEX idx_cancel_request_booking (booking_id, status),
    INDEX idx_cancel_request_customer (customer_user_id, created_at),
    CONSTRAINT fk_cancel_request_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_cancel_request_customer FOREIGN KEY (customer_user_id)
        REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_cancel_request_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "Customer booking portal migration completed.\n";
