@extends('layouts.admin')
@section('title', 'Cập nhật đặt sân')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Cập nhật đặt sân #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></h3>
    <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-secondary">← Danh sách</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Thông tin đơn đặt</h5>

                <div class="mb-3">
                    <div class="text-muted small">Sân</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($booking['pitch_name']); ?> (Sân <?php echo (int)$booking['pitch_type']; ?> người)</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Ngày / Giờ</div>
                    <div class="fw-semibold"><?php echo formatDate($booking['booking_date']); ?> — <?php echo substr($booking['start_time'],0,5); ?> đến <?php echo substr($booking['end_time'],0,5); ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Khách hàng</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                    <div class="text-muted"><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                    <?php if (!empty($booking['customer_email'])): ?>
                        <div class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-0 border-top pt-3">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">Tổng tiền:</span>
                        <span class="fw-bold text-success fs-5"><?php echo formatMoney($booking['total_price']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Cập nhật trạng thái & ghi chú</h5>
                <form method="post" action="<?php echo route('admin/bookings/' . $booking['id']); ?>">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" class="form-select" required>
                            <?php
                                $curStatus = $old['status'] ?? $booking['status'];
                            ?>
                            <option value="pending"   <?php echo $curStatus === 'pending'   ? 'selected' : ''; ?>>Chờ duyệt</option>
                            <option value="confirmed" <?php echo $curStatus === 'confirmed' ? 'selected' : ''; ?>>Đã duyệt (đã nhận tiền)</option>
                            <option value="cancelled" <?php echo $curStatus === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Ghi chú nội bộ</label>
                        <textarea name="notes" rows="4" class="form-control"><?php echo htmlspecialchars($old['notes'] ?? $booking['notes'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-secondary">Hủy</a>
                        <button type="submit" class="btn btn-success px-4">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
