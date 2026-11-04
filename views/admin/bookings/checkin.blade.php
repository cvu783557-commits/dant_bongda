@extends('layouts.admin')
@section('title', 'Check-in khách')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">✅ Check-in khách</h3>
        <p class="text-muted mb-0">Tra cứu đơn đã xác nhận và đánh dấu khách đã đến sân.</p>
    </div>
    <a href="<?php echo route('admin/bookings/calendar') . '?date=' . urlencode($date); ?>" class="btn btn-outline-success">
        📅 Xem lịch sân
    </a>
</div>

<?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

<div class="alert alert-info">
    Check-in chỉ áp dụng cho đơn đã xác nhận trong ngày hôm nay. Khi check-in thành công, trạng thái đơn chuyển sang
    <strong>Đang sử dụng</strong>.
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo route('admin/check-in'); ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="checkinDate" class="form-label">Ngày đặt sân</label>
                <input type="date" id="checkinDate" name="date" class="form-control" value="<?php echo e($date); ?>">
            </div>
            <div class="col-md-6">
                <label for="checkinQuery" class="form-label">Tìm đơn / khách hàng</label>
                <input type="search" id="checkinQuery" name="q" class="form-control"
                       placeholder="Mã đơn, tên hoặc số điện thoại"
                       value="<?php echo e($query); ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Tìm kiếm</button>
                <a href="<?php echo route('admin/check-in'); ?>" class="btn btn-outline-secondary">Hôm nay</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h4 class="h5 fw-bold mb-0">Quét mã QR của khách</h4>
    </div>
    <div class="card-body">
        <p class="text-muted">Cho phép camera truy cập, hướng vào mã QR khách đưa. Nếu thiết bị quét QR như bàn phím, có thể quét vào ô nhập mã.</p>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-outline-success" id="startQrScanner">Bật camera quét QR</button>
            <button type="button" class="btn btn-outline-secondary d-none" id="stopQrScanner">Tắt camera</button>
        </div>
        <video id="qrScannerVideo" class="d-none w-100 rounded border mb-3" style="max-width:480px;" playsinline></video>
        <form method="post" action="<?php echo route('admin/check-in/scan'); ?>" class="row g-2">
            <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
            <div class="col-md-8">
                <label for="checkinTokenInput" class="form-label">Mã QR</label>
                <input type="text" id="checkinTokenInput" name="checkin_token" class="form-control"
                       pattern="[a-fA-F0-9]{64}" minlength="64" maxlength="64"
                       placeholder="Quét mã QR hoặc nhập mã 64 ký tự" autocomplete="off" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-success">Xác nhận check-in</button>
            </div>
        </form>
        <div class="small text-muted mt-2" id="qrScannerMessage" role="status"></div>
    </div>
</div>

<?php if ($date !== date('Y-m-d')): ?>
    <div class="alert alert-warning">Chỉ xem được danh sách cho ngày khác; thao tác check-in chỉ mở cho đơn của hôm nay.</div>
<?php endif; ?>

