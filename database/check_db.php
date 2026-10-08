<?php
/**
 * Diagnostic tool: kiểm tra cấu trúc DB hiện tại
 */
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host   = $_ENV['DB_HOST'];
$port   = $_ENV['DB_PORT'];
$user   = $_ENV['DB_USERNAME'];
$pass   = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "=== KIỂM TRA DATABASE: {$dbname} ===\n\n";

    // 1. List tất cả tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "1. DANH SÁCH BẢNG HIỆN CÓ (" . count($tables) . "):\n";
    if (empty($tables)) {
        echo "   ❌ KHÔNG CÓ BẢNG NÀO\n";
    } else {
        foreach ($tables as $t) {
            $rc = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
            echo "   - {$t}: {$rc} dòng\n";
        }
    }
    echo "\n";

    // 2. Kiểm tra cột của bookings (V1 hay V2?)
    if (in_array('bookings', $tables)) {
        echo "2. CẤU TRÚC BẢNG bookings:\n";
        $cols = $pdo->query("SHOW COLUMNS FROM bookings")->fetchAll();
        foreach ($cols as $c) {
            echo "   - {$c['Field']} ({$c['Type']})  NULL:{$c['Null']}  Key:{$c['Key']}\n";
        }
        $hasV2 = false;
        foreach ($cols as $c) {
            if ($c['Field'] === 'customer_id' || $c['Field'] === 'start_time') { $hasV2 = true; break; }
        }
        echo "   ➜ BOOKINGS VERSION: " . ($hasV2 ? "✅ V2 (time-range, đầy đủ nghiệp vụ)" : "❌ V1 CŨ (time_slot_id, chưa đủ nghiệp vụ)") . "\n";
    } else {
        echo "2. ❌ KHÔNG CÓ BẢNG bookings\n";
    }
    echo "\n";

    // 3. Kiểm tra users
    if (in_array('users', $tables)) {
        echo "3. DANH SÁCH USERS:\n";
        $rows = $pdo->query("SELECT id,username,full_name,role,status FROM users ORDER BY id")->fetchAll();
        if (empty($rows)) echo "   ❌ KHÔNG CÓ USER NÀO (cần chạy seed_users.php)\n";
        foreach ($rows as $r) {
            echo "   - [#{$r['id']}] {$r['username']} / {$r['full_name']}  Role={$r['role']}  Status={$r['status']}\n";
        }
    } else {
        echo "3. ❌ KHÔNG CÓ BẢNG users (cần chạy run_upgrade.php)\n";
    }
    echo "\n";

    // 4. Kiểm tra pitches
    if (in_array('pitches', $tables)) {
        echo "4. DANH SÁCH SÂN:\n";
        $rows = $pdo->query("SELECT id,name,type,price_per_hour,status FROM pitches ORDER BY id")->fetchAll();
        foreach ($rows as $r) {
            $price = number_format((float)$r['price_per_hour'],0,',','.').' ₫';
            echo "   - [#{$r['id']}] {$r['name']} (Sân {$r['type']}N) - {$price}/giờ - {$r['status']}\n";
        }
    } else {
        echo "4. ❌ KHÔNG CÓ BẢNG pitches\n";
    }

    echo "\n=== KẾT LUẬN ===\n";
    if (!in_array('bookings', $tables) || !in_array('users', $tables) || !in_array('customers', $tables)) {
        echo "❌ DB CHƯA NÂNG CẤP LÊN V2 -> Chạy 2 lệnh:\n";
        echo "   php database\\run_upgrade.php\n";
        echo "   php database\\seed_users.php\n";
    } elseif (in_array('bookings', $tables)) {
        $colsTmp = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'customer_id'")->fetch();
        if (!$colsTmp) echo "❌ bookings vẫn là V1 cũ -> chạy run_upgrade.php\n";
        else echo "✅ DB HOÀN TOÀN V2 - có thể truy cập /admin/login để dùng quản lý lịch đặt sân.\n";
    }

} catch (\Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    exit(1);
}
