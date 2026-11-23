<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/..')->load();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT']
        . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('email', $columns, true)) {
    $pdo->exec('ALTER TABLE users ADD COLUMN email VARCHAR(100) NULL AFTER phone');
    echo "Added users.email\n";
} else {
    echo "users.email already exists\n";
}

$customerColumns = $pdo->query('SHOW COLUMNS FROM customers')->fetchAll(PDO::FETCH_COLUMN);
if (in_array('email', $customerColumns, true)) {
    $updated = $pdo->exec(
        "UPDATE users u
         JOIN customers c ON c.phone = u.phone
         SET u.email = c.email
         WHERE (u.email IS NULL OR u.email = '')
           AND c.email IS NOT NULL AND c.email <> ''"
    );
    echo "Restored email for {$updated} existing account(s)\n";
}

echo "User email migration completed.\n";
