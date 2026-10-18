<?php

namespace App\Models;

use App\Model;

class Notification extends Model
{
    protected $table = 'user_notifications';

    public function createForUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $url,
        string $dedupeKey
    ): void {
        $this->connection->executeStatement(
            "INSERT IGNORE INTO {$this->table}
                (user_id, type, title, message, url, dedupe_key, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $userId,
                mb_substr($type, 0, 40),
                mb_substr($title, 0, 160),
                mb_substr($message, 0, 500),
                mb_substr($url, 0, 255),
                mb_substr($dedupeKey, 0, 120),
            ]
        );
    }

    public function createForBookingOwner(
        int $bookingId,
        string $type,
        string $title,
        string $message,
        string $dedupeKey
    ): void {
        $userId = $this->connection->executeQuery(
            'SELECT created_by FROM bookings WHERE id = ? LIMIT 1',
            [$bookingId]
        )->fetchOne();

        if ($userId === false || $userId === null) {
            return;
        }

        $this->createForUser(
            (int)$userId,
            $type,
            $title,
            $message,
            route('my-bookings'),
            $dedupeKey
        );
    }

    public function forUser(int $userId): array
    {
        return $this->connection->executeQuery(
            "SELECT id, type, title, message, url, is_read, created_at
             FROM {$this->table}
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT 100",
            [$userId]
        )->fetchAllAssociative();
    }

    public function unreadCount(int $userId): int
    {
        return (int)$this->connection->executeQuery(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND is_read = 0",
            [$userId]
        )->fetchOne();
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        return $this->connection->executeStatement(
            "UPDATE {$this->table} SET is_read = 1
             WHERE id = ? AND user_id = ? AND is_read = 0",
            [$notificationId, $userId]
        ) === 1;
    }

    public function markAllRead(int $userId): void
    {
        $this->connection->executeStatement(
            "UPDATE {$this->table} SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }
}
