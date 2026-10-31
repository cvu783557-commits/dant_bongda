<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin người đặt sân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo route('site-theme'); ?>" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo route('/'); ?>">⚽ SânBóng.Pro</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link" href="<?php echo route('/'); ?>">Trang chủ</a>
            <a class="nav-link" href="<?php echo route('schedule'); ?>">Lịch sân</a>
            <a class="nav-link" href="<?php echo route('my-bookings'); ?>">Lịch sử đặt sân</a>
            <a class="nav-link" href="<?php echo route('notifications'); ?>">
                Thông báo<?php if (!empty($notificationCount)): ?> <span class="badge rounded-pill bg-danger"><?php echo (int)$notificationCount; ?></span><?php endif; ?>
            </a>
            <span class="nav-link"><?php echo e($currentUser['full_name']); ?></span>
        </div>
    </div>
</nav>

<main class="container py-4 py-md-5" style="max-width: 760px;">
    <div class="mb-4">
        <a class="text-decoration-none"
           href="<?php echo route('pitches/' . (int)$pitch['id'] . '?date=' . urlencode($bookingDate) . '&court=' . (int)$courtNumber); ?>">
            &larr; Quay lại chọn khung giờ
        </a>
        <h1 class="h2 fw-bold mt-3 mb-1">Thông tin người đặt sân</h1>
        <p class="text-secondary mb-0">Kiểm tra lịch đã chọn và nhập thông tin liên hệ trước khi thanh toán.</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Lịch đặt đã chọn</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Sân</dt>
                <dd class="col-sm-7"><?php echo e($pitch['name']); ?> — Sân <?php echo (int)$courtNumber; ?></dd>
                <dt class="col-sm-5">Ngày / tháng</dt>
                <dd class="col-sm-7">
                    <?php if ($bookingMode === 'monthly'): ?>
                        Mỗi ngày trong tháng <?php echo e(date('m/Y', strtotime($bookingMonth . '-01'))); ?>
                        (<?php echo (int)$bookingCount; ?> buổi)
                    <?php else: ?>
                        <?php echo e(formatDate($bookingDate)); ?>
                    <?php endif; ?>
                </dd>
                <dt class="col-sm-5">Giờ</dt>
                <dd class="col-sm-7"><?php echo e($startTime); ?>–<?php echo e($endTime); ?></dd>
                <dt class="col-sm-5">Tổng tiền</dt>
                <dd class="col-sm-7"><?php echo e(formatMoney($total)); ?></dd>
                <dt class="col-sm-5">Tiền cọc dự kiến</dt>
                <dd class="col-sm-7"><?php echo e(formatMoney($deposit)); ?></dd>
            </dl>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?php echo route('bookings/details/' . $token); ?>">
                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="customer_name">Họ tên người đặt <span class="text-danger">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" required
                           minlength="2" maxlength="100"
                           value="<?php echo e($old['customer_name'] ?? $currentUser['full_name']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="customer_phone">Số điện thoại <span class="text-danger">*</span></label>
                    <input type="tel" id="customer_phone" name="customer_phone" class="form-control" required
                           pattern="^0[0-9]{9,10}$"
                           value="<?php echo e($old['customer_phone'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="customer_email">Email (tùy chọn)</label>
                    <input type="email" id="customer_email" name="customer_email" class="form-control"
                           value="<?php echo e($old['customer_email'] ?? $currentUser['email'] ?? ''); ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="notes">Ghi chú (tùy chọn)</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500" class="form-control"><?php echo e($old['notes'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                    Xác nhận thông tin, tiếp tục thanh toán
                </button>
            </form>
        </div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
