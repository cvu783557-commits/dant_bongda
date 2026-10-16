@extends('layouts.admin')
@section('title', 'Thêm lịch đặt sân')
@section('content')

<?php
$pitchIdSel = (int)old('pitch_id', $_GET['pitch_id'] ?? 0);
$dateSel    = old('booking_date', $_GET['date'] ?? date('Y-m-d'));
$startSel   = old('start_time', $_GET['start_time'] ?? '');
$endSel     = old('end_time', $_GET['end_time'] ?? '');

$priceMap = [];
foreach ($pitches as $p) $priceMap[(int)$p['id']] = (float)$p['price_per_hour'];
$priceMapJs = json_encode($priceMap, JSON_UNESCAPED_UNICODE);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">➕ Thêm lịch đặt sân</h3>
    <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-secondary">← Danh sách</a>
</div>

<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form id="booking-create-form" method="post" action="<?php echo route('admin/bookings'); ?>" onsubmit="return beforeSubmit();">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">👥 Thông tin khách hàng</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control" required
                               placeholder="Nhập tên khách hàng"
                               value="<?php echo e(old('customer_name')); ?>"
                               minlength="2" maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" name="customer_phone" class="form-control" required
                               pattern="^0[0-9]{9,10}$"
                               placeholder="VD: 0901234567"
                               value="<?php echo e(old('customer_phone')); ?>">
                        <div class="form-text">Bắt đầu bằng số 0, gồm 10-11 số.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="customer_email" class="form-control"
                               placeholder="VD: email@example.com (không bắt buộc)"
                               value="<?php echo e(old('customer_email')); ?>">
                    </div>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-3 border-bottom pb-2">💳 Thanh toán</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tổng tiền thuê (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="total_price" id="total_price_input" class="form-control" required
                               min="0" step="1000"
                               placeholder="Tổng tiền (tự tính theo giờ, bạn có thể chỉnh sửa)"
                               value="<?php echo e(old('total_price')); ?>">
                        <div class="form-text">Hệ thống tự tính = giá/giờ × số giờ. Bạn có thể điều chỉnh thủ công.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tiền đặt cọc (VNĐ)</label>
                            <input type="number" name="deposit" class="form-control"
                                   min="0" step="1000" placeholder="VD: 200000"
                                   value="<?php echo e(old('deposit', 0)); ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Đã thanh toán (VNĐ)</label>
                            <input type="number" name="paid_amount" class="form-control"
                                   min="0" step="1000" placeholder="VD: 500000"
                                   value="<?php echo e(old('paid_amount', 0)); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phương thức thanh toán</label>
                        <select name="payment_method" class="form-select">
                            <?php foreach ($paymentMethods as $k => $v): ?>
                                <option value="<?php echo $k; ?>" <?php echo old('payment_method', 'cash') === $k ? 'selected' : ''; ?>>
                                    <?php echo e($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái lịch ban đầu</label>
                        <select name="status" class="form-select">
                            <?php foreach ($bookingStatuses as $k => $v): ?>
                                <option value="<?php echo $k; ?>" <?php echo old('status', 'pending') === $k ? 'selected' : ''; ?>>
                                    <?php echo e($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">🏟️ Thông tin đặt sân</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn sân <span class="text-danger">*</span></label>
                        <select name="pitch_id" id="pitch_id" class="form-select" required onchange="calcTotal();">
                            <option value="">-- Vui lòng chọn sân --</option>
                            <?php foreach ($pitches as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $pitchIdSel === (int)$p['id'] ? 'selected' : ''; ?>
                                        data-price="<?php echo (float)$p['price_per_hour']; ?>">
                                    <?php echo e($p['name']); ?> (Sân <?php echo (int)$p['type']; ?> người - <?php echo formatMoney($p['price_per_hour']); ?>/giờ)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngày đặt <span class="text-danger">*</span></label>
                        <input type="date" name="booking_date" id="booking_date" required
                               class="form-control"
                               value="<?php echo e($dateSel); ?>"
                               onchange="checkOverlap();"
                               min="<?php echo date('Y-m-d'); ?>">
                        <div class="form-text">Chỉ được chọn hôm nay hoặc ngày tương lai.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" required
                                   class="form-control" min="06:00" max="22:59"
                                   value="<?php echo e($startSel); ?>"
                                   onchange="calcTotal(); checkOverlap();">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Giờ kết thúc <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" required
                                   class="form-control" min="06:01" max="23:00"
                                   value="<?php echo e($endSel); ?>"
                                   onchange="calcTotal(); checkOverlap();">
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border" id="overlap_box">
                        <div class="d-flex align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm text-secondary d-none" id="overlap_spinner" role="status"></span>
                            <strong id="overlap_status">Chọn sân + ngày + giờ để kiểm tra trùng lịch</strong>
                        </div>
                        <div class="small text-muted mt-1" id="overlap_message">Hệ thống tự động kiểm tra trùng khung giờ.</div>
                    </div>

                    <div class="mb-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="small text-muted">Giá / 1 giờ</div>
                                <div class="fw-semibold" id="display_price_hour">—</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Số giờ thuê</div>
                                <div class="fw-semibold" id="display_hours">0 giờ</div>
                            </div>
                            <div class="col-12 border-top pt-2 mt-1">
                                <div class="small text-muted">Tổng tiền (tự tính)</div>
                                <div class="fw-bold text-success fs-5" id="display_total_auto">0 ₫</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" rows="3" class="form-control"
                                  placeholder="VD: Đặt trước nhóm 12 người, cần bóng đá, nước uống..."
                                  maxlength="1000"><?php echo e(old('note')); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-4 border-top mt-4">
                <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-secondary px-4">Hủy</a>
                <button type="submit" id="btn_submit" class="btn btn-success px-5">💾 Lưu lịch đặt</button>
            </div>
        </form>
    </div>
</div>

<script>
var priceMap = <?php echo $priceMapJs; ?>;
var checking = false, overlapOk = false, overlapMsg = '';

function hoursBetween(startStr, endStr) {
    if (!startStr || !endStr) return 0;
    var s = startStr.split(':').map(Number);
    var e = endStr.split(':').map(Number);
    var sMin = s[0]*60 + (s[1]||0);
    var eMin = e[0]*60 + (e[1]||0);
    var diff = (eMin - sMin) / 60;
    return diff > 0 ? diff : 0;
}

function formatMoney(num) {
    num = Number(num) || 0;
    return new Intl.NumberFormat('vi-VN').format(num) + ' ₫';
}

function calcTotal() {
    var pid = Number(document.getElementById('pitch_id').value) || 0;
    var price = priceMap[pid] || 0;
    var st = document.getElementById('start_time').value;
    var et = document.getElementById('end_time').value;
    var h  = hoursBetween(st, et);

    document.getElementById('display_price_hour').textContent = price > 0 ? formatMoney(price) : '—';
    document.getElementById('display_hours').textContent = h + ' giờ';
    var t = price * h;
    document.getElementById('display_total_auto').textContent = formatMoney(t);

    var inp = document.getElementById('total_price_input');
    if (inp && (inp.value === '' || Number(inp.value) === 0)) {
        if (t > 0) inp.value = Math.round(t);
    }
}

function checkOverlap() {
    var pid = document.getElementById('pitch_id').value;
    var dt  = document.getElementById('booking_date').value;
    var st  = document.getElementById('start_time').value;
    var et  = document.getElementById('end_time').value;

    var statusEl = document.getElementById('overlap_status');
    var msgEl    = document.getElementById('overlap_message');
    var box      = document.getElementById('overlap_box');
    var spin     = document.getElementById('overlap_spinner');

    overlapOk = false;

    if (!pid || !dt || !st || !et) {
        box.className = 'mb-3 p-3 bg-light rounded border';
        statusEl.textContent = 'Chọn sân + ngày + giờ để kiểm tra trùng lịch';
        msgEl.textContent = 'Hệ thống tự động kiểm tra trùng khung giờ.';
        overlapMsg = '';
        return;
    }
    if (st >= et) {
        box.className = 'mb-3 p-3 bg-danger bg-opacity-10 border border-danger rounded';
        statusEl.textContent = '⚠️ Giờ không hợp lệ';
        msgEl.textContent = 'Giờ bắt đầu phải nhỏ hơn giờ kết thúc.';
        return;
    }

    checking = true;
    spin.classList.remove('d-none');
    statusEl.textContent = 'Đang kiểm tra...';
    msgEl.textContent = '';

    var url = '<?php echo route('admin/bookings/check-overlap'); ?>'
        + '?pitch_id=' + encodeURIComponent(pid)
        + '&booking_date=' + encodeURIComponent(dt)
        + '&start_time=' + encodeURIComponent(st)
        + '&end_time=' + encodeURIComponent(et);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r){ return r.json(); })
        .then(function(res){
            spin.classList.add('d-none');
            if (res && res.ok && !res.overlap) {
                overlapOk = true;
                overlapMsg = '';
                box.className = 'mb-3 p-3 bg-success bg-opacity-10 border border-success rounded';
                statusEl.textContent = '✅ Khung giờ này TRỐNG - có thể đặt';
                msgEl.textContent = res.message || '';
            } else {
                overlapOk = false;
                overlapMsg = (res && res.message) ? res.message : 'Khung giờ đã có người đặt';
                box.className = 'mb-3 p-3 bg-danger bg-opacity-10 border border-danger rounded';
                statusEl.textContent = '❌ Khung giờ ĐÃ BỊ ĐẶT';
                msgEl.textContent = overlapMsg;
            }
        })
        .catch(function(){
            spin.classList.add('d-none');
            statusEl.textContent = '⚠️ Lỗi kiểm tra (backend vẫn kiểm tra khi lưu)';
            msgEl.textContent = '';
        });
}

function beforeSubmit() {
    var pid = document.getElementById('pitch_id').value;
    var dt  = document.getElementById('booking_date').value;
    var st  = document.getElementById('start_time').value;
    var et  = document.getElementById('end_time').value;
    var stName = document.querySelector('input[name="customer_name"]').value.trim();
    var stPhone = document.querySelector('input[name="customer_phone"]').value.trim();

    if (!pid || !dt || !st || !et || !stName || !stPhone) return true;

    if (st >= et) { alert('Giờ bắt đầu phải nhỏ hơn giờ kết thúc.'); return false; }

    // Hỏi xác nhận nếu chưa kiểm tra overlap
    if (!overlapOk) {
        return confirm('⚠ Chưa kiểm tra trùng lịch hoặc khung giờ đã bị đặt.\nBackend vẫn sẽ kiểm tra lại.\nBạn muốn tiếp tục lưu?');
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function(){
    calcTotal();
    if (document.getElementById('pitch_id').value &&
        document.getElementById('booking_date').value &&
        document.getElementById('start_time').value &&
        document.getElementById('end_time').value) {
        checkOverlap();
    }
});
</script>
@endsection
