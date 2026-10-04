<?php

namespace App\Models;

use App\Model;

class Customer extends Model
{
    public function all($search = '')
    {
        $sql = "SELECT b.customer_phone,
                       MAX(b.customer_name) AS customer_name,
                       MAX(b.customer_email) AS customer_email,
                       COUNT(*) AS booking_count,
                       SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END) AS confirmed_total,
                       MAX(b.booking_date) AS last_booking_date
                FROM bookings b";
        $params = [];

        if ($search !== '') {
            $sql .= " WHERE EXISTS (
                          SELECT 1 FROM bookings matched
                          WHERE matched.customer_phone = b.customer_phone
                            AND (matched.customer_name LIKE ? OR matched.customer_phone LIKE ? OR matched.customer_email LIKE ?)
                      )";
            $term = '%' . $search . '%';
            $params = [$term, $term, $term];
        }

        $sql .= " GROUP BY b.customer_phone ORDER BY last_booking_date DESC, customer_name ASC";

        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    public function findByPhone($phone)
    {
        $sql = "SELECT customer_phone,
                       MAX(customer_name) AS customer_name,
                       MAX(customer_email) AS customer_email,
                       COUNT(*) AS booking_count,
                       SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) AS confirmed_total
                FROM bookings
                WHERE customer_phone = ?
                GROUP BY customer_phone";

        return $this->connection->executeQuery($sql, [$phone])->fetchAssociative();
    }

    public function bookingsByPhone($phone)
    {
        $sql = "SELECT b.*, p.name AS pitch_name, p.type AS pitch_type,
                       ts.start_time, ts.end_time
                FROM bookings b
                LEFT JOIN pitches p ON p.id = b.pitch_id
                LEFT JOIN time_slots ts ON ts.id = b.time_slot_id
                WHERE b.customer_phone = ?
                ORDER BY b.booking_date DESC, b.created_at DESC";

        return $this->connection->executeQuery($sql, [$phone])->fetchAllAssociative();
    }
}