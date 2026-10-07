<?php

namespace App\Models;

use App\Model;

class BookingService extends Model
{
    protected $table = 'booking_services';

    public function allByBooking($bookingId)
    {
        $sql = "SELECT bs.*,
                       s.name      AS service_name,
                       s.category  AS service_category,
                       s.unit      AS service_unit,
                       s.image     AS service_image
                FROM {$this->table} bs
                LEFT JOIN services s ON s.id = bs.service_id
                WHERE bs.booking_id = ?
                ORDER BY bs.id ASC";
        return $this->connection->executeQuery($sql, [$bookingId])->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql = "SELECT bs.*,
                       s.name      AS service_name,
                       s.category  AS service_category,
                       s.unit      AS service_unit
                FROM {$this->table} bs
                LEFT JOIN services s ON s.id = bs.service_id
                WHERE bs.id = ? LIMIT 1";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function sumSubtotalByBooking($bookingId)
    {
        $sql = "SELECT COALESCE(SUM(subtotal), 0) AS total_service
                FROM {$this->table}
                WHERE booking_id = ?";
        $row = $this->connection->executeQuery($sql, [$bookingId])->fetchAssociative();
        return (float)($row['total_service'] ?? 0);
    }

    public function create(array $data)
    {
        $serviceId = (int)$data['service_id'];
        $quantity  = (int)($data['quantity'] ?? 1);
        $unitPrice = (float)($data['unit_price'] ?? 0);
        $subtotal  = (float)($data['subtotal'] ?? ($quantity * $unitPrice));

        $sql = "INSERT INTO {$this->table}
                (booking_id, service_id, quantity, unit_price, subtotal, note, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $this->connection->executeStatement($sql, [
            (int)$data['booking_id'],
            $serviceId,
            $quantity,
            $unitPrice,
            $subtotal,
            $data['note'] ?? null,
        ]);

        $id = (int)$this->connection->lastInsertId();

        try {
            $svc = new Service();
            $svc->updateStock($serviceId, -$quantity);
        } catch (\Throwable $e) {
            // Giảm tồn kho không khóa nghiệp vụ chính
        }

        return $id;
    }

    public function updateItem($id, array $data)
    {
        $old = $this->find($id);
        if (!$old) return false;

        $quantity  = (int)($data['quantity'] ?? $old['quantity']);
        $unitPrice = (float)($data['unit_price'] ?? $old['unit_price']);
        $subtotal  = (float)($data['subtotal'] ?? ($quantity * $unitPrice));

        $sql = "UPDATE {$this->table} SET
                    quantity = ?, unit_price = ?, subtotal = ?, note = ?, updated_at = NOW()
                WHERE id = ?";

        $this->connection->executeStatement($sql, [
            $quantity,
            $unitPrice,
            $subtotal,
            $data['note'] ?? null,
            (int)$id,
        ]);

        $deltaQty = $quantity - (int)$old['quantity'];
        if ($deltaQty !== 0) {
            try {
                $svc = new Service();
                $svc->updateStock((int)$old['service_id'], -$deltaQty);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return true;
    }

    public function delete($id)
    {
        $old = $this->find($id);
        if (!$old) return false;

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->connection->executeStatement($sql, [(int)$id]);

        try {
            $svc = new Service();
            $svc->updateStock((int)$old['service_id'], (int)$old['quantity']);
        } catch (\Throwable $e) {
            // ignore
        }

        return true;
    }

    public function deleteAllByBooking($bookingId)
    {
        $items = $this->allByBooking($bookingId);
        foreach ($items as $it) {
            $this->delete((int)$it['id']);
        }
        return true;
    }

    public function topServices($fromDate = null, $toDate = null, $limit = 10)
    {
        $sql = "SELECT s.id, s.name, s.category, s.unit,
                       COALESCE(SUM(bs.quantity), 0) AS total_qty,
                       COALESCE(SUM(bs.subtotal), 0) AS total_revenue
                FROM services s
                LEFT JOIN {$this->table} bs ON bs.service_id = s.id
                LEFT JOIN bookings b ON b.id = bs.booking_id
                WHERE 1=1";
        $params = [];
        if ($fromDate) { $sql .= " AND b.booking_date >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $sql .= " AND b.booking_date <= ?"; $params[] = $toDate;   }
        $sql .= " GROUP BY s.id, s.name, s.category, s.unit
                  ORDER BY total_revenue DESC, total_qty DESC
                  LIMIT ?";
        $params[] = (int)$limit;
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }
}
