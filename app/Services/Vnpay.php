<?php

namespace App\Services;

class Vnpay
{
    private const SANDBOX_URL = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
    private const SANDBOX_REFUND_URL = 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction';
    private const PRODUCTION_REFUND_URL = 'https://merchant.vnpayment.vn/merchant_webapi/api/transaction';

    private string $tmnCode;
    private string $hashSecret;
    private string $paymentUrl;
    private string $returnUrl;
    private string $refundUrl;

    public function __construct()
    {
        $this->tmnCode = trim($_ENV['VNPAY_TMN_CODE'] ?? '');
        $this->hashSecret = trim($_ENV['VNPAY_HASH_SECRET'] ?? '');
        $this->paymentUrl = trim($_ENV['VNPAY_URL'] ?? self::SANDBOX_URL);
        $defaultRefundUrl = str_contains((string)parse_url($this->paymentUrl, PHP_URL_HOST), 'sandbox')
            ? self::SANDBOX_REFUND_URL
            : self::PRODUCTION_REFUND_URL;
        $this->refundUrl = trim($_ENV['VNPAY_REFUND_URL'] ?? $defaultRefundUrl);
        $appUrl = !empty($_ENV['APP_URL']) ? rtrim($_ENV['APP_URL'], '/') : rtrim(base_url(), '/');
        $returnUrl = trim($_ENV['VNPAY_RETURN_URL'] ?? '');
        $this->returnUrl = $returnUrl !== '' ? $returnUrl : ($appUrl . '/bookings/vnpay-return');

        if ($this->tmnCode === '' || $this->hashSecret === '') {
            throw new \RuntimeException('Chưa cấu hình VNPAY_TMN_CODE và VNPAY_HASH_SECRET trong file .env.');
        }
        if (!filter_var($this->paymentUrl, FILTER_VALIDATE_URL)
            || !filter_var($this->returnUrl, FILTER_VALIDATE_URL)
            || !filter_var($this->refundUrl, FILTER_VALIDATE_URL)
            || parse_url($this->refundUrl, PHP_URL_SCHEME) !== 'https') {
            throw new \RuntimeException('Cấu hình URL thanh toán VNPay không hợp lệ.');
        }
    }

    public function createPaymentUrl(
        int|string $transactionRef,
        int $amount,
        string $clientIp,
        string $orderInfo,
        string $bankCode = ''
    ): string
    {
        return $this->createPaymentRequest(
            $transactionRef,
            $amount,
            $clientIp,
            $orderInfo,
            $bankCode
        )['url'];
    }

    public function createPaymentRequest(
        int|string $transactionRef,
        int $amount,
        string $clientIp,
        string $orderInfo,
        string $bankCode = ''
    ): array {
        $txnRef = $transactionRef . '_' . gmdate('YmdHis') . random_int(1000, 9999);
        $createDate = date('YmdHis');
        $input = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => $amount * 100,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $this->returnUrl,
            'vnp_IpAddr' => $clientIp,
            'vnp_CreateDate' => $createDate,
        ];

        if ($bankCode !== '') {
            if (!in_array($bankCode, ['VNBANK', 'VNPAYQR'], true)) {
                throw new \InvalidArgumentException('Phương thức thanh toán VNPay không hợp lệ.');
            }
            $input['vnp_BankCode'] = $bankCode;
        }

        $query = $this->buildQuery($input);
        $signature = hash_hmac('sha512', $query, $this->hashSecret);

