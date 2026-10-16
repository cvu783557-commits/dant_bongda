<?php

if (!function_exists('formatMoney')) {
    function formatMoney($amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . ' ₫';
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date): string
    {
        if (!$date) {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($date)->format('d/m/Y');
    }
}

if (!function_exists('file_url')) {
    function file_url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return url('/' . ltrim($path, '/'));
    }
}
