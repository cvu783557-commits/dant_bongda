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

$pitches = [
    [
        'name' => 'Sân bóng Đại học Thủy Lợi - sân 7 người',
        'aliases' => ['Sân B1 - Sân 7 người cao cấp - Sân bóng Đại học Thủy Lợi'],
        'type' => 7,
        'field_count' => 4,
        'address' => 'Ngõ 95 Chùa Bộc, Kim Liên, Đống Đa, Hà Nội',
        'description' => 'Gồm 4 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Đại học Thủy Lợi - sân 11 người',
        'aliases' => [],
        'type' => 11,
        'field_count' => 1,
        'address' => 'Ngõ 95 Chùa Bộc, Kim Liên, Đống Đa, Hà Nội',
        'description' => 'Gồm 1 sân 11 người.',
    ],
    [
        'name' => 'Sân bóng Đại học Y',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => 'Số 1 Tôn Thất Tùng, Trung Tự, Đống Đa, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Hoàng Cầu',
        'aliases' => [],
        'type' => 7,
        'field_count' => 4,
        'address' => '42 phố Hoàng Cầu, Ô Chợ Dừa, Đống Đa, Hà Nội',
        'description' => 'Gồm 4 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng đá Phòng không - Không quân - sân 7 người',
        'aliases' => [],
        'type' => 7,
        'field_count' => 8,
        'address' => 'Đường Lê Trọng Tấn, Khương Mai, Thanh Xuân, Hà Nội',
        'description' => 'Gồm 8 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Viettel 1',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => 'Ngõ 155 Trường Chinh, Phương Liệt, Thanh Xuân, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Đầm Hồng 2',
        'aliases' => [],
        'type' => 7,
        'field_count' => 12,
        'address' => 'Khu vực Đầm Hồng, Phương Liệt, Thanh Xuân, giáp Khương Đình, Hoàng Mai, Hà Nội',
        'description' => 'Gồm 12 sân 7 người.',
    ],
    [
        'name' => 'Sân vận động Hoàng Mai',
        'aliases' => [],
        'type' => 7,
        'field_count' => 8,
        'address' => 'Khu Linh Đàm, Hoàng Mai, Hà Nội',
        'description' => 'Gồm 8 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Thành Lâm 3',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => 'Ngõ 3 Tôn Thất Thuyết, Cầu Giấy, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Đại học Quốc gia - Ngoại ngữ',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => 'Cầu Giấy, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Tu Hoàng Di Trạch',
        'aliases' => [],
        'type' => 7,
        'field_count' => 8,
        'address' => 'Tổ 8 Tu Hoàng, Xuân Phương, Nam Từ Liêm, Hà Nội',
        'description' => 'Gồm 8 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng B5 Mai Dịch',
        'aliases' => [],
        'type' => 7,
        'field_count' => 2,
        'address' => 'Phú Diễn, Bắc Từ Liêm, Hà Nội',
        'description' => 'Gồm 2 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Long Biên',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => 'Số 2 phố Phúc Tân, khu vực ven Hồng Hà, Long Biên, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng La Thành - Bồ Đề',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => 'Đường ven đê Phú Viên, Bồ Đề, Long Biên, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng Xuân La',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => '101 đường Xuân La, Tây Hồ, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
    [
        'name' => 'Sân bóng 10-10',
        'aliases' => [],
        'type' => 7,
        'field_count' => 6,
        'address' => 'Đường Trần Huy Liệu, Giảng Võ, Ba Đình, Hà Nội',
        'description' => 'Gồm 6 sân 7 người.',
    ],
];

$pdo->beginTransaction();
try {
    $updated = 0;
    $inserted = 0;

    foreach ($pitches as $pitch) {
        $names = array_values(array_unique(array_merge([$pitch['name']], $pitch['aliases'])));
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $find = $pdo->prepare(
            "SELECT id, name, price_per_hour, status
             FROM pitches WHERE type = ? AND name IN ({$placeholders}) ORDER BY id LIMIT 1"
        );
        $find->execute(array_merge([$pitch['type']], $names));
        $existing = $find->fetch();
        $defaultPrice = $pitch['type'] === 7 ? 480000 : 0;
        $defaultStatus = $pitch['type'] === 7 ? 'active' : 'inactive';

        if ($existing) {
            $isLegacyThuyLoi = in_array($existing['name'], $pitch['aliases'], true);
            $pricePerHour = (float)$existing['price_per_hour'];
            $status = $existing['status'];
            if ($isLegacyThuyLoi || $pricePerHour <= 0) {
                $pricePerHour = $defaultPrice;
                $status = $defaultStatus;
            }
            $update = $pdo->prepare(
                "UPDATE pitches
                 SET name = ?, price_per_hour = ?, description = ?, address = ?,
                     field_count = ?, status = ?
                 WHERE id = ?"
            );
            $update->execute([
                $pitch['name'],
                $pricePerHour,
                $pitch['description'],
                $pitch['address'],
                $pitch['field_count'],
                $status,
                (int)$existing['id'],
            ]);
            $updated++;
            echo "Updated pitch #{$existing['id']}: {$pitch['name']} ({$status}, {$pricePerHour} VND/hour)\n";
            continue;
        }

        $insert = $pdo->prepare(
            "INSERT INTO pitches
                (name, type, price_per_hour, description, address, opening_time, closing_time,
                 field_count, status, created_at)
             VALUES (?, ?, ?, ?, ?, '06:00:00', '23:00:00', ?, ?, NOW())"
        );
        $insert->execute([
            $pitch['name'],
            $pitch['type'],
            $defaultPrice,
            $pitch['description'],
            $pitch['address'],
            $pitch['field_count'],
            $defaultStatus,
        ]);
        $inserted++;
        echo "Added pitch #{$pdo->lastInsertId()}: {$pitch['name']} ({$defaultStatus}, {$defaultPrice} VND/hour)\n";
    }

    $pdo->commit();
    echo "Hanoi pitch seed completed: {$inserted} added, {$updated} updated. Seven-a-side pitches use 480000 VND/hour; 11-a-side pitches stay inactive until priced.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed failed: {$e->getMessage()}\n");
    exit(1);
}