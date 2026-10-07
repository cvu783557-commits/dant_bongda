<?php

use eftec\bladeone\BladeOne;
use App\Models\Booking;

if (!function_exists('view')) {
    function view($view, $data = [])
    {
        $views = __DIR__ . '/views';
        $cache = __DIR__ . '/storage/compiles';

        $blade = new BladeOne($views, $cache, BladeOne::MODE_DEBUG);

        echo $blade->run($view, $data);
    }
}

if (!function_exists('is_upload')) {
    function is_upload($key)
    {
        return isset($_FILES[$key]) && $_FILES[$key]['size'] > 0;
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $envBase = $_ENV['APP_URL'] ?? null;
        $httpHost = $_SERVER['HTTP_HOST'] ?? null;

        if ($httpHost) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443
                ? 'https'
                : 'http';
            return $scheme . '://' . $httpHost;
        }

        if ($envBase) {
            return rtrim($envBase, '/');
        }

        return 'http://localhost';
    }
}

if (!function_exists('redirect')) {
    function redirect($path)
    {
        header('Location: ' . rtrim(base_url(), '/') . '/' . ltrim($path, '/'));
        exit;
    }
}

if (!function_exists('redirect404')) {
    function redirect404()
    {
        header('HTTP/1.1 404 Not Found');
        echo '<h1>404 - Not Found</h1><p>Trang bạn tìm không tồn tại.</p><a href="'.rtrim(base_url(),'/').'/admin/bookings">Quay lại quản lý đặt sân</a>';
        exit;
    }
}

if (!function_exists('file_url')) {
    function file_url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('debug')) {
    function debug(...$data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }
}

if (!function_exists('route')) {
    function route($path)
    {
        return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('setFlash')) {
    function setFlash($key, $message)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('getFlash')) {
    function getFlash($key = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if ($key === null) {
            $all = $_SESSION['flash'] ?? [];
            unset($_SESSION['flash']);
            return $all;
        }

        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }

        return null;
    }
}

if (!function_exists('old')) {
    function old($key, $default = '')
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('keepOld')) {
    function keepOld($data)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['old'] = $data;
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney($num)
    {
        return number_format((float)$num, 0, ',', '.') . ' ₫';
    }
}

if (!function_exists('formatDate')) {
    function formatDate($sqlDate)
    {
        if (!$sqlDate) return '';
        $ts = strtotime($sqlDate);
        if (!$ts) return htmlspecialchars($sqlDate);
        return date('d/m/Y', $ts);
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($sqlDateTime)
    {
        if (!$sqlDateTime) return '';
        $ts = strtotime($sqlDateTime);
        if (!$ts) return htmlspecialchars($sqlDateTime);
        return date('d/m/Y H:i', $ts);
    }
}

if (!function_exists('formatTime')) {
    function formatTime($sqlTime)
    {
        if (!$sqlTime) return '';
        return substr($sqlTime, 0, 5);
    }
}

if (!function_exists('bookingStatusBadge')) {
    function bookingStatusBadge($status)
    {
        $labels = Booking::getBookingStatuses();
        $color  = Booking::getBookingStatusColor($status);
        $label  = $labels[$status] ?? $status;

        $map = [
            'primary'   => 'bg-primary-subtle text-primary border-primary-subtle',
            'secondary' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
            'success'   => 'bg-success-subtle text-success border-success-subtle',
            'danger'    => 'bg-danger-subtle text-danger border-danger-subtle',
            'warning'   => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
            'info'      => 'bg-info-subtle text-info-emphasis border-info-subtle',
        ];
        $cls = $map[$color] ?? $map['secondary'];
        return '<span class="badge border '.$cls.'">'.htmlspecialchars($label).'</span>';
    }
}

if (!function_exists('paymentStatusBadge')) {
    function paymentStatusBadge($status)
    {
        $labels = Booking::getPaymentStatuses();
        $color  = Booking::getPaymentStatusColor($status);
        $label  = $labels[$status] ?? $status;

        $map = [
            'primary'   => 'bg-primary-subtle text-primary border-primary-subtle',
            'secondary' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
            'success'   => 'bg-success-subtle text-success border-success-subtle',
            'danger'    => 'bg-danger-subtle text-danger border-danger-subtle',
            'warning'   => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
            'info'      => 'bg-info-subtle text-info-emphasis border-info-subtle',
        ];
        $cls = $map[$color] ?? $map['secondary'];
        return '<span class="badge border '.$cls.'">'.htmlspecialchars($label).'</span>';
    }
}

if (!function_exists('e')) {
    function e($str)
    {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('bookingCode')) {
    function bookingCode($id)
    {
        return '#' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    }
}
