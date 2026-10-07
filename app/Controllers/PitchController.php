<?php

namespace App\Controllers;

use App\Controller;
use App\Models\Pitch;
use App\Models\Booking;
use App\Models\Customer;
use App\Services\Vnpay;
use Rakit\Validation\Validator;

class PitchController extends Controller
{
    protected $pitch;
    protected $booking;
    protected $customer;
    protected $validator;

    public function __construct()
    {
        $this->pitch     = new Pitch();
        $this->booking   = new Booking();
        $this->customer  = new Customer();
        $this->validator = new Validator();
    }

    public function index()
    {
        $pitches = $this->pitch->getAvailable();

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('home', [
            'pitches' => $pitches,
            'success' => $success,
            'error'   => $error,
        ]);
    }

    private function publicTimeSlots()
    {
        $slots = [];
        for ($h = 6; $h <= 22; $h++) {
            $hh = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
            $next = str_pad((string)($h + 1), 2, '0', STR_PAD_LEFT);
            $slots[] = [
                'label'    => "{$hh}:00 - {$next}:00",
                'start'    => "{$hh}:00",
                'end'      => "{$next}:00",
                'start_sec' => $h * 3600,
            ];
        }
        return $slots;
    }

    public function show($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch || $pitch['status'] !== 'active') {
            redirect404();
            return;
        }

        $date  = $_GET['date'] ?? date('Y-m-d');
        $today = date('Y-m-d');
        if ($date < $today) $date = $today;

        $hourSlots = $this->publicTimeSlots();
        $bookings  = $this->booking->getByPitchAndDate($id, $date, false);

