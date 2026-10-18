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

$columns = [
    'pitches' => ['field_count' => 'INT UNSIGNED NOT NULL DEFAULT 1'],
    'bookings' => ['court_number' => 'INT UNSIGNED NOT NULL DEFAULT 1'],
    'booking_series' => ['court_number' => 'INT UNSIGNED NOT NULL DEFAULT 1'],
    'pitch_locks' => ['court_number' => 'INT UNSIGNED NOT NULL DEFAULT 1'],
];

foreach ($columns as $table => $tableColumns) {
    $existing = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tableColumns as $column => $definition) {
        if (!in_array($column, $existing, true)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            echo "Added {$table}.{$column}\n";
        }
    }
}

$indexes = [
    'bookings' => [
        'idx_booking_pitch_court_date' => '(pitch_id, court_number, booking_date)',
    ],
    'booking_series' => [
        'idx_booking_series_pitch_court_month' => '(pitch_id, court_number, booking_month)',
    ],
    'pitch_locks' => [
        'idx_lock_pitch_court_date' => '(pitch_id, court_number, lock_date)',
    ],
];

foreach ($indexes as $table => $tableIndexes) {
    $existing = $pdo->query("SHOW INDEX FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN, 2);
    foreach ($tableIndexes as $name => $columnsSql) {
        if (!in_array($name, $existing, true)) {
            $pdo->exec("CREATE INDEX `{$name}` ON `{$table}` {$columnsSql}");
            echo "Added {$table}.{$name}\n";
        }
    }
}

echo "Pitch court migration completed. Existing bookings and locks belong to court 1.\n";