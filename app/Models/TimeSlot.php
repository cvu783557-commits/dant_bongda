<?php

namespace App\Models;

use App\Model;

class TimeSlot extends Model
{
    protected $table = 'time_slots';

    public function all()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY start_time ASC";
        return $this->connection->executeQuery($sql)->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function formatRange($slot)
    {
        if (!$slot) return '';
        $start = substr($slot['start_time'], 0, 5);
        $end   = substr($slot['end_time'],   0, 5);
        return "{$start} - {$end}";
    }

    public function forPitch($pitchId)
    {
        $sql = "SELECT * FROM pitch_time_prices WHERE pitch_id = ? ORDER BY start_time ASC";
        return $this->connection->executeQuery($sql, [(int)$pitchId])->fetchAllAssociative();
    }

    public function replaceForPitch($pitchId, array $prices)
    {
        $pitchId = (int)$pitchId;
        $this->connection->executeStatement(
            "DELETE FROM pitch_time_prices WHERE pitch_id = ?",
            [$pitchId]
        );

        foreach ($prices as $row) {
            $this->connection->executeStatement(
                "INSERT INTO pitch_time_prices
                 (pitch_id, start_time, end_time, weekday_price, weekend_price)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $pitchId,
                    $row['start_time'],
                    $row['end_time'],
                    max(0, (int)$row['weekday_price']),
                    max(0, (int)$row['weekend_price']),
                ]
            );
        }
    }

    public function getPriceForTime($pitchId, $startTime, $endTime, $date)
    {
        $isWeekend = in_array((int)date('N', strtotime($date)), [6, 7], true);
        $column = $isWeekend ? 'weekend_price' : 'weekday_price';
        $row = $this->connection->executeQuery(
            "SELECT {$column} AS price
             FROM pitch_time_prices
             WHERE pitch_id = ? AND start_time <= ? AND end_time >= ?
             ORDER BY start_time ASC
             LIMIT 1",
            [(int)$pitchId, substr($startTime, 0, 5) . ':00', substr($endTime, 0, 5) . ':00']
        )->fetchAssociative();

        return $row ? (float)$row['price'] : null;
    }
}
