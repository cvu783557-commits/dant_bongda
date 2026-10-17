<?php

namespace App\Models;

use App\Model;

class NewsletterSubscriber extends Model
{
    public function subscribe(string $email): void
    {
        $this->connection->executeStatement(
            'INSERT INTO newsletter_subscribers (email, consented_at, created_at)
             VALUES (?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE consented_at = NOW()',
            [$email]
        );
    }
}
