-- ============================================================
-- HỆ THỐNG ĐẶT SÂN BÓNG - DATABASE SCHEMA + SEED
-- ============================================================

-- Nếu cần tạo CSDL từ đầu:
-- CREATE DATABASE IF NOT EXISTS db_football_field_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE db_football_field_booking;

-- ------------------------------------------------------------
-- 1. Bảng pitches (Sân bóng)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS pitches;
DROP TABLE IF EXISTS time_slots;

CREATE TABLE pitches (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    type            TINYINT NOT NULL COMMENT '5=Sân 5 người, 7=Sân 7 người, 11=Sân 11 người',
    price_per_hour  DECIMAL(12,0) NOT NULL DEFAULT 0,
    description     TEXT NULL,
    image           VARCHAR(255) NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Bảng time_slots (Khung giờ thuê sân)
-- ------------------------------------------------------------
CREATE TABLE time_slots (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Bảng bookings (Đơn đặt sân)
-- ------------------------------------------------------------
CREATE TABLE bookings (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    pitch_id        INT NOT NULL,
    customer_name   VARCHAR(100) NOT NULL,
    customer_phone  VARCHAR(20)  NOT NULL,
    customer_email  VARCHAR(100) NULL,
    booking_date    DATE NOT NULL,
    time_slot_id    INT NOT NULL,
    total_price     DECIMAL(12,0) NOT NULL DEFAULT 0,
    status          ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    notes           TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_pitch  FOREIGN KEY (pitch_id)       REFERENCES pitches(id)       ON DELETE CASCADE,
    CONSTRAINT fk_booking_slot   FOREIGN KEY (time_slot_id)   REFERENCES time_slots(id)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_booking_date    ON bookings(booking_date);
CREATE INDEX idx_booking_status  ON bookings(status);
CREATE INDEX idx_booking_pitch   ON bookings(pitch_id);

-- ============================================================
-- SEED DỮ LIỆU MẪU
-- ============================================================

-- Seed pitches (4 sân mẫu)
INSERT INTO pitches (name, type, price_per_hour, description, image, status) VALUES
('Sân A1 - Sân 5 người cỏ nhân tạo', 5,  250000, 'Sân 5 người cỏ nhân tạo mới, lưới bao quanh đầy đủ, hệ thống chiếu sáng LED.', 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Mini%20football%20field%205%20people%20with%20artificial%20grass%20green%20clean%20bright%20outdoor&image_size=square', 'active'),
('Sân A2 - Sân 5 người cỏ tự nhiên', 5,  200000, 'Sân 5 người cỏ tự nhiên, môi trường trong lành, phù hợp luyện tập nhẹ.', 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Outdoor%20small%20football%20pitch%20with%20natural%20grass%20sunny%20day&image_size=square', 'active'),
('Sân B1 - Sân 7 người cao cấp',    7,  450000, 'Sân 7 người tiêu chuẩn, cỏ nhân tạo dày, bóng đèn, phòng thay đồ riêng.', 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=7%20people%20football%20field%20premium%20artificial%20grass%20floodlight%20night&image_size=square', 'active'),
('Sân C1 - Sân 11 người tiêu chuẩn', 11, 800000, 'Sân 11 người kích thước chuẩn FIFA, cỏ tự nhiên, khán đài, hệ thống chiếu sáng chuyên nghiệp.', 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Full%2011%20player%20football%20stadium%20grass%20pitch%20green%20professional&image_size=square', 'active');

-- Seed time_slots: 17 khung giờ từ 06:00 -> 23:00 (mỗi khung 1 tiếng)
INSERT INTO time_slots (start_time, end_time) VALUES
('06:00:00','07:00:00'),
('07:00:00','08:00:00'),
('08:00:00','09:00:00'),
('09:00:00','10:00:00'),
('10:00:00','11:00:00'),
('11:00:00','12:00:00'),
('12:00:00','13:00:00'),
('13:00:00','14:00:00'),
('14:00:00','15:00:00'),
('15:00:00','16:00:00'),
('16:00:00','17:00:00'),
('17:00:00','18:00:00'),
('18:00:00','19:00:00'),
('19:00:00','20:00:00'),
('20:00:00','21:00:00'),
('21:00:00','22:00:00'),
('22:00:00','23:00:00');

-- Seed bookings mẫu (2 đơn hôm nay + 1 đơn ngày mai)
-- Lấy hôm nay và ngày mai
SET @today = CURDATE();
SET @tomorrow = DATE_ADD(CURDATE(), INTERVAL 1 DAY);

INSERT INTO bookings (pitch_id, customer_name, customer_phone, customer_email, booking_date, time_slot_id, total_price, status, notes) VALUES
(1, 'Nguyễn Văn A', '0901234567', 'vana@example.com',   @today,     15, 250000, 'confirmed', 'Đặt trước nhóm 10 người'),
(3, 'Trần Thị B',  '0912345678', 'thib@example.com',   @today,     16, 450000, 'pending',   ''),
(4, 'Lê Văn C',    '0934567890', 'vanc@example.com',   @tomorrow,  14, 800000, 'confirmed', 'Giải đấu giữa 2 công ty');
