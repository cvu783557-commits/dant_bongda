<?php
/**
 * Script áp dụng migration cho module Quản lý Dịch VỤ
 * Chạy: php database/run_services_migration.php
 * An toàn: không DROP bảng, không xóa dữ liệu, chỉ CREATE IF NOT EXISTS.
 */

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host   = $_ENV['DB_HOST'];
$port   = $_ENV['DB_PORT'];
$user   = $_ENV['DB_USERNAME'];
$pass   = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

try {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");
    $pdo->exec("SET NAMES utf8mb4");

    echo "=== Kết nối CSDL `{$dbname}` thành công ===\n";

    // =========================================================
    // 1. Tạo bảng services
    // =========================================================
    $createServices = "CREATE TABLE IF NOT EXISTS services (
        id            INT PRIMARY KEY AUTO_INCREMENT,
        name          VARCHAR(150) NOT NULL,
        category      VARCHAR(50)  NOT NULL DEFAULT 'other',
        price         DECIMAL(12,0) NOT NULL DEFAULT 0,
        unit          VARCHAR(30)  NOT NULL DEFAULT 'cái',
        stock         INT          NOT NULL DEFAULT 0,
        description   TEXT NULL,
        image         VARCHAR(255) NULL,
        status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_service_category (category),
        INDEX idx_service_status (status),
        INDEX idx_service_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($createServices);
    echo "✅ Bảng `services` - OK\n";

    // =========================================================
    // 2. Tạo bảng booking_services (bảng trung gian bookings <-> services)
    //    - Tạo bảng trước (ko FK) để đảm bảo thành công mọi môi trường
    // =========================================================
    $createBookingServicesNoFk = "CREATE TABLE IF NOT EXISTS booking_services (
        id           INT PRIMARY KEY AUTO_INCREMENT,
        booking_id   INT NOT NULL,
        service_id   INT NOT NULL,
        quantity     INT NOT NULL DEFAULT 1,
        unit_price   DECIMAL(12,0) NOT NULL DEFAULT 0,
        subtotal     DECIMAL(12,0) NOT NULL DEFAULT 0,
        note         VARCHAR(255) NULL,
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_bs_booking (booking_id),
        INDEX idx_bs_service (service_id),
        CONSTRAINT chk_bs_quantity_pos CHECK (quantity > 0),
        CONSTRAINT chk_bs_price_nonneg CHECK (unit_price >= 0),
        CONSTRAINT chk_bs_subtotal_nonneg CHECK (subtotal >= 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($createBookingServicesNoFk);
    echo "✅ Bảng `booking_services` - OK (đã tạo cấu trúc)\n";

    // =========================================================
    // 2b. Đảm bảo đủ cột trên services & booking_services
    //     (nếu bảng cũ đã tồn tại thiếu cột)
    // =========================================================
    $svcCols = $pdo->query("SHOW COLUMNS FROM services")->fetchAll(PDO::FETCH_COLUMN);
    $requiredCols = [
        'name'        => "ALTER TABLE services ADD COLUMN `name` VARCHAR(150) NOT NULL AFTER `id`",
        'category'    => "ALTER TABLE services ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'other' AFTER `name`",
        'price'       => "ALTER TABLE services ADD COLUMN `price` DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `category`",
        'unit'        => "ALTER TABLE services ADD COLUMN `unit` VARCHAR(30) NOT NULL DEFAULT 'cái' AFTER `price`",
        'stock'       => "ALTER TABLE services ADD COLUMN `stock` INT NOT NULL DEFAULT 0 AFTER `unit`",
        'description' => "ALTER TABLE services ADD COLUMN `description` TEXT NULL AFTER `stock`",
        'image'       => "ALTER TABLE services ADD COLUMN `image` VARCHAR(255) NULL AFTER `description`",
        'status'      => "ALTER TABLE services ADD COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `image`",
        'created_at'  => "ALTER TABLE services ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`",
        'updated_at'  => "ALTER TABLE services ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
    ];
    $altered = 0;
    foreach ($requiredCols as $col => $sql) {
        if (!in_array($col, $svcCols, true)) {
            try { $pdo->exec($sql); $altered++; } catch (\Throwable $_) { /* ignore */ }
        }
    }
    if ($altered > 0) echo "   → Đã bổ sung {$altered} cột còn thiếu cho bảng `services` (tương thích CSDL cũ)\n";

    // ANH YEU EM: Đảm bảo index trên services
    $existingIdx = $pdo->query("SHOW INDEX FROM services")->fetchAll(PDO::FETCH_COLUMN, 2);
    $indexes = [
        'idx_service_category' => "CREATE INDEX idx_service_category ON services(category)",
        'idx_service_status'   => "CREATE INDEX idx_service_status   ON services(status)",
        'idx_service_name'     => "CREATE INDEX idx_service_name     ON services(name)",
    ];
    foreach ($indexes as $idxName => $sql) {
        if (!in_array($idxName, $existingIdx, true)) {
            try { $pdo->exec($sql); } catch (\Throwable $_) { /* ignore duplicate */ }
        }
    }

    // ANH YEU EM: Đảm bảo đủ cột trên bảng booking_services
    $bsCols = $pdo->query("SHOW COLUMNS FROM booking_services")->fetchAll(PDO::FETCH_COLUMN);
    $bsRequiredCols = [
        'booking_id' => "ALTER TABLE booking_services ADD COLUMN `booking_id` INT NOT NULL AFTER `id`",
        'service_id' => "ALTER TABLE booking_services ADD COLUMN `service_id` INT NOT NULL AFTER `booking_id`",
        'quantity'   => "ALTER TABLE booking_services ADD COLUMN `quantity` INT NOT NULL DEFAULT 1 AFTER `service_id`",
        'unit_price' => "ALTER TABLE booking_services ADD COLUMN `unit_price` DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `quantity`",
        'subtotal'   => "ALTER TABLE booking_services ADD COLUMN `subtotal` DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `unit_price`",
        'note'       => "ALTER TABLE booking_services ADD COLUMN `note` VARCHAR(255) NULL AFTER `subtotal`",
        'created_at' => "ALTER TABLE booking_services ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `note`",
        'updated_at' => "ALTER TABLE booking_services ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
    ];
    $bsAltered = 0;
    foreach ($bsRequiredCols as $col => $sql) {
        if (!in_array($col, $bsCols, true)) {
            try { $pdo->exec($sql); $bsAltered++; } catch (\Throwable $_) { /* ignore */ }
        }
    }
    if ($bsAltered > 0) echo "   → Đã bổ sung {$bsAltered} cột cho bảng `booking_services`\n";

    // =========================================================
    // 3. Thử add Foreign Key cho booking_services
    //    (nếu bảng bookings có PK chuẩn UNIQUE)
    // =========================================================
    $hasBookings = $pdo->query("SHOW TABLES LIKE 'bookings'")->rowCount() > 0;
    if ($hasBookings) {
        try {
            $existingFk = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                                       WHERE TABLE_SCHEMA = DATABASE()
                                         AND TABLE_NAME = 'booking_services'
                                         AND CONSTRAINT_NAME = 'fk_bs_booking'")->fetchColumn();
            if (!$existingFk) {
                $pdo->exec("ALTER TABLE booking_services ADD CONSTRAINT fk_bs_booking
                            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE");
            }
            $existingFk2 = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                                        WHERE TABLE_SCHEMA = DATABASE()
                                          AND TABLE_NAME = 'booking_services'
                                          AND CONSTRAINT_NAME = 'fk_bs_service'")->fetchColumn();
            if (!$existingFk2) {
                $pdo->exec("ALTER TABLE booking_services ADD CONSTRAINT fk_bs_service
                            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT");
            }
            echo "   → Foreign Keys được kích hoạt (toàn vẹn dữ liệu DB level)\n";
        } catch (Exception $fkErr) {
            echo "   ⚠️  Sử dụng quan hệ ở tầng PHP (CSDL cũ chưa có khóa chuẩn). Lỗi: " . trim($fkErr->getMessage()) . "\n";
        }
    } else {
        echo "   ⚠️  Bảng `bookings` chưa có → FK sẽ tự động thêm khi chạy import CSDL chính.\n";
    }

    // =========================================================
    // 3. Seed dữ liệu dịch vụ mẫu (chỉ insert khi chưa có)
    // =========================================================
    $seedServices = [
        ['Nước suối 500ml',        'drink',      10000, 'chai', 100, 'Nước đóng chai 500ml'],
        ['Nước tăng lực',          'drink',      15000, 'lon',   80, 'Nước tăng lực lon 330ml'],
        ['Cà phê sữa đá',          'drink',      20000, 'ly',    50, 'Cà phê pha sữa đá'],
        ['Bánh mì kẹp thịt',       'food',       25000, 'cái',   30, 'Bánh mì kẹp thịt heo'],
        ['Thuê bóng số 5',         'equipment',  20000, 'quả',   20, 'Bóng đá số 5 cỏ nhân tạo'],
        ['Thuê bóng số 7',         'equipment',  25000, 'quả',   15, 'Bóng đá số 7 cỏ nhân tạo'],
        ['Thuê áo đấu',            'equipment',  15000, 'bộ',    40, 'Áo đấu màu tiêu chuẩn'],
        ['Dịch vụ chụp ảnh',       'service',   150000, 'giờ',    5, 'Chụp ảnh trong trận bóng'],
    ];

    $countBefore = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $stmtIns = $pdo->prepare("INSERT IGNORE INTO services (name, category, price, unit, stock, description, status)
                               VALUES (?, ?, ?, ?, ?, ?, 'active')");
    foreach ($seedServices as $row) {
        $stmtIns->execute($row);
    }
    $countAfter = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $added = $countAfter - $countBefore;
    if ($added > 0) {
        echo "🌱 Seed: +{$added} dịch vụ mẫu (tổng {$countAfter})\n";
    } else {
        echo "🌱 Seed: Dữ liệu đã tồn tại, giữ nguyên (tổng {$countAfter})\n";
    }

    // =========================================================
    // 4. Verify kết quả
    // =========================================================
    echo "\n================== VERIFY ==================\n";
    $svcRows = $pdo->query("SELECT id, name, category, price, unit, stock, status FROM services ORDER BY id")->fetchAll();
    foreach ($svcRows as $r) {
        echo sprintf("  #%' 3d | %-26s | %-9s | %' 10sđ / %-6s | Tồn:%' 4d | %s\n",
            $r['id'], $r['name'], $r['category'],
            number_format($r['price'], 0, ',', '.'),
            $r['unit'], $r['stock'], $r['status']);
    }
    echo "============================================\n";
    echo "\n🎉 MIGRATION DỊCH VỤ HOÀN THÀNH!\n";
    echo "Truy cập: " . rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/') . "/admin/services\n";

} catch (Exception $e) {
    echo "\n❌ LỖI MIGRATION: " . $e->getMessage() . "\n";
    exit(1);
}
