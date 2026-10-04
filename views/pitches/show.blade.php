<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pitch['name']); ?> — Đặt sân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: #1f2937;
        }
        .slot-badge {
            font-size: 0.8rem;
            padding: 4px 6px;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo route('/'); ?>">⚽ SânBóng.Pro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="topNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="<?php echo route('/'); ?>">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo route('admin/login'); ?>">Trang quản lý</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row g-5">
        <div class="col-lg-6">
            <a href="<?php echo route('/'); ?>" class="text-decoration-none small text-success mb-3 d-inline-block">← Quay lại danh sách sân</a>
            <img src="<?php echo $pitch['image'] ? (str_starts_with($pitch['image'], 'http') ? $pitch['image'] : file_url($pitch['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                 class="w-100 rounded shadow-sm mb-4"
                 style="height:320px; object-fit:cover;"
                 alt="<?php echo htmlspecialchars($pitch['name']); ?>">
            <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($pitch['name']); ?></h2>
            <div class="d-flex gap-2 mb-3">
                <span class="badge bg-success-subtle text-success border border-success-subtle">Sân <?php echo (int)$pitch['type']; ?> người</span>
                <span class="text-success fw-bold fs-5"><?php echo formatMoney($pitch['price_per_hour']); ?> / giờ</span>
            </div>
            <?php if (!empty($pitch['description'])): ?>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($pitch['description'])); ?></p>
            <?php endif; ?>

            <div class="card border-0 bg-light mt-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Tình trạng sân — Ngày <?php echo formatDate($date); ?></h6>
                    <div class="row g-2">
                        <?php foreach ($hourSlots as $idx => $s): ?>
                            <?php $taken = isset($takenSlots[$idx]); ?>
                            <div class="col-4">
                                <div class="slot-badge text-center <?php echo $taken ? 'bg-danger-subtle text-danger-emphasis' : 'bg-success-subtle text-success-emphasis'; ?>">
                                    <?php echo htmlspecialchars($s['label']); ?>
                                    <?php if ($taken) echo ' • Đã đặt'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 small text-muted">
                        Lưu ý: Các khung giờ "Đã đặt" không thể chọn. Bạn có thể đặt liên tục nhiều giờ bằng cách chọn Giờ bắt đầu & Giờ kết thúc.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Thông tin đặt sân</h4>

                    <form method="get" action="" class="mb-4">
                        <label class="form-label fw-semibold">Chọn ngày đặt:</label>
                        <div class="d-flex gap-2">
                            <input type="date"
                                   name="date"
                                   value="<?php echo htmlspecialchars($date); ?>"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="form-control"
                                   required>
                            <button type="submit" class="btn btn-outline-success">Xem lịch</button>
                        </div>
                    </form>

                    <form method="post" id="bookingForm" action="<?php echo route('bookings'); ?>" novalidate>
                        <input type="hidden" name="pitch_id" value="<?php echo (int)$pitch['id']; ?>">
                        <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($date); ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Giờ bắt đầu <span class="text-danger">*</span></label>
                                <input type="time" id="start_time" name="start_time"
                                       value="<?php echo htmlspecialchars($old['start_time'] ?? ''); ?>"
                                       min="<?php echo htmlspecialchars($minStart); ?>"
                                       max="22:59"
                                       class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Giờ kết thúc <span class="text-danger">*</span></label>
                                <input type="time" id="end_time" name="end_time"
                                       value="<?php echo htmlspecialchars($old['end_time'] ?? ''); ?>"
                                       min="06:01"
                                       max="<?php echo htmlspecialchars($maxEnd); ?>"
                                       class="form-control" required>
                            </div>
                        </div>

                        <div id="overlapBox" class="alert d-none mb-3"></div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Số giờ thuê</label>
                                <input type="text" id="hoursDisplay" readonly value="0 giờ" class="form-control-plaintext fw-semibold">
                            </div>
                            <div class="col-6 text-end">
                                <label class="form-label">Giá / giờ</label>
                                <div class="form-control-plaintext fw-semibold text-success"><?php echo formatMoney($pitch['price_per_hour']); ?></div>
                            </div>
                        </div>

                        <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-semibold">Thành tiền (ước tính):</span>
                            <span class="fw-bold fs-5" id="totalDisplay"><?php echo formatMoney(0); ?></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Họ tên khách hàng <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($old['customer_name'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" name="customer_phone" class="form-control" required
                                   pattern="^0[0-9]{9,10}$"
                                   value="<?php echo htmlspecialchars($old['customer_phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email (tùy chọn)</label>
                            <input type="email" name="customer_email" class="form-control"
                                   value="<?php echo htmlspecialchars($old['customer_email'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú (tùy chọn)</label>
                            <textarea name="notes" rows="2" class="form-control"><?php echo htmlspecialchars($old['notes'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Xác nhận đặt sân</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="border-top py-4 bg-light mt-5">
    <div class="container text-center text-secondary small">
        © <?php echo date('Y'); ?> SânBóng.Pro — Hệ thống đặt sân bóng online
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    const PRICE_PER_HOUR = <?php echo (float)$pitch['price_per_hour']; ?>;
    const PITCH_ID       = <?php echo (int)$pitch['id']; ?>;
    const BOOKING_DATE   = "<?php echo htmlspecialchars($date); ?>";
    const CHECK_URL      = "<?php echo route('admin/bookings/check-overlap'); ?>";

    const startEl   = document.getElementById('start_time');
    const endEl     = document.getElementById('end_time');
    const hoursEl   = document.getElementById('hoursDisplay');
    const totalEl   = document.getElementById('totalDisplay');
    const overlapEl = document.getElementById('overlapBox');
    const form      = document.getElementById('bookingForm');

    function fmtMoney(n){
        return n.toLocaleString('vi-VN') + ' ₫';
    }
    function timeToSec(t){
        if(!t) return null;
        const [h,m] = t.split(':').map(Number);
        return h*3600 + m*60;
    }

    function recalc(){
        const s = timeToSec(startEl.value);
        const e = timeToSec(endEl.value);
        if(s === null || e === null || e <= s){
            hoursEl.value = '0 giờ';
            totalEl.textContent = fmtMoney(0);
            return null;
        }
        const hours = (e - s) / 3600;
        hoursEl.value = hours.toFixed(2).replace(/\.?0+$/,'') + ' giờ';
        const total = Math.round(hours * PRICE_PER_HOUR);
        totalEl.textContent = fmtMoney(total);
        return hours;
    }

    async function checkOverlap(){
        if(!startEl.value || !endEl.value){ overlapEl.classList.add('d-none'); return true; }
        const h = recalc();
        if(h === null){
            overlapEl.className = 'alert alert-warning mb-3';
            overlapEl.textContent = 'Vui lòng chọn Giờ bắt đầu < Giờ kết thúc';
            overlapEl.classList.remove('d-none');
            return false;
        }
        const params = new URLSearchParams({
            pitch_id: PITCH_ID,
            booking_date: BOOKING_DATE,
            start_time: startEl.value + ':00',
            end_time: endEl.value + ':00',
        });
        try{
            const r = await fetch(CHECK_URL + '?' + params.toString());
            const j = await r.json();
            if(j.ok){
                overlapEl.className = 'alert alert-success mb-3';
                overlapEl.textContent = '✓ Khung giờ này trống, bạn có thể đặt sân';
                overlapEl.classList.remove('d-none');
                return true;
            }else{
                overlapEl.className = 'alert alert-danger mb-3';
                let txt = j.message || 'Khung giờ này đã được đặt';
                if(j.booking){
                    txt += ' (trùng với khách ' + (j.booking.customer_name||'') + ' ' + (j.booking.start_time||'').slice(0,5) + '-' + (j.booking.end_time||'').slice(0,5) + ')';
                }
                overlapEl.textContent = '✗ ' + txt;
                overlapEl.classList.remove('d-none');
                return false;
            }
        }catch(err){
            overlapEl.classList.add('d-none');
            return true;
        }
    }

    startEl.addEventListener('input', function(){
        if(endEl.value && startEl.value >= endEl.value){
            const s = startEl.value;
            if(s){
                const [h,m] = s.split(':').map(Number);
                const nh = Math.min(h+1, 23);
                endEl.value = String(nh).padStart(2,'0') + ':' + String(m).padStart(2,'0');
            }
        }
        recalc(); checkOverlap();
    });
    endEl.addEventListener('input', function(){ recalc(); checkOverlap(); });
    recalc();

    form.addEventListener('submit', async function(e){
        const valid = await checkOverlap();
        if(!valid){
            e.preventDefault();
            alert('Khung giờ bạn chọn đã bị trùng, vui lòng chọn khung giờ khác.');
            return;
        }
    });
})();
</script>
</body>
</html>
