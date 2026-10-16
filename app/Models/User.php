<?php

namespace App\Models;

use App\Model;

class User extends Model
{
    protected $table = 'users';

    public function all($status = null)
    {
        $sql = "SELECT id, username, full_name, phone, role, status, created_at FROM {$this->table}";
        $params = [];
        if (in_array($status, ['active', 'inactive', 'pending'], true)) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY FIELD(status, 'inactive', 'active', 'pending'), FIELD(role, 'ADMIN', 'USER'), full_name ASC";
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function findByUsername($username)
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1";
        return $this->connection->executeQuery($sql, [$username])->fetchAssociative();
    }

    public function usernameExists($username)
    {
        return (bool)$this->connection->fetchOne(
            "SELECT 1 FROM {$this->table} WHERE username = ? LIMIT 1",
            [$username]
        );
    }

    public function register($username, $password, $fullName, $phone = null)
    {
        $this->connection->insert($this->table, [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'phone' => $phone ?: null,
            'role' => 'USER',
            'status' => 'active',
        ]);

        return (int)$this->connection->lastInsertId();
    }

    public function updateAccess($id, $role, $status)
    {
        if (!in_array($role, ['ADMIN', 'USER'], true)
            || !in_array($status, ['active', 'inactive'], true)) {
            return ['success' => false, 'error' => 'Quyền hoặc trạng thái tài khoản không hợp lệ.'];
        }

        $this->connection->beginTransaction();
        try {
            $target = $this->connection->executeQuery(
                "SELECT id, role, status FROM {$this->table} WHERE id = ? FOR UPDATE",
                [(int)$id]
            )->fetchAssociative();

            if (!$target) {
                $this->connection->rollBack();
                return ['success' => false, 'error' => 'Không tìm thấy tài khoản.'];
            }

            if ($target['role'] === 'ADMIN' && $target['status'] === 'active'
                && ($role !== 'ADMIN' || $status !== 'active')) {
                $activeAdmins = $this->connection->executeQuery(
                    "SELECT id FROM {$this->table} WHERE role = 'ADMIN' AND status = 'active' FOR UPDATE"
                )->fetchAllAssociative();

                if (count($activeAdmins) <= 1) {
                    $this->connection->rollBack();
                    return [
                        'success' => false,
                        'error' => 'Không thể khóa hoặc hạ quyền ADMIN hoạt động cuối cùng.',
                    ];
                }
            }

            $this->connection->update($this->table, [
                'role' => $role,
                'status' => $status,
            ], ['id' => (int)$id]);
            $this->connection->commit();

            return ['success' => true];
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    public function verify($username, $password)
    {
        $user = $this->findByUsername($username);
        if (!$user) return false;
        if (!password_verify($password, $user['password_hash'] ?? '')) return false;
        return $user;
    }

    public function getActiveUser()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid = $_SESSION['auth_user_id'] ?? null;
        if (!$uid) return null;
        $user = $this->find($uid);
        if (!$user || ($user['status'] ?? '') !== 'active'
            || !in_array($user['role'] ?? '', ['ADMIN', 'USER'], true)) {
            unset($_SESSION['auth_user_id'], $_SESSION['auth_username'], $_SESSION['auth_role'], $_SESSION['auth_full_name']);
            return null;
        }
        return $user;
    }

    public function isAdmin($user = null)
    {
        if ($user === null) $user = $this->getActiveUser();
        return $user && ($user['role'] ?? '') === 'ADMIN';
    }

    public function canAccessAdmin($user = null)
    {
        if ($user === null) $user = $this->getActiveUser();
        return $this->isAdmin($user);
    }

    public function canCreate($user = null)
    {
        return $this->canAccessAdmin($user);
    }

    public function canEdit($user = null)
    {
        return $this->canAccessAdmin($user);
    }

    public function canCancel($user = null)
    {
        return $this->canAccessAdmin($user);
    }

    public function canDelete($user = null)
    {
        return $this->isAdmin($user);
    }

    public function canUpdatePayment($user = null)
    {
        return $this->canAccessAdmin($user);
    }

    public function canConfirm($user = null)
    {
        return $this->canAccessAdmin($user);
    }
}
