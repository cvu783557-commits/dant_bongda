<?php

namespace App\Controllers;

use App\Controller;
use App\Models\Pitch;
use App\Models\TimeSlot;
use App\Models\Booking;
use Rakit\Validation\Validator;

class PitchController extends Controller
{
    protected $pitch;
    protected $timeSlot;
    protected $booking;
    protected $validator;

    public function __construct()
    {
        $this->pitch     = new Pitch();
        $this->timeSlot  = new TimeSlot();
        $this->booking   = new Booking();
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

    public function show($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch || $pitch['status'] !== 'active') {
            redirect404();
            return;
        }

        $date     = $_GET['date'] ?? date('Y-m-d');
        $today    = date('Y-m-d');
        if ($date < $today) $date = $today;

        $slots        = $this->timeSlot->all();
        $bookedSlotIds = $this->booking->getBookedSlotIds($id, $date);

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('pitches.show', [
            'pitch'         => $pitch,
            'slots'         => $slots,
            'date'          => $date,
            'bookedSlotIds' => $bookedSlotIds,
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
            'pitch_id'      => 'required|integer',
            'customer_name' => 'required|min:2|max:100',
            'customer_phone'=> 'required|regex:/^0[0-9]{9,10}$/',
            'customer_email'=> 'nullable|email',
            'booking_date'  => 'required|date',
            'time_slot_id'  => 'required|integer',
            'notes'         => 'nullable|max:500',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        $pitch = $this->pitch->find($data['pitch_id'] ?? 0);
        if (!$pitch) {
            $errors['pitch_id'] = 'Sân không tồn tại';
        }

        if (empty($errors) && !empty($data['booking_date'])) {
            $today = date('Y-m-d');
            if ($data['booking_date'] < $today) {
                $errors['booking_date'] = 'Ngày đặt không hợp lệ';
            }
        }

        if (empty($errors)) {
            $slotExists = $this->timeSlot->find($data['time_slot_id']);
            if (!$slotExists) {
                $errors['time_slot_id'] = 'Khung giờ không hợp lệ';
            } else {
                if ($this->booking->isSlotTaken($data['pitch_id'], $data['booking_date'], $data['time_slot_id'])) {
                    $errors['time_slot_id'] = 'Khung giờ này đã được đặt, vui lòng chọn giờ khác';
                }
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            unset($_SESSION['old']);
            redirect('pitches/' . ($data['pitch_id'] ?? 0) . '?date=' . ($data['booking_date'] ?? date('Y-m-d')));
            return;
        }

        $totalPrice = (int)$pitch['price_per_hour'];

        $bookingId = $this->booking->create([
            'pitch_id'       => $data['pitch_id'],
            'customer_name'  => trim($data['customer_name']),
            'customer_phone' => trim($data['customer_phone']),
            'customer_email' => trim($data['customer_email'] ?? ''),
            'booking_date'   => $data['booking_date'],
            'time_slot_id'   => $data['time_slot_id'],
            'total_price'    => $totalPrice,
            'status'         => 'pending',
            'notes'          => trim($data['notes'] ?? ''),
        ]);

        unset($_SESSION['old']);
        setFlash('success', 'Đặt sân thành công! Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.');
        redirect('bookings/success/' . $bookingId);
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
