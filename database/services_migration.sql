-- Migration: Module quản lý DỊCH VỤ (Services)
-- Áp dụng cho: db_football_field_booking
-- Chạy lần lượt, an toàn với IF NOT EXISTS

USE db_football_field_booking;

-- ================================================================
-- Bảng 1: services — Danh mục dịch vụ phụ trội (nước uống, đồ ăn, thiết bị...)
-- ================================================================
CREATE TABLE IF NOT EXISTS services (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Bảng 2: booking_services — Liên kết giữa ĐẶT SÂN và DỊCH VỤ (many-to-many + quantity + subtotal)
-- ================================================================
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

-- ================================================================
-- Seed dữ liệu mẫu (chỉ insert khi chưa có dịch vụ nào)
-- ================================================================
INSERT INTO services (name, category, price, unit, stock, description, status)
SELECT s.name, s.category, s.price, s.unit, s.stock, s.description, 'active'
FROM (
    SELECT 'Nước suối 500ml'        AS name, 'drink'   AS category, 10000 AS price, 'chai' AS unit, 100 AS stock, 'Nước đóng chai 500ml'        AS description
    UNION ALL SELECT 'Nước tăng lực',      'drink',   15000, 'lon',  80,  'Nước tăng lực lon 330ml'
    UNION ALL SELECT 'Cà phê sữa đá',      'drink',   20000, 'ly',   50,  'Cà phê pha sữa đá'
    UNION ALL SELECT 'Bánh mì kẹp thịt',   'food',    25000, 'cái',  30,  'Bánh mì kẹp thịt heo'
    UNION ALL SELECT 'Thuê bóng số 5',     'equipment', 20000, 'quả', 20,  'Bóng đá số 5 cỏ nhân tạo'
    UNION ALL SELECT 'Thuê bóng số 7',     'equipment', 25000, 'quả', 15,  'Bóng đá số 7 cỏ nhân tạo'
    UNION ALL SELECT 'Thuê áo đấu',        'equipment', 15000, 'bộ',  40,  'Áo đấu màu tiêu chuẩn'
    UNION ALL SELECT 'Dịch vụ chụp ảnh',   'service',  150000, 'giờ', 5,  'Chụp ảnh trong trận bóng'
) AS s
WHERE NOT EXISTS (SELECT 1 FROM services sv WHERE sv.name = s.name);
