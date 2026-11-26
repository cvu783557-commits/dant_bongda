<?php

namespace App\Models;

use App\Model;

class ServiceSale extends Model
{
    public function createSale(
        int $serviceId,
        int $quantity,
        string $customerName,
        string $customerPhone,
        string $paymentMethod,
        int $adminId
    ): array {
        if ($serviceId <= 0 || $quantity < 1 || $quantity > 20
            || !in_array($paymentMethod, ['cash', 'banking'], true)) {
            return ['success' => false, 'error' => 'Dịch vụ hoặc số lượng không hợp lệ.'];
        }

        $this->connection->beginTransaction();
        try {
            $service = $this->connection->executeQuery(
                "SELECT id, name, unit, price, stock
                 FROM services WHERE id = ? AND status = 'active' FOR UPDATE",
                [$serviceId]
            )->fetchAssociative();
            if (!$service) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Dịch vụ không tồn tại hoặc đã ngừng kinh doanh.'];
            }
            if ((int)$service['stock'] < $quantity) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Số lượng dịch vụ "' . $service['name'] . '" hiện không đủ.'];
            }

            $unitPrice = (float)$service['price'];
            $total = $unitPrice * $quantity;
            $this->connection->insert('service_sales', [
                'service_id' => $serviceId,
                'service_name' => $service['name'],
                'unit' => $service['unit'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $total,
                'customer_name' => $customerName !== '' ? $customerName : null,
                'customer_phone' => $customerPhone !== '' ? $customerPhone : null,
                'payment_method' => $paymentMethod,
                'recorded_by' => $adminId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $saleId = (int)$this->connection->lastInsertId();

            $changed = $this->connection->executeStatement(
                "UPDATE services SET stock = stock - ?, updated_at = NOW()
                 WHERE id = ? AND status = 'active' AND stock >= ?",
                [$quantity, $serviceId, $quantity]
            );
            if ($changed !== 1) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Tồn kho vừa thay đổi; vui lòng kiểm tra và thử lại.'];
            }

            $this->connection->commit();
            return ['success' => true, 'id' => $saleId];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            error_log('Service sale creation failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Không thể ghi nhận giao dịch. Vui lòng thử lại.'];
        }
    }

    public function recentForAdmin(): array
    {
        return $this->connection->executeQuery(
            'SELECT id, service_name, unit, quantity, total_amount, customer_name,
                    customer_phone, payment_method, created_at
             FROM service_sales
             ORDER BY created_at DESC, id DESC
             LIMIT 30'
        )->fetchAllAssociative();
    }

    public function findForInvoice(int $saleId): ?array
    {
        $sale = $this->connection->executeQuery(
            'SELECT ss.*, u.full_name AS recorded_by_name
             FROM service_sales ss
             LEFT JOIN users u ON u.id = ss.recorded_by
             WHERE ss.id = ?',
            [$saleId]
        )->fetchAssociative();

        return $sale ?: null;
    }
}
