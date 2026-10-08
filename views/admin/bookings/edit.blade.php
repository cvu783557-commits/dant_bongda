@extends('layouts.admin')
@section('title', 'Sửa lịch đặt sân')
@section('content')

<?php
$id = (int)$booking['id'];
$priceMap = [];
foreach ($pitches as $p) $priceMap[(int)$p['id']] = (float)$p['price_per_hour'];
$priceMapJs = json_encode($priceMap, JSON_UNESCAPED_UNICODE);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">✏️ Sửa lịch <?php echo bookingCode($id); ?></h3>
    <div class="d-flex gap-2">
        <a href="<?php echo route('admin/bookings/' . $id); ?>" class="btn btn-outline-primary">Chi tiết</a>
        <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-secondary">← Danh sách</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?php echo route('admin/bookings/' . $id); ?>" onsubmit="return beforeSubmit();">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">👥 Thông tin khách hàng</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control" required
                               value="<?php echo e(old('customer_name', $booking['customer_name'])); ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" name="customer_phone" class="form-control" required
                                   pattern="^0[0-9]{9,10}$"
                                   value="<?php echo e(old('customer_phone', $booking['customer_phone'])); ?>">
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="customer_email" class="form-control"
                                   value="<?php echo e(old('customer_email', $booking['customer_email'] ?? '')); ?>">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-3 border-bottom pb-2">💳 Thanh toán</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tổng tiền (VNĐ)</label>
                        <input type="number" name="total_price" class="form-control" id="total_price_input"
                               min="0" step="1000"
                               value="<?php echo e(old('total_price', $booking['total_price'])); ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tiền đặt cọc</label>
                            <input type="number" name="deposit" class="form-control" min="0" step="1000"
                                   value="<?php echo e(old('deposit', $booking['deposit'])); ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Đã thanh toán</label>
                            <input type="number" name="paid_amount" class="form-control" min="0" step="1000"
                                   value="<?php echo e(old('paid_amount', $booking['paid_amount'])); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phương thức thanh toán</label>
                        <select name="payment_method" class="form-select">
                            <?php foreach ($paymentMethods as $k => $v): ?>
                                <option value="<?php echo $k; ?>" <?php echo old('payment_method', $booking['payment_method']) === $k ? 'selected' : ''; ?>>
                                    <?php echo e($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h5 class="fw-bold mb-3 border-bottom pb-2 pt-3">🔁 Trạng thái</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái lịch</label>
                        <select name="status" class="form-select">
                            <?php foreach ($bookingStatuses as $k => $v): ?>
                                <?php
                                    $oldS = old('status', $booking['status']);
                                    $sel = $oldS === $k ? 'selected' : '';
                                ?>
                                <option value="<?php echo $k; ?>" <?php echo $sel; ?>>
                                    <?php echo e($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Lưu ý: không được chuyển trạng thái sai quy trình (pending→confirmed→in_progress→completed hoặc pending/cancelled).</div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">🏟️ Thông tin đặt sân</h5>

                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="small text-muted">Trạng thái lịch hiện tại</div>
                        <div><?php echo bookingStatusBadge($booking['status']); ?> <?php echo paymentStatusBadge($booking['payment_status']); ?></div>
                        <div class="small text-muted mt-2">Còn lại phải thanh toán</div>
                        <div class="fw-bold text-danger fs-5"><?php echo formatMoney($remaining); ?></div>
                        <div class="small text-muted mt-1">Thời lượng: <strong><?php echo $hours; ?> giờ</strong></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sân <span class="text-danger">*</span></label>
                        <select name="pitch_id" id="pitch_id" class="form-select" required onchange="calcTotal();">
                            <?php foreach ($pitches as $p): ?>
                                <option value="<?php echo $p['id']; ?>"
                                    <?php echo (int)old('pitch_id', $booking['pitch_id']) === (int)$p['id'] ? 'selected' : ''; ?>
                                    data-price="<?php echo (float)$p['price_per_hour']; ?>">
                                    <?php echo e($p['name']); ?> (Sân <?php echo (int)$p['type']; ?> - <?php echo formatMoney($p['price_per_hour']); ?>/giờ)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngày đặt <span class="text-danger">*</span></label>
                        <input type="date" name="booking_date" id="booking_date" required class="form-control"
                               value="<?php echo e(old('booking_date', $booking['booking_date'])); ?>"
                               onchange="checkOverlap();">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" required
                                   class="form-control" min="06:00" max="22:59"
                                   value="<?php echo e(old('start_time', substr($booking['start_time'],0,5))); ?>"
                                   onchange="calcTotal(); checkOverlap();">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Giờ kết thúc <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" required
                                   class="form-control" min="06:01" max="23:00"
                                   value="<?php echo e(old('end_time', substr($booking['end_time'],0,5))); ?>"
                                   onchange="calcTotal(); checkOverlap();">
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border" id="overlap_box">
                        <div class="d-flex align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm text-secondary d-none" id="overlap_spinner"></span>
                            <strong id="overlap_status">Kiểm tra trùng lịch (tự động khi thay đổi)</strong>
                        </div>
                        <div class="small text-muted mt-1" id="overlap_message">Nếu đổi sân/ngày/giờ hệ thống sẽ kiểm tra lại.</div>
                    </div>

                    <div class="mb-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                        <div class="row g-2">
                            <div class="col-6"><div class="small text-muted">Giá / giờ</div><div class="fw-semibold" id="display_price_hour">—</div></div>
                            <div class="col-6"><div class="small text-muted">Số giờ</div><div class="fw-semibold" id="display_hours">0 giờ</div></div>
                            <div class="col-12 border-top pt-2 mt-1">
                                <div class="small text-muted">Tổng (tự tính)</div>
                                <div class="fw-bold text-success fs-5" id="display_total_auto">0 ₫</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" rows="3" class="form-control" maxlength="1000"><?php echo e(old('note', $booking['note'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-4 border-top mt-4">
                <a href="<?php echo route('admin/bookings/' . $id); ?>" class="btn btn-outline-secondary px-4">Hủy</a>
                <button type="submit" class="btn btn-success px-5">💾 Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
var priceMap = <?php echo $priceMapJs; ?>;
var EXCLUDE_ID = <?php echo $id; ?>;
var overlapOk = true;

function hoursBetween(s,e){ if(!s||!e) return 0; var a=s.split(':').map(Number),b=e.split(':').map(Number); var d=(b[0]*60+(b[1]||0) - a[0]*60-(a[1]||0))/60; return d>0?d:0;}
function fm(n){return new Intl.NumberFormat('vi-VN').format(Number(n)||0)+' ₫';}
function calcTotal(){
    var p=priceMap[Number(document.getElementById('pitch_id').value)||0]||0;
    var s=document.getElementById('start_time').value,e=document.getElementById('end_time').value;
    var h=hoursBetween(s,e);
    document.getElementById('display_price_hour').textContent = p>0?fm(p):'—';
    document.getElementById('display_hours').textContent = h+' giờ';
    document.getElementById('display_total_auto').textContent = fm(p*h);
}
function checkOverlap(){
    var pid = document.getElementById('pitch_id').value,
        dt  = document.getElementById('booking_date').value,
        st  = document.getElementById('start_time').value,
        et  = document.getElementById('end_time').value;
    var sEl=document.getElementById('overlap_status'),
        mEl=document.getElementById('overlap_message'),
        box=document.getElementById('overlap_box'),
        sp=document.getElementById('overlap_spinner');
    overlapOk=true;
    if(!pid||!dt||!st||!et){ sEl.textContent='Chọn sân+ngày+giờ để kiểm tra'; mEl.textContent=''; box.className='mb-3 p-3 bg-light rounded border'; return;}
    if(st>=et){ box.className='mb-3 p-3 bg-danger bg-opacity-10 border border-danger rounded'; sEl.textContent='⚠ Giờ không hợp lệ'; mEl.textContent='Start < End'; return;}
    sp.classList.remove('d-none'); sEl.textContent='Đang kiểm tra...';
    fetch('<?php echo route('admin/bookings/check-overlap'); ?>?pitch_id='+pid+'&booking_date='+dt+'&start_time='+st+'&end_time='+et+'&exclude_id='+EXCLUDE_ID)
        .then(function(r){return r.json();}).then(function(res){
            sp.classList.add('d-none');
            if(res && res.ok && !res.overlap){
                overlapOk=true;
                box.className='mb-3 p-3 bg-success bg-opacity-10 border border-success rounded';
                sEl.textContent='✅ Không trùng lịch'; mEl.textContent=res.message||'';
            }else{
                overlapOk=false;
                box.className='mb-3 p-3 bg-danger bg-opacity-10 border border-danger rounded';
                sEl.textContent='❌ Trùng lịch';
                mEl.textContent=(res&&res.message)?res.message:'Đã có người đặt khung giờ này';
            }
        }).catch(function(){ sp.classList.add('d-none'); sEl.textContent='⚠ Lỗi (backend vẫn kiểm tra)';});
}
function beforeSubmit(){
    var st=document.getElementById('start_time').value,et=document.getElementById('end_time').value;
    if(st>=et){alert('Giờ bắt đầu phải nhỏ hơn giờ kết thúc');return false;}
    if(!overlapOk){return confirm('⚠ Có vẻ trùng lịch hoặc chưa kiểm tra. Backend vẫn sẽ kiểm tra lại. Tiếp tục?');}
    return true;
}
document.addEventListener('DOMContentLoaded',function(){ calcTotal(); checkOverlap(); });
</script>
@endsection
