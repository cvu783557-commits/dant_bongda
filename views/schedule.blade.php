<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sân bóng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; color: #1f2937; }
        .schedule-slot { min-height: 72px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo route('/'); ?>">⚽ SânBóng.Pro</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link" href="<?php echo route('/'); ?>">Danh sách sân</a>
            <a class="nav-link active" href="<?php echo route('schedule'); ?>">Lịch sân</a>
            <a class="nav-link" href="<?php echo route('my-bookings'); ?>">Lịch sử đặt sân</a>
            <?php if (($currentUser['role'] ?? '') === 'USER'): ?>
                <a class="nav-link" href="<?php echo route('notifications'); ?>">
                    Thông báo<?php if (!empty($notificationCount)): ?> <span class="badge rounded-pill bg-danger"><?php echo (int)$notificationCount; ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container py-4 py-md-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Lịch sân bóng</h1>
            <p class="text-secondary mb-0">Xem khung giờ trống trước khi đặt. Thông tin khách đặt sân không được hiển thị công khai.</p>
        </div>
        <form method="get" action="<?php echo route('schedule'); ?>" class="d-flex gap-2">
            <input type="date" name="date" class="form-control" value="<?php echo e($date); ?>"
                   min="<?php echo date('Y-m-d'); ?>" required>
            <button type="submit" class="btn btn-success">Xem lịch</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-warning" role="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <span class="badge bg-success-subtle text-success border border-success-subtle">Trống</span>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Đã đặt</span>
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Đang khóa</span>
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Đã qua</span>
    </div>

    <?php if (!$pitches): ?>
        <div class="alert alert-info">Hiện chưa có sân hoạt động.</div>
    <?php else: ?>
        <div class="d-grid gap-3">
            <?php foreach ($pitches as $pitch): ?>
                <section class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1"><?php echo e($pitch['name']); ?></h2>
                                <div class="small text-secondary">
                                    Sân con <?php echo (int)$pitch['court_number']; ?>
                                    ·
                                    Sân <?php echo (int)$pitch['type']; ?> người
                                    · <?php echo e(formatTime($pitch['opening_time'] ?? '06:00:00')); ?>–<?php echo e(formatTime($pitch['closing_time'] ?? '23:00:00')); ?>
                                    · <?php echo formatMoney($pitch['price_per_hour']); ?>/giờ
                                </div>
                            </div>
                            <a class="btn btn-outline-success btn-sm"
                               href="<?php echo route('pitches/' . (int)$pitch['id']) . '?date=' . urlencode($date) . '&court=' . (int)$pitch['court_number']; ?>">
                                Đặt sân <?php echo (int)$pitch['court_number']; ?>
                            </a>
                        </div>
                        <?php if (!$pitch['slots']): ?>
                            <p class="text-secondary small mb-0">Sân chưa cập nhật khung giờ hoạt động.</p>
                        <?php else: ?>
                            <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-4 row-cols-xl-6 g-2">
                                <?php foreach ($pitch['slots'] as $slot): ?>
                                    <?php
                                        $slotClasses = [
                                            'available' => 'bg-success-subtle text-success-emphasis border-success-subtle',
                                            'booked' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',
                                            'locked' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                            'past' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                        ];
                                        $statusLabels = [
                                            'available' => 'Trống',
                                            'booked' => 'Đã đặt',
                                            'locked' => 'Đang khóa',
                                            'past' => 'Đã qua',
                                        ];
                                        $slotUrl = route('pitches/' . (int)$pitch['id']) . '?date=' . urlencode($date) . '&court=' . (int)$pitch['court_number'];
                                    ?>
                                    <div class="col">
                                        <?php if ($slot['status'] === 'available'): ?>
                                            <a href="<?php echo $slotUrl; ?>#bookingForm"
                                               class="schedule-slot d-flex flex-column justify-content-center align-items-center text-center text-decoration-none border rounded p-2 <?php echo $slotClasses[$slot['status']]; ?>">
                                                <span class="fw-semibold"><?php echo e($slot['label']); ?></span>
                                                <small><?php echo e($statusLabels[$slot['status']]); ?></small>
                                            </a>
                                        <?php else: ?>
                                            <div class="schedule-slot d-flex flex-column justify-content-center align-items-center text-center border rounded p-2 <?php echo $slotClasses[$slot['status']]; ?>">
                                                <span class="fw-semibold"><?php echo e($slot['label']); ?></span>
                                                <small><?php echo e($statusLabels[$slot['status']]); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
