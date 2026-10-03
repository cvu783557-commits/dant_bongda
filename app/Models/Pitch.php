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

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table}
                (name, type, price_per_hour, description, image, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $this->connection->executeStatement($sql, [
            $data['name'],
            $data['type'],
            $data['price_per_hour'],
            $data['description'] ?? null,
            $data['image'] ?? null,
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
