<?php

namespace App\Models;

use App\Model;

class Pitch extends Model
{
    protected $table = 'pitches';

    public function all($status = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY id ASC";

        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function getAvailable()
    {
        return $this->all('active');
    }

    public function getPublicListings($date, $startTime, $endTime, $type = null, $availableOnly = false, $district = '')
    {
        $sql = "SELECT p.*,
                    CASE
                        WHEN ? < p.opening_time OR ? > p.closing_time THEN 'outside_hours'
                        ELSE 'available'
                    END AS availability_status
                FROM {$this->table} p
                WHERE p.status = 'active'";
        $params = [$startTime, $endTime];

        if (in_array((int)$type, [5, 7, 11], true)) {
            $sql .= " AND p.type = ?";
            $params[] = (int)$type;
        }

        if ($district === '__unassigned') {
            $sql .= " AND (p.address IS NULL OR TRIM(p.address) = '')";
        } elseif ($district !== '') {
            $sql .= " AND p.address LIKE ?";
            $params[] = '%' . $district . '%';
        }

        $sql .= " ORDER BY p.price_per_hour ASC, p.id ASC";

        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function getBlockedCourts(int $pitchId, string $date, string $startTime, string $endTime): array
    {
        return $this->connection->executeQuery(
            "SELECT court_number, 'booked' AS block_type
             FROM bookings
             WHERE pitch_id = ? AND booking_date = ? AND status <> 'cancelled'
               AND start_time < ? AND end_time > ?
             UNION
             SELECT court_number, 'maintenance' AS block_type
             FROM pitch_locks
             WHERE pitch_id = ? AND lock_date = ?
               AND start_time < ? AND end_time > ?",
            [$pitchId, $date, $endTime, $startTime, $pitchId, $date, $endTime, $startTime]
        )->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table}
                (name, type, price_per_hour, description, image, address, opening_time,
                 closing_time, field_count, grass_quality, has_lighting, has_changing_room, has_parking,
                 extra_services, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->connection->executeStatement($sql, [
            $data['name'],
            $data['type'],
            $data['price_per_hour'],
            $data['description'] ?? null,
            $data['image'] ?? null,
            $data['address'] ?? null,
            $data['opening_time'] ?? '06:00:00',
            $data['closing_time'] ?? '23:00:00',
            max(1, (int)($data['field_count'] ?? 1)),
            $data['grass_quality'] ?? null,
            (int)($data['has_lighting'] ?? 0),
            (int)($data['has_changing_room'] ?? 0),
            (int)($data['has_parking'] ?? 0),
            $data['extra_services'] ?? null,
            $data['status'] ?? 'active',
        ]);

        return $this->connection->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE {$this->table} SET
                    name = ?, type = ?, price_per_hour = ?,
                    description = ?, status = ?";
        $params = [
            $data['name'],
            $data['type'],
            $data['price_per_hour'],
            $data['description'] ?? null,
            $data['status'] ?? 'active',
        ];

        foreach ([
            'address',
            'opening_time',
            'closing_time',
            'field_count',
            'grass_quality',
            'has_lighting',
            'has_changing_room',
            'has_parking',
            'extra_services',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $sql .= ", {$field} = ?";
                $params[] = in_array($field, ['has_lighting', 'has_changing_room', 'has_parking', 'field_count'], true)
                    ? (int)$data[$field]
                    : $data[$field];
            }
        }

        if (isset($data['image'])) {
            $sql .= ", image = ?";
            $params[] = $data['image'];
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        return $this->connection->executeStatement($sql, $params);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->connection->executeStatement($sql, [$id]);
    }

    public function count()
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE status = 'active'";
        $row = $this->connection->executeQuery($sql)->fetchAssociative();
        return $row['total'] ?? 0;
    }
}
