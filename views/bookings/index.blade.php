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
        $serviceRequestsByBooking = [];
        foreach (($serviceRequests ?? []) as $serviceRequest) {
            $serviceRequestsByBooking[$serviceRequest['booking_id']][] = $serviceRequest;
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
                        <?php if ($booking['status'] === \App\Models\Booking::STATUS_CONFIRMED
                            && $booking['booking_date'] >= date('Y-m-d')): ?>
                            <?php if (!empty($booking['checkin_token'])): ?>
                                <div class="alert alert-success mb-3">
                                    <div class="fw-bold mb-2">Mã QR check-in — đưa nhân viên quét khi đến sân</div>
                                    <div class="p-3 border rounded bg-white d-flex flex-column align-items-center">
                                        <div class="booking-checkin-qr"
                                             data-checkin-token="<?php echo e($booking['checkin_token']); ?>"></div>
                                        <div class="small text-secondary mt-2 text-center">
                                            Đơn #<?php echo (int)$booking['id']; ?> ·
                                            <?php echo e(formatDate($booking['booking_date'])); ?> ·
                                            Sân <?php echo (int)($booking['court_number'] ?? 1); ?>
                                        </div>
                                        <p class="small text-secondary mt-1 mb-0 text-center">
                                            Bạn có thể đưa màn hình này cho nhân viên hoặc gửi mã QR cho người đến sân thay bạn.
                                        </p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-3">
                                    Không tạo được mã QR check-in cho đơn này. Vui lòng tải lại trang hoặc liên hệ nhân viên.
                                </div>
                            <?php endif; ?>
                        <?php elseif ($booking['status'] === \App\Models\Booking::STATUS_PENDING): ?>
                            <div class="alert alert-info mb-3">
                                Mã QR check-in sẽ hiển thị tại đây sau khi đơn được xác nhận.
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($serviceRequestsByBooking[$booking['id']])): ?>
                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="fw-semibold mb-2">Yêu cầu dịch vụ đã gửi</div>
                                <?php foreach ($serviceRequestsByBooking[$booking['id']] as $serviceRequest): ?>
                                    <?php
                                    $serviceRequestStatus = [
                                        'pending' => ['Đang chờ nhân viên', 'warning'],
                                        'accepted' => ['Đã xác nhận', 'success'],
                                        'rejected' => ['Bị từ chối', 'danger'],
                                    ];
                                    [$serviceRequestLabel, $serviceRequestColor] =
                                        $serviceRequestStatus[$serviceRequest['status']] ?? ['Không rõ', 'secondary'];
                                    ?>
                                    <div class="small <?php echo $serviceRequest['status'] === 'pending' ? 'mb-2' : ''; ?>">
                                        <span class="badge text-bg-<?php echo $serviceRequestColor; ?>"><?php echo $serviceRequestLabel; ?></span>
                                        <?php echo e($serviceRequest['item_summary'] ?? ''); ?>
                                        <span class="text-secondary">· <?php echo e(formatDateTime($serviceRequest['created_at'])); ?></span>
                                        <?php if (!empty($serviceRequest['staff_note'])): ?>
                                            <div class="text-secondary">Ghi chú nhân viên: <?php echo e($serviceRequest['staff_note']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($booking['status'] === \App\Models\Booking::STATUS_IN_PROGRESS
                            && $booking['booking_date'] === date('Y-m-d')): ?>
                            <details class="border rounded p-3 mb-3">
                                <summary class="fw-semibold text-success" style="cursor:pointer;">
                                    🛒 Gọi thêm dịch vụ — gửi yêu cầu cho nhân viên
                                </summary>
                                <?php if (empty($availableServices)): ?>
                                    <p class="text-secondary small mt-3 mb-0">Hiện chưa có dịch vụ còn hàng.</p>
                                <?php else: ?>
                                    <form method="post" action="<?php echo route('my-bookings/' . (int)$booking['id'] . '/service-requests'); ?>" class="mt-3">
                                        <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                                        <div class="row g-2">
                                            <?php foreach ($availableServices as $service): ?>
                                                <div class="col-md-6">
                                                    <label class="form-label border rounded p-2 w-100 h-100">
                                                        <span class="d-flex justify-content-between gap-2">
                                                            <span class="fw-semibold"><?php echo e($service['name']); ?></span>
                                                            <span class="text-success text-nowrap"><?php echo formatMoney($service['price']); ?></span>
                                                        </span>
                                                        <span class="small text-secondary d-block">
                                                            Còn <?php echo (int)$service['stock']; ?> <?php echo e($service['unit']); ?>
                                                        </span>
                                                        <span class="d-flex align-items-center gap-2 mt-2">
                                                            <span class="small">Số lượng</span>
                                                            <input type="number" class="form-control form-control-sm"
                                                                   name="quantity[<?php echo (int)$service['id']; ?>]"
                                                                   value="0" min="0" max="<?php echo min(20, (int)$service['stock']); ?>">
                                                        </span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <label class="form-label mt-2" for="service-note-<?php echo (int)$booking['id']; ?>">
                                            Ghi chú cho nhân viên (không bắt buộc)
                                        </label>
                                        <textarea class="form-control mb-2"
                                                  id="service-note-<?php echo (int)$booking['id']; ?>"
                                                  name="customer_note" maxlength="500" rows="2"
                                                  placeholder="Ví dụ: mang ra sân số 2"></textarea>
                                        <p class="form-text">Yêu cầu chưa được phục vụ cho đến khi nhân viên xác nhận. Tiền dịch vụ sẽ được cộng vào đơn sau khi xác nhận.</p>
                                        <button type="submit" class="btn btn-success">Gửi yêu cầu dịch vụ</button>
                                    </form>
                                <?php endif; ?>
                            </details>
                        <?php endif; ?>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.querySelectorAll('.booking-checkin-qr').forEach(function (element) {
    if (typeof QRCode === 'undefined') {
        element.textContent = 'Không tải được bộ tạo mã QR. Vui lòng tải lại trang.';
        return;
    }
    new QRCode(element, {
        text: element.dataset.checkinToken,
        width: 180,
        height: 180,
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>
</body>
</html>
