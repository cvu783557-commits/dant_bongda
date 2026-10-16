@extends('layouts.admin')
@section('title', 'Hủy lịch ' . bookingCode($booking['id']))
@section('content')

<?php $id = (int)$booking['id']; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">❌ Hủy lịch <?php echo bookingCode($id); ?></h3>
    <a href="<?php echo route('admin/bookings/' . $id); ?>" class="btn btn-outline-secondary">← Quay lại chi tiết</a>
</div>

<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h5 class="fw-bold mb-0">Thông tin lịch đặt</h5></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-4 text-muted">Khách hàng</div>
                    <div class="col-8 fw-semibold"><?php echo e($booking['customer_name']); ?> (<?php echo e($booking['customer_phone']); ?>)</div>
                    <div class="col-4 text-muted">Sân</div>
                    <div class="col-8 fw-semibold"><?php echo e($booking['pitch_name']); ?></div>
                    <div class="col-4 text-muted">Ngày</div>
                    <div class="col-8 fw-semibold"><?php echo formatDate($booking['booking_date']); ?></div>
                    <div class="col-4 text-muted">Giờ</div>
                    <div class="col-8 fw-semibold"><?php echo formatTime($booking['start_time']) . ' → ' . formatTime($booking['end_time']); ?></div>
                    <div class="col-4 text-muted">Tổng tiền</div>
                    <div class="col-8 fw-semibold text-success"><?php echo formatMoney($booking['total_price']); ?></div>
                    <div class="col-4 text-muted">Trạng thái</div>
                    <div class="col-8"><?php echo bookingStatusBadge($booking['status']); ?></div>
                    <div class="col-4 text-muted">Đã thanh toán</div>
                    <div class="col-8 fw-semibold text-info">
                        <?php echo formatMoney($booking['paid_amount']); ?>
                        <?php if ((float)$booking['paid_amount'] > 0): ?>
                            <span class="text-muted small">(Lưu ý xử lý hoàn trả tiền cho khách)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm border-danger border-opacity-25">
            <div class="card-header bg-danger bg-opacity-10 border-bottom border-danger border-opacity-25">
                <h5 class="fw-bold mb-0">Xác nhận hủy lịch</h5>
            </div>
            <div class="card-body">
                <form method="post" action="" onsubmit="return confirm('Bạn có chắc chắn hủy lịch này? Sau khi hủy khung giờ sẽ được giải phóng.');">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lý do hủy <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" rows="5" required class="form-control"
                                  placeholder="Nhập lý do hủy lịch (VD: Khách hàng báo hủy, sân trục trặc kỹ thuật, ...)"></textarea>
                        <div class="form-text">Lý do này được lưu lại để tra cứu sau này.</div>
                    </div>
                    <div class="alert alert-warning small">
                        <strong>⚠ Lưu ý:</strong> Sau khi hủy lịch:<br>
                        • Khung giờ sẽ được giải phóng (khách khác có thể đặt).<br>
                        • Trạng thái chuyển sang <strong>Đã hủy</strong> (không thể hoàn tác, trừ khi ADMIN sửa thủ công).<br>
                        • Nếu khách đã đặt cọc/thanh toán, cần phối hợp hoàn tiền thủ công theo quy định của quầy.
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-3">
                        <a href="<?php echo route('admin/bookings/' . $id); ?>" class="btn btn-outline-secondary px-4">Không, giữ lịch</a>
                        <button type="submit" class="btn btn-danger px-5">❌ Xác nhận hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
