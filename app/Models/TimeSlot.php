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
}
