<?php

namespace App\Models;

use App\Model;

class PitchRating extends Model
{
    protected $table = 'pitch_ratings';

    public function createForCompletedBooking(int $bookingId, int $userId, int $rating, string $comment): array
    {
        if ($rating < 1 || $rating > 5 || mb_strlen($comment) > 1000) {
            return ['success' => false, 'error' => 'Thông tin đánh giá không hợp lệ.'];
        }

        $booking = $this->connection->executeQuery(
            "SELECT id, pitch_id, created_by, status
             FROM bookings
             WHERE id = ? AND created_by = ?
             LIMIT 1",
            [$bookingId, $userId]
        )->fetchAssociative();

        if (!$booking) {
            return ['success' => false, 'error' => 'Không tìm thấy đơn đặt sân của tài khoản này.'];
        }
        if ($booking['status'] !== 'completed') {
            return ['success' => false, 'error' => 'Bạn chỉ có thể đánh giá sau khi đơn đặt sân hoàn thành.'];
        }

        $existing = $this->connection->executeQuery(
            "SELECT id FROM {$this->table} WHERE booking_id = ? LIMIT 1",
            [$bookingId]
        )->fetchOne();
        if ($existing) {
            return ['success' => false, 'error' => 'Đơn đặt sân này đã được đánh giá.'];
        }

        try {
            $this->connection->insert($this->table, [
                'booking_id' => $bookingId,
                'pitch_id' => (int)$booking['pitch_id'],
                'customer_user_id' => $userId,
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return ['success' => false, 'error' => 'Đơn đặt sân này đã được đánh giá.'];
        }

        return ['success' => true];
    }

    public function forPitch(int $pitchId): array
    {
        return $this->connection->executeQuery(
            "SELECT pr.rating, pr.comment, pr.created_at, b.booking_date
             FROM {$this->table} pr
             JOIN bookings b ON b.id = pr.booking_id
             WHERE pr.pitch_id = ?
             ORDER BY pr.created_at DESC, pr.id DESC
             LIMIT 20",
            [$pitchId]
        )->fetchAllAssociative();
    }

    public function summaryForPitch(int $pitchId): array
    {
        $summary = $this->connection->executeQuery(
            "SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average
             FROM {$this->table}
             WHERE pitch_id = ?",
            [$pitchId]
        )->fetchAssociative();

        return [
            'total' => (int)($summary['total'] ?? 0),
            'average' => (float)($summary['average'] ?? 0),
        ];
    }
}
