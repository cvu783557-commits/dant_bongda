<?php
/**
 * File nâng cấp CSDL lên Version 2 - QUẢN LÝ LỊCH SÂN BÓNG ĐÁ (hoàn chỉnh)
 * Cách chạy: php database/run_upgrade.php
 * Idempotent: chạy nhiều lần vẫn an toàn.
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

function tableExists($pdo, $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    return $stmt && $stmt->rowCount() > 0;
}
function columnExists($pdo, $table, $col) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    return $stmt && $stmt->rowCount() > 0;
}
function rowCount($pdo, $table) {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM `{$table}`");
    $r = $stmt->fetch();
    return (int)($r['c'] ?? 0);
}
function stepOK($msg) { echo "[OK] {$msg}\n"; }
function stepSkip($msg) { echo "[SKIP] {$msg}\n"; }
function stepInfo($msg) { echo "[..] {$msg}\n"; }

try {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("ALTER DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    stepInfo("===== BUỚC 1: Đảm bảo bảng PITCHES, TIME_SLOTS (V1 base) =====");
    if (!tableExists($pdo, 'pitches')) {
        $pdo->exec("CREATE TABLE pitches (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            type INT NOT NULL DEFAULT 7,
            price_per_hour DECIMAL(12,0) NOT NULL DEFAULT 0,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        stepOK("Tạo bảng pitches");
        // seed sân 5 và 7 người
        $pdo->exec("INSERT IGNORE INTO pitches (id, name, type, price_per_hour, description, status) VALUES
        (1, 'Sân A1 - Sân 5 người cỏ tự nhiên', 5, 300000, 'Sân 5 người cỏ tự nhiên, rộng rãi thoáng mát.', 'active'),
        (2, 'Sân A2 - Sân 7 người cỏ nhân tạo', 7, 500000, 'Sân 7 người cỏ nhân tạo chất lượng, có mái che.', 'active'),
        (3, 'Sân B1 - Sân 7 người tiêu chuẩn', 7, 950000, 'Sân 7 người kích thước tiêu chuẩn.', 'active')");
        stepOK("Seed mẫu sân 5 và 7 người");
    } else {
        stepSkip("Bảng pitches đã tồn tại (" . rowCount($pdo,'pitches') . " dòng)");
    }
    if (!tableExists($pdo, 'time_slots')) {
        $pdo->exec("CREATE TABLE time_slots (
            id INT PRIMARY KEY AUTO_INCREMENT,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            label VARCHAR(50) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $slotVals = [];
        for ($h = 6; $h <= 22; $h++) {
            $hh = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
            $nh = str_pad((string)($h+1), 2, '0', STR_PAD_LEFT);
            $slotVals[] = "('{$hh}:00:00', '{$nh}:00:00', '{$hh}:00-{$nh}:00')";
        }
        $pdo->exec("INSERT IGNORE INTO time_slots (start_time, end_time, label) VALUES " . implode(", ", $slotVals));
        stepOK("Tạo bảng time_slots 17 khung giờ (06:00→23:00)");
    } else {
        stepSkip("Bảng time_slots đã tồn tại (" . rowCount($pdo,'time_slots') . " dòng)");
    }

    stepInfo("\n===== BUỚC 2: Tạo bảng USERS (phân quyền ADMIN/STAFF) =====");
    if (!tableExists($pdo, 'users')) {
        $pdo->exec("CREATE TABLE users (
            id              INT PRIMARY KEY AUTO_INCREMENT,
            username        VARCHAR(50)  NOT NULL UNIQUE,
            password_hash   VARCHAR(255) NOT NULL,
            full_name       VARCHAR(100) NOT NULL,
            phone           VARCHAR(20)  NULL,
            role            ENUM('ADMIN','STAFF') NOT NULL DEFAULT 'STAFF',
            status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        stepOK("Tạo bảng users");
    } else {
        stepSkip("Bảng users đã tồn tại (" . rowCount($pdo,'users') . " dòng)");
    }

    stepInfo("\n===== BUỚC 3: Tạo bảng CUSTOMERS (khách hàng riêng biệt) =====");
    if (!tableExists($pdo, 'customers')) {
        $pdo->exec("CREATE TABLE customers (
            id              INT PRIMARY KEY AUTO_INCREMENT,
            name            VARCHAR(100) NOT NULL,
            phone           VARCHAR(20)  NOT NULL UNIQUE,
            email           VARCHAR(100) NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_customer_phone (phone),
            INDEX idx_customer_name  (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        stepOK("Tạo bảng customers");
    } else {
        stepSkip("Bảng customers đã tồn tại (" . rowCount($pdo,'customers') . " dòng)");
    }

    stepInfo("\n===== BUỚC 3B: Tạo bảng PITCH_LOCKS (khóa khung giờ sân) =====");
    if (!tableExists($pdo, 'pitch_locks')) {
        $pdo->exec("CREATE TABLE pitch_locks (
            id          INT PRIMARY KEY AUTO_INCREMENT,
            pitch_id    INT NOT NULL,
            lock_date   DATE NOT NULL,
            start_time  TIME NOT NULL,
            end_time    TIME NOT NULL,
            reason      VARCHAR(255) NULL,
            created_by  INT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_lock_pitch_date (pitch_id, lock_date),
            INDEX idx_lock_start_time (start_time),
            CONSTRAINT fk_pitchlock_pitch FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
            CONSTRAINT fk_pitchlock_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT chk_lock_time CHECK (start_time < end_time),
            CONSTRAINT chk_lock_range CHECK (start_time >= '06:00:00' AND end_time <= '23:00:00')
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        stepOK("Tạo bảng pitch_locks");
    } else {
        stepSkip("Bảng pitch_locks đã tồn tại (" . rowCount($pdo,'pitch_locks') . " dòng)");
    }

    stepInfo("\n===== BUỚC 4: Di chuyển khách hàng từ bookings V1 inline sang customers =====");
    $customersCount = rowCount($pdo, 'customers');
    $haveV1Bookings = tableExists($pdo, 'bookings') && columnExists($pdo, 'bookings', 'customer_name') && columnExists($pdo, 'bookings', 'customer_phone');
    if ($customersCount === 0 && $haveV1Bookings) {
        $pdo->exec("INSERT IGNORE INTO customers (name, phone, email)
        SELECT MAX(customer_name) AS name, customer_phone AS phone, MAX(customer_email) AS email
        FROM bookings WHERE customer_phone IS NOT NULL AND customer_phone <> ''
        GROUP BY customer_phone");
        stepOK("Migrate khách hàng inline từ bookings V1 -> customers (" . rowCount($pdo,'customers') . " dòng)");
    } else {
        stepSkip("Không cần migrate khách hàng (customers đã có dữ liệu, HOẶC bookings V1 không còn dạng inline)");
    }

    stepInfo("\n===== BUỚC 5: Rename bookings V1 -> bookings_old_v1 (lưu trữ an toàn) =====");
    if (tableExists($pdo, 'bookings') && !tableExists($pdo, 'bookings_old_v1') && columnExists($pdo, 'bookings','time_slot_id')) {
        $countOld = rowCount($pdo, 'bookings');
        $pdo->exec("RENAME TABLE bookings TO bookings_old_v1");
        stepOK("RENAME bookings ({$countOld} dòng) -> bookings_old_v1");
    } else {
        if (tableExists($pdo, 'bookings_old_v1')) {
            stepSkip("bookings_old_v1 đã tồn tại (" . rowCount($pdo,'bookings_old_v1') . " dòng), bỏ qua rename");
        } elseif (tableExists($pdo, 'bookings') && columnExists($pdo, 'bookings','start_time')) {
            stepSkip("Bảng bookings đã là phiên bản V2 (có cột start_time/end_time kiểu TIME), bỏ qua rename");
        } elseif (!tableExists($pdo, 'bookings')) {
            stepSkip("Chưa có bảng bookings nào, sẽ tạo V2 mới");
        } else {
            stepSkip("Bỏ qua bước rename (trạng thái không xác định)");
        }
    }

    stepInfo("\n===== BUỚC 6: Tạo bảng BOOKINGS V2 đầy đủ nghiệp vụ =====");
    if (!tableExists($pdo, 'bookings')) {
        $pdo->exec("CREATE TABLE bookings (
            id                      INT PRIMARY KEY AUTO_INCREMENT,
            customer_id             INT NOT NULL COMMENT 'Khóa ngoại -> customers.id',
            pitch_id                INT NOT NULL COMMENT 'Khóa ngoại -> pitches.id',
            booking_date            DATE NOT NULL COMMENT 'Ngày đặt sân',
            start_time              TIME NOT NULL COMMENT 'Giờ bắt đầu (TIME)',
            end_time                TIME NOT NULL COMMENT 'Giờ kết thúc (TIME)',
            total_price             DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Tổng tiền thuê',
            deposit                 DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Tiền đặt cọc',
            paid_amount             DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Số tiền đã thanh toán',
            payment_method          ENUM('cash','banking','momo','zalo','other') NOT NULL DEFAULT 'cash' COMMENT 'Phương thức thanh toán',
            payment_status          ENUM('unpaid','deposit','partial','paid') NOT NULL DEFAULT 'unpaid' COMMENT 'Trạng thái thanh toán',
            status                  ENUM('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái lịch',
            cancellation_reason     TEXT NULL COMMENT 'Lý do hủy lịch (nếu có)',
            note                    TEXT NULL COMMENT 'Ghi chú nội bộ hoặc của khách',
            created_by              INT NULL COMMENT 'Người tạo lịch -> users.id',
            created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at              DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_booking_date       (booking_date),
            INDEX idx_booking_status     (status),
            INDEX idx_payment_status     (payment_status),
            INDEX idx_pitch_date         (pitch_id, booking_date),
            INDEX idx_customer_id        (customer_id),
            INDEX idx_created_by         (created_by),

            CONSTRAINT fk_bookings_v2_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
            CONSTRAINT fk_bookings_v2_pitch    FOREIGN KEY (pitch_id)    REFERENCES pitches(id)   ON DELETE CASCADE,
            CONSTRAINT fk_bookings_v2_user     FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,

            CONSTRAINT chk_bookings_v2_time_valid          CHECK (start_time < end_time),
            CONSTRAINT chk_bookings_v2_total_price_nonneg  CHECK (total_price >= 0),
            CONSTRAINT chk_bookings_v2_deposit_nonneg      CHECK (deposit >= 0),
            CONSTRAINT chk_bookings_v2_paid_nonneg         CHECK (paid_amount >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        stepOK("Tạo bảng bookings V2");
    } else {
        stepSkip("Bảng bookings đã tồn tại (" . rowCount($pdo,'bookings') . " dòng)");
    }

    $paymentMethodColumn = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'")->fetch();
    if (!$paymentMethodColumn) {
        throw new RuntimeException("Không tìm thấy cột bookings.payment_method");
    }
    if (strpos($paymentMethodColumn['Type'], "'vnpay'") === false) {
        $pdo->exec("ALTER TABLE bookings
            MODIFY payment_method ENUM('cash','banking','momo','zalo','vnpay','other')
            NOT NULL DEFAULT 'cash'");
        stepOK("Bổ sung VNPay vào phương thức thanh toán");
    } else {
        stepSkip("Phương thức thanh toán VNPay đã sẵn sàng");
    }

    stepInfo("\n===== BUỚC 7: Migrate dữ liệu từ bookings_old_v1 -> bookings V2 =====");
    if (tableExists($pdo, 'bookings_old_v1') && tableExists($pdo, 'bookings')) {
        $newCount = rowCount($pdo, 'bookings');
        $oldCount = rowCount($pdo, 'bookings_old_v1');
        if ($newCount === 0 && $oldCount > 0) {
            $pdo->exec("INSERT INTO bookings (customer_id, pitch_id, booking_date, start_time, end_time,
                          total_price, deposit, paid_amount, payment_method, payment_status,
                          status, cancellation_reason, note, created_by)
            SELECT
                c.id                     AS customer_id,
                o.pitch_id               AS pitch_id,
                o.booking_date           AS booking_date,
                COALESCE(ts.start_time, '18:00:00') AS start_time,
                COALESCE(ts.end_time,   '20:00:00') AS end_time,
                o.total_price            AS total_price,
                0                        AS deposit,
                CASE WHEN o.status = 'confirmed' THEN o.total_price ELSE 0 END AS paid_amount,
                'cash'                   AS payment_method,
                CASE
                    WHEN o.status = 'confirmed' THEN 'paid'
                    ELSE 'unpaid'
                END                      AS payment_status,
                CASE
                    WHEN o.status = 'confirmed' THEN 'confirmed'
                    WHEN o.status = 'cancelled' THEN 'cancelled'
                    ELSE 'pending'
                END                      AS status,
                NULL                     AS cancellation_reason,
                o.notes                  AS note,
                NULL                     AS created_by
            FROM bookings_old_v1 o
            INNER JOIN customers c ON c.phone = o.customer_phone
            LEFT JOIN time_slots ts ON ts.id = o.time_slot_id");
            stepOK("Migrate {$oldCount} lịch từ bookings_old_v1 -> bookings V2");
        } else {
            stepSkip("Bỏ qua (bookings V2 đã có {$newCount} dòng, HOẶC bookings_old_v1 trống)");
        }
    } else {
        stepSkip("Không có bookings_old_v1 hoặc chưa có bookings V2");
    }

    stepInfo("\n===== BUỚC 8: Seed thêm sân mẫu (tùy chọn) =====");
    if (tableExists($pdo, 'pitches')) {
        $pdo->exec("INSERT INTO pitches (name, type, price_per_hour, description, status)
        SELECT samples.name, samples.type, samples.price_per_hour, samples.description, samples.status
        FROM (
            SELECT 'Sân B2 - Sân 7 người cỏ nhân tạo' AS name, 7 AS type, 480000 AS price_per_hour,
                   'Sân 7 người cỏ nhân tạo, chiếu sáng ban đêm, phòng thay đồ riêng.' AS description, 'active' AS status
        ) AS samples
        WHERE NOT EXISTS (SELECT 1 FROM pitches p WHERE p.name = samples.name)");
        stepOK("Đảm bảo có sân mẫu 7 người (không thêm trùng tên)");

        $pdo->exec("DELETE FROM pitches
            WHERE type = 11
              AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.pitch_id = pitches.id)
              AND NOT EXISTS (SELECT 1 FROM pitch_locks pl WHERE pl.pitch_id = pitches.id)");
        $pdo->exec("UPDATE pitches SET status = 'inactive' WHERE type = 11 AND status <> 'inactive'");
        stepOK("Ngừng kinh doanh sân 11 người; giữ sân có lịch sử để bảo toàn dữ liệu");
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n========================================\n";
    echo "✅  NÂNG CẤP CSDL V2 THÀNH CÔNG!\n";
    echo "========================================\n";
    echo "Tiếp theo, chạy: php database/seed_users.php\n";
    echo "   Để tạo tài khoản ADMIN (admin/admin123) và STAFF (staff/staff123)\n\n";

    // In bảng tổng kết
    $finalTables = ['users', 'customers', 'pitches', 'time_slots', 'pitch_locks', 'bookings'];
    echo "TỔNG KẾT CÁC BẢNG:\n";
    foreach ($finalTables as $t) {
        if (tableExists($pdo, $t)) {
            echo "  - {$t}: " . rowCount($pdo, $t) . " dòng\n";
        } else {
            echo "  - {$t}: [KHÔNG TỒN TẠI]\n";
        }
    }
    if (tableExists($pdo, 'bookings_old_v1')) {
        echo "  - bookings_old_v1: " . rowCount($pdo,'bookings_old_v1') . " dòng (lưu trữ, có thể DROP sau khi verify)\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo "\n========================================\n";
    echo "❌ LỖI KHI NÂNG CẤP CSDL: \n";
    echo "   " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "========================================\n";
    exit(1);
}
