<?php

namespace App\Models;

use App\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    public function all($filters = [])
    {
        $sql = "SELECT b.*,
                       p.name AS pitch_name, p.type AS pitch_type,
                       ts.start_time, ts.end_time
                FROM {$this->table} b
                LEFT JOIN pitches p ON p.id = b.pitch_id
                LEFT JOIN time_slots ts ON ts.id = b.time_slot_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['booking_date'])) {
            $sql .= " AND b.booking_date = ?";
            $params[] = $filters['booking_date'];
        }

        if (!empty($filters['pitch_id'])) {
            $sql .= " AND b.pitch_id = ?";
            $params[] = $filters['pitch_id'];
        }

        $sql .= " ORDER BY b.booking_date DESC, b.created_at DESC";

        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql = "SELECT b.*,
                       p.name AS pitch_name, p.type AS pitch_type, p.price_per_hour,
                       ts.start_time, ts.end_time
                FROM {$this->table} b
                LEFT JOIN pitches p ON p.id = b.pitch_id
                LEFT JOIN time_slots ts ON ts.id = b.time_slot_id
                WHERE b.id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function getByDate($pitchId, $date)
    {
        $sql = "SELECT b.*, ts.start_time, ts.end_time
                FROM {$this->table} b
                LEFT JOIN time_slots ts ON ts.id = b.time_slot_id
                WHERE b.pitch_id = ?
                  AND b.booking_date = ?
                  AND b.status IN ('pending','confirmed')
                ORDER BY ts.start_time ASC";
        return $this->connection->executeQuery($sql, [$pitchId, $date])->fetchAllAssociative();
    }

    public function getBookedSlotIds($pitchId, $date)
    {
        $rows = $this->getByDate($pitchId, $date);
        $ids = [];
        foreach ($rows as $r) {
            if (isset($r['time_slot_id'])) $ids[] = $r['time_slot_id'];
        }
        return $ids;
    }

    public function isSlotTaken($pitchId, $date, $slotId, $excludeBookingId = null)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM {$this->table}
                WHERE pitch_id = ?
                  AND booking_date = ?
                  AND time_slot_id = ?
                  AND status IN ('pending','confirmed')";
        $params = [$pitchId, $date, $slotId];

        if ($excludeBookingId) {
            $sql .= " AND id <> ?";
            $params[] = $excludeBookingId;
        }

        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        return ($row['total'] ?? 0) > 0;
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table}
                (pitch_id, customer_name, customer_phone, customer_email,
                 booking_date, time_slot_id, total_price, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->connection->executeStatement($sql, [
            $data['pitch_id'],
            $data['customer_name'],
            $data['customer_phone'],
            $data['customer_email'] ?? null,
            $data['booking_date'],
            $data['time_slot_id'],
            $data['total_price'],
            $data['status'] ?? 'pending',
            $data['notes'] ?? null,
        ]);

        return $this->connection->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE {$this->table} SET
                    status = ?, notes = ?
                WHERE id = ?";

        return $this->connection->executeStatement($sql, [
            $data['status'] ?? 'pending',
            $data['notes'] ?? null,
            $id,
        ]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->connection->executeStatement($sql, [$id]);
    }

    public function countToday()
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE booking_date = CURDATE()";
        $row = $this->connection->executeQuery($sql)->fetchAssociative();
        return $row['total'] ?? 0;
    }

    public function revenueConfirmed()
    {
        $sql = "SELECT SUM(total_price) AS total
                FROM {$this->table}
                WHERE status = 'confirmed'";
        $row = $this->connection->executeQuery($sql)->fetchAssociative();
        return (float)($row['total'] ?? 0);
    }
}