<section class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h4 class="h5 fw-bold mb-0">Chờ check-in <span class="badge bg-primary"><?php echo count($confirmedBookings); ?></span></h4>
    </div>
    <?php if (!$confirmedBookings): ?>
        <div class="card-body text-muted">Không có đơn đã xác nhận phù hợp trong ngày này.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Sân</th>
                        <th>Khung giờ</th>
                        <th>Thanh toán</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($confirmedBookings as $booking): ?>
                        <tr>
                            <td class="fw-semibold">
                                <a href="<?php echo route('admin/bookings/' . (int)$booking['id']); ?>">
                                    <?php echo bookingCode($booking['id']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($booking['customer_name']); ?></div>
                                <a class="small text-muted" href="tel:<?php echo e($booking['customer_phone']); ?>">
                                    <?php echo e($booking['customer_phone']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo e($booking['pitch_name']); ?>
                                <div class="small text-muted">Sân con <?php echo (int)($booking['court_number'] ?? 1); ?></div>
                            </td>
                            <td class="text-nowrap">
                                <?php echo formatTime($booking['start_time']); ?>–<?php echo formatTime($booking['end_time']); ?>
                            </td>
                            <td>
                                <?php echo paymentStatusBadge($booking['payment_status']); ?>
                                <div class="small text-muted"><?php echo e(formatMoney($booking['paid_amount'])); ?> đã thu</div>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo route('admin/bookings/' . (int)$booking['id']); ?>"
                                   class="btn btn-sm btn-outline-secondary">Chi tiết</a>
                                <?php if (!empty($canConfirm) && $date === date('Y-m-d')): ?>
                                    <a href="<?php echo route('admin/check-in/' . (int)$booking['id']); ?>"
                                       class="btn btn-sm btn-success">✅ Đối chiếu / Check-in</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h4 class="h5 fw-bold mb-0">Đã check-in <span class="badge bg-info text-dark"><?php echo count($checkedInBookings); ?></span></h4>
    </div>
    <?php if (!$checkedInBookings): ?>
        <div class="card-body text-muted">Chưa có đơn nào được check-in trong ngày này.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Sân</th>
                        <th>Khung giờ</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checkedInBookings as $booking): ?>
                        <tr>
                            <td class="fw-semibold">
                                <a href="<?php echo route('admin/bookings/' . (int)$booking['id']); ?>">
                                    <?php echo bookingCode($booking['id']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo e($booking['customer_name']); ?></div>
                                <small class="text-muted"><?php echo e($booking['customer_phone']); ?></small>
                            </td>
                            <td><?php echo e($booking['pitch_name']); ?> · Sân <?php echo (int)($booking['court_number'] ?? 1); ?></td>
                            <td class="text-nowrap"><?php echo formatTime($booking['start_time']); ?>–<?php echo formatTime($booking['end_time']); ?></td>
                            <td><?php echo bookingStatusBadge($booking['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<script>
(function () {
    const startButton = document.getElementById('startQrScanner');
    const stopButton = document.getElementById('stopQrScanner');
    const video = document.getElementById('qrScannerVideo');
    const input = document.getElementById('checkinTokenInput');
    const message = document.getElementById('qrScannerMessage');
    let stream = null;
    let scanning = false;
    let detector = null;

    async function scanFrame() {
        if (!scanning) return;
        try {
            const codes = await detector.detect(video);
            const token = codes.length ? String(codes[0].rawValue).trim() : '';
            if (/^[a-fA-F0-9]{64}$/.test(token)) {
                input.value = token;
                message.textContent = 'Đã quét mã. Đang gửi yêu cầu check-in…';
                stopScanner();
                input.form.requestSubmit();
                return;
            }
        } catch (error) {
            message.textContent = 'Không đọc được hình ảnh từ camera. Vui lòng thử lại hoặc nhập mã thủ công.';
            stopScanner();
            return;
        }
        requestAnimationFrame(scanFrame);
    }

    function stopScanner() {
        scanning = false;
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        video.srcObject = null;
        video.classList.add('d-none');
        stopButton.classList.add('d-none');
        startButton.classList.remove('d-none');
    }

    startButton.addEventListener('click', async function () {
        if (!('BarcodeDetector' in window)) {
            message.textContent = 'Trình duyệt chưa hỗ trợ quét QR bằng camera. Hãy dùng ứng dụng quét QR hoặc máy quét để nhập mã vào ô bên dưới.';
            return;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            message.textContent = 'Không truy cập được camera. Hãy dùng HTTPS/localhost hoặc nhập mã QR thủ công.';
            return;
        }
        try {
            detector = new BarcodeDetector({formats: ['qr_code']});
            stream = await navigator.mediaDevices.getUserMedia({
                video: {facingMode: {ideal: 'environment'}},
                audio: false
            });
            video.srcObject = stream;
            await video.play();
            scanning = true;
            video.classList.remove('d-none');
            startButton.classList.add('d-none');
            stopButton.classList.remove('d-none');
            message.textContent = 'Đưa mã QR vào khung hình camera.';
            requestAnimationFrame(scanFrame);
        } catch (error) {
            stopScanner();
            message.textContent = 'Không thể bật camera. Hãy cấp quyền truy cập camera hoặc nhập mã QR thủ công.';
        }
    });

    stopButton.addEventListener('click', stopScanner);
    window.addEventListener('pagehide', stopScanner);
})();
</script>
@endsection
