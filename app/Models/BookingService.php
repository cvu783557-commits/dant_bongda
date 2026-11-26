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

    public function addToActiveBooking(int $bookingId, int $serviceId, int $quantity): array
    {
        if ($bookingId <= 0 || $serviceId <= 0 || $quantity < 1 || $quantity > 20) {
            return ['success' => false, 'error' => 'Dịch vụ hoặc số lượng không hợp lệ.'];
        }

        $this->connection->beginTransaction();
        try {
            $now = date('H:i:s');
            $booking = $this->connection->executeQuery(
                "SELECT id, total_price, deposit, paid_amount
                 FROM bookings
                 WHERE id = ? AND booking_date = ?
                   AND start_time <= ? AND end_time > ?
                   AND status IN ('confirmed', 'in_progress')
                 FOR UPDATE",
                [$bookingId, date('Y-m-d'), $now, $now]
            )->fetchAssociative();
            if (!$booking) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Đơn đặt sân không còn trong thời gian hoạt động.'];
            }

            $service = $this->connection->executeQuery(
                "SELECT id, price, stock
                 FROM services
                 WHERE id = ? AND status = 'active'
                 FOR UPDATE",
                [$serviceId]
            )->fetchAssociative();
            if (!$service) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Dịch vụ không tồn tại hoặc đã ngừng kinh doanh.'];
            }
            if ((int)$service['stock'] < $quantity) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Số lượng dịch vụ hiện không đủ trong kho.'];
            }

            $unitPrice = (float)$service['price'];
            $subtotal = $unitPrice * $quantity;
            $this->connection->insert($this->table, [
                'booking_id' => $bookingId,
                'service_id' => $serviceId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $stockUpdated = $this->connection->executeStatement(
                "UPDATE services
                 SET stock = stock - ?, updated_at = NOW()
                 WHERE id = ? AND status = 'active' AND stock >= ?",
                [$quantity, $serviceId, $quantity]
            );
            if ($stockUpdated !== 1) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Tồn kho vừa thay đổi; vui lòng kiểm tra và thử lại.'];
            }

            $newTotal = (float)$booking['total_price'] + $subtotal;
            $paymentStatus = Booking::calcPaymentStatus(
                $newTotal,
                (float)$booking['deposit'],
                (float)$booking['paid_amount']
            );
            $this->connection->executeStatement(
                'UPDATE bookings SET total_price = ?, payment_status = ?, updated_at = NOW() WHERE id = ?',
                [$newTotal, $paymentStatus, $bookingId]
            );

            $this->connection->commit();
            return ['success' => true, 'subtotal' => $subtotal];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Adding service to active booking failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể thêm dịch vụ. Vui lòng thử lại.'];
        }
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
