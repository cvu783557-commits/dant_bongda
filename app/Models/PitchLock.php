<?php

namespace App\Models;

use App\Model;

class PitchLock extends Model
{
    protected $table = 'pitch_locks';

    public function all($filters = [])
    {
        $sql = "SELECT pl.*,
                       p.name AS pitch_name,
                       u.full_name AS creator_name
                FROM {$this->table} pl
                LEFT JOIN pitches p ON p.id = pl.pitch_id
                LEFT JOIN users   u ON u.id = pl.created_by
                WHERE 1=1";
        $params = [];

        if (!empty($filters['pitch_id'])) {
            $sql .= " AND pl.pitch_id = ?";
            $params[] = (int)$filters['pitch_id'];
        }
        if (!empty($filters['lock_date'])) {
            $sql .= " AND pl.lock_date = ?";
            $params[] = $filters['lock_date'];
        }

        $sql .= " ORDER BY pl.lock_date DESC, pl.start_time ASC, pl.id DESC";
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql = "SELECT pl.*,
                       p.name AS pitch_name,
                       u.full_name AS creator_name
                FROM {$this->table} pl
                LEFT JOIN pitches p ON p.id = pl.pitch_id
                LEFT JOIN users   u ON u.id = pl.created_by
                WHERE pl.id = ? LIMIT 1";
        return $this->connection->executeQuery($sql, [(int)$id])->fetchAssociative();
    }

    public function getByPitchAndDate($pitchId, $date)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE pitch_id = ? AND lock_date = ?
                ORDER BY start_time ASC";
        return $this->connection->executeQuery($sql, [(int)$pitchId, $date])->fetchAllAssociative();
    }

    public function getAllByPitchAndDate($pitchId, $date)
    {
        return $this->getByPitchAndDate($pitchId, $date);
    }

    public function checkOverlap($pitchId, $date, $startTime, $endTime, $excludeId = null)
    {
        $sql = "SELECT pl.*, p.name AS pitch_name
                FROM {$this->table} pl
                LEFT JOIN pitches p ON p.id = pl.pitch_id
                WHERE pl.pitch_id = ?
                  AND pl.lock_date = ?
                  AND pl.start_time < ?
                  AND pl.end_time   > ?";
        $params = [(int)$pitchId, $date, $endTime, $startTime];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND pl.id <> ?";
            $params[] = (int)$excludeId;
        }

        $sql .= " LIMIT 1";
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        return $row ?: null;
    }

    public function create(array $data)
    {
        $this->connection->beginTransaction();
        try {
            $pitchId   = (int)$data['pitch_id'];
            $date      = $data['lock_date'];
            $startTime = $data['start_time'];
            $endTime   = $data['end_time'];

            $lockSql = "SELECT id FROM pitches WHERE id = ? FOR UPDATE";
            $this->connection->executeQuery($lockSql, [$pitchId]);

            $overlap = $this->checkOverlap($pitchId, $date, $startTime, $endTime);
            if ($overlap) {
                $this->connection->rollBack();
                return [
                    'success' => false,
                    'error'   => 'Khung giờ này đã bị khóa trước đó (#' . $overlap['id'] . ' ' . substr($overlap['start_time'],0,5) . '-' . substr($overlap['end_time'],0,5) . ')',
                ];
            }

            $sql = "INSERT INTO {$this->table}
                    (pitch_id, lock_date, start_time, end_time, reason, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $this->connection->executeStatement($sql, [
                $pitchId,
                $date,
                $startTime,
                $endTime,
                isset($data['reason']) ? trim($data['reason']) : null,
                isset($data['created_by']) ? ((int)$data['created_by'] ?: null) : null,
            ]);

            $id = (int)$this->connection->lastInsertId();
            $this->connection->commit();
            return ['success' => true, 'id' => $id];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            return ['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->connection->executeStatement($sql, [(int)$id]);
    }

    public function findOneAt($pitchId, $date, $startTime, $endTime)
    {
        return $this->checkOverlap($pitchId, $date, $startTime, $endTime);
    }
}
