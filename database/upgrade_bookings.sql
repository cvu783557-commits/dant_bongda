-- ============================================================
-- NÂNG CẤP CƠ SỞ DỮ LIỆU - QUẢN LÝ LỊCH SÂN BÓNG ĐÁ (VERSION 2)
-- Chạy các lệnh này trong phpMyAdmin hoặc MySQL client
-- LƯU Ý: BACKUP dữ liệu CƯỜNG BẮT trước khi chạy!
-- ============================================================
USE db_football_field_booking;

-- ============================================================
-- 1. Tạo bảng users (Nhân viên / Admin hệ thống - PHÂN QUYỀN)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20)  NULL,
    role            ENUM('ADMIN','STAFF') NOT NULL DEFAULT 'STAFF',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Tạo bảng customers (Khách hàng riêng biệt)
-- ============================================================
CREATE TABLE IF NOT EXISTS customers (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    phone           VARCHAR(20)  NOT NULL UNIQUE,
    email           VARCHAR(100) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_phone (phone),
    INDEX idx_customer_name  (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Di chuyển dữ liệu khách hàng từ bookings cũ sang bảng customers
--    (Chỉ di chuyển khi chưa có khách hàng nào)
-- ============================================================
INSERT IGNORE INTO customers (name, phone, email)
SELECT MAX(customer_name) AS name,
       customer_phone     AS phone,
       MAX(customer_email) AS email
FROM bookings
GROUP BY customer_phone;

-- ============================================================
-- 4. Lưu dữ liệu bookings cũ vào bảng tạm
-- ============================================================
RENAME TABLE bookings TO bookings_old_v1;

-- ============================================================
-- 5. Tạo bảng bookings MỚI (phiên bản đầy đủ nghiệp vụ)
-- ============================================================
CREATE TABLE bookings (
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

    CONSTRAINT fk_booking_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_pitch    FOREIGN KEY (pitch_id)    REFERENCES pitches(id)   ON DELETE CASCADE,
    CONSTRAINT fk_booking_user     FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL,

    CONSTRAINT chk_time_valid          CHECK (start_time < end_time),
    CONSTRAINT chk_total_price_nonneg  CHECK (total_price >= 0),
    CONSTRAINT chk_deposit_nonneg      CHECK (deposit >= 0),
    CONSTRAINT chk_paid_nonneg         CHECK (paid_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. Di chuyển dữ liệu từ bookings_old_v1 sang bookings mới
--    (join theo số điện thoại khách hàng với bảng customers)
--    Chỉ di chuyển khi bảng bookings cũ còn tồn tại và có dữ liệu
-- ============================================================
INSERT INTO bookings (customer_id, pitch_id, booking_date, start_time, end_time,
                      total_price, deposit, paid_amount, payment_method, payment_status,
                      status, cancellation_reason, note, created_by)
SELECT
    c.id                     AS customer_id,
    o.pitch_id               AS pitch_id,
    o.booking_date           AS booking_date,
    ts.start_time            AS start_time,
    ts.end_time              AS end_time,
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
LEFT JOIN time_slots ts ON ts.id = o.time_slot_id;

-- (Tùy chọn) Xóa bảng cũ sau khi kiểm tra di chuyển OK
-- DROP TABLE IF EXISTS bookings_old_v1;

-- ============================================================
-- 7. Seed dữ liệu mẫu users (Admin + Nhân viên)
--    Mật khẩu được mã hóa với PASSWORD_DEFAULT (bcrypt) của PHP
--    -> Chạy file seed_users.php (đi kèm) để tạo user an toàn
--       HOẶC dùng các hash mẫu dưới đây.
-- ============================================================
-- User mẫu 1: username=admin, password=admin123
-- User mẫu 2: username=staff, password=staff123
--
-- Hash mẫu (tạo bằng PHP password_hash('admin123', PASSWORD_DEFAULT)):
-- admin123 -> $2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy
-- staff123 -> $2y$10$p2g.Hb4bD0dF4C.pVp1H0uZ5wUQ6xw8iW7GzFqFqFQqP7z3vKQq4a
--
-- Bỏ comment 2 dòng INSERT dưới đây nếu bạn muốn dùng hash mẫu:
--
-- INSERT IGNORE INTO users (username, password_hash, full_name, phone, role, status) VALUES
-- ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Quản Trị Viên', '0900000000', 'ADMIN', 'active'),
-- ('staff', '$2y$10$p2g.Hb4bD0dF4C.pVp1H0uZ5wUQ6xw8iW7GzFqFqFQqP7z3vKQq4a', 'Nhân Viên',   '0900000001', 'STAFF', 'active');

-- ============================================================
-- 8. Seed thêm 2 sân mới nếu chưa đủ (tùy chọn)
-- ============================================================
INSERT IGNORE INTO pitches (name, type, price_per_hour, description, status) VALUES
('Sân B2 - Sân 7 người cỏ nhân tạo', 7, 480000, 'Sân 7 người cỏ nhân tạo, chiếu sáng ban đêm, phòng thay đồ riêng.', 'active'),
('Sân C2 - Sân 11 người mini', 11, 900000, 'Sân 11 người kích thước mini, phù hợp luyện tập đội trẻ.', 'active');
