@extends('layouts.admin')
@section('title', 'Xóa lịch ' . bookingCode($booking['id']))
@section('content')

<?php $id = (int)$booking['id']; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">🗑 Xóa lịch <?php echo bookingCode($id); ?></h3>
    <a href="<?php echo route('admin/bookings/' . $id); ?>" class="btn btn-outline-secondary">← Quay lại</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm border-danger border-opacity-25">
            <div class="card-header bg-danger bg-opacity-10 border-bottom border-danger border-opacity-25">
                <h5 class="fw-bold mb-0">⚠ Cảnh báo xóa vĩnh viễn</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <div class="fw-semibold">Xóa lịch sẽ xóa HOÀN TOÀN dữ liệu khỏi cơ sở dữ liệu.</div>
                    <div class="small">Không thể hoàn tác. Nên dùng tính năng <strong>HỦY LỊCH</strong> thay vì xóa để lưu lịch sử.</div>
                </div>

                <div class="p-3 bg-light rounded border mb-4">
                    <div class="row g-2">
                        <div class="col-4 text-muted">Mã lịch</div>
                        <div class="col-8 fw-semibold"><?php echo bookingCode($id); ?></div>
                        <div class="col-4 text-muted">Khách</div>
                        <div class="col-8 fw-semibold"><?php echo e($booking['customer_name']); ?> (<?php echo e($booking['customer_phone']); ?>)</div>
                        <div class="col-4 text-muted">Sân / Ngày</div>
                        <div class="col-8 fw-semibold"><?php echo e($booking['pitch_name']); ?> • <?php echo formatDate($booking['booking_date']); ?></div>
                        <div class="col-4 text-muted">Giờ</div>
                        <div class="col-8 fw-semibold"><?php echo formatTime($booking['start_time']) . ' → ' . formatTime($booking['end_time']); ?></div>
                        <div class="col-4 text-muted">Trạng thái</div>
                        <div class="col-8"><?php echo bookingStatusBadge($booking['status']); ?></div>
                        <div class="col-4 text-muted">Tổng tiền</div>
                        <div class="col-8 fw-semibold text-success"><?php echo formatMoney($booking['total_price']); ?></div>
                    </div>
                </div>

                <form method="post" action="" onsubmit="return confirm('Bạn CHẮC CHẮN muốn xóa lịch này VĨNH VIỄN? Dữ liệu không thể khôi phục!');">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo route('admin/bookings/' . $id); ?>" class="btn btn-outline-secondary px-4">Không, quay lại</a>
                        <button type="submit" class="btn btn-danger px-5">🗑 Xóa vĩnh viễn</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
