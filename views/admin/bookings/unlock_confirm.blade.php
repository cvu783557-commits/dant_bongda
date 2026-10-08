@extends('layouts.admin')
@section('title', 'Mở khóa khung giờ')
@section('content')

<?php
$lockDate = $lock['lock_date'];
$lockPitch = $lock['pitch_name'] ?? 'Sân #' . $lock['pitch_id'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">🔓 Xác nhận mở khóa</h3>
    <a href="<?php echo route('admin/bookings/calendar') . '?date=' . $lockDate; ?>" class="btn btn-outline-secondary">← Quay lại lịch</a>
</div>

<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="card border-0 shadow-sm border-warning">
    <div class="card-header bg-warning bg-opacity-10 border-warning fw-semibold text-warning-emphasis">
        ⚠ Bạn sắp mở khóa khung giờ sau:
    </div>
    <div class="card-body p-4">
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted">Mã khóa</div>
                <div class="fw-bold text-warning">#<?php echo str_pad((string)$lock['id'], 6, '0', STR_PAD_LEFT); ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted">Sân bị khóa</div>
                <div class="fw-semibold"><?php echo e($lockPitch); ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted">Ngày khóa</div>
                <div class="fw-semibold"><?php echo formatDate($lockDate); ?></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="small text-muted">Khung giờ</div>
                <div class="fw-semibold"><?php echo formatTime($lock['start_time']); ?> → <?php echo formatTime($lock['end_time']); ?></div>
            </div>
            <div class="col-12">
                <div class="small text-muted">Lý do khóa</div>
                <div class="fw-semibold text-muted"><?php echo !empty($lock['reason']) ? e($lock['reason']) : '<span class="fst-italic">(Không có ghi chú)</span>'; ?></div>
            </div>
            <?php if (!empty($lock['creator_name'])): ?>
            <div class="col-sm-6">
                <div class="small text-muted">Người khóa lúc</div>
                <div class="fw-semibold"><?php echo e($lock['creator_name']); ?> • <?php echo formatDateTime($lock['created_at']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="mb-4 p-3 bg-danger bg-opacity-10 border border-danger rounded">
            <div class="fw-semibold text-danger mb-1">🔓 Hiệu lực khi mở khóa:</div>
            <ul class="small text-muted mb-0">
                <li>Khách hàng và nhân viên <strong>có thể đặt lịch</strong> vào khung giờ này ngay lập tức.</li>
                <li>Nếu cần bảo trì lại, bạn phải tạo khóa mới.</li>
            </ul>
        </div>

        <form method="post" action="<?php echo route('admin/pitch-lock/' . (int)$lock['id'] . '/unlock'); ?>">
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?php echo route('admin/bookings/calendar') . '?date=' . $lockDate; ?>" class="btn btn-outline-secondary px-4">Quay lại</a>
                <button type="submit" class="btn btn-danger px-5 fw-semibold">🔓 Xác nhận mở khóa</button>
            </div>
        </form>
    </div>
</div>
@endsection
