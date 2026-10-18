<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚽ Dịch vụ đi kèm - Hệ thống đặt sân bóng online</title>
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
            padding: 70px 0 50px;
        }
        .service-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
        }
        .service-photo {
            height: 180px;
            object-fit: cover;
            background: #f1f5f9;
        }
        .category-badge {
            font-weight: 500;
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
                <li class="nav-item"><a class="nav-link" href="<?php echo route('/'); ?>#pitches">Danh sách sân</a></li>
                <li class="nav-item"><a class="nav-link active fw-semibold" href="<?php echo route('services'); ?>">Dịch vụ</a></li>
                <?php if (!empty($currentUser)): ?>
                    <li class="nav-item"><span class="nav-link">Xin chào, <?php echo e($currentUser['full_name']); ?> (<?php echo e($currentUser['role']); ?>)</span></li>
                    <?php if (($currentUser['role'] ?? '') === 'ADMIN'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo route('admin/bookings'); ?>">Quản trị</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <form method="post" action="<?php echo route('logout'); ?>">
                            <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                            <button class="nav-link border-0 bg-transparent" type="submit">Đăng xuất</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo route('login'); ?>">Đăng nhập</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo route('register'); ?>">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container text-center">
        <h1 class="display-6 fw-bold mb-3">Dịch vụ đi kèm</h1>
        <p class="lead mb-0 opacity-90">Đồ uống, đồ ăn và thiết bị sẵn sàng cho mỗi trận bóng — chọn ngay khi đặt sân</p>
    </div>
</section>

<div class="container py-5">
    <?php if ($success): ?>
        <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1">Danh sách dịch vụ</h2>
            <p class="text-muted mb-0">Giá và tình trạng tồn kho được cập nhật liên tục từ hệ thống quản lý.</p>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
            <?php echo (int)$totalServices; ?> dịch vụ đang cung cấp
        </span>
    </div>

    <?php if (empty($groupedServices)): ?>
        <div class="alert alert-info">Hiện chưa có dịch vụ nào đang được cung cấp. Vui lòng quay lại sau.</div>
    <?php else: ?>
        <?php
            $badgeMap = [
                'primary'   => 'bg-primary-subtle text-primary border-primary-subtle',
                'secondary' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                'success'   => 'bg-success-subtle text-success border-success-subtle',
                'danger'    => 'bg-danger-subtle text-danger border-danger-subtle',
                'warning'   => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                'info'      => 'bg-info-subtle text-info-emphasis border-info-subtle',
            ];
        ?>
        <?php foreach ($groupedServices as $group): ?>
            <?php $badgeClass = $badgeMap[$group['color']] ?? $badgeMap['secondary']; ?>
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h4 class="fw-bold mb-0"><?php echo e($group['name']); ?></h4>
                    <span class="badge border category-badge <?php echo $badgeClass; ?>"><?php echo count($group['services']); ?></span>
                </div>
                <div class="row g-4">
                    <?php foreach ($group['services'] as $service): ?>
                        <?php
                            $imgUrl = $service['image']
                                ? (str_starts_with($service['image'], 'http') ? $service['image'] : file_url($service['image']))
                                : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=' . urlencode($group['name'] . ' product commercial simple white background') . '&image_size=square';
                            $inStock = (int)$service['stock'] > 0;
                        ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <div class="card service-card h-100 border-0 shadow-sm overflow-hidden">
                                <img src="<?php echo $imgUrl; ?>" class="card-img-top service-photo" alt="<?php echo e($service['name']); ?>">
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <span class="badge border <?php echo $badgeClass; ?>"><?php echo e($group['name']); ?></span>
                                        <?php if ($inStock): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Còn hàng</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Tạm hết</span>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="card-title fw-bold mb-2"><?php echo e($service['name']); ?></h5>
                                    <?php if (!empty($service['description'])): ?>
                                        <p class="text-muted small mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            <?php echo e($service['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="text-success fw-bold fs-5 mb-2">
                                        <?php echo formatMoney($service['price']); ?>
                                        <span class="text-muted fw-normal fs-6">/ <?php echo e($service['unit']); ?></span>
                                    </div>
                                    <div class="small text-muted mt-auto">
                                        <?php if ($inStock): ?>
                                            Còn <?php echo (int)$service['stock']; ?> <?php echo e($service['unit']); ?>
                                        <?php else: ?>
                                            Đang chờ nhập thêm
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card border-0 bg-light">
            <div class="card-body text-center py-4">
                <p class="mb-3 fw-semibold">Muốn đặt sân kèm dịch vụ?</p>
                <a href="<?php echo route('/'); ?>#pitches" class="btn btn-success px-4">Đặt sân ngay</a>
            </div>
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
