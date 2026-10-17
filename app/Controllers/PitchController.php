<?php

namespace App\Controllers;

use App\Controller;
use App\Models\Pitch;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Customer;
use App\Models\PitchLock;
use App\Models\Service;
use App\Models\User;
use App\Services\Vnpay;
use Rakit\Validation\Validator;

class PitchController extends Controller
{
    protected $pitch;
    protected $booking;
    protected $bookingService;
    protected $pitchLock;
    protected $service;
    protected $customer;
    protected $user;
    protected $validator;

    public function __construct()
    {
        $this->pitch     = new Pitch();
        $this->booking   = new Booking();
        $this->bookingService = new BookingService();
        $this->pitchLock = new PitchLock();
        $this->service   = new Service();
        $this->customer  = new Customer();
        $this->user      = new User();
        $this->validator = new Validator();
    }

    public function index()
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        $dateParts = is_string($date) ? explode('-', $date) : [];
        $dateValid = count($dateParts) === 3
            && ctype_digit(implode('', $dateParts))
            && checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])
            && $date === sprintf('%04d-%02d-%02d', (int)$dateParts[0], (int)$dateParts[1], (int)$dateParts[2]);
        $filterError = null;
        if (!$dateValid || $date < date('Y-m-d')) {
            $date = date('Y-m-d');
            $filterError = 'Ngày xem không hợp lệ hoặc đã qua. Đang hiển thị lịch từ hôm nay.';
        }

        $nextHour = (int)date('G') + (date('i') === '00' ? 0 : 1);
        $defaultTime = $nextHour <= 22 ? sprintf('%02d:00', max(6, $nextHour)) : '18:00';
        if ($nextHour > 22 && $date === date('Y-m-d') && !isset($_GET['date'])) {
            $date = date('Y-m-d', strtotime('+1 day'));
        }
        $time = $_GET['time'] ?? $defaultTime;
        if (!is_string($time) || !preg_match('/^(0[6-9]|1[0-9]|2[0-2]):00$/', $time)) {
            $time = '18:00';
            $filterError = 'Vui lòng chọn giờ bắt đầu từ 06:00 đến 22:00.';
        }
        if ($date === date('Y-m-d') && $time . ':00' <= date('H:i:s')) {
            $nextAvailableHour = (int)date('G') + 1;
            if ($nextAvailableHour <= 22) {
                $time = sprintf('%02d:00', max(6, $nextAvailableHour));
            } else {
                $date = date('Y-m-d', strtotime('+1 day'));
                $time = '18:00';
            }
            $filterError = 'Khung giờ đã qua. Đang hiển thị khung giờ sắp tới.';
        }

        $type = $_GET['type'] ?? '';
        if (!is_string($type) || !in_array($type, ['', '5', '7'], true)) {
            $type = '';
        }

        $availableOnly = ($_GET['availability'] ?? '') === 'available';
        $startTime = $time . ':00';
        $endTime = date('H:i:s', strtotime($startTime . ' +1 hour'));
        $pitches = $this->pitch->getPublicListings($date, $startTime, $endTime, $type, $availableOnly);
        foreach ($pitches as &$pitch) {
            $pitch['code'] = 'S' . str_pad((string)$pitch['id'], 3, '0', STR_PAD_LEFT);
        }
        unset($pitch);

        $success = getFlash('success');
        $error   = getFlash('error') ?: $filterError;

        return view('home', [
            'pitches' => $pitches,
            'services' => $this->service->getActive(),
            'success' => $success,
            'error'   => $error,
            'filters' => [
                'date' => $date,
                'time' => $time,
                'type' => (string)$type,
                'availability' => $availableOnly ? 'available' : '',
            ],
            'timeOptions' => range(6, 22),
            'currentUser' => $this->user->getActiveUser(),
        ]);
    }

    private function publicTimeSlots($openingTime = '06:00:00', $closingTime = '23:00:00')
    {
        $slots = [];
        $start = ((int)substr($openingTime, 0, 2) * 3600) + ((int)substr($openingTime, 3, 2) * 60);
        $close = ((int)substr($closingTime, 0, 2) * 3600) + ((int)substr($closingTime, 3, 2) * 60);
        for ($slotStart = $start; $slotStart + 3600 <= $close; $slotStart += 3600) {
            $slotEnd = $slotStart + 3600;
            $slots[] = [
                'label'    => gmdate('H:i', $slotStart) . ' - ' . gmdate('H:i', $slotEnd),
                'start'    => gmdate('H:i', $slotStart),
                'end'      => gmdate('H:i', $slotEnd),
                'start_sec' => $slotStart,
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

        $openingTime = $pitch['opening_time'] ?? '06:00:00';
        $closingTime = $pitch['closing_time'] ?? '23:00:00';
        $hourSlots = $this->publicTimeSlots($openingTime, $closingTime);
        $bookings  = $this->booking->getByPitchAndDate($id, $date, false);
        $locks = $this->pitchLock->getByPitchAndDate($id, $date);

        $takenSlots = [];
        $lockedSlots = [];
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
        foreach ($locks as $lock) {
            $sStart = ((int)substr($lock['start_time'], 0, 2) * 3600)
                + ((int)substr($lock['start_time'], 3, 2) * 60);
            $sEnd = ((int)substr($lock['end_time'], 0, 2) * 3600)
                + ((int)substr($lock['end_time'], 3, 2) * 60);
            foreach ($hourSlots as $idx => $hs) {
                if ($hs['start_sec'] < $sEnd && $hs['start_sec'] + 3600 > $sStart) {
                    $lockedSlots[$idx] = $lock;
                }
            }
        }

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('pitches.show', [
            'pitch'         => $pitch,
            'services'      => $this->service->getAvailableForBooking(),
            'hourSlots'     => $hourSlots,
            'date'          => $date,
            'takenSlots'    => $takenSlots,
            'lockedSlots'   => $lockedSlots,
            'bookingsToday' => $bookings,
            'minStart'      => formatTime($openingTime),
            'maxStart'      => date('H:i', strtotime($closingTime . ' -1 minute')),
            'minEnd'        => date('H:i', strtotime($openingTime . ' +1 minute')),
            'maxEnd'        => formatTime($closingTime),
            'bookingMonth'  => date('Y-m'),
            'success'       => $success,
            'error'         => $error,
            'old'           => $_SESSION['old'] ?? [],
            'currentUser'   => $this->user->getActiveUser(),
        ]);
    }

    /**
     * Trang xem dịch vụ dành cho người dùng.
     * Dữ liệu lấy trực tiếp từ bảng `services` (cùng nguồn với trang quản trị admin)
     * nên mọi thay đổi về tên, giá, đơn vị, tồn kho, ảnh, trạng thái đều được đồng bộ.
     */
    public function services()
    {
        $activeServices = $this->service->getActive();

        // Nhóm dịch vụ theo danh mục, giữ đúng thứ tự và nhãn của Service::CATEGORIES
        $grouped = [];
        foreach (Service::CATEGORIES as $key => $label) {
            $grouped[$key] = [
                'name'     => $label,
                'color'    => Service::getCategoryColor($key),
                'services' => [],
            ];
        }
        foreach ($activeServices as $service) {
            $category = $service['category'] ?? 'other';
            if (!isset($grouped[$category])) {
                $category = 'other';
            }
            $grouped[$category]['services'][] = $service;
        }
        // Chỉ giữ lại những danh mục thực sự có dịch vụ
        $grouped = array_filter($grouped, static fn ($group) => !empty($group['services']));

        return view('services.index', [
            'groupedServices' => $grouped,
            'totalServices'   => count($activeServices),
            'success'         => getFlash('success'),
            'error'           => getFlash('error'),
            'currentUser'     => $this->user->getActiveUser(),
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

        $bookingMode = $data['booking_mode'] ?? 'single';
        $data['booking_mode'] = $bookingMode;
        $rules = [
            'booking_mode'   => 'required|in:single,monthly',
            'pitch_id'       => 'required|integer',
            'customer_name'  => 'required|min:2|max:100',
            'customer_phone' => 'required|regex:/^0[0-9]{9,10}$/',
            'customer_email' => 'nullable|email',
            'start_time'     => 'required|regex:/^\d{2}:\d{2}$/',
            'end_time'       => 'required|regex:/^\d{2}:\d{2}$/',
            'notes'          => 'nullable|max:500',
        ];
        if ($bookingMode === 'monthly') {
            $rules['booking_month'] = 'required|regex:/^\d{4}-\d{2}$/';
        } else {
            $rules['booking_date'] = 'required|date';
        }

        $errors = $this->validate($this->validator, $data, $rules);

        $pitch = $this->pitch->find((int)($data['pitch_id'] ?? 0));
        if (!$pitch || $pitch['status'] !== 'active') {
            $errors['pitch_id'] = 'Sân không tồn tại hoặc hiện không nhận đặt sân';
        }

        $bookingDate = $data['booking_date'] ?? '';
        $startTime   = trim($data['start_time'] ?? '') . ':00';
        $endTime     = trim($data['end_time']   ?? '') . ':00';
        if (strlen($startTime) === 5) $startTime .= ':00';
        if (strlen($endTime)   === 5) $endTime   .= ':00';

        $occurrenceDates = [];
        $bookingMonth = '';

        if (empty($errors) && $bookingMode === 'monthly') {
            $bookingMonth = $data['booking_month'] ?? '';
            [$year, $month] = array_map('intval', explode('-', $bookingMonth));
            if ($year < 1 || !checkdate($month, 1, $year)) {
                $errors['booking_month'] = 'Tháng đặt sân không hợp lệ';
            } elseif ($bookingMonth < date('Y-m')) {
                $errors['booking_month'] = 'Tháng đặt sân không được trong quá khứ';
            } else {
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    if ($date < date('Y-m-d')) {
                        continue;
                    }
                    if ($date === date('Y-m-d')
                        && strtotime($date . ' ' . $startTime) < time()) {
                        continue;
                    }
                    $occurrenceDates[] = $date;
                }
                if (!$occurrenceDates) {
                    $errors['booking_month'] = 'Tháng đã chọn không còn ngày phù hợp để đặt';
                }
                $bookingDate = $occurrenceDates[0] ?? '';
            }
        } elseif (empty($errors) && $bookingDate) {
            $today = date('Y-m-d');
            if ($bookingDate < $today) {
                $errors['booking_date'] = 'Ngày đặt không được trong quá khứ';
            }
        }

        if (empty($errors)) {
            if ($startTime >= $endTime) {
                $errors['end_time'] = 'Giờ kết thúc phải lớn hơn giờ bắt đầu';
            } else {
                $pitchOpening = $pitch['opening_time'] ?? Booking::MIN_OPERATING_HOUR;
                $pitchClosing = $pitch['closing_time'] ?? Booking::MAX_OPERATING_HOUR;
                if (!$this->booking->isWithinOperatingHours($startTime, $endTime)
                    || $startTime < $pitchOpening
                    || $endTime > $pitchClosing) {
                    $errors['start_time'] = 'Giờ đặt nằm ngoài khung hoạt động của sân ('
                        . substr($pitchOpening, 0, 5) . ' - ' . substr($pitchClosing, 0, 5) . ')';
                }
            }
        }

        if (empty($errors) && $bookingMode === 'single' && $bookingDate === date('Y-m-d')) {
            $nowSec = time();
            $todayStart = strtotime($bookingDate . ' ' . $startTime);
            if ($todayStart < $nowSec) {
                $errors['start_time'] = 'Không thể đặt khung giờ đã qua trong ngày hôm nay';
            }
        }

        $serviceItems = [];
        $selectedServiceIds = $data['selected_services'] ?? [];
        $serviceQuantities = $data['service_quantities'] ?? [];
        if (!is_array($selectedServiceIds) || !is_array($serviceQuantities)) {
            $errors['services'] = 'Danh sách dịch vụ không hợp lệ.';
        } else {
            $availableServices = [];
            foreach ($this->service->getAvailableForBooking() as $availableService) {
                $availableServices[(int)$availableService['id']] = $availableService;
            }

            $seenServiceIds = [];
            foreach ($selectedServiceIds as $selectedServiceId) {
                if (!is_scalar($selectedServiceId) || !ctype_digit((string)$selectedServiceId)) {
                    $errors['services'] = 'Dịch vụ được chọn không hợp lệ.';
                    break;
                }
                $serviceId = (int)$selectedServiceId;
                if (isset($seenServiceIds[$serviceId])) {
                    $errors['services'] = 'Dịch vụ bị chọn lặp lại.';
                    break;
                }
                $seenServiceIds[$serviceId] = true;

                if (!isset($availableServices[$serviceId])) {
                    $errors['services'] = 'Một dịch vụ vừa hết hàng hoặc ngừng kinh doanh. Vui lòng tải lại trang.';
                    break;
                }

                $quantityInput = $serviceQuantities[$serviceId] ?? null;
                if (!is_scalar($quantityInput) || !preg_match('/^[1-9]\d*$/', (string)$quantityInput)) {
                    $errors['services'] = 'Số lượng dịch vụ được chọn không hợp lệ.';
                    break;
                }

                $quantity = (int)$quantityInput;
                $occurrenceCount = $bookingMode === 'monthly' ? count($occurrenceDates) : 1;
                if ($quantity * $occurrenceCount > (int)$availableServices[$serviceId]['stock']) {
                    $errors['services'] = 'Dịch vụ "' . $availableServices[$serviceId]['name']
                        . '" không đủ số lượng cho ' . $occurrenceCount . ' buổi.';
                    break;
                }

                $serviceItems[] = [
                    'service_id' => $serviceId,
                    'quantity' => $quantity,
                    'unit_price' => (float)$availableServices[$serviceId]['price'],
                ];
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('pitches/' . ((int)($data['pitch_id'] ?? 0)) . '?date='
                . urlencode($bookingDate ?: date('Y-m-d')));
            return;
        }

        $customer = $this->customer->findOrCreate(
            trim($data['customer_name']),
            trim($data['customer_phone']),
            isset($data['customer_email']) ? trim($data['customer_email']) : null
        );

        $hours = Booking::calcHoursDiff($startTime, $endTime);
        $serviceTotalPerSession = array_sum(array_map(
            static fn ($item) => $item['quantity'] * $item['unit_price'],
            $serviceItems
        ));
        $sessionTotal = max(0, round($hours * (float)$pitch['price_per_hour'], 0))
            + $serviceTotalPerSession;
        $sessionDeposit = max(1, (int)ceil($sessionTotal * 0.30));
        $total = $bookingMode === 'monthly' ? $sessionTotal * count($occurrenceDates) : $sessionTotal;
        $deposit = $bookingMode === 'monthly'
            ? max(1, (int)ceil($total * 0.30))
            : $sessionDeposit;
        if ($deposit < 5000) {
            setFlash('error', 'Tiền đặt cọc qua VNPay phải từ 5.000 ₫ trở lên.');
            redirect('pitches/' . (int)$pitch['id'] . '?date=' . urlencode($bookingDate));
            return;
        }

        if ($bookingMode === 'monthly') {
            $occurrences = [];
            $depositPerOccurrence = intdiv($deposit, count($occurrenceDates));
            $remainingDeposit = $deposit % count($occurrenceDates);
            foreach ($occurrenceDates as $index => $date) {
                $occurrences[] = [
                    'booking_date' => $date,
                    'total_price' => $sessionTotal,
                    'deposit' => $depositPerOccurrence + ($index < $remainingDeposit ? 1 : 0),
                ];
            }
            $result = $this->booking->createMonthlySeries([
                'customer_id' => (int)$customer['id'],
                'pitch_id' => (int)$pitch['id'],
                'booking_month' => $bookingMonth . '-01',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_price' => $total,
                'deposit' => $deposit,
                'note' => isset($data['notes']) ? trim($data['notes']) : '',
                'services' => $serviceItems,
            ], $occurrences);
        } else {
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
                'services'       => $serviceItems,
            ]);
        }

        unset($_SESSION['old']);

        if (!$result['success']) {
            setFlash('error', $result['error'] ?? 'Đặt sân không thành công');
            redirect('pitches/' . ((int)$pitch['id']) . '?date=' . $bookingDate);
            return;
        }

        $bookingId = $bookingMode === 'monthly'
            ? (int)$result['first_booking_id']
            : (int)$result['id'];
        $paymentReference = $bookingMode === 'monthly'
            ? 'M' . (int)$result['series_id']
            : (string)$bookingId;
        try {
            $paymentUrl = $vnpay->createPaymentUrl(
                $paymentReference,
                $deposit,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $bookingMode === 'monthly'
                    ? 'Dat coc lich san thang #' . (int)$result['series_id']
                    : 'Dat coc don san #' . $bookingId
            );
        } catch (\Throwable $e) {
            $this->logError('VNPay payment URL error for booking #' . $bookingId . ': ' . $e->getMessage());
            if ($bookingMode === 'monthly') {
                foreach ($result['booking_ids'] as $seriesBookingId) {
                    $this->booking->cancel($seriesBookingId, 'Không khởi tạo được thanh toán VNPay');
                }
            } else {
                $this->booking->cancel($bookingId, 'Không khởi tạo được thanh toán VNPay');
            }
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
        if (!preg_match('/^(M)?([1-9][0-9]*)_[0-9]{14}[0-9]{4}$/', $transactionRef, $matches)) {
            setFlash('error', 'Mã giao dịch VNPay không hợp lệ.');
            redirect('/');
            return;
        }

        $isMonthly = ($matches[1] ?? '') === 'M';
        $referenceId = (int)$matches[2];
        $series = $isMonthly ? $this->booking->findSeries($referenceId) : null;
        $bookingId = $isMonthly
            ? ($series ? $this->booking->getSeriesFirstBookingId($referenceId) : null)
            : $referenceId;
        $booking = $bookingId ? $this->booking->find($bookingId) : null;
        $expectedAmount = $isMonthly
            ? ($series ? (int)$series['deposit'] * 100 : 0)
            : ($booking ? (int)$booking['deposit'] * 100 : 0);
        if (!$booking || ($isMonthly && !$series)
            || (int)($_GET['vnp_Amount'] ?? 0) !== $expectedAmount) {
            setFlash('error', 'Số tiền phản hồi không khớp với tiền đặt cọc; chưa ghi nhận thanh toán.');
            if ($bookingId) {
                redirect('bookings/success/' . $bookingId);
            } else {
                redirect('/');
            }
            return;
        }

        if (($_GET['vnp_ResponseCode'] ?? '') === '00' && ($_GET['vnp_TransactionStatus'] ?? '') === '00') {
            $payment = $isMonthly
                ? $this->booking->recordSeriesVnpayDeposit($referenceId)
                : $this->booking->recordVnpayDeposit($bookingId);
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
        if (!preg_match('/^(M)?([1-9][0-9]*)_[0-9]{14}[0-9]{4}$/', $transactionRef, $matches)) {
            echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']);
            return;
        }

        $isMonthly = ($matches[1] ?? '') === 'M';
        $referenceId = (int)$matches[2];
        $series = $isMonthly ? $this->booking->findSeries($referenceId) : null;
        $bookingId = $isMonthly
            ? ($series ? $this->booking->getSeriesFirstBookingId($referenceId) : null)
            : $referenceId;
        $booking = $bookingId ? $this->booking->find($bookingId) : null;
        if (!$booking) {
            echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']);
            return;
        }
        $expectedAmount = $isMonthly
            ? (int)($series['deposit'] ?? 0) * 100
            : (int)$booking['deposit'] * 100;
        if (($isMonthly && !$series) || (int)($_GET['vnp_Amount'] ?? 0) !== $expectedAmount) {
            echo json_encode(['RspCode' => '04', 'Message' => 'Invalid amount']);
            return;
        }
        $paymentStatus = $isMonthly ? $series['payment_status'] : $booking['payment_status'];
        if ($paymentStatus === Booking::PAY_DEPOSIT
            || $paymentStatus === Booking::PAY_PARTIAL
            || $paymentStatus === Booking::PAY_PAID) {
            echo json_encode(['RspCode' => '02', 'Message' => 'Order already confirmed']);
            return;
        }
        if (($_GET['vnp_ResponseCode'] ?? '') !== '00' || ($_GET['vnp_TransactionStatus'] ?? '') !== '00') {
            echo json_encode(['RspCode' => '00', 'Message' => 'Payment not successful']);
            return;
        }

        $payment = $isMonthly
            ? $this->booking->recordSeriesVnpayDeposit($referenceId)
            : $this->booking->recordVnpayDeposit($bookingId);
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
        $series = !empty($booking['series_id'])
            ? $this->booking->findSeries((int)$booking['series_id'])
            : null;

        return view('bookings.success', [
            'booking' => $booking,
            'bookingServices' => $this->bookingService->allByBooking((int)$booking['id']),
            'series' => $series,
            'success' => $success,
            'error'   => $error,
        ]);
    }
}
