<?php

namespace App\Models;

use App\Model;

class OwnerProfile extends Model
{
    protected $table = 'owner_profiles';

    public function saveProfile(int $userId, array $data): void
    {
        $this->connection->insert($this->table, [
            'user_id' => $userId,
            'business_name' => trim((string)($data['business_name'] ?? '')),
            'business_phone' => trim((string)($data['business_phone'] ?? '')),
            'business_address' => trim((string)($data['business_address'] ?? '')),
            'tax_code' => trim((string)($data['tax_code'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function allForAdmin(): array
    {
        try {
            $sql = "
                SELECT op.*, u.username, u.full_name, u.phone, u.role, u.status AS user_status
                FROM {$this->table} op
                LEFT JOIN users u ON u.id = op.user_id
                ORDER BY op.created_at DESC
            ";

            return $this->connection->executeQuery($sql)->fetchAllAssociative();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function approve(int $userId): bool
    {
        $this->connection->beginTransaction();
        try {
            $this->connection->update($this->table, ['status' => 'approved'], ['user_id' => $userId]);
            $this->connection->update('users', ['role' => 'OWNER', 'status' => 'active'], ['id' => $userId]);
            $this->connection->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    public function reject(int $userId): bool
    {
        $this->connection->beginTransaction();
        try {
            $this->connection->update($this->table, ['status' => 'rejected'], ['user_id' => $userId]);
            $this->connection->update('users', ['status' => 'inactive'], ['id' => $userId]);
            $this->connection->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }
}
