<?php

namespace App\Services;

class Vnpay
{
    private const SANDBOX_URL = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

    private string $tmnCode;
    private string $hashSecret;
    private string $paymentUrl;
    private string $returnUrl;

    public function __construct()
    {
        $this->tmnCode = trim($_ENV['VNPAY_TMN_CODE'] ?? '');
        $this->hashSecret = trim($_ENV['VNPAY_HASH_SECRET'] ?? '');
        $this->paymentUrl = trim($_ENV['VNPAY_URL'] ?? self::SANDBOX_URL);
        $appUrl = rtrim($_ENV['APP_URL'] ?? base_url(), '/');
        $this->returnUrl = trim($_ENV['VNPAY_RETURN_URL'] ?? ($appUrl . '/bookings/vnpay-return'));

        if ($this->tmnCode === '' || $this->hashSecret === '') {
            throw new \RuntimeException('Chưa cấu hình VNPAY_TMN_CODE và VNPAY_HASH_SECRET trong file .env.');
        }
        if (!filter_var($this->paymentUrl, FILTER_VALIDATE_URL) || !filter_var($this->returnUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Cấu hình URL thanh toán VNPay không hợp lệ.');
        }
    }

    public function createPaymentUrl(int $bookingId, int $amount, string $clientIp, string $orderInfo): string
    {
        $input = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => $amount * 100,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $bookingId . '_' . gmdate('YmdHis') . random_int(1000, 9999),
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $this->returnUrl,
            'vnp_IpAddr' => $clientIp,
            'vnp_CreateDate' => date('YmdHis'),
        ];

        $query = $this->buildQuery($input);
        $signature = hash_hmac('sha512', $query, $this->hashSecret);

        return $this->paymentUrl . '?' . $query . '&vnp_SecureHash=' . $signature;
    }

    public function verifyCallback(array $callback): bool
    {
        $signature = strtolower((string)($callback['vnp_SecureHash'] ?? ''));
        if ($signature === '') {
            return false;
        }

        unset($callback['vnp_SecureHash'], $callback['vnp_SecureHashType']);
        $signedFields = [];
        foreach ($callback as $key => $value) {
            if (str_starts_with((string)$key, 'vnp_')) {
                $signedFields[$key] = $value;
            }
        }

        $expected = hash_hmac('sha512', $this->buildQuery($signedFields), $this->hashSecret);
        return hash_equals($expected, $signature);
    }

    private function buildQuery(array $fields): string
    {
        ksort($fields, SORT_STRING);
        return http_build_query($fields, '', '&', PHP_QUERY_RFC1738);
    }
}
