<?php

namespace App\Models;

use App\Model;

class ServiceRequest extends Model
{
    public function createForCustomer(int $bookingId, int $userId, array $items, string $note = ''): array
    {
        $normalized = [];
        foreach ($items as $serviceId => $quantity) {
            $serviceId = filter_var($serviceId, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if ($serviceId === false || $serviceId <= 0 || $quantity === false || $quantity < 0 || $quantity > 20) {
                return ['success' => false, 'error' => 'Dịch vụ hoặc số lượng không hợp lệ.'];
            }
            if ($quantity > 0) {
                $normalized[(int)$serviceId] = (int)$quantity;
            }
        }
        if (!$normalized) {
            return ['success' => false, 'error' => 'Vui lòng chọn ít nhất một dịch vụ và số lượng.'];
        }
        if (mb_strlen($note) > 500) {
            return ['success' => false, 'error' => 'Ghi chú không được vượt quá 500 ký tự.'];
        }

        $this->connection->beginTransaction();
        try {
            $booking = $this->connection->executeQuery(
                'SELECT id, created_by, status, booking_date FROM bookings WHERE id = ? FOR UPDATE',
                [$bookingId]
            )->fetchAssociative();
            if (!$booking || (int)$booking['created_by'] !== $userId) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Không tìm thấy đơn đặt sân của bạn.'];
            }
            if ($booking['status'] !== Booking::STATUS_IN_PROGRESS || $booking['booking_date'] !== date('Y-m-d')) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Chỉ có thể yêu cầu dịch vụ khi lịch sân đang diễn ra hôm nay.'];
            }

            foreach ($normalized as $serviceId => $quantity) {
                $service = $this->connection->executeQuery(
                    "SELECT id, name, unit, price, stock
                     FROM services WHERE id = ? AND status = 'active' FOR UPDATE",
                    [$serviceId]
                )->fetchAssociative();
                if (!$service) {
                    $this->connection->rollBack();
                    return ['success' => false, 'error' => 'Một dịch vụ đã ngừng kinh doanh. Vui lòng tải lại trang.'];
                }
                if ((int)$service['stock'] < $quantity) {
                    $this->connection->rollBack();
                    return ['success' => false, 'error' => 'Số lượng dịch vụ "' . $service['name'] . '" hiện không đủ.'];
                }
            }

            $this->connection->insert('service_requests', [
                'booking_id' => $bookingId,
                'requested_by' => $userId,
                'status' => 'pending',
                'customer_note' => trim($note) ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $requestId = (int)$this->connection->lastInsertId();

            foreach ($normalized as $serviceId => $quantity) {
                $service = $this->connection->executeQuery(
                    'SELECT name, unit, price FROM services WHERE id = ?',
                    [$serviceId]
                )->fetchAssociative();
                $this->connection->insert('service_request_items', [
                    'request_id' => $requestId,
                    'service_id' => $serviceId,
                    'service_name' => $service['name'],
                    'unit' => $service['unit'],
                    'quantity' => $quantity,
                    'unit_price' => $service['price'],
                    'subtotal' => (float)$service['price'] * $quantity,
                ]);
            }

            $this->connection->commit();
            return ['success' => true, 'id' => $requestId];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Customer service request creation failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể gửi yêu cầu dịch vụ. Vui lòng thử lại.'];
        }
    }

    public function forCustomer(int $userId): array
    {
        return $this->connection->executeQuery(
            "SELECT sr.*, b.booking_date, p.name AS pitch_name,
                    (SELECT GROUP_CONCAT(CONCAT(sri.service_name, ' × ', sri.quantity) ORDER BY sri.id SEPARATOR ', ')
                     FROM service_request_items sri WHERE sri.request_id = sr.id) AS item_summary
             FROM service_requests sr
             JOIN bookings b ON b.id = sr.booking_id
             LEFT JOIN pitches p ON p.id = b.pitch_id
             WHERE sr.requested_by = ?
             ORDER BY sr.created_at DESC, sr.id DESC",
            [$userId]
        )->fetchAllAssociative();
    }

    public function allForAdmin(): array
    {
        return $this->connection->executeQuery(
            "SELECT sr.*, b.booking_date, b.start_time, b.end_time, b.court_number,
                    c.name AS customer_name, c.phone AS customer_phone,
                    p.name AS pitch_name, u.full_name AS handled_by_name,
                    (SELECT GROUP_CONCAT(CONCAT(sri.service_name, ' × ', sri.quantity,
                            ' (', FORMAT(sri.subtotal, 0), ' đ)') ORDER BY sri.id SEPARATOR ', ')
                     FROM service_request_items sri WHERE sri.request_id = sr.id) AS item_summary,
                    (SELECT COALESCE(SUM(sri.subtotal), 0)
                     FROM service_request_items sri WHERE sri.request_id = sr.id) AS request_total
             FROM service_requests sr
             JOIN bookings b ON b.id = sr.booking_id
             LEFT JOIN customers c ON c.id = b.customer_id
             LEFT JOIN pitches p ON p.id = b.pitch_id
             LEFT JOIN users u ON u.id = sr.handled_by
             ORDER BY FIELD(sr.status, 'pending', 'accepted', 'rejected'), sr.created_at DESC
             LIMIT 300"
        )->fetchAllAssociative();
    }

    public function updateStatus(int $requestId, int $adminId, string $status, string $staffNote = ''): array
    {
        if (!in_array($status, ['accepted', 'rejected'], true)) {
            return ['success' => false, 'error' => 'Trạng thái xử lý không hợp lệ.'];
        }
        if (mb_strlen($staffNote) > 500) {
            return ['success' => false, 'error' => 'Ghi chú không được vượt quá 500 ký tự.'];
        }

        $this->connection->beginTransaction();
        try {
            $request = $this->connection->executeQuery(
                'SELECT * FROM service_requests WHERE id = ? FOR UPDATE',
                [$requestId]
            )->fetchAssociative();
            if (!$request || $request['status'] !== 'pending') {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Yêu cầu không tồn tại hoặc đã được nhân viên xử lý.'];
            }

            $booking = $this->connection->executeQuery(
                'SELECT id, status, booking_date, total_price, deposit, paid_amount, series_id
                 FROM bookings WHERE id = ? FOR UPDATE',
                [(int)$request['booking_id']]
            )->fetchAssociative();
            if (!$booking) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Không tìm thấy lịch đặt sân.'];
            }
            if ($status === 'accepted'
                && ($booking['status'] !== Booking::STATUS_IN_PROGRESS || $booking['booking_date'] !== date('Y-m-d'))) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Lịch sân không còn diễn ra hôm nay nên không thể xác nhận dịch vụ.'];
            }

            $items = $this->connection->executeQuery(
                'SELECT * FROM service_request_items WHERE request_id = ? ORDER BY id ASC',
                [$requestId]
            )->fetchAllAssociative();
            if (!$items) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Yêu cầu không có dịch vụ chi tiết.'];
            }

