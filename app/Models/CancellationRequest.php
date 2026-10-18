<?php

namespace App\Models;

use App\Model;

class CancellationRequest extends Model
{
    protected $table = 'booking_cancellation_requests';

    public function createForCustomer(int $bookingId, int $userId, string $reason): array
    {
        $this->connection->beginTransaction();
        try {
            $booking = $this->connection->executeQuery(
                "SELECT id, created_by, booking_date, start_time, status, payment_method,
                        payment_status, paid_amount, deposit, series_id
                 FROM bookings WHERE id = ? FOR UPDATE",
                [$bookingId]
            )->fetchAssociative();

            if (!$booking || (int)$booking['created_by'] !== $userId) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Không tìm thấy đơn đặt sân của tài khoản này.'];
            }
            if (in_array($booking['status'], ['cancelled', 'completed', 'in_progress'], true)
                || strtotime($booking['booking_date'] . ' ' . $booking['start_time']) <= time()) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Đơn đã bắt đầu, hoàn tất hoặc bị hủy nên không thể gửi yêu cầu hủy.'];
            }
            if (!empty($booking['series_id']) && (float)$booking['paid_amount'] <= 0) {
                $this->connection->rollBack();
                return [
                    'success' => false,
                    'error' => 'Lịch đặt theo tháng chưa thanh toán; vui lòng liên hệ quản lý để tránh hủy sót cả chuỗi lịch.',
                ];
            }

            $activeRequest = $this->connection->executeQuery(
                "SELECT id FROM {$this->table}
                 WHERE booking_id = ? AND status IN ('pending','processing','approved','refunded','manual_review')
                 LIMIT 1 FOR UPDATE",
                [$bookingId]
            )->fetchAssociative();
            if ($activeRequest) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Đơn này đã có yêu cầu hủy đang xử lý hoặc đã được duyệt.'];
            }

            $refundAmount = 0;
            if (in_array($booking['payment_status'], ['deposit', 'partial', 'paid'], true)
                && (float)$booking['paid_amount'] > 0) {
                if ($booking['payment_method'] !== 'vnpay') {
                    $this->connection->rollBack();
                    return ['success' => false, 'error' => 'Đơn không thanh toán qua VNPay; vui lòng liên hệ quản lý để xử lý hoàn tiền.'];
                }
                $refundAmount = min((float)$booking['paid_amount'], (float)$booking['deposit']);
            }

            $this->connection->insert($this->table, [
                'booking_id' => $bookingId,
                'customer_user_id' => $userId,
                'request_reason' => $reason,
                'status' => 'pending',
                'refund_amount' => $refundAmount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $id = (int)$this->connection->lastInsertId();
            $this->connection->commit();
            return ['success' => true, 'id' => $id];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Cancellation request creation failed for booking #' . $bookingId . ': ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể gửi yêu cầu hủy. Vui lòng thử lại.'];
        }
    }

    public function forCustomer(int $userId): array
    {
        return $this->connection->executeQuery(
            "SELECT cr.*, b.booking_date, b.start_time, b.end_time, b.status AS booking_status,
                    b.payment_status, b.paid_amount, b.deposit, b.pitch_id, p.name AS pitch_name
             FROM {$this->table} cr
             JOIN bookings b ON b.id = cr.booking_id
             JOIN pitches p ON p.id = b.pitch_id
             WHERE cr.customer_user_id = ?
             ORDER BY cr.created_at DESC",
            [$userId]
        )->fetchAllAssociative();
    }

    public function allForAdmin(): array
    {
        return $this->connection->executeQuery(
            "SELECT cr.*, b.booking_date, b.start_time, b.end_time, b.status AS booking_status,
                    b.payment_method, b.payment_status, b.vnpay_txn_ref,
                    b.vnpay_transaction_no, b.vnpay_transaction_date,
                    c.name AS customer_name, c.phone AS customer_phone,
                    p.name AS pitch_name, u.full_name AS reviewer_name
             FROM {$this->table} cr
             JOIN bookings b ON b.id = cr.booking_id
             JOIN customers c ON c.id = b.customer_id
             JOIN pitches p ON p.id = b.pitch_id
             LEFT JOIN users u ON u.id = cr.reviewed_by
             ORDER BY FIELD(cr.status, 'pending', 'processing', 'manual_review', 'approved', 'refunded', 'rejected'),
                      cr.created_at DESC"
        )->fetchAllAssociative();
    }

    public function find(int $id): ?array
    {
        $row = $this->connection->executeQuery(
            "SELECT cr.*, b.booking_date, b.start_time, b.end_time, b.status AS booking_status,
                    b.payment_method, b.payment_status, b.paid_amount, b.deposit,
                    b.vnpay_txn_ref, b.vnpay_transaction_no, b.vnpay_transaction_date,
                    b.series_id, c.name AS customer_name, c.phone AS customer_phone,
                    p.name AS pitch_name
             FROM {$this->table} cr
             JOIN bookings b ON b.id = cr.booking_id
             JOIN customers c ON c.id = b.customer_id
             JOIN pitches p ON p.id = b.pitch_id
             WHERE cr.id = ? LIMIT 1",
            [$id]
        )->fetchAssociative();
        return $row ?: null;
    }

    public function claimForReview(int $id, int $adminId, string $requestId): ?array
    {
        $affected = $this->connection->executeStatement(
            "UPDATE {$this->table}
             SET status = 'processing', reviewed_by = ?, refund_request_id = ?, reviewed_at = NOW()
             WHERE id = ? AND status = 'pending'",
            [$adminId, $requestId, $id]
        );
        return $affected === 1 ? $this->find($id) : null;
    }

    public function setStatus(
        int $id,
        string $status,
        ?string $responseCode = null,
        ?string $message = null
    ): void {
        $this->connection->executeStatement(
            "UPDATE {$this->table}
             SET status = ?, refund_response_code = ?, refund_message = ?, reviewed_at = NOW()
             WHERE id = ?",
            [$status, $responseCode, $message, $id]
        );
    }

    public function reject(int $id, int $adminId, string $message): bool
    {
        return $this->connection->executeStatement(
            "UPDATE {$this->table}
             SET status = 'rejected', reviewed_by = ?, refund_message = ?, reviewed_at = NOW()
             WHERE id = ? AND status = 'pending'",
            [$adminId, $message, $id]
        ) === 1;
    }
}
