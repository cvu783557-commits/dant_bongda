<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚽ Hệ thống đặt sân bóng online</title>
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
        .hero {
            background: linear-gradient(135deg, #0b6623 0%, #228b22 100%);
            color: #fff;
            padding: 80px 0 60px;
        }
        .pitch-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .pitch-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
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
                <li class="nav-item"><a class="nav-link" href="#pitches">Danh sách sân</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo route('admin/bookings'); ?>">Trang quản lý</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-3">Đặt sân bóng nhanh chóng</h1>
        <p class="lead mb-4 opacity-90">Chọn sân 5 hoặc 7 người — Xem lịch trống trực tuyến — Đặt chỗ chỉ trong 1 phút</p>
        <a href="#pitches" class="btn btn-warning btn-lg px-5 fw-semibold">Đặt sân ngay</a>
    </div>
</section>

<div class="container py-5" id="pitches">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold">Danh sách sân</h2>
            <p class="text-muted mb-0">Chọn sân phù hợp với nhu cầu của bạn</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (count($pitches) === 0): ?>
        <div class="alert alert-info">Hiện chưa có sân nào, vui lòng quay lại sau.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($pitches as $p): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card pitch-card h-100 border-0 shadow-sm">
                        <img src="<?php echo $p['image'] ? (str_starts_with($p['image'], 'http') ? $p['image'] : file_url($p['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                             class="card-img-top"
                             alt="<?php echo htmlspecialchars($p['name']); ?>"
                             style="height: 220px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-success-subtle text-success border border-success-subtle align-self-start mb-2">
                                Sân <?php echo (int)$p['type']; ?> người
                            </span>
                            <h5 class="card-title fw-semibold mb-2"><?php echo htmlspecialchars($p['name']); ?></h5>
                            <div class="text-success fw-bold fs-5 mb-3">
                                <?php echo formatMoney($p['price_per_hour']); ?> <span class="text-muted fw-normal fs-6">/ giờ</span>
                            </div>
                            <?php if (!empty($p['description'])): ?>
                                <p class="text-muted small mb-4 flex-grow-1" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?php echo htmlspecialchars($p['description']); ?>
                                </p>
                            <?php else: ?>
                                <div class="flex-grow-1"></div>
                            <?php endif; ?>
                            <a href="<?php echo route('pitches/' . $p['id']); ?>"
                               class="btn btn-success w-100">Đặt sân này</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="border-top py-4 bg-light mt-5">
    <div class="container text-center text-secondary small">
        © <?php echo date('Y'); ?> SânBóng.Pro — Hệ thống đặt sân bóng online
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
