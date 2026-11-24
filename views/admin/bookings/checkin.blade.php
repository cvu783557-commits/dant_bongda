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
            <label class="btn btn-outline-primary" for="qrImageInput">Chọn ảnh QR</label>
            <input type="file" id="qrImageInput" accept="image/*" capture="environment" class="d-none">
        </div>
        <div class="mb-3 d-none" id="cameraPickerWrap">
            <label for="cameraPicker" class="form-label">Chọn camera</label>
            <select id="cameraPicker" class="form-select" style="max-width:480px;"></select>
        </div>
        <div id="qrScannerReader" class="d-none mx-auto mb-3 rounded border" style="max-width:480px;"></div>
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
<script src="<?php echo route('assets/vendor/html5-qrcode.min.js'); ?>"></script>
<script>
(function () {
    if (window.location.protocol === 'http:' && window.location.hostname !== 'localhost') {
        window.location.replace('https://' + window.location.host + window.location.pathname + window.location.search + window.location.hash);
        return;
    }

    const startButton = document.getElementById('startQrScanner');
    const stopButton = document.getElementById('stopQrScanner');
    const reader = document.getElementById('qrScannerReader');
    const cameraPickerWrap = document.getElementById('cameraPickerWrap');
    const cameraPicker = document.getElementById('cameraPicker');
    const imageInput = document.getElementById('qrImageInput');
    const input = document.getElementById('checkinTokenInput');
    const message = document.getElementById('qrScannerMessage');
    let qrScanner = null;
    let submitted = false;

    function setRunning(isRunning) {
        stopButton.classList.toggle('d-none', !isRunning);
        startButton.classList.toggle('d-none', isRunning);
    }

    function extractToken(rawText) {
        const match = String(rawText || '').trim().match(/[a-fA-F0-9]{64}/);
        return match ? match[0].toLowerCase() : '';
    }

    async function stopScanner() {
        if (qrScanner) {
            try {
                if (qrScanner.isScanning) await qrScanner.stop();
            } catch (error) {}
            try { qrScanner.clear(); } catch (error) {}
            qrScanner = null;
        }
        reader.classList.add('d-none');
        setRunning(false);
    }

    function onQrSuccess(rawText) {
        if (submitted) return;
        const token = extractToken(rawText);
        if (!token) {
            message.textContent = 'Mã QR không hợp lệ. QR cần chứa mã check-in 64 ký tự.';
            return;
        }
        submitted = true;
        input.value = token;
        message.textContent = 'Đã quét mã. Đang gửi yêu cầu check-in…';
        stopScanner();
        input.form.requestSubmit();
    }

    async function startWithCamera(cameraId) {
        if (!window.Html5Qrcode) {
            message.textContent = 'Không tải được thư viện quét QR. Kiểm tra kết nối mạng rồi thử lại.';
            return;
        }

        await stopScanner();
        submitted = false;
        qrScanner = new Html5Qrcode('qrScannerReader');
        reader.classList.remove('d-none');
        setRunning(true);
        message.textContent = 'Đưa mã QR vào khung hình camera.';

        const cameraConfig = cameraId
            ? { deviceId: { exact: cameraId } }
            : { facingMode: 'environment' };

        try {
            await qrScanner.start(
                cameraConfig,
                { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0 },
                onQrSuccess,
                function () {}
            );
        } catch (error) {
            await stopScanner();
            message.textContent = window.isSecureContext
                ? 'Không thể bật camera. Hãy cấp quyền truy cập camera và chọn đúng camera.'
                : 'Camera chỉ hoạt động trên HTTPS hoặc localhost. Hãy truy cập bằng HTTPS/localhost.';
        }
    }

    startButton.addEventListener('click', async function () {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            message.textContent = 'Trình duyệt không hỗ trợ truy cập camera. Hãy mở trang bằng HTTPS, dùng Chrome/Safari mới nhất hoặc quét ảnh QR.';
            return;
        }

        try {
            const permissionStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            permissionStream.getTracks().forEach(function (track) { track.stop(); });
            const devices = await Html5Qrcode.getCameras();
            if (!devices || !devices.length) {
                message.textContent = 'Không tìm thấy camera trên thiết bị này.';
                return;
            }

            cameraPicker.innerHTML = '';
            devices.forEach(function (device, index) {
                const option = document.createElement('option');
                option.value = device.id;
                option.textContent = device.label || ('Camera ' + (index + 1));
                cameraPicker.appendChild(option);
            });

            cameraPickerWrap.classList.remove('d-none');
            const backCamera = devices.find(function (device) {
                return /back|rear|sau|environment|camera 2/i.test(device.label);
            });
            cameraPicker.value = backCamera ? backCamera.id : devices[0].id;
            await startWithCamera(cameraPicker.value);
        } catch (error) {
            await stopScanner();
            message.textContent = 'Không thể truy cập camera. Hãy cấp quyền trong trình duyệt, bấm “Chọn ảnh QR”, hoặc nhập mã thủ công.';
        }
    });

    cameraPicker.addEventListener('change', function () {
        if (cameraPicker.value) startWithCamera(cameraPicker.value);
    });

    imageInput.addEventListener('change', async function () {
        if (!imageInput.files || !imageInput.files[0]) return;
        if (!window.Html5Qrcode) {
            message.textContent = 'Không tải được thư viện quét QR.';
            return;
        }

        const scanner = new Html5Qrcode('qrScannerReader');
        try {
            const result = await scanner.scanFile(imageInput.files[0], true);
            onQrSuccess(result);
        } catch (error) {
            message.textContent = 'Không tìm thấy mã QR trong ảnh. Hãy chọn ảnh rõ hơn.';
        } finally {
            try { scanner.clear(); } catch (error) {}
        }
    });

    stopButton.addEventListener('click', stopScanner);
    window.addEventListener('pagehide', stopScanner);
})();
</script>
@endsection
