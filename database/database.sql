-- Database schema for the football pitch booking application.
-- Safe to re-run: this script does not drop tables or existing records.
-- For upgrading legacy bookings (time_slot_id), run: php database/run_upgrade.php

CREATE DATABASE IF NOT EXISTS db_football_field_booking
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_football_field_booking;

CREATE TABLE IF NOT EXISTS users (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(20) NULL,
    role          ENUM('ADMIN','STAFF') NOT NULL DEFAULT 'STAFF',
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
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

CREATE TABLE IF NOT EXISTS bookings (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    customer_id         INT NOT NULL,
    pitch_id            INT NOT NULL,
    booking_date        DATE NOT NULL,
    start_time          TIME NOT NULL,
    end_time            TIME NOT NULL,
    total_price         DECIMAL(12,0) NOT NULL DEFAULT 0,
    deposit             DECIMAL(12,0) NOT NULL DEFAULT 0,
    paid_amount         DECIMAL(12,0) NOT NULL DEFAULT 0,
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
    CONSTRAINT fk_bookings_v2_customer FOREIGN KEY (customer_id)
        REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_v2_pitch FOREIGN KEY (pitch_id)
        REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_v2_user FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_bookings_v2_time_valid CHECK (start_time < end_time),
    CONSTRAINT chk_bookings_v2_total_price_nonneg CHECK (total_price >= 0),
    CONSTRAINT chk_bookings_v2_deposit_nonneg CHECK (deposit >= 0),
    CONSTRAINT chk_bookings_v2_paid_nonneg CHECK (paid_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add default pitches only when a pitch with the same name is absent.
INSERT INTO pitches (name, type, price_per_hour, description, status)
SELECT samples.name, samples.type, samples.price_per_hour, samples.description, 'active'
FROM (
    SELECT 'Sân A1 - Sân 5 người cỏ nhân tạo' AS name, 5 AS type, 250000 AS price_per_hour,
           'Sân 5 người cỏ nhân tạo.' AS description
    UNION ALL SELECT 'Sân A2 - Sân 5 người cỏ tự nhiên', 5, 200000, 'Sân 5 người cỏ tự nhiên.'
    UNION ALL SELECT 'Sân B1 - Sân 7 người cao cấp', 7, 450000, 'Sân 7 người tiêu chuẩn.'
    UNION ALL SELECT 'Sân B2 - Sân 7 người cỏ nhân tạo', 7, 480000, 'Sân 7 người cỏ nhân tạo.'
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

-- Create login accounts separately with: php database/seed_users.php
