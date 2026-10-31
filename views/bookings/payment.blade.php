<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đặt sân</title>
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
           href="<?php echo route('pitches/' . (int)$checkout['pitch_id'] . '?date=' . urlencode($checkout['booking_date']) . '&court=' . (int)$checkout['court_number']); ?>">
            &larr; Quay lại trang sân
        </a>
        <h1 class="h2 fw-bold mt-3 mb-1">Thanh toán đặt sân</h1>
        <p class="text-secondary mb-0">Kiểm tra thông tin và chọn cách thanh toán tiền đặt cọc.</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Thông tin đơn đặt sân</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Sân</dt>
                <dd class="col-sm-7"><?php echo e($checkout['pitch_name']); ?> — Sân <?php echo (int)$checkout['court_number']; ?></dd>
                <dt class="col-sm-5">Thời gian</dt>
                <dd class="col-sm-7">
                    <?php if (!empty($checkout['series_id'])): ?>
                        Mỗi ngày trong tháng <?php echo e(date('m/Y', strtotime($checkout['booking_month'] . '-01'))); ?>
                        (<?php echo (int)$checkout['booking_count']; ?> buổi)
                    <?php else: ?>
                        <?php echo e(formatDate($booking['booking_date'])); ?>
                    <?php endif; ?>
                    · <?php echo e(formatTime($booking['start_time'])); ?>–<?php echo e(formatTime($booking['end_time'])); ?>
                </dd>
                <dt class="col-sm-5">Tổng tiền</dt>
                <dd class="col-sm-7"><?php echo e(formatMoney($checkout['total_price'])); ?></dd>
            </dl>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="alert alert-info">
                Đặt cọc 30% tổng tiền qua VNPay để gửi yêu cầu đặt sân. Phần còn lại thanh toán tại sân.
                <div class="small mt-1">Tiền đặt cọc: <strong><?php echo e(formatMoney($checkout['deposit'])); ?></strong></div>
            </div>

            <form method="post" action="<?php echo route('bookings/payment/' . $token); ?>">
                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                <fieldset class="mb-4">
                    <legend class="h5 fw-bold">Phương thức thanh toán VNPay</legend>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="paymentBank"
                               value="bank" required checked>
                        <label class="form-check-label" for="paymentBank">
                            Thẻ ATM / tài khoản ngân hàng (sandbox)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="paymentQr"
                               value="qr" required>
                        <label class="form-check-label" for="paymentQr">Quét mã QR</label>
                    </div>
                    <div class="form-text mt-2">
                        Tài khoản test chỉ nhập trên trang sandbox VNPay/ngân hàng. Không nhập mật khẩu hoặc OTP tại website này.
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                    Tiếp tục thanh toán qua VNPay
                </button>
            </form>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
