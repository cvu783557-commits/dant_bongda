<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt sân thành công</title>
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
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm text-center p-4">
                <div class="mb-3">
                    <span class="display-1">✅</span>
                </div>
                <h3 class="fw-bold text-success mb-2">Thông tin đặt sân</h3>
                <p class="text-muted mb-4">
                    <?php if ((float)$booking['paid_amount'] >= (float)$booking['deposit'] && (float)$booking['deposit'] > 0): ?>
                        Đặt cọc VNPay đã được xác nhận. Chúng tôi sẽ liên hệ để xác nhận lịch sân.
                    <?php else: ?>
                        Đơn đang chờ thanh toán tiền cọc VNPay; lịch chỉ được xác nhận sau khi thanh toán thành công.
                    <?php endif; ?>
                </p>

                <div class="text-start bg-light rounded p-4 mb-4">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Mã đơn hàng:</div>
                        <div class="col-7 fw-bold">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Trạng thái:</div>
                        <div class="col-7">
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Chờ xác nhận</span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Sân bóng:</div>
                        <div class="col-7 fw-semibold"><?php echo htmlspecialchars($booking['pitch_name']); ?> (Sân <?php echo (int)$booking['pitch_type']; ?> người)</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Ngày đặt:</div>
                        <div class="col-7"><?php echo formatDate($booking['booking_date']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Khung giờ:</div>
                        <div class="col-7"><?php echo substr($booking['start_time'],0,5); ?> - <?php echo substr($booking['end_time'],0,5); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Khách hàng:</div>
                        <div class="col-7"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Số điện thoại:</div>
                        <div class="col-7"><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                    </div>
                    <?php if (!empty($booking['customer_email'])): ?>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Email:</div>
                        <div class="col-7"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="row border-top pt-3 mt-3">
                        <div class="col-5 fw-semibold">Tổng tiền:</div>
                        <div class="col-7 fw-bold text-success fs-5"><?php echo formatMoney($booking['total_price']); ?></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 fw-semibold">Tiền cọc VNPay:</div>
                        <div class="col-7 fw-bold"><?php echo formatMoney($booking['deposit']); ?></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-5 fw-semibold">Đã thanh toán:</div>
                        <div class="col-7"><?php echo formatMoney($booking['paid_amount']); ?></div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?php echo route('/'); ?>" class="btn btn-outline-success">Về trang chủ</a>
                    <a href="<?php echo route('pitches/' . $booking['pitch_id']); ?>" class="btn btn-success">Đặt tiếp</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
