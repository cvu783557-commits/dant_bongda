<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đặt sân của tôi</title>
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
            <a class="nav-link active" href="<?php echo route('my-bookings'); ?>">Lịch sử đặt sân</a>
            <a class="nav-link" href="<?php echo route('notifications'); ?>">
                Thông báo<?php if (!empty($notificationCount)): ?> <span class="badge rounded-pill bg-danger"><?php echo (int)$notificationCount; ?></span><?php endif; ?>
            </a>
            <form method="post" action="<?php echo route('logout'); ?>">
                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                <button type="submit" class="nav-link border-0 bg-transparent">Đăng xuất</button>
            </form>
        </div>
    </div>
</nav>
<main class="container py-4 py-md-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Lịch sử đặt sân</h1>
            <p class="text-secondary mb-0">Xin chào <?php echo e($currentUser['full_name']); ?>. Theo dõi đơn và yêu cầu hủy tại đây.</p>
        </div>
        <a href="<?php echo route('/'); ?>" class="btn btn-success">Đặt sân mới</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

    <?php
        $requestByBooking = [];
        foreach ($cancellationRequests as $request) {
            if (!isset($requestByBooking[$request['booking_id']])) {
                $requestByBooking[$request['booking_id']] = $request;
            }
        }
        $bookingStatuses = \App\Models\Booking::getBookingStatuses();
        $paymentStatuses = \App\Models\Booking::getPaymentStatuses();
    ?>
    <?php if (!$bookings): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center">
                <h2 class="h5 fw-bold">Chưa có đơn đặt sân</h2>
                <p class="text-secondary">Các đơn bạn đặt khi đăng nhập sẽ xuất hiện tại đây.</p>
                <a href="<?php echo route('/'); ?>" class="btn btn-success">Xem sân</a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-grid gap-3">
            <?php foreach ($bookings as $booking): ?>
                <?php $request = $requestByBooking[$booking['id']] ?? null; ?>
                <article class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1"><?php echo e($booking['pitch_name']); ?></h2>
                                <div class="text-secondary">
                                    Sân <?php echo (int)($booking['court_number'] ?? 1); ?> ·
                                    <?php echo e(formatDate($booking['booking_date'])); ?>
                                    · <?php echo e(formatTime($booking['start_time'])); ?>–<?php echo e(formatTime($booking['end_time'])); ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge text-bg-<?php echo e(\App\Models\Booking::getBookingStatusColor($booking['status'])); ?>">
                                    <?php echo e($bookingStatuses[$booking['status']] ?? $booking['status']); ?>
                                </span>
                                <span class="badge text-bg-<?php echo e(\App\Models\Booking::getPaymentStatusColor($booking['payment_status'])); ?>">
                                    <?php echo e($paymentStatuses[$booking['payment_status']] ?? $booking['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="row g-2 small mb-3">
                            <div class="col-sm-4"><span class="text-secondary">Mã đơn:</span> #<?php echo (int)$booking['id']; ?></div>
                            <div class="col-sm-4"><span class="text-secondary">Tổng tiền:</span> <?php echo formatMoney($booking['total_price']); ?></div>
                            <div class="col-sm-4"><span class="text-secondary">Đã thanh toán:</span> <?php echo formatMoney($booking['paid_amount']); ?></div>
                            <div class="col-sm-4"><span class="text-secondary">Tiền cọc:</span> <?php echo formatMoney($booking['deposit']); ?></div>
                            <?php if ($request): ?>
                                <div class="col-sm-8">
                                    <span class="text-secondary">Yêu cầu hủy:</span>
                                    <?php
                                        $requestLabels = [
                                            'pending' => 'Chờ quản lý duyệt',
                                            'processing' => 'Đang xử lý hoàn tiền',
                                            'approved' => 'Đã hủy',
                                            'refunded' => 'Đã hủy và hoàn tiền',
                                            'rejected' => 'Bị từ chối',
                                            'manual_review' => 'Cần quản lý kiểm tra thủ công',
                                        ];
                                    ?>
                                    <?php echo e($requestLabels[$request['status']] ?? $request['status']); ?>
                                    <?php if (!empty($request['refund_message'])): ?>
                                        <span class="text-secondary"><?php echo e($request['refund_message']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                            $hasActiveRequest = $request && in_array(
                                $request['status'],
                                ['pending', 'processing', 'approved', 'refunded', 'manual_review'],
                                true
                            );
                            $canRequestCancel = !in_array(
                                $booking['status'],
                                [\App\Models\Booking::STATUS_CANCELLED, \App\Models\Booking::STATUS_COMPLETED, \App\Models\Booking::STATUS_IN_PROGRESS],
                                true
                            ) && strtotime($booking['booking_date'] . ' ' . $booking['start_time']) > time()
                                && !$hasActiveRequest;
                        ?>
                        <?php if ($canRequestCancel): ?>
                            <details>
                                <summary class="text-danger fw-semibold" style="cursor:pointer;">Yêu cầu hủy đơn</summary>
                                <form method="post" action="<?php echo route('my-bookings/' . (int)$booking['id'] . '/cancel-request'); ?>" class="mt-3">
                                    <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                                    <label class="form-label" for="reason-<?php echo (int)$booking['id']; ?>">Lý do hủy</label>
                                    <textarea class="form-control mb-2" id="reason-<?php echo (int)$booking['id']; ?>"
                                              name="request_reason" rows="2" maxlength="500" required></textarea>
                                    <p class="form-text">Yêu cầu cần quản lý duyệt. Nếu đã đặt cọc VNPay, hệ thống sẽ gửi yêu cầu hoàn về phương thức thanh toán ban đầu sau khi được duyệt.</p>
                                    <button type="submit" class="btn btn-outline-danger">Gửi yêu cầu hủy</button>
                                </form>
                            </details>
                        <?php endif; ?>
                        <?php if ($booking['status'] === \App\Models\Booking::STATUS_COMPLETED): ?>
                            <?php if (!empty($booking['rating_id'])): ?>
                                <div class="mt-3 border-top pt-3">
                                    <div class="fw-semibold text-success">
                                        Đánh giá của bạn: <?php echo str_repeat('★', (int)$booking['customer_rating']); ?><?php echo str_repeat('☆', 5 - (int)$booking['customer_rating']); ?>
                                    </div>
                                    <?php if (!empty($booking['rating_comment'])): ?>
                                        <div class="small text-secondary"><?php echo e($booking['rating_comment']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <details class="mt-3 border-top pt-3">
                                    <summary class="text-success fw-semibold" style="cursor:pointer;">Đánh giá sân này</summary>
                                    <form method="post" action="<?php echo route('my-bookings/' . (int)$booking['id'] . '/rating'); ?>" class="mt-3">
                                        <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                                        <label class="form-label" for="rating-<?php echo (int)$booking['id']; ?>">Số sao</label>
                                        <select class="form-select mb-2" id="rating-<?php echo (int)$booking['id']; ?>" name="rating" required>
                                            <option value="">Chọn số sao</option>
                                            <option value="5">★★★★★ - Rất hài lòng</option>
                                            <option value="4">★★★★☆ - Hài lòng</option>
                                            <option value="3">★★★☆☆ - Bình thường</option>
                                            <option value="2">★★☆☆☆ - Chưa hài lòng</option>
                                            <option value="1">★☆☆☆☆ - Không hài lòng</option>
                                        </select>
                                        <label class="form-label" for="comment-<?php echo (int)$booking['id']; ?>">Nhận xét (không bắt buộc)</label>
                                        <textarea class="form-control mb-2" id="comment-<?php echo (int)$booking['id']; ?>"
                                                  name="comment" rows="2" maxlength="1000"></textarea>
                                        <button type="submit" class="btn btn-outline-success">Gửi đánh giá</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
