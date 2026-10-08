<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Pitch;
use App\Models\PitchLock;
use App\Models\TimeSlot;
use App\Models\User;
use Rakit\Validation\Validator;

class BookingController extends Controller
{
    protected $booking;
    protected $customer;
    protected $pitch;
    protected $timeSlot;
    protected $user;
    protected $validator;
    protected $pitchLock;

    public function __construct()
    {
        $this->booking   = new Booking();
        $this->customer  = new Customer();
        $this->pitch     = new Pitch();
        $this->timeSlot  = new TimeSlot();
        $this->user      = new User();
        $this->pitchLock = new PitchLock();
        $this->validator = new Validator();

        if (session_status() === PHP_SESSION_NONE) session_start();

        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        $isPublicCheck = str_contains($uri, '/check-overlap');

        if (!$isPublicCheck && !$this->user->getActiveUser()) {
            setFlash('error', 'Vui lòng đăng nhập để truy cập trang quản lý');
            redirect('admin/login');
        }
    }

    protected function currentUserId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['auth_user_id'] ?? null;
    }

    protected function currentUser()
    {
        return $this->user->getActiveUser();
    }

    /* ============================================================
     *  DASHBOARD - THỐNG KÊ TỔNG QUAN
     * ============================================================ */
    public function dashboard()
    {
        $today    = date('Y-m-d');
        $timeNow  = date('H:i:s');

        $totalToday     = $this->booking->countToday();
        $countPending   = $this->booking->countPending($today);
        $countInProg    = $this->booking->countInProgress($today);
        $countCompleted = $this->booking->countCompleted($today);
        $countCancelled = $this->booking->countCancelled($today);
        $countFreeNow   = $this->booking->countPitchesCurrentlyFree($today, $timeNow, $this->pitch);

        $revenueToday   = $this->booking->todayRevenue();
        $totalActive    = $this->pitch->count();

        $recentBookings = $this->booking->all(['booking_date' => $today]);
        if (count($recentBookings) === 0) {
            $recentBookings = $this->booking->all();
            $recentBookings = array_slice($recentBookings, 0, 10);
        }

        $pitches = $this->pitch->all('active');

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.dashboard', [
            'totalToday'      => $totalToday,
            'countPending'    => $countPending,
            'countInProgress' => $countInProg,
            'countCompleted'  => $countCompleted,
            'countCancelled'  => $countCancelled,
            'countFreeNow'    => $countFreeNow,
            'revenueToday'    => $revenueToday,
            'totalRevenue'    => $revenueToday['total_revenue'],
            'totalBooked'     => $revenueToday['total_booked'],
            'totalActive'     => $totalActive,
            'recentBookings'  => $recentBookings,
            'pitches'         => $pitches,
            'today'           => $today,
            'success'         => $success,
            'error'           => $error,
            'currentUser'     => $this->currentUser(),
        ]);
    }

    /* ============================================================
     *  INDEX - DANH SÁCH LỊCH ĐẶT (VỚI BỘ LỌC VÀ TÌM KIẾM)
     * ============================================================ */
    public function index()
    {
        if (!$this->user->isStaff()) {
            setFlash('error', 'Bạn không có quyền xem danh sách đặt sân');
            redirect('admin/dashboard');
            return;
        }

        $filters = [
            'q'              => trim($_GET['q']               ?? ''),
            'status'         => trim($_GET['status']          ?? ''),
            'payment_status' => trim($_GET['payment_status']  ?? ''),
            'pitch_id'       => trim($_GET['pitch_id']        ?? ''),
            'booking_date'   => trim($_GET['booking_date']    ?? ''),
            'from_date'      => trim($_GET['from_date']       ?? ''),
            'to_date'        => trim($_GET['to_date']         ?? ''),
        ];

        $bookings = $this->booking->all($filters);
        $pitches  = $this->pitch->all();
        $statuses      = Booking::getBookingStatuses();
        $payStatuses   = Booking::getPaymentStatuses();
        $payMethods    = Booking::getPaymentMethods();

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.bookings.index', [
            'bookings'    => $bookings,
            'pitches'     => $pitches,
            'filters'     => $filters,
            'statuses'    => $statuses,
            'payStatuses' => $payStatuses,
            'payMethods'  => $payMethods,
            'success'     => $success,
            'error'       => $error,
            'currentUser' => $this->currentUser(),
            'canDelete'   => $this->user->canDelete(),
            'canEdit'     => $this->user->canEdit(),
            'canCreate'   => $this->user->canCreate(),
            'canCancel'   => $this->user->canCancel(),
            'canConfirm'  => $this->user->canConfirm(),
            'canPay'      => $this->user->canUpdatePayment(),
        ]);
    }

    /* ============================================================
     *  CALENDAR - XEM LỊCH DẠNG BẢNG (SÂN x GIỜ)
     * ============================================================ */
    public function calendar()
    {
        if (!$this->user->isStaff()) {
            setFlash('error', 'Bạn không có quyền xem lịch sân');
            redirect('admin/dashboard');
            return;
        }

        $date      = $_GET['date']      ?? date('Y-m-d');
        $pitchId   = $_GET['pitch_id']  ?? '';

        $today     = date('Y-m-d');
        if ($date < $today && empty($_GET['date'])) $date = $today;

        $pitches   = $this->pitch->all('active');
        $slots     = $this->timeSlot->all();

        $pitchMap  = [];
        foreach ($pitches as $p) {
            if ($pitchId && (int)$pitchId !== (int)$p['id']) continue;
            $pitchMap[(int)$p['id']] = [
                'pitch'   => $p,
                'booking' => $this->booking->getByPitchAndDate((int)$p['id'], $date, false),
                'locks'   => $this->pitchLock->getByPitchAndDate((int)$p['id'], $date),
            ];
        }

        // Mỗi slot sẽ map với booking nào đó overlap nó
        function findBookingAtSlot($slotStart, $slotEnd, $bookings) {
            foreach ($bookings as $b) {
                if ($slotStart < $b['end_time'] && $slotEnd > $b['start_time']) {
                    return $b;
                }
            }
            return null;
        }

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.bookings.calendar', [
            'date'        => $date,
            'pitches'     => $pitches,
            'pitchId'     => $pitchId,
            'slots'       => $slots,
            'pitchMap'    => $pitchMap,
            'findFn'      => null,
            'success'     => $success,
            'error'       => $error,
            'canCreate'   => $this->user->canCreate(),
            'canLock'     => $this->user->canCreate(),
            'currentUser' => $this->currentUser(),
        ]);
    }

    /* ============================================================
     *  THÊM LỊCH ĐẶT (create / store)
     * ============================================================ */
    public function create()
    {
        if (!$this->user->canCreate()) {
            setFlash('error', 'Bạn không có quyền thêm lịch đặt sân');
            redirect('admin/bookings');
            return;
        }

        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.bookings.create', [
            'pitches'      => $this->pitch->all('active'),
            'timeSlots'    => $this->timeSlot->all(),
            'bookingStatuses' => Booking::getBookingStatuses(),
            'paymentStatuses' => Booking::getPaymentStatuses(),
            'paymentMethods'  => Booking::getPaymentMethods(),
            'old'          => $old,
            'error'        => $error,
            'currentUser'  => $this->currentUser(),
        ]);
    }

    public function store()
    {
        if (!$this->user->canCreate()) {
            setFlash('error', 'Bạn không có quyền thêm lịch đặt sân');
            redirect('admin/bookings');
            return;
        }

        $data = $_POST;
        keepOld($data);

        $errors = $this->validateBookingForm($data, 'create');
        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/bookings/create');
            return;
        }

        $customer = $this->customer->findOrCreate(
            trim($data['customer_name']),
            trim($data['customer_phone']),
            $data['customer_email'] ?? ''
        );
        if (!$customer) {
            setFlash('error', 'Không thể tạo/lấy thông tin khách hàng');
            redirect('admin/bookings/create');
            return;
        }

        $startTime = $this->normalizeTime($data['start_time']);
        $endTime   = $this->normalizeTime($data['end_time']);

        $hours = Booking::calcHoursDiff($startTime, $endTime);
        $pitch = $this->pitch->find((int)$data['pitch_id']);
        $pricePerHour = (float)($pitch['price_per_hour'] ?? 0);

        $total = (float)($data['total_price'] ?? 0);
        if ($total <= 0) $total = (float)$pricePerHour * max(1, $hours);

        $deposit = (float)($data['deposit'] ?? 0);
        $paid    = (float)($data['paid_amount'] ?? 0);

        $createData = [
            'customer_id'    => (int)$customer['id'],
            'pitch_id'       => (int)$data['pitch_id'],
            'booking_date'   => $data['booking_date'],
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'total_price'    => $total,
            'deposit'        => $deposit,
            'paid_amount'    => $paid,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'status'         => $data['status'] ?? Booking::STATUS_PENDING,
            'note'           => $data['note'] ?? '',
            'created_by'     => $this->currentUserId(),
        ];

        $result = $this->booking->create($createData);
        if (!$result['success']) {
            setFlash('error', $result['error']);
            redirect('admin/bookings/create');
            return;
        }

        unset($_SESSION['old']);
        setFlash('success', 'Thêm lịch đặt sân thành công! Mã đơn #' . $result['id']);
        redirect('admin/bookings/' . $result['id']);
    }

    /* ============================================================
     *  XEM CHI TIẾT LỊCH ĐẶT
     * ============================================================ */
    public function show($id)
    {
        $booking = $this->booking->find((int)$id);
        if (!$booking) { redirect404(); return; }

        $customer = $this->customer->find((int)$booking['customer_id']);
        $history  = $customer ? $this->customer->bookings((int)$customer['id']) : [];

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.bookings.show', [
            'booking'         => $booking,
            'customer'        => $customer,
            'history'         => array_filter($history, fn($h) => (int)$h['id'] !== (int)$booking['id']),
            'statuses'        => Booking::getBookingStatuses(),
            'payStatuses'     => Booking::getPaymentStatuses(),
            'payMethods'      => Booking::getPaymentMethods(),
            'success'         => $success,
            'error'           => $error,
            'currentUser'     => $this->currentUser(),
            'canEdit'         => $this->user->canEdit(),
            'canCancel'       => $this->user->canCancel(),
            'canDelete'       => $this->user->canDelete(),
            'canConfirm'      => $this->user->canConfirm(),
            'canPay'          => $this->user->canUpdatePayment(),
            'remaining'       => Booking::calcRemaining($booking['total_price'], $booking['paid_amount']),
            'hours'           => Booking::calcHoursDiff($booking['start_time'], $booking['end_time']),
        ]);
    }

    /* ============================================================
     *  SỬA LỊCH ĐẶT
     * ============================================================ */
    public function edit($id)
    {
        if (!$this->user->canEdit()) {
            setFlash('error', 'Bạn không có quyền sửa lịch đặt sân');
            redirect('admin/bookings');
            return;
        }

        $booking = $this->booking->find((int)$id);
        if (!$booking) { redirect404(); return; }

        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.bookings.edit', [
            'booking'         => $booking,
            'pitches'         => $this->pitch->all(),
            'bookingStatuses' => Booking::getBookingStatuses(),
            'paymentStatuses' => Booking::getPaymentStatuses(),
            'paymentMethods'  => Booking::getPaymentMethods(),
            'old'             => $old,
            'error'           => $error,
            'currentUser'     => $this->currentUser(),
            'remaining'       => Booking::calcRemaining($booking['total_price'], $booking['paid_amount']),
            'hours'           => Booking::calcHoursDiff($booking['start_time'], $booking['end_time']),
        ]);
    }

    public function update($id)
    {
        if (!$this->user->canEdit()) {
            setFlash('error', 'Bạn không có quyền sửa lịch đặt sân');
            redirect('admin/bookings');
            return;
        }

        $booking = $this->booking->find((int)$id);
        if (!$booking) { redirect404(); return; }

        $data = $_POST;
        keepOld($data);

        $errors = $this->validateBookingForm($data, 'update', $booking);
        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/bookings/' . $id . '/edit');
            return;
        }

        // Chỉ cập nhật khách hàng khi thay đổi thông tin
        $custId = (int)$booking['customer_id'];
        if (
            !empty($data['customer_name']) ||
            !empty($data['customer_phone'])
        ) {
            $phone = trim($data['customer_phone'] ?? $booking['customer_phone']);
            $name  = trim($data['customer_name']  ?? $booking['customer_name']);
            $email = $data['customer_email'] ?? $booking['customer_email'];
            $newCust = $this->customer->findOrCreate($name, $phone, $email);
            if ($newCust) $custId = (int)$newCust['id'];
        }

        $startTime = $this->normalizeTime($data['start_time']  ?? $booking['start_time']);
        $endTime   = $this->normalizeTime($data['end_time']    ?? $booking['end_time']);

        $update = [
            'customer_id'    => $custId,
            'pitch_id'       => (int)($data['pitch_id'] ?? $booking['pitch_id']),
            'booking_date'   => $data['booking_date'] ?? $booking['booking_date'],
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'total_price'    => (float)($data['total_price'] ?? $booking['total_price']),
            'deposit'        => (float)($data['deposit'] ?? $booking['deposit']),
            'paid_amount'    => (float)($data['paid_amount'] ?? $booking['paid_amount']),
            'payment_method' => $data['payment_method'] ?? $booking['payment_method'],
            'note'           => $data['note'] ?? ($booking['note'] ?? ''),
        ];

        // Trạng thái chỉ cập nhật khi có quyền và hợp lệ
        if (isset($data['status']) && $data['status'] !== $booking['status']) {
            $valid = $this->booking->isValidStatusTransition($booking['status'], $data['status']);
            if (!$valid['ok']) {
                setFlash('error', $valid['message']);
                redirect('admin/bookings/' . $id . '/edit');
                return;
            }
            $update['status'] = $data['status'];
        }

        $result = $this->booking->updateBooking((int)$id, $update);
        if (!$result['success']) {
            setFlash('error', $result['error']);
            redirect('admin/bookings/' . $id . '/edit');
            return;
        }

        unset($_SESSION['old']);
        setFlash('success', 'Cập nhật lịch đặt sân thành công');
        redirect('admin/bookings/' . $id);
    }

    /* ============================================================
     *  HỦY LỊCH ĐẶT (không xóa)
     * ============================================================ */
    public function cancel($id)
    {
        if (!$this->user->canCancel()) {
            setFlash('error', 'Bạn không có quyền hủy lịch đặt sân');
            redirect('admin/bookings');
            return;
        }

        $booking = $this->booking->find((int)$id);
        if (!$booking) { redirect404(); return; }

        if ($booking['status'] === Booking::STATUS_CANCELLED) {
            setFlash('error', 'Lịch này đã bị hủy trước đó');
            redirect('admin/bookings/' . $id);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reason = trim($_POST['cancellation_reason'] ?? '');
            if ($reason === '') {
                setFlash('error', 'Vui lòng nhập lý do hủy lịch');
                redirect('admin/bookings/' . $id . '/cancel');
                return;
            }
            $res = $this->booking->cancel((int)$id, $reason);
            if (!$res['success']) {
                setFlash('error', $res['error']);
                redirect('admin/bookings/' . $id . '/cancel');
                return;
            }
            setFlash('success', 'Lịch đặt #' . $id . ' đã được hủy');
            redirect('admin/bookings/' . $id);
            return;
        }

        $error = getFlash('error');
        return view('admin.bookings.cancel', [
            'booking'     => $booking,
            'error'       => $error,
            'currentUser' => $this->currentUser(),
        ]);
    }

    /* ============================================================
     *  XÓA LỊCH (chỉ ADMIN)
     * ============================================================ */
    public function destroy($id)
    {
        if (!$this->user->canDelete()) {
            setFlash('error', 'Bạn không có quyền xóa lịch đặt sân (chỉ ADMIN)');
            redirect('admin/bookings');
            return;
        }

        $booking = $this->booking->find((int)$id);
        if (!$booking) { redirect404(); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->booking->delete((int)$id);
            setFlash('success', 'Xóa lịch đặt #' . $id . ' thành công');
            redirect('admin/bookings');
            return;
        }

        return view('admin.bookings.delete', [
            'booking'     => $booking,
            'currentUser' => $this->currentUser(),
        ]);
    }

    /* ============================================================
     *  CHUYỂN TRẠNG THÁI NHANH (Xác nhận / Bắt đầu / Hoàn thành)
     * ============================================================ */
    public function confirm($id)
    {
        return $this->quickChangeStatus($id, Booking::STATUS_CONFIRMED, 'Xác nhận lịch thành công');
    }
    public function start($id)
    {
        return $this->quickChangeStatus($id, Booking::STATUS_IN_PROGRESS, 'Đánh dấu đang sử dụng thành công');
    }
    public function complete($id)
    {
        return $this->quickChangeStatus($id, Booking::STATUS_COMPLETED, 'Đánh dấu hoàn thành thành công');
    }

    protected function quickChangeStatus($id, $target, $msg)
    {
        if (!$this->user->canConfirm()) {
            setFlash('error', 'Bạn không có quyền thay đổi trạng thái');
            redirect('admin/bookings');
            return;
        }
        $res = $this->booking->changeStatus((int)$id, $target);
        if (!$res['success']) {
            setFlash('error', $res['error']);
        } else {
            setFlash('success', $msg);
        }
        $ref = $_SERVER['HTTP_REFERER'] ?? ('admin/bookings/' . $id);
        redirect(str_replace(rtrim(base_url(), '/') . '/', '', $ref));
    }

    /* ============================================================
     *  THÊM THANH TOÁN (từ trang chi tiết)
     * ============================================================ */
    public function addPayment($id)
    {
        if (!$this->user->canUpdatePayment()) {
            setFlash('error', 'Bạn không có quyền cập nhật thanh toán');
            redirect('admin/bookings/' . $id);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/bookings/' . $id);
            return;
        }

        $amount = (float)($_POST['payment_amount'] ?? 0);
        $method = $_POST['payment_method'] ?? 'cash';

        if ($amount <= 0) {
            setFlash('error', 'Số tiền thanh toán phải lớn hơn 0');
            redirect('admin/bookings/' . $id);
            return;
        }

        $res = $this->booking->addPayment((int)$id, $amount, $method);
        if (!$res['success']) {
            setFlash('error', $res['error']);
        } else {
            setFlash('success', 'Thanh toán ' . formatMoney($amount) . ' thành công');
        }
        redirect('admin/bookings/' . $id);
    }

    /* ============================================================
     *  AJAX: KIỂM TRA TRÙNG LỊCH (frontend gọi)
     * ============================================================ */
    public function checkOverlap()
    {
        header('Content-Type: application/json; charset=utf-8');

        $pitchId   = (int)($_GET['pitch_id']    ?? $_POST['pitch_id']    ?? 0);
        $date      = trim($_GET['booking_date'] ?? $_POST['booking_date'] ?? '');
        $startTime = $this->normalizeTime($_GET['start_time'] ?? $_POST['start_time'] ?? '00:00');
        $endTime   = $this->normalizeTime($_GET['end_time']   ?? $_POST['end_time']   ?? '00:00');
        $excludeId = (int)($_GET['exclude_id']  ?? $_POST['exclude_id']  ?? 0);

        $errors = [];
        if (!$this->pitch->find($pitchId)) $errors[] = 'Sân không tồn tại';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Ngày không hợp lệ';
        if ($startTime >= $endTime) $errors[] = 'Giờ bắt đầu phải nhỏ hơn giờ kết thúc';
        if (!$this->booking->isWithinOperatingHours($startTime, $endTime)) {
            $errors[] = 'Thời gian đặt ngoài giờ hoạt động (06:00 - 23:00)';
        }
        if (!empty($errors)) {
            echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }

        $lockOverlap = $this->pitchLock->checkOverlap($pitchId, $date, $startTime, $endTime);
        if ($lockOverlap) {
            echo json_encode([
                'ok' => false,
                'overlap' => true,
                'locked'  => true,
                'message' => 'Khung giờ này đã bị KHÓA' . (!empty($lockOverlap['reason']) ? ' (Lý do: ' . $lockOverlap['reason'] . ')' : '') . ' ' . substr($lockOverlap['start_time'],0,5) . '-' . substr($lockOverlap['end_time'],0,5),
                'lock' => $lockOverlap,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $overlap = $this->booking->checkOverlap($pitchId, $date, $startTime, $endTime, $excludeId);
        if ($overlap) {
            echo json_encode([
                'ok' => false,
                'overlap' => true,
                'message' => 'Trùng lịch với đơn #' . $overlap['id'] . ' (Khách ' . ($overlap['customer_name'] ?? '') . ' ' . substr($overlap['start_time'],0,5) . '-' . substr($overlap['end_time'],0,5) . ')',
                'booking' => $overlap,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['ok' => true, 'overlap' => false, 'message' => 'Khung giờ này trống'], JSON_UNESCAPED_UNICODE);
        }
    }

    /* ============================================================
     *  VALIDATE FORM NHẬP (backend, tiếng Việt rõ ràng)
     * ============================================================ */
    protected function validateBookingForm(array $data, $mode = 'create', $existing = null)
    {
        $errors = [];

        $name  = trim($data['customer_name']  ?? '');
        $phone = trim($data['customer_phone'] ?? '');
        if ($name === '' || mb_strlen($name) < 2) $errors['customer_name'] = 'Vui lòng nhập tên khách hàng (ít nhất 2 ký tự)';
        if ($phone === '') $errors['customer_phone'] = 'Vui lòng nhập số điện thoại khách hàng';
        elseif (!preg_match('/^0[0-9]{9,10}$/', $phone)) $errors['customer_phone'] = 'Số điện thoại không hợp lệ (bắt đầu bằng 0, 10-11 số)';

        if (!empty($data['customer_email'])) {
            if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['customer_email'] = 'Email không hợp lệ';
            }
        }

        $pitchId = (int)($data['pitch_id'] ?? 0);
        $pitch   = $this->pitch->find($pitchId);
        if ($pitchId <= 0 || !$pitch) $errors['pitch_id'] = 'Vui lòng chọn sân hợp lệ';
        elseif ($pitch['status'] !== 'active') $errors['pitch_id'] = 'Sân đã ngưng hoạt động';

        $date = trim($data['booking_date'] ?? '');
        if ($date === '') $errors['booking_date'] = 'Vui lòng chọn ngày đặt sân';
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
            $errors['booking_date'] = 'Ngày đặt sân không hợp lệ (định dạng YYYY-MM-DD)';
        } else {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            if ($date <= $yesterday) $errors['booking_date'] = 'Không được đặt lịch trong quá khứ (chỉ được đặt hôm nay hoặc tương lai)';
        }

        $startTime = $this->normalizeTime($data['start_time'] ?? '');
        $endTime   = $this->normalizeTime($data['end_time'] ?? '');
        if ($startTime === '00:00:00' && empty($data['start_time'])) $errors['start_time'] = 'Vui lòng chọn giờ bắt đầu';
        if ($endTime   === '00:00:00' && empty($data['end_time']))   $errors['end_time']   = 'Vui lòng chọn giờ kết thúc';

        if (empty($errors)) {
            if ($startTime >= $endTime) $errors['start_time'] = 'Giờ bắt đầu phải nhỏ hơn giờ kết thúc';
            else if (!$this->booking->isWithinOperatingHours($startTime, $endTime)) {
                $errors['start_time'] = 'Thời gian đặt phải nằm trong giờ hoạt động 06:00 -> 23:00';
            }
        }

        if (empty($errors) && $date === date('Y-m-d')) {
            $now = date('H:i:s');
            if ($startTime < $now) {
                // Soft warning: chấp nhận nhưng thông báo nếu cần
                // Ở đây khóa cứng => không cho đặt giờ đã qua trong ngày
                $errors['start_time'] = 'Không được đặt khung giờ đã qua trong ngày';
            }
        }

        $total   = (float)($data['total_price'] ?? 0);
        $deposit = (float)($data['deposit'] ?? 0);
        $paid    = (float)($data['paid_amount'] ?? 0);
        if ($total < 0) $errors['total_price'] = 'Tổng tiền không được âm';
        if ($deposit < 0) $errors['deposit'] = 'Tiền cọc không được âm';
        if ($paid < 0) $errors['paid_amount'] = 'Số tiền đã thanh toán không được âm';
        if ($deposit > $total + 0.0001) $errors['deposit'] = 'Tiền cọc không được lớn hơn tổng tiền';
        if ($paid > $total + 0.0001) $errors['paid_amount'] = 'Tiền đã thanh toán không được lớn hơn tổng tiền';

        if (!empty($data['payment_method'])) {
            $methods = array_keys(Booking::getPaymentMethods());
            if (!in_array($data['payment_method'], $methods)) $errors['payment_method'] = 'Phương thức thanh toán không hợp lệ';
        }

        if (!empty($data['status']) && $mode === 'create') {
            if (!array_key_exists($data['status'], Booking::getBookingStatuses())) {
                $errors['status'] = 'Trạng thái không hợp lệ';
            }
        }

        // Kiểm tra trùng lịch (đã có transaction lúc create, nhưng validate trước cho UX)
        if (empty($errors) && $pitch) {
            $excludeId = $existing ? (int)$existing['id'] : null;
            $statusCur = $existing['status'] ?? null;
            if ($statusCur !== Booking::STATUS_CANCELLED) {
                $lockOverlap = $this->pitchLock->checkOverlap($pitchId, $date, $startTime, $endTime);
                if ($lockOverlap) {
                    $errors['_overlap'] = 'Khung giờ này đã bị KHÓA'
                        . (!empty($lockOverlap['reason']) ? ' (Lý do: ' . $lockOverlap['reason'] . ')' : '')
                        . ' (' . substr($lockOverlap['start_time'],0,5) . ' đến ' . substr($lockOverlap['end_time'],0,5) . ')';
                }
                if (empty($errors)) {
                    $overlap = $this->booking->checkOverlap($pitchId, $date, $startTime, $endTime, $excludeId);
                    if ($overlap) {
                        $errors['_overlap'] = 'Khung giờ này đã có khách đặt: đơn #' . $overlap['id']
                            . ' - Khách ' . ($overlap['customer_name'] ?? '')
                            . ' (' . substr($overlap['start_time'],0,5) . ' đến ' . substr($overlap['end_time'],0,5) . ')';
                    }
                }
            }
        }

        return $errors;
    }

    /* ============================================================
     *  KHÓA / MỞ KHÓA KHUNG GIỜ (LOCK / UNLOCK TIME SLOT)
     *  - STAFF trở lên được phép
     *  - Form khóa: pitch_id, date, start_time, end_time, reason
     *  - Mở khóa: Xóa record trong pitch_locks
     * ============================================================ */
    public function showLockForm()
    {
        if (!$this->user->canCreate()) {
            setFlash('error', 'Bạn không có quyền khóa khung giờ');
            redirect('admin/bookings/calendar');
            return;
        }

        $pitchId = (int)($_GET['pitch_id'] ?? 0);
        $date    = trim($_GET['date']      ?? date('Y-m-d'));
        $start   = trim($_GET['start_time'] ?? '');
        $end     = trim($_GET['end_time']   ?? '');

        $error = getFlash('error');
        return view('admin.bookings.lock_form', [
            'pitches'     => $this->pitch->all('active'),
            'pitchId'     => $pitchId,
            'date'        => $date,
            'startDefault'=> $start,
            'endDefault'  => $end,
            'error'       => $error,
            'currentUser' => $this->currentUser(),
        ]);
    }

    public function storeLock()
    {
        if (!$this->user->canCreate()) {
            setFlash('error', 'Bạn không có quyền khóa khung giờ');
            redirect('admin/bookings/calendar');
            return;
        }

        $data = $_POST;
        $pitchId   = (int)($data['pitch_id'] ?? 0);
        $date      = trim($data['lock_date'] ?? '');
        $startTime = $this->normalizeTime($data['start_time'] ?? '');
        $endTime   = $this->normalizeTime($data['end_time']   ?? '');
        $reason    = trim($data['reason'] ?? '');

        $errors = [];
        $pitch = $this->pitch->find($pitchId);
        if (!$pitchId || !$pitch) $errors[] = 'Vui lòng chọn sân hợp lệ';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) $errors[] = 'Ngày không hợp lệ';
        else {
            $today = date('Y-m-d');
            if ($date < date('Y-m-d', strtotime('-1 day'))) $errors[] = 'Không khóa khung giờ trong quá khứ';
        }
        if ($startTime === '00:00:00') $errors[] = 'Vui lòng chọn giờ bắt đầu';
        if ($endTime   === '00:00:00') $errors[] = 'Vui lòng chọn giờ kết thúc';
        if ($startTime >= $endTime) $errors[] = 'Giờ bắt đầu phải nhỏ hơn giờ kết thúc';
        if (!$this->booking->isWithinOperatingHours($startTime, $endTime)) {
            $errors[] = 'Thời gian khóa phải nằm trong giờ hoạt động (06:00 - 23:00)';
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/pitch-lock/create?pitch_id=' . $pitchId . '&date=' . $date);
            return;
        }

        $existLock = $this->pitchLock->checkOverlap($pitchId, $date, $startTime, $endTime);
        if ($existLock) {
            setFlash('error', 'Khung giờ này đã khóa rồi (#' . $existLock['id'] . ')');
            redirect('admin/pitch-lock/create?pitch_id=' . $pitchId . '&date=' . $date);
            return;
        }

        $bookingOverlap = $this->booking->checkOverlap($pitchId, $date, $startTime, $endTime);
        if ($bookingOverlap) {
            setFlash('error', 'Không thể khóa: đã có lịch đặt #' . $bookingOverlap['id'] . '. Hãy hủy lịch đặt trước.');
            redirect('admin/pitch-lock/create?pitch_id=' . $pitchId . '&date=' . $date);
            return;
        }

        $res = $this->pitchLock->create([
            'pitch_id'   => $pitchId,
            'lock_date'  => $date,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'reason'     => $reason ?: null,
            'created_by' => $this->currentUserId(),
        ]);

        if (!$res['success']) {
            setFlash('error', $res['error']);
            redirect('admin/pitch-lock/create?pitch_id=' . $pitchId . '&date=' . $date);
            return;
        }

        setFlash('success', 'Khóa khung giờ thành công (#' . $res['id'] . ')');
        redirect('admin/bookings/calendar?date=' . $date . '&pitch_id=' . $pitchId);
    }

    public function unlockSlot($id)
    {
        if (!$this->user->canCreate()) {
            setFlash('error', 'Bạn không có quyền mở khóa khung giờ');
            redirect('admin/bookings/calendar');
            return;
        }

        $lock = $this->pitchLock->find((int)$id);
        if (!$lock) { redirect404(); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->pitchLock->delete((int)$id);
            setFlash('success', 'Đã mở khóa khung giờ #' . $id);
            $ref = $_SERVER['HTTP_REFERER'] ?? ('admin/bookings/calendar?date=' . $lock['lock_date']);
            redirect(str_replace(rtrim(base_url(), '/') . '/', '', $ref));
            return;
        }

        $error = getFlash('error');
        return view('admin.bookings.unlock_confirm', [
            'lock'        => $lock,
            'error'       => $error,
            'currentUser' => $this->currentUser(),
        ]);
    }

    /* ============================================================
     *  HÀM TIỆN ÍCH
     * ============================================================ */
    protected function normalizeTime($t)
    {
        $t = trim($t ?? '');
        if ($t === '') return '00:00:00';
        if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return $t;
        return '00:00:00';
    }
}
