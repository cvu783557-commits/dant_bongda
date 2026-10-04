<?php

namespace App\Controllers;

use App\Controller;
use App\Models\Pitch;
use App\Models\Booking;
use App\Models\Customer;
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

        $result = $this->booking->create([
            'customer_id'    => (int)$customer['id'],
            'pitch_id'       => (int)$pitch['id'],
            'booking_date'   => $bookingDate,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'total_price'    => $total,
            'deposit'        => 0,
            'paid_amount'    => 0,
            'payment_method' => 'cash',
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

        setFlash('success', 'Đặt sân thành công! Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.');
        redirect('bookings/success/' . (int)$result['id']);
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