        return [
            'url' => $this->paymentUrl . '?' . $query . '&vnp_SecureHash=' . $signature,
            'txn_ref' => $txnRef,
            'create_date' => $createDate,
        ];
    }

    public function refundDeposit(
        string $transactionRef,
        int $amount,
        string $transactionNo,
        string $transactionDate,
        string $createBy,
        string $orderInfo,
        string $ipAddress,
        bool $partial = false,
        ?string $requestId = null
    ): array {
        if ($amount <= 0 || !ctype_digit($transactionNo)
            || !preg_match('/^\d{14}$/', $transactionDate)) {
            throw new \InvalidArgumentException('Thông tin giao dịch cần hoàn tiền không hợp lệ.');
        }

        $request = [
            'vnp_RequestId' => $requestId ?? (date('YmdHis') . bin2hex(random_bytes(4))),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_TransactionType' => $partial ? '03' : '02',
            'vnp_TxnRef' => $transactionRef,
            'vnp_Amount' => $amount * 100,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_TransactionNo' => $transactionNo,
            'vnp_TransactionDate' => $transactionDate,
            'vnp_CreateBy' => mb_substr($createBy, 0, 100),
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_IpAddr' => $ipAddress,
        ];
        $signatureData = implode('|', [
            $request['vnp_RequestId'],
            $request['vnp_Version'],
            $request['vnp_Command'],
            $request['vnp_TmnCode'],
            $request['vnp_TransactionType'],
            $request['vnp_TxnRef'],
            $request['vnp_Amount'],
            $request['vnp_TransactionNo'],
            $request['vnp_TransactionDate'],
            $request['vnp_CreateBy'],
            $request['vnp_CreateDate'],
            $request['vnp_IpAddr'],
            $request['vnp_OrderInfo'],
        ]);
        $request['vnp_SecureHash'] = hash_hmac('sha512', $signatureData, $this->hashSecret);

        $handle = curl_init($this->refundUrl);
        if ($handle === false) {
            throw new \RuntimeException('Không khởi tạo được kết nối API hoàn tiền VNPay.');
        }
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($request, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $responseBody = curl_exec($handle);
        $curlError = curl_error($handle);
        $httpStatus = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if (!is_string($responseBody) || $httpStatus < 200 || $httpStatus >= 300) {
            $error = $curlError !== '' ? $curlError : 'HTTP ' . $httpStatus;
            throw new \RuntimeException(
                'Không có phản hồi hoàn tiền hợp lệ từ VNPay (' . $error . '); cần kiểm tra giao dịch trước khi thử lại.'
            );
        }

        $response = json_decode($responseBody, true);
        if (!is_array($response)) {
            throw new \RuntimeException('Phản hồi hoàn tiền từ VNPay không đúng định dạng.');
        }
        if (!$this->verifyRefundResponse($response)) {
            throw new \RuntimeException('Chữ ký phản hồi hoàn tiền VNPay không hợp lệ.');
        }
        if ((string)($response['vnp_TmnCode'] ?? '') !== $this->tmnCode
            || (string)($response['vnp_TxnRef'] ?? '') !== $transactionRef
            || (string)($response['vnp_Amount'] ?? '') !== (string)$request['vnp_Amount']
            || (string)($response['vnp_TransactionType'] ?? '') !== $request['vnp_TransactionType']) {
            throw new \RuntimeException('Thông tin phản hồi hoàn tiền không khớp với yêu cầu gửi VNPay.');
        }
        if (($response['vnp_ResponseCode'] ?? '') !== '00') {
            throw new \RuntimeException(
                'VNPay từ chối hoàn tiền: ' . trim((string)($response['vnp_Message'] ?? 'Lỗi không xác định.'))
            );
        }

        return [
            'request_id' => $request['vnp_RequestId'],
            'response_code' => (string)$response['vnp_ResponseCode'],
            'message' => (string)($response['vnp_Message'] ?? 'Hoàn tiền thành công'),
        ];
    }

    private function verifyRefundResponse(array $response): bool
    {
        $signature = strtolower((string)($response['vnp_SecureHash'] ?? ''));
        if ($signature === '') {
            return false;
        }

        $fields = [
            'vnp_ResponseId',
            'vnp_Command',
            'vnp_ResponseCode',
            'vnp_Message',
            'vnp_TmnCode',
            'vnp_TxnRef',
            'vnp_Amount',
            'vnp_BankCode',
            'vnp_PayDate',
            'vnp_TransactionNo',
            'vnp_TransactionType',
            'vnp_TransactionStatus',
            'vnp_OrderInfo',
        ];
        $values = [];
        foreach ($fields as $field) {
            $values[] = (string)($response[$field] ?? '');
        }

        return hash_equals(hash_hmac('sha512', implode('|', $values), $this->hashSecret), $signature);
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
