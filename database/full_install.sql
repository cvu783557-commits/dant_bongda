-- ============================================================
-- HỆ THỐNG ĐẶT SÂN BÓNG - DATABASE FULL (Version 2.0 - ĐẦY ĐỦ NGHIỆP VỤ)
-- File này có thể import THẲNG vào phpMyAdmin để chạy được NGAY
-- Tương thích code: dant_bongda (MVC PHP 8.x - Doctrine DBAL + BladeOne)
-- ============================================================

-- Bước 0: (Tuỳ chọn) Tạo CSDL từ đầu
-- CREATE DATABASE IF NOT EXISTS db_football_field_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE db_football_field_booking;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Bước 1: Xoá các bảng cũ (nếu có) theo đúng thứ tự phụ thuộc FK
-- ============================================================
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS pitch_locks;
DROP TABLE IF EXISTS time_slots;
DROP TABLE IF EXISTS pitches;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Bước 2: Tạo bảng users (Nhân viên / ADMIN - PHÂN QUYỀN)
-- * 2 role chính: ADMIN (quyền full) / STAFF (thao tác nghiệp vụ - không được xóa)
-- ============================================================
CREATE TABLE users (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    username        VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Tên đăng nhập',
    password_hash   VARCHAR(255) NOT NULL COMMENT 'Mật khẩu bcrypt từ PHP password_hash()',
    full_name       VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị nhân viên',
    phone           VARCHAR(20)  NULL COMMENT 'Số điện thoại liên lạc',
    role            ENUM('ADMIN','STAFF') NOT NULL DEFAULT 'STAFF' COMMENT 'Phân quyền ADMIN / STAFF',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Trạng thái tài khoản - active = đăng nhập được',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role   (role),
    INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bước 3: Tạo bảng customers (Khách hàng riêng - tách khỏi bookings)
-- * 1 khách -> N đơn đặt lịch
-- ============================================================
CREATE TABLE customers (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL COMMENT 'Họ tên khách hàng',
    phone           VARCHAR(20)  NOT NULL UNIQUE COMMENT 'Số điện thoại (KHÓA DUY NHẤT - để phân biệt khách)',
    email           VARCHAR(100) NULL COMMENT 'Email liên lạc (không bắt buộc)',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_phone (phone),
    INDEX idx_customer_name  (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bước 4: Tạo bảng pitches (Sân bóng)
-- ============================================================
CREATE TABLE pitches (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL COMMENT 'Tên sân (VD: Sân A1 - Sân 5 người)',
    type            TINYINT NOT NULL COMMENT 'Loại sân: 5 / 7 / 11 người',
    price_per_hour  DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Giá thuê / 1 giờ (VNĐ)',
    description     TEXT NULL COMMENT 'Mô tả chi tiết sân',
    image           VARCHAR(255) NULL COMMENT 'URL ảnh đại diện sân',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'active = hoạt động, inactive = tạm ngừng (không cho đặt)',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pitch_type   (type),
    INDEX idx_pitch_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bước 5: Tạo bảng time_slots (Khung giờ tiêu chuẩn - tham chiếu UI)
-- * Lịch đặt KHÔNG dùng time_slot_id nữa, dùng start_time/end_time trực tiếp (TIME)
-- * Bảng này chỉ để hiển thị lịch dạng lưới (SÂN x GIỜ) và gợi ý người dùng
-- ============================================================
CREATE TABLE time_slots (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    start_time  TIME NOT NULL COMMENT 'Giờ bắt đầu khung 1 tiếng',
    end_time    TIME NOT NULL COMMENT 'Giờ kết thúc khung 1 tiếng',
    INDEX idx_slot_start (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bước 6: Tạo bảng pitch_locks (KHÓA KHUNG GIỜ - tính năng mới yêu cầu)
-- * Để khóa sân theo giờ theo ngày (bảo trì, sự kiện, giải đấu...)
-- * Trùng overlap với pitch_locks thì KHÔNG cho đặt lịch
-- ============================================================
CREATE TABLE pitch_locks (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    pitch_id    INT NOT NULL COMMENT 'FK -> pitches.id (Sân bị khóa)',
    lock_date   DATE NOT NULL COMMENT 'Ngày bị khóa',
    start_time  TIME NOT NULL COMMENT 'Giờ bắt đầu khóa',
    end_time    TIME NOT NULL COMMENT 'Giờ kết thúc khóa',
    reason      VARCHAR(255) NULL COMMENT 'Lý do khóa (hiển thị cho nhân viên / khách)',
    created_by  INT NULL COMMENT 'FK -> users.id (ai là người khóa)',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_lock_pitch_date  (pitch_id, lock_date),
    INDEX idx_lock_start_time  (start_time),

    CONSTRAINT fk_pitchlock_pitch FOREIGN KEY (pitch_id)   REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_pitchlock_user  FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE SET NULL,

    CONSTRAINT chk_lock_time CHECK (start_time < end_time),
    CONSTRAINT chk_lock_range CHECK (
        start_time >= '06:00:00' AND end_time <= '23:00:00'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bước 7: Tạo bảng bookings (Đơn đặt sân - Version 2 đầy đủ nghiệp vụ)
-- * Quan hệ: N-1 với customers, pitches, users (created_by)
-- * Có đầy đủ nghiệp vụ đặt cọc, thanh toán nhiều lần, trạng thái 5 bước
-- ============================================================
CREATE TABLE bookings (
    id                      INT PRIMARY KEY AUTO_INCREMENT,
    customer_id             INT NOT NULL COMMENT 'FK -> customers.id (Khách hàng đặt sân)',
    pitch_id                INT NOT NULL COMMENT 'FK -> pitches.id (Sân đặt)',
    booking_date            DATE NOT NULL COMMENT 'Ngày đặt sân (YYYY-MM-DD)',
    start_time              TIME NOT NULL COMMENT 'Giờ bắt đầu (TIME - có thể qua nhiều slot)',
    end_time                TIME NOT NULL COMMENT 'Giờ kết thúc (TIME - luôn > start_time)',
    total_price             DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Tổng tiền thuê sân (đơn giá × số giờ, có thể điều chỉnh)',
    deposit                 DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Tiền đặt cọc (đã trả lúc đặt)',
    paid_amount             DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Tổng số tiền đã thanh toán (có thể trả nhiều đợt)',
    payment_method          ENUM('cash','banking','momo','zalo','other') NOT NULL DEFAULT 'cash' COMMENT 'Hình thức thanh toán cuối cùng',
    payment_status          ENUM('unpaid','deposit','partial','paid') NOT NULL DEFAULT 'unpaid' COMMENT 'Tự tính theo total/deposit/paid_amount',
    status                  ENUM('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Luồng trạng thái lịch sân',
    cancellation_reason     TEXT NULL COMMENT 'Lý do hủy lịch (khi status = cancelled)',
    note                    TEXT NULL COMMENT 'Ghi chú nội bộ hoặc yêu cầu của khách',
    created_by              INT NULL COMMENT 'FK -> users.id (nhân viên tạo đơn)',
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
    CONSTRAINT chk_paid_nonneg         CHECK (paid_amount >= 0),
    CONSTRAINT chk_hours_valid CHECK (
        start_time >= '06:00:00' AND end_time <= '23:00:00'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bước 8: SEED DỮ LIỆU MẪU users (Đăng nhập hệ thống quản trị)
-- ⚠️ MẬT KHẨU mã hóa BCRYPT (PASSWORD_DEFAULT) thật - PHP password_verify() CHẤP NHẬN ĐÚNG
-- ------------------------------------------------------------
-- 👉 user ADMIN:    username = admin   |  password = admin123
-- 👉 user NHÂN VIÊN: username = staff   |  password = staff123
-- ============================================================
INSERT INTO users (id, username, password_hash, full_name, phone, role, status) VALUES
(1, 'admin', '$2y$10$KV35i89isk6B5EvRf/7UfukZFKQf0MF/LGk6S5BKSPHNAehyuN7v.',
        'Quản Trị Viên', '0900000000', 'ADMIN', 'active'),
(2, 'staff', '$2y$10$gZwoEO.KNJyzEWpeQgmnhOTYVJZaetle0tb7RA/AXSNmvY8AvLEta',
        'Nhân Viên Bán Hàng', '0900000001', 'STAFF', 'active');

-- ============================================================
-- Bước 9: SEED DỮ LIỆU MẪU customers (Khách hàng mẫu)
-- ============================================================
INSERT INTO customers (id, name, phone, email) VALUES
(1, 'Nguyễn Văn An',     '0901234567', 'an.nguyen@example.com'),
(2, 'Trần Thị Bình',    '0912345678', 'binh.tran@example.com'),
(3, 'Lê Văn Cường',     '0934567890', 'cuong.le@example.com'),
(4, 'Đặng Thị Duyên',   '0977111222', 'duyen.dang@example.com'),
(5, 'Phạm Văn Em',      '0988999777', 'em.pham@example.com'),
(6, 'Ngô Thị Phượng',   '0966555444', 'phuong.ngo@example.com'),
(7, 'Bùi Văn Giang',    '0944333222', 'giang.bui@example.com');

-- ============================================================
-- Bước 10: SEED DỮ LIỆU pitches (6 sân mẫu các loại)
-- ============================================================
INSERT INTO pitches (id, name, type, price_per_hour, description, image, status) VALUES
(1, 'Sân A1 - Sân 5 người cỏ nhân tạo',    5,  250000,
    'Sân 5 người cỏ nhân tạo mới nhập khẩu, lưới bao quanh đầy đủ, hệ thống chiếu sáng LED ban đêm, chỗ để xe rộng rãi.',
    'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Mini%20football%20field%205%20people%20with%20artificial%20grass%20green%20clean%20bright%20outdoor&image_size=square',
    'active'),

(2, 'Sân A2 - Sân 5 người cỏ tự nhiên',    5,  200000,
    'Sân 5 người cỏ tự nhiên, môi trường trong lành thoáng đãng, phù hợp luyện tập nhẹ nhàng đầu tuần.',
    'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Outdoor%20small%20football%20pitch%20with%20natural%20grass%20sunny%20day&image_size=square',
    'active'),

(3, 'Sân B1 - Sân 7 người cao cấp',        7,  450000,
    'Sân 7 người tiêu chuẩn thi đấu, cỏ nhân tạo dày chịu lực tốt, hệ thống đèn pha ban đêm, phòng thay đồ riêng có nước nóng lạnh.',
    'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=7%20people%20football%20field%20premium%20artificial%20grass%20floodlight%20night&image_size=square',
    'active'),

(4, 'Sân B2 - Sân 7 người cỏ nhân tạo',    7,  480000,
    'Sân 7 người cỏ nhân tạo thế hệ mới, thoát nước tốt ngay cả khi mưa lớn, chiếu sáng 4 đèn pha công suất lớn, phòng thay đồ riêng biệt.',
    'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Seven%20side%20football%20pitch%20new%20artificial%20turf%20bright%20led%20lights&image_size=square',
    'active'),

(5, 'Sân C1 - Sân 11 người tiêu chuẩn FIFA', 11, 800000,
    'Sân 11 người kích thước tiêu chuẩn FIFA, cỏ tự nhiên chăm sóc chuyên nghiệp, có khán đài 200 chỗ ngồi, hệ thống chiếu sáng chuyên nghiệp cho các trận đấu ban đêm.',
    'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Full%2011%20player%20football%20stadium%20grass%20pitch%20green%20professional&image_size=square',
    'active'),

(6, 'Sân C2 - Sân 11 người mini',         11, 900000,
    'Sân 11 người kích thước mini (80% so với tiêu chuẩn), phù hợp luyện tập đội trẻ U16/U18, cỏ nhân tạo, phòng thay đồ, khu vực khán đài.',
    'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Mini%2011%20aside%20football%20pitch%20artificial%20grass%20youth%20training&image_size=square',
    'active');

-- ============================================================
-- Bước 11: SEED DỮ LIỆU time_slots (17 khung giờ 06:00 -> 23:00 mỗi khung 1 tiếng)
-- ============================================================
INSERT INTO time_slots (id, start_time, end_time) VALUES
(1,  '06:00:00','07:00:00'),
(2,  '07:00:00','08:00:00'),
(3,  '08:00:00','09:00:00'),
(4,  '09:00:00','10:00:00'),
(5,  '10:00:00','11:00:00'),
(6,  '11:00:00','12:00:00'),
(7,  '12:00:00','13:00:00'),
(8,  '13:00:00','14:00:00'),
(9,  '14:00:00','15:00:00'),
(10, '15:00:00','16:00:00'),
(11, '16:00:00','17:00:00'),
(12, '17:00:00','18:00:00'),
(13, '18:00:00','19:00:00'),
(14, '19:00:00','20:00:00'),
(15, '20:00:00','21:00:00'),
(16, '21:00:00','22:00:00'),
(17, '22:00:00','23:00:00');

-- ============================================================
-- Bước 12: SEED DỮ LIỆU MẪU pitch_locks (VÍ DỤ khóa khung giờ)
-- * Khóa 1 khung giờ để demo tính năng
-- ============================================================
-- Lấy ngày mai làm ngày mẫu seed (tương đối) - vì đây là SQL tĩnh,
-- sau khi import xong bạn có thể vào "Khóa khung giờ" tạo mới theo ngày thật.
-- Dưới đây là seed tĩnh ví dụ cho ngày CURDATE() + 3 ngày, đảm bảo không lỗi NULL:
INSERT INTO pitch_locks (pitch_id, lock_date, start_time, end_time, reason, created_by) VALUES
-- Ví dụ: Sân A1 khóa buổi sáng ngày kia (3 ngày nữa) bảo trì cỏ
(1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '06:00:00', '09:00:00',
    '🔧 Bảo trì, chăm sóc cỏ nhân tạo - đội ngũ kỹ thuật đến làm việc vào buổi sáng', 1),
-- Ví dụ: Sân C1 (sân 11 STD) khóa cả chiều + tối ngày kia cho giải đấu
(5, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '15:00:00', '21:00:00',
    '⚽ Giải đấu Bóng đá Văn phòng Thành phố - 8 đội tranh cup', 2);

-- ============================================================
-- Bước 13: SEED DỮ LIỆU MẪU bookings (7 đơn ví dụ phân bố hôm nay & ngày mai)
-- ============================================================
SET @today     = CURDATE();
SET @tomorrow  = DATE_ADD(CURDATE(), INTERVAL 1 DAY);
SET @plus2d    = DATE_ADD(CURDATE(), INTERVAL 2 DAY);

INSERT INTO bookings (
    customer_id, pitch_id, booking_date, start_time, end_time,
    total_price, deposit, paid_amount,
    payment_method, payment_status, status, cancellation_reason, note, created_by
) VALUES
-- ======== HÔM NAY (Hôm nay 3 đơn, trạng thái đa dạng) ========
-- Đơn #1: Nhân viên An, Sân B1 (7 người), 19h-21h (2 tiếng), Đã xác nhận, đã trả đủ
(1, 3, @today, '19:00:00', '21:00:00',
    900000, 200000, 900000,
    'cash', 'paid', 'confirmed', NULL,
    'Đội bóng công ty A, yêu cầu thêm 10 lít nước uống đá', 1),

-- Đơn #2: Bình, Sân A1 (5 người), 20h-22h, Đang sử dụng, mới đặt cọc
(2, 1, @today, '20:00:00', '22:00:00',
    500000, 150000, 150000,
    'momo', 'deposit', 'in_progress', NULL,
    'Nhóm bạn bè cùng công sở chơi cuối tuần - cọc chuyển khoản MoMo', 2),

-- Đơn #3: Cường, Sân B2 (7 người), 17h-18h, Chờ xác nhận - chưa thanh toán gì
(3, 4, @today, '17:00:00', '18:00:00',
    480000, 0, 0,
    'cash', 'unpaid', 'pending', NULL,
    'Gọi điện đặt qua tổng đài - nhắn tin xác nhận sau', 1),

-- ======== NGÀY MAI (3 đơn - bao gồm cả trạng thái HỦY) ========
-- Đơn #4: Duyên, Sân C1 (11 người), 15h-17h, Xác nhận - thanh toán 1 phần (50%)
(4, 5, @tomorrow, '15:00:00', '17:00:00',
    1600000, 500000, 800000,
    'banking', 'partial', 'confirmed', NULL,
    'Đội bóng 2 công ty đối tác giao hữu - chuyển khoản 50% trước', 1),

-- Đơn #5: Em, Sân A2 (5 người), 07h-09h, ĐÃ HOÀN THÀNH (VD hôm qua nhưng kế về mai)
(5, 2, @tomorrow, '07:00:00', '09:00:00',
    400000, 0, 400000,
    'cash', 'paid', 'completed', NULL,
    'Mỗi sáng thứ 2,4,6 đội thể dục quận tập luyện - thu theo tháng', 2),

-- Đơn #6: Phượng, Sân A1 (5 người), 18h-20h, ĐÃ HỦY - có lý do
(6, 1, @tomorrow, '18:00:00', '20:00:00',
    500000, 100000, 100000,
    'zalo', 'deposit', 'cancelled',
    'Đội khách báo hủy vì trời mưa lớn chiều nay, khách chủ động hủy trước 24h (được hoàn 100% cọc)',
    'Đặt nhóm 12 người nhưng hủy do thời tiết xấu', 2),

-- ======== NGÀY MỐT (1 đơn nữa) ========
-- Đơn #7: Giang, Sân C2 (11 người mini), 08h-11h, Xác nhận, Đã trả đủ
(7, 6, @plus2d, '08:00:00', '11:00:00',
    2700000, 500000, 2700000,
    'cash', 'paid', 'confirmed', NULL,
    'Giải đấu U17 Thành phố - 3 trận, 2 giờ 30 phút tổng cộng (thêm 1 giờ chờ)', 1);

-- ============================================================
-- ============================================================
-- ✅ KẾT THÚC SCRIPT IMPORT - CSDL ĐẦY ĐỦ SẴN SÀNG SỬ DỤNG
-- ============================================================
-- Hướng dẫn:
--  1. Mở phpMyAdmin -> chọn CSDL (hoặc tạo CSDL mới: db_football_field_booking)
--  2. Tab Import -> chọn file này -> Go
--  3. Mở trình duyệt vào trang: /dant_bongda/admin/login
--     => Đăng nhập ADMIN:  admin / admin123
--     => Đăng nhập STAFF:  staff / staff123
--  4. Thử các tính năng:
--     📅 Lịch sân (dạng bảng sân×giờ, 🔒khóa khung giờ, double-click ô trống để khóa nhanh)
--     📋 Danh sách đặt sân (filter, tìm kiếm, đổi trạng thái nhanh...)
--     ➕ Thêm lịch đặt (kiểm tra trùng lịch tự động AJAX, tính tiền theo giờ)
--     ✏️ Sửa, 🗑️Xóa (chỉ ADMIN), ❌Hủy lịch, 💰Thanh toán nhiều đợt
-- ============================================================
