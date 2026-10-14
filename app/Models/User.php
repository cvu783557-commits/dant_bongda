<?php

namespace App\Models;

use App\Model;

class User extends Model
{
    public function findByUsername(string $username)
    {
        return $this->connection
            ->executeQuery('SELECT * FROM users WHERE username = ? LIMIT 1', [$username])
            ->fetchAssociative();
    }

    public function verify(string $username, string $password)
    {
        $user = $this->findByUsername($username);

        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        return password_verify($password, $user['password_hash']) ? $user : false;
    }
}
