<?php

namespace App\Models;

use App\Model;

class User extends Model
{
    protected $table = 'users';

    public function all($status = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY role DESC, full_name ASC";
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

    public function verify($username, $password)
    {
        $user = $this->findByUsername($username);
        if (!$user || $user['status'] !== 'active') return false;
        if (!password_verify($password, $user['password_hash'] ?? '')) return false;
        return $user;
    }

    public function getActiveUser()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid = $_SESSION['auth_user_id'] ?? null;
        if (!$uid) return null;
        return $this->find($uid);
    }

    public function isAdmin($user = null)
    {
        if ($user === null) $user = $this->getActiveUser();
        return $user && ($user['role'] ?? '') === 'ADMIN';
    }

    public function isStaff($user = null)
    {
        if ($user === null) $user = $this->getActiveUser();
        return $user && in_array($user['role'] ?? '', ['ADMIN', 'STAFF']);
    }

    public function canCreate($user = null)
    {
        return $this->isStaff($user);
    }

    public function canEdit($user = null)
    {
        return $this->isStaff($user);
    }

    public function canCancel($user = null)
    {
        return $this->isStaff($user);
    }

    public function canDelete($user = null)
    {
        return $this->isAdmin($user);
    }

    public function canUpdatePayment($user = null)
    {
        return $this->isStaff($user);
    }

    public function canConfirm($user = null)
    {
        return $this->isStaff($user);
    }
}
