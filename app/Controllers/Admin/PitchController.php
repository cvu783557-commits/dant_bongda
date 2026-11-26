<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Booking;
use App\Models\Pitch;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use Rakit\Validation\Validator;

class PitchController extends Controller
{
    protected $pitch;
    protected $validator;
    protected $user;
    protected $timeSlot;

    public function __construct()
    {
        $this->pitch     = new Pitch();
        $this->validator = new Validator();
        $this->user      = new User();
        $this->timeSlot  = new TimeSlot();

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!$this->user->canAccessAdmin()) {
            if (!$this->user->getActiveUser()) {
                setFlash('error', 'Vui lòng đăng nhập bằng tài khoản ADMIN để truy cập trang quản lý');
                redirect('login');
            }
            setFlash('error', 'Tài khoản USER không có quyền truy cập trang quản trị.');
            redirect('/');
        }
    }

    public function index()
    {
        $pitches = array_values(array_filter(
            $this->pitch->all(),
            static fn ($pitch) => in_array((int)$pitch['type'], [5, 7], true)
        ));

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.pitches.index', [
            'pitches' => $pitches,
            'success' => $success,
            'error'   => $error,
            'user'    => $this->user->getActiveUser(),
            'canDelete' => $this->user->canDelete(),
        ]);
    }

    /**
     * Trang "Sân đang hoạt động" - bảng giám sát vận hành theo thời gian thực.
     * Hiển thị các sân đang hoạt động (status = active) và trạng thái từng sân con
     * ngay tại thời điểm truy cập: Trống hoặc Đang sử dụng (kèm thông tin khách).
     */
    public function operating()
    {
        $date = date('Y-m-d');
        $now  = date('H:i:s');

        // Chỉ lấy các sân đang hoạt động, đồng bộ bộ lọc loại sân với trang Quản lý sân.
        $pitches = array_values(array_filter(
            $this->pitch->all('active'),
            static fn ($pitch) => in_array((int)$pitch['type'], [5, 7], true)
        ));

        $booking = new Booking();
        $service = new Service();

        $board       = [];
        $totalCourts = 0;
        $busyCourts  = 0;

        foreach ($pitches as $pitch) {
            $courtCount = max(1, (int)($pitch['field_count'] ?? 1));
            $rows       = $booking->getByPitchAndDate((int)$pitch['id'], $date, false);

            // Phân loại đơn đặt của từng sân con: đang sử dụng (bao trùm giờ hiện tại) / sắp đá.
            $currentByCourt  = [];
            $expiredByCourt  = [];
            $upcomingByCourt = [];
            foreach ($rows as $b) {
                $court = max(1, (int)($b['court_number'] ?? 1));
                if ($now >= $b['start_time'] && $now < $b['end_time']) {
                    $currentByCourt[$court] = $b;
                } elseif ($b['status'] === Booking::STATUS_IN_PROGRESS && $now >= $b['end_time']) {
                    if (!isset($expiredByCourt[$court]) || $b['end_time'] > $expiredByCourt[$court]['end_time']) {
                        $expiredByCourt[$court] = $b;
                    }
                } elseif ($b['start_time'] > $now) {
                    if (!isset($upcomingByCourt[$court]) || $b['start_time'] < $upcomingByCourt[$court]['start_time']) {
                        $upcomingByCourt[$court] = $b;
                    }
                }
            }

            $courts = [];
            for ($c = 1; $c <= $courtCount; $c++) {
                $isExpired = !isset($currentByCourt[$c]) && isset($expiredByCourt[$c]);
                $courtBooking = $currentByCourt[$c] ?? $expiredByCourt[$c] ?? null;
                $extensionQuote = $courtBooking
                    && $courtBooking['status'] === Booking::STATUS_IN_PROGRESS
                    ? $booking->getExtensionQuote((int)$courtBooking['id'])
                    : null;
                $courts[] = [
                    'number'   => $c,
                    'busy'     => $courtBooking !== null,
                    'expired'  => $isExpired,
                    'booking'  => $courtBooking,
                    'extension_quote' => $extensionQuote,
                    'upcoming' => $upcomingByCourt[$c] ?? null,
                ];
            }

            $busyCount    = count(array_unique(array_merge(array_keys($currentByCourt), array_keys($expiredByCourt))));
            $totalCourts += $courtCount;
            $busyCourts  += $busyCount;

            $board[] = [
                'pitch'       => $pitch,
                'court_count' => $courtCount,
                'courts'      => $courts,
                'busy_count'  => $busyCount,
                'free_count'  => $courtCount - $busyCount,
            ];
        }

        return view('admin.pitches.operating', [
            'board'        => $board,
            'date'         => $date,
            'now'          => $now,
            'totalPitches' => count($board),
            'totalCourts'  => $totalCourts,
            'busyCourts'   => $busyCourts,
            'freeCourts'   => $totalCourts - $busyCourts,
            'availableServices' => $service->getAvailableForBooking(),
            'currentUser'  => $this->user->getActiveUser(),
            'success'      => getFlash('success'),
            'error'        => getFlash('error'),
        ]);
    }

    public function show($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }

        return view('admin.pitches.show', [
            'pitch' => $pitch,
        ]);
    }

    public function create()
    {
        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.pitches.create', [
            'old'   => $old,
            'error' => $error,
            'timePrices' => $this->defaultTimePrices(250000),
        ]);
    }

    private function defaultTimePrices($basePrice)
    {
        $defaults = [
            ['06:00', '08:00', 250000, 300000],
            ['08:00', '12:00', 250000, 300000],
            ['12:00', '17:00', 275000, 325000],
            ['17:00', '20:00', 600000, 600000],
            ['20:00', '23:00', 325000, 375000],
        ];
        $rows = [];
        for ($hour = 6; $hour < 23; $hour++) {
            $start = sprintf('%02d:00', $hour);
            $end = sprintf('%02d:00', $hour + 1);
            $weekday = (int)$basePrice;
            $weekend = (int)$basePrice;
            foreach ($defaults as $block) {
                if ($start >= $block[0] && $start < $block[1]) {
                    $weekday = $block[2];
                    $weekend = $block[3];
                    break;
                }
            }
            $rows[] = [
                'start_time' => $start,
                'end_time' => $end,
                'weekday_price' => $weekday,
                'weekend_price' => $weekend,
            ];
        }
        return $rows;
    }

    private function submittedTimePrices(array $data)
    {
        $prices = [];
        $starts = $data['time_prices']['start'] ?? [];
        $ends = $data['time_prices']['end'] ?? [];
        $weekdays = $data['time_prices']['weekday'] ?? [];
        $weekends = $data['time_prices']['weekend'] ?? [];

        if (!is_array($starts)) return $prices;
        foreach ($starts as $index => $start) {
            $start = trim((string)$start);
            $end = trim((string)($ends[$index] ?? ''));
            if ($start === '' || $end === '') continue;
            $prices[] = [
                'start_time' => $start . ':00',
                'end_time' => $end . ':00',
                'weekday_price' => max(0, (int)($weekdays[$index] ?? 0)),
                'weekend_price' => max(0, (int)($weekends[$index] ?? 0)),
            ];
        }
        return $prices;
    }

    private function validateTimePrices(array $prices, $openingTime = '06:00', $closingTime = '23:00')
    {
        if (!$prices) return 'Vui lòng thêm ít nhất một khung giờ';

        $openingSec = ((int)substr($openingTime, 0, 2) * 3600) + ((int)substr($openingTime, 3, 2) * 60);
        $closingSec = $closingTime === '00:00'
            ? 86400
            : ((int)substr($closingTime, 0, 2) * 3600) + ((int)substr($closingTime, 3, 2) * 60);
        if ($closingSec < $openingSec) $closingSec += 86400;

        foreach ($prices as &$row) {
            $row['_start_sec'] = ((int)substr($row['start_time'], 0, 2) * 3600)
                + ((int)substr($row['start_time'], 3, 2) * 60);
            $row['_end_sec'] = ((int)substr($row['end_time'], 0, 2) * 3600)
                + ((int)substr($row['end_time'], 3, 2) * 60);
            if ($row['_start_sec'] < $openingSec) $row['_start_sec'] += 86400;
            if ($row['_end_sec'] <= $row['_start_sec']) $row['_end_sec'] += 86400;
        }
        unset($row);
        usort($prices, static fn ($a, $b) => $a['_start_sec'] <=> $b['_start_sec']);

        $previousEnd = null;
        foreach ($prices as $row) {
            $start = substr($row['start_time'], 0, 5);
            $end = substr($row['end_time'], 0, 5);
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $start)
                || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $end)) {
                return 'Giờ bắt đầu/kết thúc của khung giờ không hợp lệ';
            }
            if ($row['_start_sec'] < $openingSec || $row['_end_sec'] > $closingSec) {
                return 'Khung giờ phải nằm trong giờ mở cửa và đóng cửa';
            }
            if ($previousEnd !== null && $row['_start_sec'] < $previousEnd) {
                return 'Các khung giờ không được bị chồng lấn';
            }
            $previousEnd = $row['_end_sec'];
        }
        return null;
    }

    private function validatePitchHours(array $data)
    {
        $openingTime = substr((string)($data['opening_time'] ?? ''), 0, 5);
        $closingTime = substr((string)($data['closing_time'] ?? ''), 0, 5);
        $errors = [];

        if (!is_string($openingTime) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $openingTime)) {
            $errors['opening_time'] = 'Giờ mở cửa không hợp lệ';
        }
        if (!is_string($closingTime) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $closingTime)) {
            $errors['closing_time'] = 'Giờ đóng cửa không hợp lệ';
        }

        if (!$errors) {
            $openingAllowsMidnight = $openingTime === '00:00';
            $closingIsMidnight = $closingTime === '00:00';
            if (!$openingAllowsMidnight && $openingTime >= '24:00') {
                $errors['opening_time'] = 'Giờ mở cửa không hợp lệ';
            } elseif ($openingTime < '00:00' || (!$closingIsMidnight && $closingTime < '00:00')) {
                $errors['opening_time'] = 'Giờ hoạt động không hợp lệ';
            }
        }

        return $errors;
    }

    public function store()
    {
        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'           => 'required|min:3|max:100',
            'type'           => 'required|in:5,7,11',
            'price_per_hour' => 'required|numeric|min:0',
            'field_count'    => 'required|integer|min:1|max:50',
            'description'    => 'nullable|max:2000',
            'address'        => 'nullable|max:255',
            'opening_time'   => 'required',
            'closing_time'   => 'required',
            'grass_quality'  => 'nullable|max:100',
            'has_lighting'   => 'required|in:0,1',
            'has_changing_room' => 'required|in:0,1',
            'has_parking'    => 'required|in:0,1',
            'extra_services' => 'nullable|max:1000',
            'status'         => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);
        if (!$errors) {
            $errors = $this->validatePitchHours($data);
        }

        $imagePath = null;
        if (is_upload('image')) {
            try {
                $imagePath = $this->uploadFile($_FILES['image'], 'pitches');
            } catch (\Exception $e) {
                $errors['image'] = 'Lỗi upload ảnh: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/pitches/create');
            return;
        }

        $timePrices = $this->submittedTimePrices($data);
        $timePriceError = $this->validateTimePrices(
            $timePrices,
            substr($data['opening_time'] ?? '06:00', 0, 5),
            substr($data['closing_time'] ?? '23:00', 0, 5)
        );
        if ($timePriceError) {
            setFlash('error', $timePriceError);
            redirect('admin/pitches/create');
            return;
        }

        $id = $this->pitch->create([
            'name'           => trim($data['name']),
            'type'           => (int)$data['type'],
            'price_per_hour' => (int)$data['price_per_hour'],
            'field_count'    => (int)$data['field_count'],
            'description'    => trim($data['description'] ?? ''),
            'image'          => $imagePath,
            'address'        => trim($data['address'] ?? ''),
            'opening_time'   => $data['opening_time'] . ':00',
            'closing_time'   => $data['closing_time'] . ':00',
            'grass_quality'  => trim($data['grass_quality'] ?? ''),
            'has_lighting'   => (int)$data['has_lighting'],
            'has_changing_room' => (int)$data['has_changing_room'],
            'has_parking'    => (int)$data['has_parking'],
            'extra_services' => trim($data['extra_services'] ?? ''),
            'status'         => $data['status'],
        ]);
        $this->timeSlot->replaceForPitch($id, $timePrices);

        unset($_SESSION['old']);
        setFlash('success', 'Thêm sân thành công');
        redirect('admin/pitches');
    }

    public function edit($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }
        if (!in_array((int)$pitch['type'], [5, 7, 11], true)) {
            setFlash('error', 'Sân loại này đã ngừng hoạt động và chỉ được lưu để giữ lịch sử đặt sân.');
            redirect('admin/pitches');
            return;
        }

        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');
        $timePrices = $this->timeSlot->forPitch((int)$id);
        if (!$timePrices) {
            $timePrices = $this->defaultTimePrices((int)$pitch['price_per_hour']);
        }

        return view('admin.pitches.edit', [
            'pitch' => $pitch,
            'old'   => $old,
            'error' => $error,
            'timePrices' => $timePrices,
        ]);
    }

    public function update($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }
        if (!in_array((int)$pitch['type'], [5, 7, 11], true)) {
            setFlash('error', 'Không thể sửa sân loại đã ngừng hoạt động.');
            redirect('admin/pitches');
            return;
        }

        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'           => 'required|min:3|max:100',
            'type'           => 'required|in:5,7,11',
            'price_per_hour' => 'required|numeric|min:0',
            'field_count'    => 'required|integer|min:1|max:50',
            'description'    => 'nullable|max:2000',
            'address'        => 'nullable|max:255',
            'opening_time'   => 'required',
            'closing_time'   => 'required',
            'grass_quality'  => 'nullable|max:100',
            'has_lighting'   => 'required|in:0,1',
            'has_changing_room' => 'required|in:0,1',
            'has_parking'    => 'required|in:0,1',
            'extra_services' => 'nullable|max:1000',
            'status'         => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);
        if (!$errors) {
            $errors = $this->validatePitchHours($data);
        }

        $imagePath = null;
        $changeImage = false;
        if (is_upload('image')) {
            try {
                $imagePath = $this->uploadFile($_FILES['image'], 'pitches');
                $changeImage = true;
            } catch (\Exception $e) {
                $errors['image'] = 'Lỗi upload ảnh: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/pitches/' . $id . '/edit');
            return;
        }

        $updateData = [
            'name'           => trim($data['name']),
            'type'           => (int)$data['type'],
            'price_per_hour' => (int)$data['price_per_hour'],
            'field_count'    => (int)$data['field_count'],
            'description'    => trim($data['description'] ?? ''),
            'address'        => trim($data['address'] ?? ''),
            'opening_time'   => $data['opening_time'] . ':00',
            'closing_time'   => $data['closing_time'] . ':00',
            'grass_quality'  => trim($data['grass_quality'] ?? ''),
            'has_lighting'   => (int)$data['has_lighting'],
            'has_changing_room' => (int)$data['has_changing_room'],
            'has_parking'    => (int)$data['has_parking'],
            'extra_services' => trim($data['extra_services'] ?? ''),
            'status'         => $data['status'],
        ];
        if ($changeImage) {
            $updateData['image'] = $imagePath;
        }

        $timePrices = $this->submittedTimePrices($data);
        $timePriceError = $this->validateTimePrices(
            $timePrices,
            substr($data['opening_time'] ?? '06:00', 0, 5),
            substr($data['closing_time'] ?? '23:00', 0, 5)
        );
        if ($timePriceError) {
            setFlash('error', $timePriceError);
            redirect('admin/pitches/' . $id . '/edit');
            return;
        }
        $this->pitch->update($id, $updateData);
        $this->timeSlot->replaceForPitch($id, $timePrices);

        unset($_SESSION['old']);
        setFlash('success', 'Cập nhật sân thành công');
        redirect('admin/pitches');
    }

    public function toggleStatus($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }
        if (!in_array((int)$pitch['type'], [5, 7], true)) {
            setFlash('error', 'Không thể kích hoạt lại sân loại đã ngừng kinh doanh.');
            redirect('admin/pitches');
            return;
        }

        $newStatus = $pitch['status'] === 'active' ? 'inactive' : 'active';

        $this->pitch->update($id, [
            'name'           => $pitch['name'],
            'type'           => (int)$pitch['type'],
            'price_per_hour' => (int)$pitch['price_per_hour'],
            'description'    => $pitch['description'] ?? '',
            'status'         => $newStatus,
        ]);

        setFlash('success', $newStatus === 'active' ? 'Kích hoạt sân thành công' : 'Tạm dừng sân thành công');
        redirect('admin/pitches');
    }

    public function destroy($id)
    {
        if (!$this->user->canDelete()) {
            setFlash('error', 'Chỉ ADMIN mới có quyền xóa sân.');
            redirect('admin/pitches');
            return;
        }

        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }
        if (!in_array((int)$pitch['type'], [5, 7], true)) {
            setFlash('error', 'Sân đã có loại ngừng kinh doanh được giữ lại để bảo toàn lịch sử đặt sân.');
            redirect('admin/pitches');
            return;
        }

        try {
            $this->pitch->delete($id);
            setFlash('success', 'Xóa sân thành công');
        } catch (\Exception $e) {
            setFlash('error', 'Không thể xóa sân này (đã có đơn đặt)');
        }

        redirect('admin/pitches');
    }
}