            if ($status === 'accepted') {
                $addedTotal = 0.0;
                foreach ($items as $item) {
                    $changed = $this->connection->executeStatement(
                        "UPDATE services SET stock = stock - ?, updated_at = NOW()
                         WHERE id = ? AND status = 'active' AND stock >= ?",
                        [(int)$item['quantity'], (int)$item['service_id'], (int)$item['quantity']]
                    );
                    if ($changed !== 1) {
                        $this->connection->rollBack();
                        return [
                            'success' => false,
                            'error' => 'Dịch vụ "' . $item['service_name'] . '" không còn đủ tồn kho; chưa xác nhận yêu cầu.',
                        ];
                    }
                    $this->connection->insert('booking_services', [
                        'booking_id' => (int)$booking['id'],
                        'service_id' => (int)$item['service_id'],
                        'quantity' => (int)$item['quantity'],
                        'unit_price' => (float)$item['unit_price'],
                        'subtotal' => (float)$item['subtotal'],
                        'note' => 'Yêu cầu bổ sung #' . $requestId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $addedTotal += (float)$item['subtotal'];
                }

                $newTotal = (float)$booking['total_price'] + $addedTotal;
                $paymentStatus = Booking::calcPaymentStatus(
                    $newTotal,
                    (float)$booking['deposit'],
                    (float)$booking['paid_amount']
                );
                $this->connection->executeStatement(
                    'UPDATE bookings SET total_price = ?, payment_status = ?, updated_at = NOW() WHERE id = ?',
                    [$newTotal, $paymentStatus, (int)$booking['id']]
                );

                if (!empty($booking['series_id'])) {
                    $series = $this->connection->executeQuery(
                        'SELECT total_price, deposit, paid_amount FROM booking_series WHERE id = ? FOR UPDATE',
                        [(int)$booking['series_id']]
                    )->fetchAssociative();
                    if ($series) {
                        $this->connection->executeStatement(
                            'UPDATE booking_series SET total_price = total_price + ?, payment_status = ?, updated_at = NOW() WHERE id = ?',
                            [
                                $addedTotal,
                                Booking::calcPaymentStatus(
                                    (float)$series['total_price'] + $addedTotal,
                                    (float)$series['deposit'],
                                    (float)$series['paid_amount']
                                ),
                                (int)$booking['series_id'],
                            ]
                        );
                    }
                }
            }

            $this->connection->executeStatement(
                'UPDATE service_requests
                 SET status = ?, staff_note = ?, handled_by = ?, handled_at = NOW()
                 WHERE id = ?',
                [$status, trim($staffNote) ?: null, $adminId, $requestId]
            );
            $this->connection->commit();
            return [
                'success' => true,
                'booking_id' => (int)$booking['id'],
                'requested_by' => (int)$request['requested_by'],
            ];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Service request handling failed for request #' . $requestId . ': ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể xử lý yêu cầu. Vui lòng thử lại.'];
        }
    }
}
