-- Database schema for the football pitch booking application.
-- Safe to re-run: this script does not drop tables or existing records.
-- Existing databases: run database/run_upgrade.php, then the customer portal and
-- ratings/notifications migration scripts with PHP.

CREATE DATABASE IF NOT EXISTS db_football_field_booking
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_football_field_booking;

-- Public sign-ups create active USER accounts immediately; ADMIN access remains restricted.
CREATE TABLE IF NOT EXISTS users (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(20) NULL,
    role          ENUM('ADMIN','USER') NOT NULL DEFAULT 'USER',
    status        ENUM('active','inactive','pending') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role),
    INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(20) NOT NULL UNIQUE,
    email      VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pitches (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    name           VARCHAR(150) NOT NULL,
    type           INT NOT NULL,
    price_per_hour DECIMAL(12,0) NOT NULL DEFAULT 0,
    description    TEXT NULL,
    image          VARCHAR(255) NULL,
    address        VARCHAR(255) NULL,
    opening_time   TIME NOT NULL DEFAULT '06:00:00',
    closing_time   TIME NOT NULL DEFAULT '23:00:00',
    grass_quality  VARCHAR(100) NULL,
    has_lighting   TINYINT(1) NOT NULL DEFAULT 0,
    has_changing_room TINYINT(1) NOT NULL DEFAULT 0,
    has_parking    TINYINT(1) NOT NULL DEFAULT 0,
    extra_services TEXT NULL,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pitch_type (type),
    INDEX idx_pitch_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_slots (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    start_time TIME NOT NULL,
    end_time   TIME NOT NULL,
    label      VARCHAR(50) NULL,
    INDEX idx_slot_start (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pitch_locks (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    pitch_id   INT NOT NULL,
    lock_date  DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time   TIME NOT NULL,
    reason     VARCHAR(255) NULL,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lock_pitch_date (pitch_id, lock_date),
    INDEX idx_lock_start_time (start_time),
    CONSTRAINT fk_pitchlock_pitch FOREIGN KEY (pitch_id)
        REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_pitchlock_user FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_lock_time CHECK (start_time < end_time),
    CONSTRAINT chk_lock_range CHECK (
        start_time >= '06:00:00' AND end_time <= '23:00:00'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_series (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    customer_id    INT NOT NULL,
    pitch_id       INT NOT NULL,
    booking_month  DATE NOT NULL,
    start_time     TIME NOT NULL,
    end_time       TIME NOT NULL,
    total_price    DECIMAL(12,0) NOT NULL DEFAULT 0,
    deposit        DECIMAL(12,0) NOT NULL DEFAULT 0,
    paid_amount    DECIMAL(12,0) NOT NULL DEFAULT 0,
    payment_method ENUM('vnpay') NOT NULL DEFAULT 'vnpay',
    payment_status ENUM('unpaid','deposit','partial','paid') NOT NULL DEFAULT 'unpaid',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    customer_id         INT NOT NULL,
    pitch_id            INT NOT NULL,
    series_id           INT NULL,
    booking_date        DATE NOT NULL,
    start_time          TIME NOT NULL,
    end_time            TIME NOT NULL,
    total_price         DECIMAL(12,0) NOT NULL DEFAULT 0,
    deposit             DECIMAL(12,0) NOT NULL DEFAULT 0,
    paid_amount         DECIMAL(12,0) NOT NULL DEFAULT 0,
    vnpay_txn_ref       VARCHAR(100) NULL,
    vnpay_transaction_no VARCHAR(15) NULL,
    vnpay_transaction_date CHAR(14) NULL,
    payment_method      ENUM('cash','banking','momo','zalo','vnpay','other') NOT NULL DEFAULT 'cash',
    payment_status      ENUM('unpaid','deposit','partial','paid') NOT NULL DEFAULT 'unpaid',
    status              ENUM('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    cancellation_reason TEXT NULL,
    note                TEXT NULL,
    created_by          INT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_date (booking_date),
    INDEX idx_booking_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_pitch_date (pitch_id, booking_date),
    INDEX idx_customer_id (customer_id),
    INDEX idx_created_by (created_by),
    INDEX idx_booking_series (series_id),
    INDEX idx_booking_vnpay_txn_ref (vnpay_txn_ref),
    CONSTRAINT fk_bookings_v2_customer FOREIGN KEY (customer_id)
        REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_v2_pitch FOREIGN KEY (pitch_id)
        REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_v2_user FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_bookings_series FOREIGN KEY (series_id)
        REFERENCES booking_series(id) ON DELETE SET NULL,
    CONSTRAINT chk_bookings_v2_time_valid CHECK (start_time < end_time),
    CONSTRAINT chk_bookings_v2_total_price_nonneg CHECK (total_price >= 0),
    CONSTRAINT chk_bookings_v2_deposit_nonneg CHECK (deposit >= 0),
    CONSTRAINT chk_bookings_v2_paid_nonneg CHECK (paid_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_cancellation_requests (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pitch_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    pitch_id INT NOT NULL,
    customer_user_id INT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pitch_rating_booking (booking_id),
    INDEX idx_pitch_ratings_pitch_created (pitch_id, created_at),
    CONSTRAINT chk_pitch_rating_value CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_pitch_rating_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_pitch_rating_pitch FOREIGN KEY (pitch_id)
        REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_pitch_rating_customer FOREIGN KEY (customer_user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(160) NOT NULL,
    message VARCHAR(500) NOT NULL,
    url VARCHAR(255) NOT NULL,
    dedupe_key VARCHAR(120) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_notification_dedupe (user_id, dedupe_key),
    INDEX idx_user_notifications_unread (user_id, is_read, created_at),
    CONSTRAINT fk_user_notification_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services module
CREATE TABLE IF NOT EXISTS services (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    name          VARCHAR(150) NOT NULL,
    category      VARCHAR(50) NOT NULL DEFAULT 'other',
    price         DECIMAL(12,0) NOT NULL DEFAULT 0,
    unit          VARCHAR(30) NOT NULL DEFAULT 'cái',
    stock         INT NOT NULL DEFAULT 0,
    description   TEXT NULL,
    image         VARCHAR(255) NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_category (category),
    INDEX idx_service_status (status),
    INDEX idx_service_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_services (
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
    CONSTRAINT fk_bs_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_bs_service FOREIGN KEY (service_id)
        REFERENCES services(id) ON DELETE RESTRICT,
    CONSTRAINT chk_bs_quantity_pos CHECK (quantity > 0),
    CONSTRAINT chk_bs_price_nonneg CHECK (unit_price >= 0),
    CONSTRAINT chk_bs_subtotal_nonneg CHECK (subtotal >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sample services only when a service with the same name is absent.
INSERT INTO services (name, category, price, unit, stock, description, status)
SELECT samples.name, samples.category, samples.price, samples.unit, samples.stock, samples.description, 'active'
FROM (
    SELECT 'Nước suối 500ml' AS name, 'drink' AS category, 10000 AS price, 'chai' AS unit, 100 AS stock,
           'Nước đóng chai 500ml' AS description
    UNION ALL SELECT 'Nước tăng lực', 'drink', 15000, 'lon', 80, 'Nước tăng lực lon 330ml'
    UNION ALL SELECT 'Cà phê sữa đá', 'drink', 20000, 'ly', 50, 'Cà phê pha sữa đá'
    UNION ALL SELECT 'Bánh mì kẹp thịt', 'food', 25000, 'cái', 30, 'Bánh mì kẹp thịt heo'
    UNION ALL SELECT 'Thuê bóng số 5', 'equipment', 20000, 'quả', 20, 'Bóng đá số 5 cỏ nhân tạo'
    UNION ALL SELECT 'Thuê bóng số 7', 'equipment', 25000, 'quả', 15, 'Bóng đá số 7 cỏ nhân tạo'
    UNION ALL SELECT 'Thuê áo đấu', 'equipment', 15000, 'bộ', 40, 'Áo đấu màu tiêu chuẩn'
    UNION ALL SELECT 'Dịch vụ chụp ảnh', 'service', 150000, 'giờ', 5, 'Chụp ảnh trong trận bóng'
) AS samples
WHERE NOT EXISTS (SELECT 1 FROM services s WHERE s.name = samples.name);

-- Add default pitches only when a pitch with the same name is absent.
-- Keep existing default pitch records in sync without changing their IDs or bookings.
UPDATE pitches SET name = CASE name
    WHEN 'Sân A1 - Sân 5 người cỏ nhân tạo' THEN 'Sân A1 - Sân 5 người cỏ nhân tạo - Sân bóng Phòng không - Không quân'
    WHEN 'Sân A2 - Sân 5 người cỏ tự nhiên' THEN 'Sân A2 - Sân 5 người cỏ tự nhiên - Sân bóng Cao đẳng Múa'
    WHEN 'Sân B1 - Sân 7 người cao cấp' THEN 'Sân B1 - Sân 7 người cao cấp - Sân bóng Đại học Thủy Lợi'
    WHEN 'Sân B2 - Sân 7 người cỏ nhân tạo' THEN 'Sân B2 - Sân 7 người cỏ nhân tạo - Sân bóng Học viện An Ninh'
    ELSE name
END
WHERE name IN (
    'Sân A1 - Sân 5 người cỏ nhân tạo',
    'Sân A2 - Sân 5 người cỏ tự nhiên',
    'Sân B1 - Sân 7 người cao cấp',
    'Sân B2 - Sân 7 người cỏ nhân tạo'
);

INSERT INTO pitches (name, type, price_per_hour, description, status)
SELECT samples.name, samples.type, samples.price_per_hour, samples.description, 'active'
FROM (
    SELECT 'Sân A1 - Sân 5 người cỏ nhân tạo - Sân bóng Phòng không - Không quân' AS name, 5 AS type, 250000 AS price_per_hour,
           'Sân 5 người cỏ nhân tạo.' AS description
    UNION ALL SELECT 'Sân A2 - Sân 5 người cỏ tự nhiên - Sân bóng Cao đẳng Múa', 5, 200000, 'Sân 5 người cỏ tự nhiên.'
    UNION ALL SELECT 'Sân B1 - Sân 7 người cao cấp - Sân bóng Đại học Thủy Lợi', 7, 450000, 'Sân 7 người tiêu chuẩn.'
    UNION ALL SELECT 'Sân B2 - Sân 7 người cỏ nhân tạo - Sân bóng Học viện An Ninh', 7, 480000, 'Sân 7 người cỏ nhân tạo.'
) AS samples
WHERE NOT EXISTS (SELECT 1 FROM pitches p WHERE p.name = samples.name);

-- Keep historical pitches with linked records, but remove unused 11-a-side pitches.
DELETE FROM pitches
WHERE type = 11
  AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.pitch_id = pitches.id)
  AND NOT EXISTS (SELECT 1 FROM pitch_locks pl WHERE pl.pitch_id = pitches.id);

UPDATE pitches SET status = 'inactive' WHERE type = 11 AND status <> 'inactive';

-- Add any missing hourly slots between 06:00 and 23:00.
INSERT INTO time_slots (start_time, end_time)
SELECT samples.start_time, samples.end_time
FROM (
    SELECT '06:00:00' AS start_time, '07:00:00' AS end_time
    UNION ALL SELECT '07:00:00', '08:00:00'
    UNION ALL SELECT '08:00:00', '09:00:00'
    UNION ALL SELECT '09:00:00', '10:00:00'
    UNION ALL SELECT '10:00:00', '11:00:00'
    UNION ALL SELECT '11:00:00', '12:00:00'
    UNION ALL SELECT '12:00:00', '13:00:00'
    UNION ALL SELECT '13:00:00', '14:00:00'
    UNION ALL SELECT '14:00:00', '15:00:00'
    UNION ALL SELECT '15:00:00', '16:00:00'
    UNION ALL SELECT '16:00:00', '17:00:00'
    UNION ALL SELECT '17:00:00', '18:00:00'
    UNION ALL SELECT '18:00:00', '19:00:00'
    UNION ALL SELECT '19:00:00', '20:00:00'
    UNION ALL SELECT '20:00:00', '21:00:00'
    UNION ALL SELECT '21:00:00', '22:00:00'
    UNION ALL SELECT '22:00:00', '23:00:00'
) AS samples
WHERE NOT EXISTS (
    SELECT 1 FROM time_slots t
    WHERE t.start_time = samples.start_time AND t.end_time = samples.end_time
);

-- Bootstrap an ADMIN if needed with: php database/seed_users.php (prints a random password once).
