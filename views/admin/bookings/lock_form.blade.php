@extends('layouts.admin')
@section('title', 'Khóa khung giờ')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">🔒 Khóa khung giờ sân</h3>
    <div class="d-flex gap-2">
        <a href="<?php echo route('admin/bookings/calendar') . '?date=' . $date . ($pitchId ? '&pitch_id=' . $pitchId : ''); ?>" class="btn btn-outline-secondary">← Lịch sân</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?php echo route('admin/pitch-lock'); ?>">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">🏟️ Thông tin khóa</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn sân <span class="text-danger">*</span></label>
                        <select name="pitch_id" class="form-select" required>
                            <option value="">-- Vui lòng chọn sân --</option>
                            <?php foreach ($pitches as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo (int)$pitchId === (int)$p['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($p['name']); ?> (Sân <?php echo (int)$p['type']; ?> người - <?php echo formatMoney($p['price_per_hour']); ?>/giờ)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngày khóa <span class="text-danger">*</span></label>
                        <input type="date" name="lock_date" required class="form-control"
                               value="<?php echo e($date); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" required class="form-control"
                                   min="06:00" max="22:59"
                                   value="<?php echo e($startDefault); ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Giờ kết thúc <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" required class="form-control"
                                   min="06:01" max="23:00"
                                   value="<?php echo e($endDefault); ?>">
                            <div class="form-text">Giờ hoạt động: 06:00 - 23:00</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">💡 Lý do / Ghi chú</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lý do khóa</label>
                        <select name="reason" class="form-select" id="reason_select" onchange="syncReasonText();">
                            <option value="">-- Chọn hoặc nhập lý do bên dưới --</option>
                            <option value="Bảo trì sân">🔧 Bảo trì, sửa chữa sân</option>
                            <option value="Sự kiện riêng">🎉 Sự kiện riêng</option>
                            <option value="Giải đấu">⚽ Giải đấu tranh thưởng</option>
                            <option value="Không hoạt động">🚫 Tạm ngừng hoạt động</option>
                        </select>
                        <div class="form-text">Lý do này sẽ hiển thị khi khách / nhân viên kiểm tra khung giờ.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hoặc nhập lý do chi tiết:</label>
                        <textarea name="reason" rows="4" id="reason_text" class="form-control"
                                  placeholder="VD: Bảo trì cỏ sân sau giải đấu cuối tuần, vệ sinh đèn chiếu sáng..."
                                  maxlength="500"></textarea>
                    </div>

                    <div class="mb-3 p-3 bg-warning bg-opacity-10 border border-warning rounded">
                        <div class="fw-semibold text-warning-emphasis mb-1">⚠ Lưu ý quan trọng</div>
                        <ul class="small text-muted mb-0 lh-base">
                            <li>Khung giờ <strong>đã có người đặt</strong> không thể khóa. Hãy hủy lịch đặt trước.</li>
                            <li>Sau khi khóa, không ai có thể đặt lịch trùng khung giờ này.</li>
                            <li>Để mở khóa, vào trang lịch sân, click vào khung bị khóa và chọn "Mở khóa".</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-4 border-top mt-4">
                <a href="<?php echo route('admin/bookings/calendar') . '?date=' . $date; ?>" class="btn btn-outline-secondary px-4">Hủy</a>
                <button type="submit" class="btn btn-warning px-5 fw-semibold">🔒 Xác nhận khóa</button>
            </div>
        </form>
    </div>
</div>

<script>
function syncReasonText() {
    var sel = document.getElementById('reason_select');
    var txt = document.getElementById('reason_text');
    if (sel.value !== '' && txt.value.trim() === '') {
        txt.value = sel.value;
    }
}
</script>
@endsection
