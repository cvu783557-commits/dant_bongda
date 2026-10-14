<?php

namespace App\Controllers;

use App\Controller;
use App\Models\Pitch;
use App\Models\TimeSlot;
use App\Models\Booking;
use App\Models\NewsletterSubscriber;
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

        $slots = $this->timeSlot->all();
        $reservations = $this->booking->getReservationsForDate($id, $date);

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('pitches.show', [
            'pitch'         => $pitch,
            'slots'         => $slots,
            'date'          => $date,
            'reservations'  => $reservations,
            'success'       => $success,
            'error'         => $error,
            'old'           => $_SESSION['old'] ?? [],
        ]);
    }

    public function newsletterSubscribe($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch || $pitch['status'] !== 'active') {
            redirect404();
            return;
        }

        $data = $_POST;
        $rules = [
            'newsletter_email' => 'required|email|max:100',
            'newsletter_consent' => 'required|in:1',
        ];
        $errors = $this->validate($this->validator, $data, $rules);

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            $_SESSION['newsletter_email'] = trim($data['newsletter_email'] ?? '');
            $date = $data['date'] ?? date('Y-m-d');
            redirect('pitches/' . $id . '?date=' . rawurlencode($date) . '#newsletter');
            return;
        }

        $subscriber = new NewsletterSubscriber();
        $subscriber->subscribe(mb_strtolower(trim($data['newsletter_email'])));
        unset($_SESSION['newsletter_email']);
        setFlash('success', 'Đăng ký nhận thông tin ưu đãi thành công.');
        $date = $data['date'] ?? date('Y-m-d');
        redirect('pitches/' . $id . '?date=' . rawurlencode($date) . '#newsletter');
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
            'child_pitch_no'=> 'required|integer|min:1',
            'duration_hours'=> 'required|integer|min:1',
            'notes'         => 'nullable|max:500',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        $pitch = $this->pitch->find($data['pitch_id'] ?? 0);
        if (!$pitch) {
            $errors['pitch_id'] = 'Sân không tồn tại';
        } elseif ($pitch['status'] !== 'active' || (int)($pitch['is_open'] ?? 1) !== 1) {
            $errors['pitch_id'] = 'Sân hiện không nhận đặt chỗ';
        } elseif ((int)($data['child_pitch_no'] ?? 0) > (int)$pitch['field_count']) {
            $errors['child_pitch_no'] = 'Sân con không hợp lệ';
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
                $duration = (int)$data['duration_hours'];
                $startTime = new \DateTimeImmutable($data['booking_date'] . ' ' . $slotExists['start_time']);
                $endTime = $startTime->modify('+' . $duration . ' hours');
                $closingTime = new \DateTimeImmutable($data['booking_date'] . ' ' . $pitch['closing_time']);
                $slots = $this->timeSlot->all();
                $availableEnds = array_column($slots, 'end_time');
                $availableStarts = array_column($slots, 'start_time');
                $expectedEnd = $endTime->format('H:i:s');
                $hasContinuousSlots = true;
                for ($hour = 0; $hour < $duration; $hour++) {
                    if (!in_array($startTime->modify('+' . $hour . ' hours')->format('H:i:s'), $availableStarts, true)) {
                        $hasContinuousSlots = false;
                        break;
                    }
                }

                if ($startTime->format('H:i:s') < $pitch['opening_time']
                    || $endTime > $closingTime
                    || !in_array($expectedEnd, $availableEnds, true)
                    || !$hasContinuousSlots) {
                    $errors['duration_hours'] = 'Thời lượng thuê vượt quá giờ hoạt động của sân';
                } elseif ($data['booking_date'] === date('Y-m-d') && $startTime->format('H:i:s') <= date('H:i:s')) {
                    $errors['time_slot_id'] = 'Không thể đặt khung giờ đã qua';
                } elseif ($this->booking->isPeriodTaken(
                    $data['pitch_id'],
                    $data['booking_date'],
                    $data['time_slot_id'],
                    $duration,
                    (int)$data['child_pitch_no']
                )) {
                    $errors['time_slot_id'] = 'Khung giờ này không còn trống cho thời lượng đã chọn';
                }
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            unset($_SESSION['old']);
            redirect('pitches/' . ($data['pitch_id'] ?? 0) . '?date=' . ($data['booking_date'] ?? date('Y-m-d')));
            return;
        }

        $duration = (int)$data['duration_hours'];
        $totalPrice = (int)$pitch['price_per_hour'] * $duration;

        $bookingId = $this->booking->create([
            'pitch_id'       => $data['pitch_id'],
            'customer_name'  => trim($data['customer_name']),
            'customer_phone' => trim($data['customer_phone']),
            'customer_email' => trim($data['customer_email'] ?? ''),
            'booking_date'   => $data['booking_date'],
            'time_slot_id'   => $data['time_slot_id'],
            'child_pitch_no'=> (int)$data['child_pitch_no'],
            'duration_hours'=> $duration,
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
