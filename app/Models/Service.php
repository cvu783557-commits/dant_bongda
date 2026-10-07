<?php

namespace App\Models;

use App\Model;

class Service extends Model
{
    protected $table = 'services';

    const CATEGORIES = [
        'drink'     => 'Đồ uống',
        'food'      => 'Đồ ăn',
        'equipment' => 'Thiết bị',
        'service'   => 'Dịch vụ khác',
        'other'     => 'Khác',
    ];

    public static function getCategoryName($key)
    {
        return self::CATEGORIES[$key] ?? 'Khác';
    }

    public static function getCategoryColor($key)
    {
        return match ($key) {
            'drink'     => 'info',
            'food'      => 'warning',
            'equipment' => 'primary',
            'service'   => 'success',
            default     => 'secondary',
        };
    }

    public function all($status = null, $category = null)
    {
        $sql    = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($status) {
            $sql    .= " AND status = ?";
            $params[] = $status;
        }

        if ($category) {
            $sql    .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY category ASC, name ASC";

        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function getActive()
    {
        return $this->all('active');
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function search($keyword, $status = null)
    {
        $sql    = "SELECT * FROM {$this->table} WHERE name LIKE ?";
        $params = ['%' . trim($keyword) . '%'];

        if ($status) {
            $sql    .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY name ASC";
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table}
                (name, category, price, unit, stock, description, image, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->connection->executeStatement($sql, [
            trim($data['name']),
            $data['category']  ?? 'other',
            (float)($data['price'] ?? 0),
            trim($data['unit'] ?? 'cái'),
            (int)($data['stock'] ?? 0),
            $data['description'] ?? null,
            $data['image']       ?? null,
            $data['status']      ?? 'active',
        ]);

        return $this->connection->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE {$this->table} SET
                    name = ?, category = ?, price = ?, unit = ?,
                    stock = ?, description = ?, status = ?";
        $params = [
            trim($data['name']),
            $data['category']  ?? 'other',
            (float)($data['price'] ?? 0),
            trim($data['unit'] ?? 'cái'),
            (int)($data['stock'] ?? 0),
            $data['description'] ?? null,
            $data['status']      ?? 'active',
        ];

        if (isset($data['image'])) {
            $sql     .= ", image = ?";
            $params[] = $data['image'];
        }

        $sql     .= " WHERE id = ?";
        $params[] = $id;

        return $this->connection->executeStatement($sql, $params);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->connection->executeStatement($sql, [$id]);
    }

    public function updateStock($id, $delta)
    {
        $sql = "UPDATE {$this->table}
                SET stock = GREATEST(0, stock + ?), updated_at = NOW()
                WHERE id = ?";
        return $this->connection->executeStatement($sql, [(int)$delta, (int)$id]);
    }

    public function count($status = null)
    {
        $sql    = "SELECT COUNT(*) AS total FROM {$this->table} WHERE 1=1";
        $params = [];
        if ($status) {
            $sql    .= " AND status = ?";
            $params[] = $status;
        }
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        return (int)($row['total'] ?? 0);
    }

    public function countByCategory()
    {
        $sql = "SELECT category, COUNT(*) AS cnt
                FROM {$this->table}
                GROUP BY category
                ORDER BY cnt DESC";
        return $this->connection->executeQuery($sql)->fetchAllAssociative();
    }

    public function lowStock($threshold = 10)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status = 'active' AND stock <= ?
                ORDER BY stock ASC, name ASC";
        return $this->connection->executeQuery($sql, [$threshold])->fetchAllAssociative();
    }

    public function getSalesStats($id)
    {
        $sql = "SELECT
                    COALESCE(COUNT(bs.id), 0) AS total_orders,
                    COALESCE(SUM(bs.quantity), 0) AS total_qty_sold,
                    COALESCE(SUM(bs.subtotal), 0) AS total_revenue
                 FROM {$this->table} s
                 LEFT JOIN booking_services bs ON bs.service_id = s.id
                 WHERE s.id = ?
                 GROUP BY s.id";
        $row = $this->connection->executeQuery($sql, [$id])->fetchAssociative();
        return $row ?: ['total_orders' => 0, 'total_qty_sold' => 0, 'total_revenue' => 0];
    }

    public function getRelatedServices($id, $category, $limit = 6)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE category = ? AND id != ? AND status = 'active'
                ORDER BY name ASC LIMIT {$limit}";
        return $this->connection->executeQuery($sql, [
            $category ?? 'other', (int)$id
        ])->fetchAllAssociative();
    }
}
