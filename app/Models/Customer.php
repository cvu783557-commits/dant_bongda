<?php

namespace App\Models;

use App\Model;

class Customer extends Model
{
    protected $table = 'customers';

    public function all($search = '')
    {
        $sql = "SELECT c.*,
                       c.name AS customer_name,
                       c.phone AS customer_phone,
                       c.email AS customer_email,
                       (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) AS booking_count,
                       (SELECT COALESCE(SUM(CASE WHEN b.status <> 'cancelled' THEN b.total_price ELSE 0 END),0) FROM bookings b WHERE b.customer_id = c.id) AS total_spent,
                       (SELECT COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END),0) FROM bookings b WHERE b.customer_id = c.id) AS confirmed_total,
                       (SELECT MAX(b.booking_date) FROM bookings b WHERE b.customer_id = c.id) AS last_booking_date
                FROM {$this->table} c";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?";
            $term = '%' . $search . '%';
            $params = [$term, $term, $term];
        }

        $sql .= " ORDER BY last_booking_date DESC, c.name ASC";
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function findWithSummary($id)
    {
        $sql = "SELECT c.*,
                       c.name AS customer_name,
                       c.phone AS customer_phone,
                       c.email AS customer_email,
                       (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) AS booking_count,
                       (SELECT COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END),0) FROM bookings b WHERE b.customer_id = c.id) AS confirmed_total
                FROM {$this->table} c
                WHERE c.id = ?";
        return $this->connection->executeQuery($sql, [$id])->fetchAssociative();
    }

    public function findByPhone($phone)
    {
        $sql = "SELECT * FROM {$this->table} WHERE phone = ? LIMIT 1";
        return $this->connection->executeQuery($sql, [$phone])->fetchAssociative();
    }

    public function findByNamePhone($name, $phone)
    {
        $sql = "SELECT * FROM {$this->table} WHERE phone = ? LIMIT 1";
        $found = $this->connection->executeQuery($sql, [$phone])->fetchAssociative();
        if ($found) return $found;

        $sql = "SELECT * FROM {$this->table} WHERE name = ? AND phone = ? LIMIT 1";
        return $this->connection->executeQuery($sql, [$name, $phone])->fetchAssociative();
    }

    public function findOrCreate($name, $phone, $email = null)
    {
        $name  = trim($name);
        $phone = trim($phone);
        $email = trim($email ?? '');
        if ($email === '') $email = null;

        $existing = $this->findByPhone($phone);
        if ($existing) {
            $changed = false;
            $sqlUpdate = "UPDATE {$this->table} SET ";
            $params = [];
            if ($existing['name'] !== $name) {
                $sqlUpdate .= "name = ?, ";
                $params[] = $name;
                $changed = true;
            }
            if ($email !== null && $existing['email'] != $email) {
                $sqlUpdate .= "email = ?, ";
                $params[] = $email;
                $changed = true;
            }
            if ($changed) {
                $sqlUpdate = rtrim($sqlUpdate, ', ') . " WHERE id = ?";
                $params[] = $existing['id'];
                $this->connection->executeStatement($sqlUpdate, $params);
                return $this->find($existing['id']);
            }
            return $existing;
        }

        $sql = "INSERT INTO {$this->table} (name, phone, email, created_at) VALUES (?, ?, ?, NOW())";
        $this->connection->executeStatement($sql, [$name, $phone, $email]);
        $id = $this->connection->lastInsertId();
        return $this->find($id);
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE {$this->table} SET name = ?, phone = ?, email = ? WHERE id = ?";
        return $this->connection->executeStatement($sql, [
            trim($data['name']),
            trim($data['phone']),
            trim($data['email'] ?? '') ?: null,
            $id,
        ]);
    }

    public function bookings($customerId)
    {
        $sql = "SELECT b.*, p.name AS pitch_name, p.type AS pitch_type
                FROM bookings b
                LEFT JOIN pitches p ON p.id = b.pitch_id
                WHERE b.customer_id = ?
                ORDER BY b.booking_date DESC, b.created_at DESC";
        return $this->connection->executeQuery($sql, [$customerId])->fetchAllAssociative();
    }
}
