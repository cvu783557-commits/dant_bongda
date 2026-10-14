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
                            <div class="fw-semibold"><?php echo htmlspecialchars($pitch['hotline'] ?? '0901 234 567'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Quy mô</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($pitch['pitch_size'] ?? ('Sân ' . (int)$pitch['type'] . ' người')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Số lượng sân</div>
                            <div class="fw-semibold"><?php echo (int)($pitch['field_count'] ?? 1); ?> sân</div>
                        </div>
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

                    <form method="post" action="<?php echo route('bookings'); ?>" novalidate>
                        <input type="hidden" name="pitch_id" value="<?php echo (int)$pitch['id']; ?>">
                        <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($date); ?>">

                        <p class="form-label fw-semibold mb-2">
                            Chọn khung giờ — Ngày <?php echo formatDate($date); ?>
                        </p>

                        <div class="row g-2 mb-4">
                            <?php foreach ($slots as $s): ?>
                                <?php
                                    $taken = in_array($s['id'], $bookedSlotIds);
                                    $start = substr($s['start_time'], 0, 5);
                                    $end   = substr($s['end_time'], 0, 5);
                                ?>
                                <div class="col-4">
                                    <label class="w-100">
                                        <input type="radio"
                                               name="time_slot_id"
                                               value="<?php echo $s['id']; ?>"
                                               class="btn-check"
                                               <?php echo $taken ? 'disabled' : ''; ?>
                                               <?php echo (isset($old['time_slot_id']) && (int)$old['time_slot_id'] === (int)$s['id']) ? 'checked' : ''; ?>>
                                        <span class="btn btn-outline-<?php echo $taken ? 'secondary disabled opacity-50' : 'success'; ?> w-100 small">
                                            <?php echo "{$start}-{$end}"; ?>
                                            <?php if ($taken) echo ' ❌'; ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
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

                        <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-semibold">Thành tiền:</span>
                            <span class="fw-bold fs-5"><?php echo formatMoney($pitch['price_per_hour']); ?></span>
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
