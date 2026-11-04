@extends('layouts.admin')
@section('title', 'Chi tiết check-in')
@section('content')

<?php
$bookingId = (int)$booking['id'];
$bookingCodeValue = 'BK' . str_pad((string)$bookingId, 6, '0', STR_PAD_LEFT);
$remaining = max(0, (float)$booking['total_price'] - (float)$booking['paid_amount']);
$checkInStatus = match ($booking['status']) {
    \App\Models\Booking::STATUS_CONFIRMED => ['Chờ check-in', 'warning'],
    \App\Models\Booking::STATUS_IN_PROGRESS => ['Đã check-in', 'success'],
    default => [\App\Models\Booking::getBookingStatuses()[$booking['status']] ?? $booking['status'], 'secondary'],
};
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">Chi tiết check-in</h3>
        <p class="text-muted mb-0">Đối chiếu thông tin khách và đơn trước khi xác nhận khách đã đến.</p>
    </div>
    <a href="<?php echo route('admin/check-in'); ?>" class="btn btn-outline-secondary">← Quay lại check-in</a>
</div>

<?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
            <h4 class="h5 text-uppercase fw-bold mb-0">Chi tiết check-in</h4>
            <span class="badge rounded-pill text-bg-<?php echo e($checkInStatus[1]); ?> fs-6">
                <?php echo e($checkInStatus[0]); ?>
            </span>
        </div>

        <h5 class="fw-semibold mb-3">Thông tin khách hàng</h5>
        <div class="table-responsive mb-4">
            <table class="table align-middle">
                <thead>
                    <tr><th scope="col" style="width:48%">Trường</th><th scope="col">Thông tin</th></tr>
                </thead>
                <tbody>
                    <tr><td>Mã đặt sân</td><td class="fw-semibold"><?php echo e($bookingCodeValue); ?></td></tr>
                    <tr><td>Khách hàng</td><td><?php echo e($booking['customer_name'] ?? ''); ?></td></tr>
                    <tr>
                        <td>Số điện thoại</td>
                        <td><a href="tel:<?php echo e($booking['customer_phone'] ?? ''); ?>"><?php echo e($booking['customer_phone'] ?? ''); ?></a></td>
                    </tr>
                    <tr>
                        <td>Sân</td>
                        <td><?php echo e($booking['pitch_name'] ?? ''); ?> · Sân số <?php echo str_pad((string)($booking['court_number'] ?? 1), 2, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr><td>Ngày đặt</td><td><?php echo e(formatDate($booking['booking_date'])); ?></td></tr>
                    <tr>
                        <td>Giờ chơi</td>
                        <td><?php echo e(formatTime($booking['start_time'])); ?> – <?php echo e(formatTime($booking['end_time'])); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h5 class="fw-semibold mb-3">Thông tin thanh toán</h5>
        <div class="table-responsive mb-4">
            <table class="table align-middle">
                <thead>
                    <tr><th scope="col" style="width:48%">Trường</th><th scope="col" class="text-end">Thông tin</th></tr>
                </thead>
                <tbody>
                    <tr><td>Tổng tiền</td><td class="text-end"><?php echo e(formatMoney($booking['total_price'])); ?></td></tr>
                    <tr><td>Đã thanh toán</td><td class="text-end text-success"><?php echo e(formatMoney($booking['paid_amount'])); ?></td></tr>
                    <tr><td>Còn lại</td><td class="text-end fw-semibold"><?php echo e(formatMoney($remaining)); ?></td></tr>
                </tbody>
            </table>
        </div>

        <h5 class="fw-semibold mb-3">Thông tin check-in</h5>
        <ul class="mb-4">
            <li class="mb-2">
                Thời gian check-in:
                <strong><?php echo !empty($booking['checked_in_at']) ? e(formatDateTime($booking['checked_in_at'])) : 'Chưa xác nhận'; ?></strong>
            </li>
            <li class="mb-2">
                Nhân viên thực hiện:
                <strong><?php echo e($booking['checkin_staff_name'] ?? 'Chưa có'); ?></strong>
            </li>
            <li>
                Ghi chú:
                <strong><?php echo !empty($booking['checkin_note']) ? e($booking['checkin_note']) : 'Chưa có'; ?></strong>
            </li>
        </ul>

        <?php if ($canCheckIn): ?>
            <hr class="mb-4">
            <div class="mb-3">
                <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis">
                    Có thể xác nhận khi đã đối chiếu thông tin
                </span>
            </div>
            <form method="post" action="<?php echo route('admin/check-in/' . $bookingId . '/confirm'); ?>">
                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                <label for="checkinNote" class="form-label fw-semibold">Ghi chú check-in (không bắt buộc)</label>
                <textarea id="checkinNote" name="checkin_note" class="form-control mb-3" rows="3"
                          maxlength="500" placeholder="Ví dụ: Người đến sân check-in thay người đặt"></textarea>
                <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold"
                        onclick="return confirm('Xác nhận khách của đơn <?php echo e($bookingCodeValue); ?> đã đến sân?');">
                    Xác nhận Check-in
                </button>
            </form>
        <?php elseif ($booking['status'] === \App\Models\Booking::STATUS_IN_PROGRESS): ?>
            <div class="alert alert-success mb-0">Đơn này đã được check-in.</div>
        <?php elseif ($booking['booking_date'] !== date('Y-m-d')): ?>
            <div class="alert alert-warning mb-0">Chỉ check-in được đơn đặt sân trong ngày hôm nay.</div>
        <?php else: ?>
            <div class="alert alert-secondary mb-0">Đơn cần được xác nhận trước khi check-in.</div>
        <?php endif; ?>
    </div>
</div>
@endsection
