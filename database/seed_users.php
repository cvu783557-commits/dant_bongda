<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Model;

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

class UserSeeder extends Model
{
    public function createUser($username, $password, $fullName, $phone, $role)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT IGNORE INTO users (username, password_hash, full_name, phone, role, status)
                VALUES (?, ?, ?, ?, ?, 'active')";

        $this->connection->executeStatement($sql, [
            $username, $hash, $fullName, $phone, $role
        ]);

        return $hash;
    }

    public function listUsers()
    {
        return $this->connection->executeQuery(
            "SELECT id, username, full_name, phone, role, status, created_at FROM users ORDER BY id ASC"
        )->fetchAllAssociative();
    }
}

echo "===============================================\n";
echo "   Hệ thống Đặt Sân Bóng - Seed Users\n";
echo "===============================================\n\n";

try {
    $seeder = new UserSeeder();

    echo "[1] Tạo user ADMIN...\n";
    $hashAdmin = $seeder->createUser('admin', 'admin123', 'Quản Trị Viên', '0900000000', 'ADMIN');
    echo "    -> username: admin     password: admin123\n";
    echo "    -> hash: " . $hashAdmin . "\n\n";

    echo "[2] Tạo user NHÂN VIÊN...\n";
    $hashStaff = $seeder->createUser('staff', 'staff123', 'Nhân Viên Bán Hàng', '0900000001', 'STAFF');
    echo "    -> username: staff     password: staff123\n";
    echo "    -> hash: " . $hashStaff . "\n\n";

    echo "[3] Danh sách users hiện có:\n";
    $rows = $seeder->listUsers();
    if (count($rows) === 0) {
        echo "    -> (không có user)\n";
    } else {
        foreach ($rows as $r) {
            echo "    - [#{$r['id']}] {$r['username']} | {$r['full_name']} | Role: {$r['role']} | Trạng thái: {$r['status']}\n";
        }
    }

    echo "\n===============================================\n";
    echo "   HOÀN TẤT - Seed users thành công!\n";
    echo "===============================================\n";
} catch (Throwable $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