        $takenSlots = [];
        foreach ($bookings as $bk) {
            $sH = (int)substr($bk['start_time'], 0, 2);
            $sM = (int)substr($bk['start_time'], 3, 2);
            $eH = (int)substr($bk['end_time'], 0, 2);
            $eM = (int)substr($bk['end_time'], 3, 2);
            $sStart = $sH * 3600 + $sM * 60;
            $sEnd   = $eH * 3600 + $eM * 60;
            foreach ($hourSlots as $idx => $hs) {
                $slotStart = $hs['start_sec'];
                $slotEnd   = $slotStart + 3600;
                if ($slotStart < $sEnd && $slotEnd > $sStart) {
                    $takenSlots[$idx] = $bk;
                }
            }
        }

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('pitches.show', [
            'pitch'         => $pitch,
            'hourSlots'     => $hourSlots,
            'date'          => $date,
            'takenSlots'    => $takenSlots,
            'bookingsToday' => $bookings,
            'minStart'      => '06:00',
            'maxEnd'        => '23:00',
            'success'       => $success,
            'error'         => $error,
            'old'           => $_SESSION['old'] ?? [],
        ]);
    }

    public function bookingStore()
    {
        $data = $_POST;
        keepOld($data);

        try {
            $vnpay = new Vnpay();
        } catch (\RuntimeException $e) {
            setFlash('error', $e->getMessage());
            redirect('pitches/' . ((int)($data['pitch_id'] ?? 0)) . '?date=' . urlencode($data['booking_date'] ?? date('Y-m-d')));
            return;
        }

        $rules = [
            'pitch_id'       => 'required|integer',
            'customer_name'  => 'required|min:2|max:100',
            'customer_phone' => 'required|regex:/^0[0-9]{9,10}$/',
            'customer_email' => 'nullable|email',
            'booking_date'   => 'required|date',
            'start_time'     => 'required|regex:/^\d{2}:\d{2}$/',
            'end_time'       => 'required|regex:/^\d{2}:\d{2}$/',
            'notes'          => 'nullable|max:500',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        $pitch = $this->pitch->find((int)($data['pitch_id'] ?? 0));
        if (!$pitch) {
            $errors['pitch_id'] = 'Sân không tồn tại';
        }

        $bookingDate = $data['booking_date'] ?? '';
        $startTime   = trim($data['start_time'] ?? '') . ':00';
        $endTime     = trim($data['end_time']   ?? '') . ':00';
        if (strlen($startTime) === 5) $startTime .= ':00';
        if (strlen($endTime)   === 5) $endTime   .= ':00';

        if (empty($errors) && $bookingDate) {
            $today = date('Y-m-d');
            if ($bookingDate < $today) {
                $errors['booking_date'] = 'Ngày đặt không được trong quá khứ';
            }
        }

        if (empty($errors)) {
            if ($startTime >= $endTime) {
                $errors['end_time'] = 'Giờ kết thúc phải lớn hơn giờ bắt đầu';
            } else {
                if (!$this->booking->isWithinOperatingHours($startTime, $endTime)) {
                    $errors['start_time'] = 'Giờ đặt nằm ngoài khung hoạt động (06:00 - 23:00)';
                }
            }
        }

        if (empty($errors) && $bookingDate === date('Y-m-d')) {
            $nowSec = time();
            $todayStart = strtotime($bookingDate . ' ' . $startTime);
            if ($todayStart < $nowSec) {
                $errors['start_time'] = 'Không thể đặt khung giờ đã qua trong ngày hôm nay';
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('pitches/' . ((int)($data['pitch_id'] ?? 0)) . '?date=' . ($bookingDate ?: date('Y-m-d')));
            return;
        }

        $customer = $this->customer->findOrCreate(
            trim($data['customer_name']),
            trim($data['customer_phone']),
            isset($data['customer_email']) ? trim($data['customer_email']) : null
        );

        $hours = Booking::calcHoursDiff($startTime, $endTime);
        $total = max(0, round($hours * (float)$pitch['price_per_hour'], 0));
        $deposit = max(1, (int)ceil($total * 0.30));
        if ($deposit < 5000) {
            setFlash('error', 'Tiền đặt cọc qua VNPay phải từ 5.000 ₫ trở lên.');
            redirect('pitches/' . (int)$pitch['id'] . '?date=' . urlencode($bookingDate));
            return;
        }

        $result = $this->booking->create([
            'customer_id'    => (int)$customer['id'],
            'pitch_id'       => (int)$pitch['id'],
            'booking_date'   => $bookingDate,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'total_price'    => $total,
            'deposit'        => $deposit,
            'paid_amount'    => 0,
            'payment_method' => 'vnpay',
            'payment_status' => Booking::PAY_UNPAID,
            'status'         => Booking::STATUS_PENDING,
            'note'           => isset($data['notes']) ? trim($data['notes']) : '',
            'created_by'     => null,
        ]);

        unset($_SESSION['old']);

        if (!$result['success']) {
            setFlash('error', $result['error'] ?? 'Đặt sân không thành công');
            redirect('pitches/' . ((int)$pitch['id']) . '?date=' . $bookingDate);
            return;
        }

        $bookingId = (int)$result['id'];
        try {
            $paymentUrl = $vnpay->createPaymentUrl(
                $bookingId,
                $deposit,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'Dat coc don san #' . $bookingId
            );
        } catch (\Throwable $e) {
            $this->logError('VNPay payment URL error for booking #' . $bookingId . ': ' . $e->getMessage());
            $this->booking->cancel($bookingId, 'Không khởi tạo được thanh toán VNPay');
            setFlash('error', 'Không thể khởi tạo giao dịch VNPay. Vui lòng thử lại hoặc liên hệ quản lý.');
            redirect('pitches/' . (int)$pitch['id'] . '?date=' . urlencode($bookingDate));
            return;
        }

        header('Location: ' . $paymentUrl);
        exit;
    }

    public function vnpayReturn()
    {
        try {
            $vnpay = new Vnpay();
        } catch (\RuntimeException $e) {
            setFlash('error', $e->getMessage());
            redirect('/');
            return;
        }

        if (!$vnpay->verifyCallback($_GET)) {
            setFlash('error', 'Chữ ký phản hồi VNPay không hợp lệ; chưa ghi nhận thanh toán.');
            redirect('/');
            return;
        }

        $transactionRef = (string)($_GET['vnp_TxnRef'] ?? '');
        if (!preg_match('/^([1-9][0-9]*)_[0-9]{14}[0-9]{4}$/', $transactionRef, $matches)) {
            setFlash('error', 'Mã giao dịch VNPay không hợp lệ.');
            redirect('/');
            return;
        }

        $bookingId = (int)$matches[1];
        $booking = $this->booking->find($bookingId);
        $expectedAmount = $booking ? (int)$booking['deposit'] * 100 : 0;
        if (!$booking || (int)($_GET['vnp_Amount'] ?? 0) !== $expectedAmount) {
            setFlash('error', 'Số tiền phản hồi không khớp với tiền đặt cọc; chưa ghi nhận thanh toán.');
            redirect('bookings/success/' . $bookingId);
            return;
        }

        if (($_GET['vnp_ResponseCode'] ?? '') === '00' && ($_GET['vnp_TransactionStatus'] ?? '') === '00') {
            $payment = $this->booking->recordVnpayDeposit($bookingId);
            if (!$payment['success']) {
                setFlash('error', $payment['error']);
            } else {
                setFlash('success', 'Thanh toán tiền đặt cọc VNPay thành công.');
            }
        } else {
            setFlash('error', 'Giao dịch VNPay chưa thành công. Đơn đặt sân đang chờ thanh toán.');
        }

        redirect('bookings/success/' . $bookingId);
    }

    public function vnpayIpn()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $vnpay = new Vnpay();
        } catch (\RuntimeException $e) {
            http_response_code(503);
            echo json_encode(['RspCode' => '99', 'Message' => 'VNPay chưa được cấu hình']);
            return;
        }

        if (!$vnpay->verifyCallback($_GET)) {
            echo json_encode(['RspCode' => '97', 'Message' => 'Invalid signature']);
            return;
        }

        $transactionRef = (string)($_GET['vnp_TxnRef'] ?? '');
        if (!preg_match('/^([1-9][0-9]*)_[0-9]{14}[0-9]{4}$/', $transactionRef, $matches)) {
            echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']);
            return;
        }

        $bookingId = (int)$matches[1];
        $booking = $this->booking->find($bookingId);
        if (!$booking) {
            echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']);
            return;
        }
        if ((int)($_GET['vnp_Amount'] ?? 0) !== (int)$booking['deposit'] * 100) {
            echo json_encode(['RspCode' => '04', 'Message' => 'Invalid amount']);
            return;
        }
        if ($booking['payment_status'] === Booking::PAY_DEPOSIT
            || $booking['payment_status'] === Booking::PAY_PARTIAL
            || $booking['payment_status'] === Booking::PAY_PAID) {
            echo json_encode(['RspCode' => '02', 'Message' => 'Order already confirmed']);
            return;
        }
        if (($_GET['vnp_ResponseCode'] ?? '') !== '00' || ($_GET['vnp_TransactionStatus'] ?? '') !== '00') {
            echo json_encode(['RspCode' => '00', 'Message' => 'Payment not successful']);
            return;
        }

        $payment = $this->booking->recordVnpayDeposit($bookingId);
        if (!$payment['success']) {
            echo json_encode(['RspCode' => '99', 'Message' => 'Could not update payment']);
            return;
        }
        echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    public function bookingSuccess($id)
    {
        $booking = $this->booking->find($id);
        if (!$booking) {
            redirect404();
            return;
        }

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('bookings.success', [
            'booking' => $booking,
            'success' => $success,
            'error'   => $error,
        ]);
    }
}
