<?php
    $fieldCount = max(1, (int)($pitch['field_count'] ?? 1));
    $hotline = trim((string)($pitch['hotline'] ?? ''));
    $hotlineDigits = preg_replace('/\D+/', '', $hotline);
    $hasContactPhone = preg_match('/^0[0-9]{9,10}$/', $hotlineDigits) === 1;
?>
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
            font-synthesis-weight: none;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            color: #1f2937;
        }
        body, input, textarea, select, button {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
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
                <li class="nav-item"><a class="nav-link" href="<?php echo route('admin/bookings'); ?>">Trang quản lý</a></li>
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
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <?php echo htmlspecialchars($pitch['pitch_size'] ?? ('Sân ' . (int)$pitch['type'] . ' người')); ?>
                </span>
                <span class="text-success fw-bold fs-5"><?php echo formatMoney($pitch['price_per_hour']); ?> / giờ</span>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Thông tin tổng quan sân bóng</h4>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="savePitch()">💾 Lưu sân</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="sharePitch()">🔗 Chia sẻ sân</button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-muted">Địa chỉ</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($pitch['address'] ?? 'Địa chỉ sân chưa cập nhật'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Trạng thái</div>
                            <div class="fw-semibold <?php echo (($pitch['is_open'] ?? 1) == 1 && ($pitch['status'] ?? 'active') === 'active') ? 'text-success' : 'text-warning'; ?>">
                                <?php echo (($pitch['is_open'] ?? 1) == 1 && ($pitch['status'] ?? 'active') === 'active') ? 'Đang hoạt động' : 'Tạm đóng'; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Giờ mở cửa</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars(substr($pitch['opening_time'] ?? '06:00:00', 0, 5)); ?> - <?php echo htmlspecialchars(substr($pitch['closing_time'] ?? '23:00:00', 0, 5)); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Hotline</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($hotline !== '' ? $hotline : 'Chưa cập nhật'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Quy mô</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($pitch['pitch_size'] ?? ('Sân ' . (int)$pitch['type'] . ' người')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Số lượng sân</div>
                            <div class="fw-semibold"><?php echo (int)($pitch['field_count'] ?? 1); ?> sân</div>
                        </div>
                        <?php if ($hasContactPhone): ?>
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-success" href="tel:<?php echo htmlspecialchars($hotlineDigits); ?>">Liên hệ</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <div class="small text-muted mb-2">Danh sách sân con</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php for ($childNo = 1; $childNo <= $fieldCount; $childNo++): ?>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo htmlspecialchars($pitch['name']); ?> - PB <?php echo $childNo; ?>
                                        · <?php echo (int)$pitch['type']; ?> người
                                    </span>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-2">Bảng giá thuê sân</h4>
                    <p class="text-muted small mb-3">
                        Mỗi khung giờ tính 1 giờ. Hiện tại sân áp dụng cùng một mức giá cho ngày thường và cuối tuần.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-3">
                            <thead class="table-success">
                                <tr>
                                    <th scope="col">Khung giờ</th>
                                    <th scope="col">Thứ Hai - Thứ Sáu</th>
                                    <th scope="col">Thứ Bảy - Chủ nhật</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($slots as $s): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars(substr($s['start_time'], 0, 5)); ?> -
                                            <?php echo htmlspecialchars(substr($s['end_time'], 0, 5)); ?>
                                        </td>
                                        <td class="fw-semibold text-success"><?php echo formatMoney($pitch['price_per_hour']); ?> / giờ</td>
                                        <td class="fw-semibold text-success"><?php echo formatMoney($pitch['price_per_hour']); ?> / giờ</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-light border mb-0 small">
                        <strong>Chi phí:</strong> Giá đã bao gồm phí thuê sân trong một giờ.
                        Dịch vụ hoặc phụ phí khác (nếu có) sẽ được thông báo riêng trước khi xác nhận đặt sân.
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-3">Chính sách và lưu ý đặt sân</h4>
                    <div class="mb-3">
                        <h6 class="fw-semibold">Chính sách đặt cọc</h6>
                        <p class="text-muted mb-0">Website không thu tiền đặt cọc trực tuyến. Vui lòng liên hệ sân để xác nhận yêu cầu đặt cọc trước khi thanh toán.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-semibold">Chính sách hủy sân / hoàn tiền</h6>
                        <p class="text-muted mb-0">Điều kiện hủy sân và hoàn tiền do sân xác nhận trực tiếp. Hãy hỏi rõ điều kiện trước khi chuyển tiền đặt cọc.</p>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <strong>Lưu ý an toàn:</strong> Yêu cầu đặt sân chỉ được xác nhận sau khi sân liên hệ lại. Chỉ thanh toán theo thông tin đã xác minh trực tiếp với sân.
                    </div>
                </div>
            </div>

            <?php if (!empty($pitch['description'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold mb-2">Giới thiệu sân bóng</h5>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($pitch['description'])); ?></p>
                </div>
            <?php endif; ?>

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

                    <form method="post" action="<?php echo route('bookings'); ?>" id="bookingForm" novalidate>
                        <input type="hidden" name="pitch_id" value="<?php echo (int)$pitch['id']; ?>">
                        <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($date); ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="child-pitch" class="form-label fw-semibold">Chọn sân con</label>
                                <select name="child_pitch_no" id="child-pitch" class="form-select" required>
                                    <?php for ($childNo = 1; $childNo <= $fieldCount; $childNo++): ?>
                                        <option value="<?php echo $childNo; ?>"
                                            <?php echo (int)($old['child_pitch_no'] ?? 1) === $childNo ? 'selected' : ''; ?>>
                                            Sân <?php echo htmlspecialchars($pitch['name']); ?> - PB <?php echo $childNo; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="duration-hours" class="form-label fw-semibold">Thời lượng thuê</label>
                                <select name="duration_hours" id="duration-hours" class="form-select" required>
                                    <?php for ($hours = 1; $hours <= count($slots); $hours++): ?>
                                        <option value="<?php echo $hours; ?>"
                                            <?php echo (int)($old['duration_hours'] ?? 1) === $hours ? 'selected' : ''; ?>>
                                            <?php echo $hours; ?> giờ
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <p class="form-label fw-semibold mb-2">
                            Chọn giờ bắt đầu — Ngày <?php echo formatDate($date); ?>
                        </p>
                        <div class="d-flex gap-3 small mb-2">
                            <span class="text-success">● Còn trống</span>
                            <span class="text-danger">● Đã đặt / không đủ thời lượng</span>
                        </div>

                        <div class="row g-2 mb-4">
                            <?php foreach ($slots as $s): ?>
                                <?php
                                    $start = substr($s['start_time'], 0, 5);
                                    $end   = substr($s['end_time'], 0, 5);
                                ?>
                                <div class="col-4">
                                    <label class="w-100">
                                        <input type="radio"
                                               name="time_slot_id"
                                               value="<?php echo $s['id']; ?>"
                                               class="btn-check time-option"
                                               data-start="<?php echo htmlspecialchars($s['start_time']); ?>"
                                               data-end="<?php echo htmlspecialchars($s['end_time']); ?>"
                                               <?php echo (isset($old['time_slot_id']) && (int)$old['time_slot_id'] === (int)$s['id']) ? 'checked' : ''; ?>>
                                        <span class="btn btn-outline-success w-100 small time-option-label">
                                            <?php echo "{$start}-{$end}"; ?> <span class="availability-label">· Còn trống</span>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <p id="selected-period-summary" class="small text-muted" aria-live="polite">
                            Chưa chọn khung giờ.
                        </p>
                        <button type="button" id="continue-booking" class="btn btn-success w-100 fw-bold mb-4">Đặt sân ngay</button>

                        <div id="customer-fields" class="<?php echo !empty($old['customer_name']) ? '' : 'd-none'; ?>">
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

                        <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-semibold">Thành tiền:</span>
                            <span id="estimated-total" class="fw-bold fs-5"><?php echo formatMoney($pitch['price_per_hour']); ?></span>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Xác nhận đặt sân</button>
                        </div>
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

<script>
    function savePitch() {
        const key = 'saved_pitches';
        const saved = JSON.parse(localStorage.getItem(key) || '[]');
        const id = <?php echo (int)$pitch['id']; ?>;
        if (!saved.includes(id)) {
            saved.push(id);
            localStorage.setItem(key, JSON.stringify(saved));
        }
        alert('Đã lưu sân thành công.');
    }

    function sharePitch() {
        const url = window.location.href;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
                alert('Đã sao chép liên kết sân bóng.');
            });
            return;
        }
        alert('Liên kết sân bóng: ' + url);
    }

    const reservations = <?php echo json_encode($reservations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const timeOptions = Array.from(document.querySelectorAll('.time-option'));
    const childPitchSelect = document.getElementById('child-pitch');
    const durationSelect = document.getElementById('duration-hours');
    const customerFields = document.getElementById('customer-fields');
    const hourlyPrice = <?php echo (int)$pitch['price_per_hour']; ?>;
    const openingMinutes = <?php echo ((int)substr($pitch['opening_time'] ?? '06:00:00', 0, 2) * 60) + (int)substr($pitch['opening_time'] ?? '06:00:00', 3, 2); ?>;
    const closingMinutes = <?php echo ((int)substr($pitch['closing_time'] ?? '23:00:00', 0, 2) * 60) + (int)substr($pitch['closing_time'] ?? '23:00:00', 3, 2); ?>;
    const selectedBookingDate = <?php echo json_encode($date); ?>;
    const today = <?php echo json_encode(date('Y-m-d')); ?>;
    const currentMinutes = <?php echo ((int)date('H') * 60) + (int)date('i'); ?>;

    function minutesSinceMidnight(time) {
        const parts = time.split(':').map(Number);
        return parts[0] * 60 + parts[1];
    }

    function formatTime(minutes) {
        return String(Math.floor(minutes / 60)).padStart(2, '0') + ':' + String(minutes % 60).padStart(2, '0');
    }

    function updateAvailability() {
        const selectedChild = Number(childPitchSelect.value);
        const duration = Number(durationSelect.value);

        timeOptions.forEach(function (option) {
            const start = minutesSinceMidnight(option.dataset.start);
            const end = start + duration * 60;
            const insideOpeningHours = start >= openingMinutes && end <= closingMinutes;
            const notPassed = selectedBookingDate !== today || start > currentMinutes;
            const overlapsReservation = reservations.some(function (reservation) {
                return Number(reservation.child_pitch_no) === selectedChild
                    && start < minutesSinceMidnight(reservation.end_time)
                    && end > minutesSinceMidnight(reservation.start_time);
            });
            const available = insideOpeningHours && notPassed && !overlapsReservation;
            const label = option.nextElementSibling;
            const statusText = label.querySelector('.availability-label');

            option.disabled = !available;
            if (!available && option.checked) option.checked = false;
            label.classList.toggle('btn-outline-success', available);
            label.classList.toggle('btn-outline-secondary', !available);
            label.classList.toggle('opacity-50', !available);
            statusText.textContent = available ? '· Còn trống' : (overlapsReservation ? '· Đã đặt' : (!notPassed ? '· Đã qua' : '· Không đủ thời lượng'));
        });

        const total = new Intl.NumberFormat('vi-VN').format(hourlyPrice * duration) + ' ₫';
        document.getElementById('estimated-total').textContent = total;

        const selectedOption = document.querySelector('.time-option:checked');
        const summary = document.getElementById('selected-period-summary');
        summary.textContent = selectedOption
            ? 'Đang chọn PB ' + selectedChild + ': ' + formatTime(minutesSinceMidnight(selectedOption.dataset.start))
                + ' - ' + formatTime(minutesSinceMidnight(selectedOption.dataset.start) + duration * 60)
                + ' (' + duration + ' giờ)'
            : 'Chưa chọn khung giờ.';
    }

    childPitchSelect.addEventListener('change', updateAvailability);
    durationSelect.addEventListener('change', updateAvailability);
    timeOptions.forEach(function (option) {
        option.addEventListener('change', updateAvailability);
    });
    updateAvailability();

    document.getElementById('continue-booking').addEventListener('click', function () {
        const selectedSlot = document.querySelector('.time-option:checked');
        if (!selectedSlot || selectedSlot.disabled) {
            alert('Vui lòng chọn một khung giờ còn trống.');
            return;
        }

        customerFields.classList.remove('d-none');
        customerFields.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
