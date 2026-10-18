<?php

namespace App\Models;

use App\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    const STATUS_PENDING     = 'pending';
    const STATUS_CONFIRMED   = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_CANCELLED   = 'cancelled';

    const PAY_UNPAID    = 'unpaid';
    const PAY_DEPOSIT   = 'deposit';
    const PAY_PARTIAL   = 'partial';
    const PAY_PAID      = 'paid';

    const MIN_OPERATING_HOUR = '06:00:00';
    const MAX_OPERATING_HOUR = '23:00:00';

    public static function getBookingStatuses()
    {
        return [
            self::STATUS_PENDING     => 'Chờ xác nhận',
            self::STATUS_CONFIRMED   => 'Đã xác nhận',
            self::STATUS_IN_PROGRESS => 'Đang sử dụng',
            self::STATUS_COMPLETED   => 'Đã hoàn thành',
            self::STATUS_CANCELLED   => 'Đã hủy',
        ];
    }

    public static function getBookingStatusColor($status)
    {
        return match ($status) {
            self::STATUS_PENDING     => 'warning',
            self::STATUS_CONFIRMED   => 'primary',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_COMPLETED   => 'success',
            self::STATUS_CANCELLED   => 'danger',
            default                   => 'secondary',
        };
    }

    public static function getPaymentStatuses()
    {
        return [
            self::PAY_UNPAID  => 'Chưa thanh toán',
            self::PAY_DEPOSIT => 'Đã đặt cọc',
            self::PAY_PARTIAL => 'Thanh toán một phần',
            self::PAY_PAID    => 'Đã thanh toán đủ',
        ];
    }

    public static function getPaymentStatusColor($st)
    {
        return match ($st) {
            self::PAY_UNPAID  => 'danger',
            self::PAY_DEPOSIT => 'warning',
            self::PAY_PARTIAL => 'info',
            self::PAY_PAID    => 'success',
            default            => 'secondary',
        };
    }

    public static function getPaymentMethods()
    {
        return [
            'cash'    => 'Tiền mặt',
            'banking' => 'Chuyển khoản ngân hàng',
            'momo'    => 'Ví MoMo',
            'zalo'    => 'ZaloPay',
            'vnpay'   => 'VNPay',
            'other'   => 'Khác',
        ];
    }

    public static function calcPaymentStatus($total, $deposit, $paid)
    {
        $total   = (float)$total;
        $deposit = (float)$deposit;
        $paid    = (float)$paid;
        if ($total <= 0) return self::PAY_UNPAID;
        if ($paid >= $total) return self::PAY_PAID;
        if ($deposit > 0 && $paid >= $deposit && $paid <= $deposit + 0.0001) return self::PAY_DEPOSIT;
        if ($paid > 0 && $paid < $total) return self::PAY_PARTIAL;
        return self::PAY_UNPAID;
    }

    public static function calcHoursDiff($startTime, $endTime)
    {
        $s = strtotime("2000-01-01 $startTime");
        $e = strtotime("2000-01-01 $endTime");
        if ($e <= $s) return 0;
        return round(($e - $s) / 3600, 2);
    }

    public static function calcRemaining($total, $paid)
    {
        $remain = (float)$total - (float)$paid;
        return $remain < 0 ? 0 : $remain;
    }

    private function buildBaseQuery()
    {
        return "SELECT b.*,
                       c.name  AS customer_name,
                       c.phone AS customer_phone,
                       c.email AS customer_email,
                       p.name  AS pitch_name,
                       p.type  AS pitch_type,
                       p.price_per_hour,
                       u.full_name AS creator_name
                FROM {$this->table} b
                LEFT JOIN customers c ON c.id = b.customer_id
                LEFT JOIN pitches   p ON p.id = b.pitch_id
                LEFT JOIN users     u ON u.id = b.created_by
                WHERE 1=1";
    }

    public function all($filters = [])
    {
        $sql    = $this->buildBaseQuery();
        $params = [];

        if (!empty($filters['q'])) {
            $q = '%' . trim($filters['q']) . '%';
            $sql .= " AND (
                CAST(b.id AS CHAR) LIKE ? OR
                c.name LIKE ? OR
                c.phone LIKE ?
            )";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= " AND b.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['pitch_id'])) {
            $sql .= " AND b.pitch_id = ?";
            $params[] = $filters['pitch_id'];
        }

        if (!empty($filters['booking_date'])) {
            $sql .= " AND b.booking_date = ?";
            $params[] = $filters['booking_date'];
        } else {
            if (!empty($filters['from_date'])) {
                $sql .= " AND b.booking_date >= ?";
                $params[] = $filters['from_date'];
            }
            if (!empty($filters['to_date'])) {
                $sql .= " AND b.booking_date <= ?";
                $params[] = $filters['to_date'];
            }
        }

        $sql .= " ORDER BY b.booking_date DESC, b.start_time DESC, b.id DESC";

        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql    = $this->buildBaseQuery() . " AND b.id = ? LIMIT 1";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function getByPitchAndDate($pitchId, $date, $includeCancelled = false)
    {
        $sql = $this->buildBaseQuery() . " AND b.pitch_id = ? AND b.booking_date = ?";
        $params = [$pitchId, $date];
        if (!$includeCancelled) {
            $sql .= " AND b.status <> ?";
            $params[] = self::STATUS_CANCELLED;
        }
        $sql .= " ORDER BY b.start_time ASC";
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function getAllByDateRange($fromDate, $toDate, $pitchId = null)
    {
        $sql    = $this->buildBaseQuery() . " AND b.booking_date BETWEEN ? AND ?";
        $params = [$fromDate, $toDate];
        if ($pitchId) {
            $sql .= " AND b.pitch_id = ?";
            $params[] = $pitchId;
        }
        $sql .= " ORDER BY b.booking_date ASC, b.start_time ASC";
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function getForUser(int $userId): array
    {
        return $this->connection->executeQuery(
            "SELECT b.*, c.name AS customer_name, c.phone AS customer_phone,
                    c.email AS customer_email, p.name AS pitch_name, p.type AS pitch_type,
                    p.price_per_hour, u.full_name AS creator_name,
                    pr.id AS rating_id, pr.rating AS customer_rating, pr.comment AS rating_comment
             FROM {$this->table} b
             LEFT JOIN customers c ON c.id = b.customer_id
             LEFT JOIN pitches p ON p.id = b.pitch_id
             LEFT JOIN users u ON u.id = b.created_by
             LEFT JOIN pitch_ratings pr ON pr.booking_id = b.id AND pr.customer_user_id = b.created_by
             WHERE b.created_by = ?
             ORDER BY b.booking_date DESC, b.start_time DESC, b.id DESC",
            [$userId]
        )->fetchAllAssociative();
    }

    public function saveVnpayReference(int $bookingId, ?int $seriesId, string $transactionRef, string $createDate): int
    {
        $sql = "UPDATE {$this->table}
                SET vnpay_txn_ref = ?, vnpay_transaction_date = ?, updated_at = NOW()
                WHERE " . ($seriesId ? 'series_id = ?' : 'id = ?');
        return $this->connection->executeStatement(
            $sql,
            $seriesId
                ? [$transactionRef, $createDate, $seriesId]
                : [$transactionRef, $createDate, $bookingId]
        );
    }

    /**
     * KIỂM TRA TRÙNG LỊCH - NGHIỆP VỤ CHÍNH
     * Các trường hợp trùng (overlap):
     *   - new_start < existing_end AND new_end > existing_start
     * Loại trừ lịch đang sửa ($excludeId)
     * Không tính lịch ĐÃ HỦY vì đã giải phóng giờ
     *
     * @return array|null Trả về booking bị trùng, null nếu không trùng
     */
    public function checkOverlap($pitchId, $date, $startTime, $endTime, $excludeId = null)
    {
        $sql = "SELECT b.*, c.name AS customer_name, c.phone AS customer_phone
                FROM {$this->table} b
                LEFT JOIN customers c ON c.id = b.customer_id
                WHERE b.pitch_id = ?
                  AND b.booking_date = ?
                  AND b.status <> ?
                  AND b.start_time < ?
                  AND b.end_time   > ?";
        $params = [$pitchId, $date, self::STATUS_CANCELLED, $endTime, $startTime];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND b.id <> ?";
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        return $row ?: null;
    }

    /**
     * Kiểm tra xem thời gian đặt có nằm trong giờ hoạt động không (06:00 - 23:00)
     */
    public function isWithinOperatingHours($startTime, $endTime)
    {
        return ($startTime >= self::MIN_OPERATING_HOUR)
            && ($endTime   <= self::MAX_OPERATING_HOUR);
    }

    private function attachServicesToBookings(array $bookingIds, array $serviceItems)
    {
        if (!$serviceItems) {
            return;
        }

        usort($serviceItems, static fn ($a, $b) => (int)$a['service_id'] <=> (int)$b['service_id']);
        foreach ($serviceItems as $item) {
            $serviceId = (int)$item['service_id'];
            $quantity = (int)$item['quantity'];
            $service = $this->connection->executeQuery(
                "SELECT id, name, price, stock FROM services
                 WHERE id = ? AND status = 'active' FOR UPDATE",
                [$serviceId]
            )->fetchAssociative();

            if (!$service) {
                throw new \RuntimeException('Một dịch vụ đã ngừng kinh doanh. Vui lòng tải lại trang và chọn lại.');
            }
            if ((float)$service['price'] !== (float)$item['unit_price']) {
                throw new \RuntimeException('Giá dịch vụ "' . $service['name'] . '" vừa thay đổi. Vui lòng tải lại trang.');
            }

            $requiredStock = $quantity * count($bookingIds);
            if ((int)$service['stock'] < $requiredStock) {
                throw new \RuntimeException(
                    'Dịch vụ "' . $service['name'] . '" không đủ tồn kho cho '
                    . count($bookingIds) . ' buổi đã chọn.'
                );
            }

            $updated = $this->connection->executeStatement(
                "UPDATE services SET stock = stock - ?, updated_at = NOW()
                 WHERE id = ? AND status = 'active' AND stock >= ?",
                [$requiredStock, $serviceId, $requiredStock]
            );
            if ($updated !== 1) {
                throw new \RuntimeException('Không thể giữ tồn kho dịch vụ "' . $service['name'] . '". Vui lòng thử lại.');
            }

            foreach ($bookingIds as $bookingId) {
                $this->connection->executeStatement(
                    "INSERT INTO booking_services
                        (booking_id, service_id, quantity, unit_price, subtotal, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [
                        (int)$bookingId,
                        $serviceId,
                        $quantity,
                        (float)$service['price'],
                        $quantity * (float)$service['price'],
                    ]
                );
            }
        }
    }

    /**
     * Kiểm tra trùng với khóa khung giờ
     */
    protected function checkPitchLockOverlap($pitchId, $date, $startTime, $endTime)
    {
        $lockObj = new \App\Models\PitchLock();
        return $lockObj->checkOverlap($pitchId, $date, $startTime, $endTime);
    }

    /**
     * Tạo mới đặt sân - SỬ DỤNG TRANSACTION + LOCK ghi để tránh đặt đồng thời
     */
    public function create(array $data)
    {
        $this->connection->beginTransaction();

        try {
            $pitchId    = (int)$data['pitch_id'];
            $customerId = (int)$data['customer_id'];
            $date       = $data['booking_date'];
            $startTime  = $data['start_time'];
            $endTime    = $data['end_time'];
            $total      = (float)($data['total_price'] ?? 0);
            $deposit    = (float)($data['deposit'] ?? 0);
            $paid       = (float)($data['paid_amount'] ?? 0);

            $pitchLockSql = "SELECT id FROM pitches WHERE id = ? FOR UPDATE";
            $this->connection->executeQuery($pitchLockSql, [$pitchId]);

            $overlap = $this->checkOverlap($pitchId, $date, $startTime, $endTime);
            if ($overlap) {
                $this->connection->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Trùng lịch với đơn #' . $overlap['id'] . ' (Khách ' . ($overlap['customer_name'] ?? '') . ' ' . substr($overlap['start_time'],0,5) . '-' . substr($overlap['end_time'],0,5) . ')',
                    'overlap' => $overlap,
                ];
            }

            $lockOverlap = $this->checkPitchLockOverlap($pitchId, $date, $startTime, $endTime);
            if ($lockOverlap) {
                $this->connection->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Khung giờ này đã bị KHÓA (Lý do: ' . ($lockOverlap['reason'] ?? 'Bảo trì/Sự kiện') . ' ' . substr($lockOverlap['start_time'],0,5) . '-' . substr($lockOverlap['end_time'],0,5) . ')',
                ];
            }

            $payStatus = self::calcPaymentStatus($total, $deposit, $paid);

            $sql = "INSERT INTO {$this->table}
                (customer_id, pitch_id, booking_date, start_time, end_time,
                 total_price, deposit, paid_amount, payment_method, payment_status,
                 status, cancellation_reason, note, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $this->connection->executeStatement($sql, [
                $customerId,
                $pitchId,
                $date,
                $startTime,
                $endTime,
                $total,
                $deposit,
                $paid,
                $data['payment_method'] ?? 'cash',
                $data['payment_status'] ?? $payStatus,
                $data['status'] ?? self::STATUS_PENDING,
                null,
                isset($data['note']) ? trim($data['note']) : null,
                isset($data['created_by']) ? ((int)$data['created_by'] ?: null) : null,
            ]);

            $id = (int)$this->connection->lastInsertId();
            $this->attachServicesToBookings([$id], $data['services'] ?? []);

            $this->connection->commit();

            return ['success' => true, 'id' => $id];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            return [
                'success' => false,
                'error' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Lỗi hệ thống khi tạo lịch đặt. Vui lòng thử lại.',
            ];
        }
    }

    public function createMonthlySeries(array $seriesData, array $occurrences)
    {
        if (!$occurrences) {
            return ['success' => false, 'error' => 'Tháng đã chọn không còn buổi đặt phù hợp.'];
        }

        $this->connection->beginTransaction();
        try {
            $pitchId = (int)$seriesData['pitch_id'];
            $this->connection->executeQuery(
                'SELECT id FROM pitches WHERE id = ? FOR UPDATE',
                [$pitchId]
            );

            foreach ($occurrences as $occurrence) {
                $overlap = $this->checkOverlap(
                    $pitchId,
                    $occurrence['booking_date'],
                    $seriesData['start_time'],
                    $seriesData['end_time']
                );
                if ($overlap) {
                    $this->connection->rollBack();
                    return [
                        'success' => false,
                        'error' => 'Khung giờ ' . substr($seriesData['start_time'], 0, 5) . '-'
                            . substr($seriesData['end_time'], 0, 5) . ' ngày '
                            . date('d/m/Y', strtotime($occurrence['booking_date']))
                            . ' đã được đặt.',
                    ];
                }

                $lockOverlap = $this->checkPitchLockOverlap(
                    $pitchId,
                    $occurrence['booking_date'],
                    $seriesData['start_time'],
                    $seriesData['end_time']
                );
                if ($lockOverlap) {
                    $this->connection->rollBack();
                    return [
                        'success' => false,
                        'error' => 'Sân bị khóa ngày '
                            . date('d/m/Y', strtotime($occurrence['booking_date']))
                            . ' trong khung giờ đã chọn.',
                    ];
                }
            }

            $this->connection->executeStatement(
                "INSERT INTO booking_series
                    (customer_id, pitch_id, booking_month, start_time, end_time,
                     total_price, deposit, paid_amount, payment_method, payment_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'vnpay', ?, NOW())",
                [
                    (int)$seriesData['customer_id'],
                    $pitchId,
                    $seriesData['booking_month'],
                    $seriesData['start_time'],
                    $seriesData['end_time'],
                    (float)$seriesData['total_price'],
                    (float)$seriesData['deposit'],
                    self::PAY_UNPAID,
                ]
            );
            $seriesId = (int)$this->connection->lastInsertId();

            $bookingIds = [];
            foreach ($occurrences as $occurrence) {
                $this->connection->executeStatement(
                    "INSERT INTO {$this->table}
                        (customer_id, pitch_id, booking_date, start_time, end_time,
                         total_price, deposit, paid_amount, payment_method, payment_status,
                         status, cancellation_reason, note, created_by, series_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'vnpay', ?, ?, NULL, ?, ?, ?, NOW(), NOW())",
                    [
                        (int)$seriesData['customer_id'],
                        $pitchId,
                        $occurrence['booking_date'],
                        $seriesData['start_time'],
                        $seriesData['end_time'],
                        (float)$occurrence['total_price'],
                        (float)$occurrence['deposit'],
                        self::PAY_UNPAID,
                        self::STATUS_PENDING,
                        $seriesData['note'] ?? '',
                        isset($seriesData['created_by']) ? ((int)$seriesData['created_by'] ?: null) : null,
                        $seriesId,
                    ]
                );
                $bookingIds[] = (int)$this->connection->lastInsertId();
            }

            $this->attachServicesToBookings($bookingIds, $seriesData['services'] ?? []);

            $this->connection->commit();
            return [
                'success' => true,
                'series_id' => $seriesId,
                'booking_ids' => $bookingIds,
                'first_booking_id' => $bookingIds[0],
            ];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Monthly booking creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Không thể tạo lịch đặt tháng. Vui lòng thử lại.',
            ];
        }
    }

    public function findSeries($id)
    {
        return $this->connection->executeQuery(
            'SELECT * FROM booking_series WHERE id = ? LIMIT 1',
            [(int)$id]
        )->fetchAssociative() ?: null;
    }

    public function getSeriesFirstBookingId($seriesId)
    {
        $id = $this->connection->executeQuery(
            "SELECT id FROM {$this->table}
             WHERE series_id = ? AND status <> ?
             ORDER BY booking_date ASC, id ASC LIMIT 1",
            [(int)$seriesId, self::STATUS_CANCELLED]
        )->fetchOne();

        return $id ? (int)$id : null;
    }

    public function recordSeriesVnpayDeposit(int $seriesId, ?string $transactionNo = null): array
    {
        $this->connection->beginTransaction();
        try {
            $series = $this->connection->executeQuery(
                'SELECT * FROM booking_series WHERE id = ? FOR UPDATE',
                [$seriesId]
            )->fetchAssociative();
            if (!$series || $series['payment_method'] !== 'vnpay' || (float)$series['deposit'] <= 0) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Không tìm thấy thông tin đặt cọc VNPay hợp lệ.'];
            }

            $bookings = $this->connection->executeQuery(
                "SELECT id, total_price, deposit, status FROM {$this->table}
                 WHERE series_id = ? ORDER BY booking_date ASC FOR UPDATE",
                [$seriesId]
            )->fetchAllAssociative();
            if (!$bookings || array_filter($bookings, static function ($booking) {
                return $booking['status'] === self::STATUS_CANCELLED;
            })) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Lịch đặt tháng không còn đầy đủ để ghi nhận thanh toán.'];
            }

            if ((float)$series['paid_amount'] < (float)$series['deposit']) {
                foreach ($bookings as $booking) {
                    $paymentStatus = self::calcPaymentStatus(
                        (float)$booking['total_price'],
                        (float)$booking['deposit'],
                        (float)$booking['deposit']
                    );
                    $this->connection->executeStatement(
                        "UPDATE {$this->table}
                         SET paid_amount = deposit, payment_status = ?,
                             vnpay_transaction_no = COALESCE(?, vnpay_transaction_no), updated_at = NOW()
                         WHERE id = ?",
                            [$paymentStatus, $transactionNo, (int)$booking['id']]
                        );
                }
                $this->connection->executeStatement(
                    "UPDATE booking_series
                     SET paid_amount = deposit, payment_status = ?, updated_at = NOW()
                     WHERE id = ?",
                    [self::calcPaymentStatus(
                        (float)$series['total_price'],
                        (float)$series['deposit'],
                        (float)$series['deposit']
                    ), $seriesId]
                );
            }
            if ($transactionNo !== null) {
                $this->connection->executeStatement(
                    "UPDATE {$this->table} SET vnpay_transaction_no = ?, updated_at = NOW()
                     WHERE series_id = ?",
                    [$transactionNo, $seriesId]
                );
            }

            $this->connection->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('VNPay monthly deposit update failed for series #' . $seriesId . ': ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể cập nhật trạng thái thanh toán VNPay.'];
        }
    }

    /**
     * Cập nhật đặt sân - Cũng dùng transaction và kiểm tra trùng lại nếu thay đổi sân/ngày/giờ
     */
    public function updateBooking($id, array $data)
    {
        $booking = $this->find($id);
        if (!$booking) return ['success' => false, 'error' => 'Lịch đặt không tồn tại'];

        $this->connection->beginTransaction();

        try {
            $pitchId    = (int)($data['pitch_id']   ?? $booking['pitch_id']);
            $date       = $data['booking_date']      ?? $booking['booking_date'];
            $startTime  = $data['start_time']        ?? $booking['start_time'];
            $endTime    = $data['end_time']          ?? $booking['end_time'];
            $total      = (float)($data['total_price']   ?? $booking['total_price']);
            $deposit    = (float)($data['deposit']       ?? $booking['deposit']);
            $paid       = (float)($data['paid_amount']   ?? $booking['paid_amount']);

            $timeOrPitchChanged = (
                $pitchId   !== (int)$booking['pitch_id'] ||
                $date      !== $booking['booking_date'] ||
                $startTime !== $booking['start_time'] ||
                $endTime   !== $booking['end_time']
            );

            if ($timeOrPitchChanged && $booking['status'] !== self::STATUS_CANCELLED) {
                $lockSql = "SELECT id FROM pitches WHERE id = ? FOR UPDATE";
                $this->connection->executeQuery($lockSql, [$pitchId]);

                $overlap = $this->checkOverlap($pitchId, $date, $startTime, $endTime, (int)$id);
                if ($overlap) {
                    $this->connection->rollBack();
                    return [
                        'success' => false,
                        'error'   => 'Trùng lịch với đơn #' . $overlap['id'] . ' (Khách ' . ($overlap['customer_name'] ?? '') . ' ' . substr($overlap['start_time'],0,5) . '-' . substr($overlap['end_time'],0,5) . ')',
                    ];
                }

                $lockOverlap = $this->checkPitchLockOverlap($pitchId, $date, $startTime, $endTime);
                if ($lockOverlap) {
                    $this->connection->rollBack();
                    return [
                        'success' => false,
                        'error'   => 'Khung giờ này đã bị KHÓA (Lý do: ' . ($lockOverlap['reason'] ?? 'Bảo trì/Sự kiện') . ')',
                    ];
                }
            }

            $payStatus = self::calcPaymentStatus($total, $deposit, $paid);

            $customerId = isset($data['customer_id']) ? (int)$data['customer_id'] : null;
            $sqlParts = [];
            $params   = [];

            if ($customerId !== null) { $sqlParts[] = "customer_id = ?";    $params[] = $customerId; }
            $sqlParts[] = "pitch_id = ?";        $params[] = $pitchId;
            $sqlParts[] = "booking_date = ?";    $params[] = $date;
            $sqlParts[] = "start_time = ?";      $params[] = $startTime;
            $sqlParts[] = "end_time = ?";        $params[] = $endTime;
            $sqlParts[] = "total_price = ?";     $params[] = $total;
            $sqlParts[] = "deposit = ?";         $params[] = $deposit;
            $sqlParts[] = "paid_amount = ?";     $params[] = $paid;
            if (isset($data['payment_method'])) { $sqlParts[] = "payment_method = ?"; $params[] = $data['payment_method']; }
            $sqlParts[] = "payment_status = ?";  $params[] = $payStatus;
            if (isset($data['status']))         { $sqlParts[] = "status = ?";          $params[] = $data['status']; }
            if (isset($data['note']))           { $sqlParts[] = "note = ?";            $params[] = trim($data['note']); }
            if (array_key_exists('cancellation_reason', $data)) {
                $sqlParts[] = "cancellation_reason = ?";
                $params[] = trim($data['cancellation_reason']) ?: null;
            }
            $sqlParts[] = "updated_at = NOW()";

            $params[] = (int)$id;
            $sql = "UPDATE {$this->table} SET " . implode(", ", $sqlParts) . " WHERE id = ?";

            $this->connection->executeStatement($sql, $params);

            $this->connection->commit();
            return ['success' => true, 'id' => (int)$id];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            return ['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    public function cancel($id, $reason, bool $refunded = false)
    {
        $this->connection->beginTransaction();
        try {
            $booking = $this->connection->executeQuery(
                "SELECT id, status, series_id FROM {$this->table} WHERE id = ? FOR UPDATE",
                [(int)$id]
            )->fetchAssociative();
            if (!$booking) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Lịch đặt không tồn tại'];
            }

            if ($booking['status'] !== self::STATUS_CANCELLED) {
                $services = $this->connection->executeQuery(
                    "SELECT service_id, SUM(quantity) AS quantity
                     FROM booking_services WHERE booking_id = ?
                     GROUP BY service_id ORDER BY service_id ASC",
                    [(int)$id]
                )->fetchAllAssociative();
                foreach ($services as $service) {
                    $this->connection->executeStatement(
                        "UPDATE services SET stock = stock + ?, updated_at = NOW() WHERE id = ?",
                        [(int)$service['quantity'], (int)$service['service_id']]
                    );
                }

                $this->connection->executeStatement(
                    "UPDATE {$this->table}
                     SET status = ?, cancellation_reason = ?,
                         paid_amount = IF(?, 0, paid_amount),
                         payment_status = IF(?, ?, payment_status),
                         updated_at = NOW()
                     WHERE id = ?",
                    [
                        self::STATUS_CANCELLED,
                        trim($reason) ?: null,
                        $refunded ? 1 : 0,
                        $refunded ? 1 : 0,
                        self::PAY_UNPAID,
                        (int)$id,
                    ]
                );
                if ($refunded && !empty($booking['series_id'])) {
                    $seriesId = (int)$booking['series_id'];
                    $series = $this->findSeries($seriesId);
                    if ($series) {
                        $seriesPaid = (float)$this->connection->executeQuery(
                            "SELECT COALESCE(SUM(paid_amount), 0)
                             FROM {$this->table} WHERE series_id = ?",
                            [$seriesId]
                        )->fetchOne();
                        $this->connection->executeStatement(
                            "UPDATE booking_series
                             SET paid_amount = ?, payment_status = ?, updated_at = NOW()
                             WHERE id = ?",
                            [
                                $seriesPaid,
                                self::calcPaymentStatus(
                                    (float)$series['total_price'],
                                    (float)$series['deposit'],
                                    $seriesPaid
                                ),
                                $seriesId,
                            ]
                        );
                    }
                }
            }

            $this->connection->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Booking cancellation failed for #' . (int)$id . ': ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể hủy đơn đặt sân.'];
        }
    }

    public function addPayment($id, $amount, $method = 'cash')
    {
        $booking = $this->find($id);
        if (!$booking) return ['success' => false, 'error' => 'Lịch đặt không tồn tại'];
        if ($booking['status'] === self::STATUS_CANCELLED) return ['success' => false, 'error' => 'Lịch đã bị hủy, không thể thanh toán'];

        $added   = (float)$amount;
        $paid    = (float)$booking['paid_amount'] + $added;
        $total   = (float)$booking['total_price'];
        $deposit = (float)$booking['deposit'];

        if ($paid > $total + 0.0001) {
            return ['success' => false, 'error' => 'Số tiền thanh toán vượt quá tổng tiền'];
        }
        if ($added < 0) {
            return ['success' => false, 'error' => 'Số tiền thanh toán không hợp lệ'];
        }

        $payStatus = self::calcPaymentStatus($total, $deposit, $paid);

        $sql = "UPDATE {$this->table} SET
                    paid_amount = ?,
                    payment_method = ?,
                    payment_status = ?,
                    updated_at = NOW()
                WHERE id = ?";

        $this->connection->executeStatement($sql, [$paid, $method, $payStatus, (int)$id]);

        if (!empty($booking['series_id'])) {
            $seriesId = (int)$booking['series_id'];
            $series = $this->findSeries($seriesId);
            if ($series) {
                $seriesPaid = (float)$this->connection->executeQuery(
                    "SELECT COALESCE(SUM(paid_amount), 0)
                     FROM {$this->table} WHERE series_id = ?",
                    [$seriesId]
                )->fetchOne();
                $seriesPaymentStatus = self::calcPaymentStatus(
                    (float)$series['total_price'],
                    (float)$series['deposit'],
                    $seriesPaid
                );
                $this->connection->executeStatement(
                    "UPDATE booking_series
                     SET paid_amount = ?, payment_status = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$seriesPaid, $seriesPaymentStatus, $seriesId]
                );
            }
        }

        return ['success' => true];
    }

    public function recordVnpayDeposit(int $id, ?string $transactionNo = null): array
    {
        $this->connection->beginTransaction();
        try {
            $booking = $this->connection->executeQuery(
                "SELECT id, deposit, paid_amount, total_price, payment_method, status
                 FROM {$this->table} WHERE id = ? FOR UPDATE",
                [$id]
            )->fetchAssociative();

            if (!$booking || $booking['payment_method'] !== 'vnpay' || (float)$booking['deposit'] <= 0) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Không tìm thấy thông tin đặt cọc VNPay hợp lệ.'];
            }
            if ($booking['status'] === self::STATUS_CANCELLED) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Lịch đặt đã bị hủy, không thể ghi nhận thanh toán.'];
            }

            $paid = (float)$booking['paid_amount'];
            $deposit = (float)$booking['deposit'];
            if ($paid < $deposit) {
                $paid = $deposit;
                $paymentStatus = self::calcPaymentStatus((float)$booking['total_price'], $deposit, $paid);
                $this->connection->executeStatement(
                    "UPDATE {$this->table}
                     SET paid_amount = ?, payment_status = ?,
                         vnpay_transaction_no = COALESCE(?, vnpay_transaction_no), updated_at = NOW()
                     WHERE id = ?",
                    [$paid, $paymentStatus, $transactionNo, $id]
                );
            } elseif ($transactionNo !== null) {
                $this->connection->executeStatement(
                    "UPDATE {$this->table} SET vnpay_transaction_no = ?, updated_at = NOW() WHERE id = ?",
                    [$transactionNo, $id]
                );
            }

            $this->connection->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('VNPay deposit update failed for booking #' . $id . ': ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể cập nhật trạng thái thanh toán VNPay.'];
        }
    }

    public function changeStatus($id, $newStatus)
    {
        $booking = $this->find($id);
        if (!$booking) return ['success' => false, 'error' => 'Lịch đặt không tồn tại'];

        $valid = $this->isValidStatusTransition($booking['status'], $newStatus);
        if (!$valid['ok']) {
            return ['success' => false, 'error' => $valid['message']];
        }

        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $this->connection->executeStatement($sql, [$newStatus, (int)$id]);
        return ['success' => true];
    }

    /**
     * Luồng trạng thái hợp lệ:
     *   pending     -> confirmed / cancelled
     *   confirmed   -> in_progress / cancelled
     *   in_progress -> completed
     *   completed   -> (không đổi)
     *   cancelled   -> (không đổi, ngoại lệ rollback thủ công ADMIN nếu cần)
     */
    public function isValidStatusTransition($old, $new)
    {
        if ($old === $new) return ['ok' => true];

        $map = [
            self::STATUS_PENDING     => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED   => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED   => [],
            self::STATUS_CANCELLED   => [],
        ];

        $allowed = $map[$old] ?? [];
        if (in_array($new, $allowed)) return ['ok' => true];

        $names = self::getBookingStatuses();
        $oldN  = $names[$old] ?? $old;
        $newN  = $names[$new] ?? $new;
        $msg   = "Không thể chuyển trạng thái từ '{$oldN}' sang '{$newN}'. ";
        if (!empty($allowed)) {
            $allowedNames = array_map(function($s) use ($names) { return $names[$s] ?? $s; }, $allowed);
            $msg .= "Trạng thái hợp lệ tiếp theo: " . implode(", ", $allowedNames);
        } else {
            $msg .= "Không được phép thay đổi trạng thái.";
        }
        return ['ok' => false, 'message' => $msg];
    }

    public function delete($id)
    {
        $this->connection->beginTransaction();
        try {
            $booking = $this->connection->executeQuery(
                "SELECT id, status FROM {$this->table} WHERE id = ? FOR UPDATE",
                [(int)$id]
            )->fetchAssociative();
            if (!$booking) {
                $this->connection->commit();
                return 0;
            }

            if ($booking['status'] !== self::STATUS_CANCELLED) {
                $services = $this->connection->executeQuery(
                    "SELECT service_id, SUM(quantity) AS quantity
                     FROM booking_services WHERE booking_id = ?
                     GROUP BY service_id ORDER BY service_id ASC",
                    [(int)$id]
                )->fetchAllAssociative();
                foreach ($services as $service) {
                    $this->connection->executeStatement(
                        "UPDATE services SET stock = stock + ?, updated_at = NOW() WHERE id = ?",
                        [(int)$service['quantity'], (int)$service['service_id']]
                    );
                }
            }

            $deleted = $this->connection->executeStatement(
                "DELETE FROM {$this->table} WHERE id = ?",
                [(int)$id]
            );
            $this->connection->commit();
            return $deleted;
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Booking deletion failed for #' . (int)$id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /* ------------------------- THỐNG KÊ (DASHBOARD) ------------------------- */

    public function countToday()
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE booking_date = CURDATE() AND status <> ?";
        $row = $this->connection->executeQuery($sql, [self::STATUS_CANCELLED])->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    public function countByStatus($status, $date = null)
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE status = ?";
        $params = [$status];
        if ($date) {
            $sql .= " AND booking_date = ?";
            $params[] = $date;
        }
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    public function countPending($date = null)
    {
        return $this->countByStatus(self::STATUS_PENDING, $date);
    }
    public function countInProgress($date = null)
    {
        return $this->countByStatus(self::STATUS_IN_PROGRESS, $date);
    }
    public function countCompleted($date = null)
    {
        return $this->countByStatus(self::STATUS_COMPLETED, $date);
    }
    public function countCancelled($date = null)
    {
        return $this->countByStatus(self::STATUS_CANCELLED, $date);
    }

    public function revenue($fromDate = null, $toDate = null)
    {
        $sql = "SELECT COALESCE(SUM(paid_amount),0) AS total_revenue,
                       COALESCE(SUM(CASE WHEN status <> ? THEN total_price ELSE 0 END),0) AS total_booked
                FROM {$this->table}
                WHERE 1=1";
        $params = [self::STATUS_CANCELLED];
        if ($fromDate) { $sql .= " AND booking_date >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $sql .= " AND booking_date <= ?"; $params[] = $toDate; }
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        return [
            'total_revenue' => (float)($row['total_revenue'] ?? 0),
            'total_booked'  => (float)($row['total_booked'] ?? 0),
        ];
    }

    public function todayRevenue()
    {
        return $this->revenue(date('Y-m-d'), date('Y-m-d'));
    }

    public function countPitchesCurrentlyFree($date, $timeNow, $pitchModel)
    {
        $pitches = $pitchModel->all('active');
        $busy    = [];
        foreach ($pitches as $p) {
            $rows = $this->getByPitchAndDate((int)$p['id'], $date, false);
            foreach ($rows as $b) {
                if ($timeNow >= $b['start_time'] && $timeNow < $b['end_time']) {
                    $busy[] = (int)$p['id'];
                    break;
                }
            }
        }
        return count($pitches) - count(array_unique($busy));
    }
}
