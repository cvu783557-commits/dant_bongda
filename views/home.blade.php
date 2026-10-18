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
        .pitch-photo {
            height: 220px;
            object-fit: cover;
        }
        .pitch-meta {
            color: #64748b;
            font-size: .9rem;
        }
        .pitch-feature {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin: .15rem .2rem .15rem 0;
            padding: .3rem .55rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            font-size: .78rem;
        }
        .location-sidebar {
            border-right: 1px solid #e2e8f0;
            padding-right: 1rem;
        }
        .location-link {
            display: block;
            padding: .55rem .7rem;
            border-radius: 4px;
            color: #475569;
            text-decoration: none;
        }
        .location-link:hover,
        .location-link.active {
            color: #146c43;
            background: #e8f5ee;
        }
        .location-link.active { font-weight: 600; }
        @media (max-width: 991.98px) {
            .location-sidebar {
                border-right: 0;
                border-bottom: 1px solid #e2e8f0;
                padding: 0 0 1rem;
            }
            .location-links {
                display: flex;
                gap: .35rem;
                overflow-x: auto;
                padding-bottom: .35rem;
            }
            .location-link { white-space: nowrap; }
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
                <li class="nav-item"><a class="nav-link" href="<?php echo route('schedule'); ?>">Xem lịch sân</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo route('my-bookings'); ?>">Lịch sử đặt sân</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo route('services'); ?>">Dịch vụ</a></li>
                <?php if (!empty($currentUser)): ?>
                    <?php if (($currentUser['role'] ?? '') === 'USER'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo route('notifications'); ?>">
                                Thông báo<?php if (!empty($notificationCount)): ?> <span class="badge rounded-pill bg-danger"><?php echo (int)$notificationCount; ?></span><?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
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
        <h1 class="display-5 fw-bold mb-3">Đặt sân bóng nhanh chóng</h1>
        <p class="lead mb-4 opacity-90">Chọn sân 5 hoặc 7 người — Xem lịch trống trực tuyến — Đặt chỗ chỉ trong 1 phút</p>
        <a href="#pitches" class="btn btn-warning btn-lg px-5 fw-semibold">Đặt sân ngay</a>
    </div>
</section>

<div class="container py-5" id="pitches">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold">Danh sách sân</h2>
            <p class="text-muted mb-0">Xem giá, tiện ích và tình trạng sân theo ngày giờ bạn muốn đặt</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="get" action="<?php echo route('/'); ?>" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label for="filter-date" class="form-label fw-semibold">Ngày muốn đặt</label>
                    <input id="filter-date" type="date" name="date" class="form-control"
                           min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($filters['date']); ?>" required>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="filter-time" class="form-label fw-semibold">Giờ bắt đầu</label>
                    <select id="filter-time" name="time" class="form-select" required>
                        <?php foreach ($timeOptions as $hour): ?>
                            <?php $optionTime = sprintf('%02d:00', $hour); ?>
                            <option value="<?php echo $optionTime; ?>" <?php echo $filters['time'] === $optionTime ? 'selected' : ''; ?>>
                                <?php echo $optionTime . ' - ' . sprintf('%02d:00', $hour + 1); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="filter-type" class="form-label fw-semibold">Loại sân</label>
                    <select id="filter-type" name="type" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="5" <?php echo $filters['type'] === '5' ? 'selected' : ''; ?>>Sân 5 người</option>
                        <option value="7" <?php echo $filters['type'] === '7' ? 'selected' : ''; ?>>Sân 7 người</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label for="filter-availability" class="form-label fw-semibold">Tình trạng</label>
                    <select id="filter-availability" name="availability" class="form-select">
                        <option value="">Tất cả sân</option>
                        <option value="available" <?php echo $filters['availability'] === 'available' ? 'selected' : ''; ?>>Chỉ sân còn trống</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-success w-100">Tìm sân</button>
                </div>
            </div>
            <div class="form-text mt-2">Tình trạng được kiểm tra cho khung giờ 1 tiếng đã chọn; sân tạm khóa/bảo trì sẽ không thể đặt trong khung đó.</div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted small mb-0">
            <?php echo count($pitches); ?> sân phù hợp ·
            <?php echo formatDate($filters['date']); ?> · <?php echo e($filters['time']); ?> - <?php echo e(date('H:i', strtotime($filters['time'] . ' +1 hour'))); ?>
        </p>
    </div>

    <?php
        $areaParams = [
            'date' => $filters['date'],
            'time' => $filters['time'],
            'type' => $filters['type'],
            'availability' => $filters['availability'],
        ];
        $areaUrl = static function ($district) use ($areaParams) {
            $params = $areaParams;
            if ($district !== '') {
                $params['district'] = $district;
            }
            return route('/') . '?' . http_build_query($params);
        };
    ?>
    <div class="row g-4 align-items-start">
        <aside class="col-lg-3" aria-label="Lọc sân theo khu vực">
            <nav class="location-sidebar">
                <div class="small text-uppercase fw-semibold text-secondary mb-2">Khu vực</div>
                <div class="location-links">
                    <a class="location-link <?php echo $filters['district'] === '' ? 'active' : ''; ?>"
                       href="<?php echo e($areaUrl('')); ?>">Tất cả khu vực</a>
                    <div class="w-100 small fw-bold text-dark px-2 pt-2">Hà Nội</div>
                    <?php foreach ($districtOptions as $districtOption): ?>
                        <a class="location-link <?php echo $filters['district'] === $districtOption ? 'active' : ''; ?>"
                           href="<?php echo e($areaUrl($districtOption)); ?>"><?php echo e($districtOption); ?></a>
                    <?php endforeach; ?>
                    <a class="location-link <?php echo $filters['district'] === '__unassigned' ? 'active' : ''; ?>"
                       href="<?php echo e($areaUrl('__unassigned')); ?>">Chưa cập nhật khu vực</a>
                </div>
            </nav>
        </aside>
        <main class="col-lg-9">
            <?php if (count($pitches) === 0): ?>
                <div class="alert alert-info">Không tìm thấy sân phù hợp với ngày, giờ và khu vực đã chọn.</div>
            <?php else: ?>
        <div class="row g-4">
            <?php foreach ($pitches as $p): ?>
                <?php
                    $availabilityLabels = [
                        'available' => ['Còn trống', 'bg-success-subtle text-success border-success-subtle'],
                        'booked' => ['Đã có lịch đặt', 'bg-danger-subtle text-danger border-danger-subtle'],
                        'maintenance' => ['Bảo trì / tạm khóa', 'bg-warning-subtle text-warning-emphasis border-warning-subtle'],
                        'outside_hours' => ['Ngoài giờ hoạt động', 'bg-secondary-subtle text-secondary border-secondary-subtle'],
                    ];
                    [$availabilityLabel, $availabilityClass] = $availabilityLabels[$p['availability_status']] ?? $availabilityLabels['outside_hours'];
                    $detailUrl = route('pitches/' . $p['id']) . '?date=' . urlencode($filters['date']);
                ?>
                <div class="col-sm-6 col-xl-4">
                    <div class="card pitch-card h-100 border-0 shadow-sm overflow-hidden">
                        <img src="<?php echo $p['image'] ? (str_starts_with($p['image'], 'http') ? $p['image'] : file_url($p['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                             class="card-img-top pitch-photo"
                             alt="<?php echo e($p['name']); ?>">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    Sân <?php echo (int)$p['type']; ?> người
                                </span>
                                <span class="badge border <?php echo $availabilityClass; ?>"><?php echo e($availabilityLabel); ?></span>
                            </div>
                            <div class="small text-muted mb-1">Mã sân: <?php echo e($p['code']); ?></div>
                            <h5 class="card-title fw-bold mb-2"><?php echo e($p['name']); ?></h5>
                            <div class="text-success fw-bold fs-5 mb-3">
                                <?php echo formatMoney($p['price_per_hour']); ?> <span class="text-muted fw-normal fs-6">/ giờ</span>
                            </div>
                            <div class="pitch-meta mb-1">
                                <span aria-hidden="true">📍</span>
                                <?php echo !empty($p['address']) ? e($p['address']) : 'Chưa cập nhật địa chỉ'; ?>
                            </div>
                            <div class="pitch-meta mb-3">
                                <span aria-hidden="true">🕒</span>
                                Hoạt động <?php echo e(formatTime($p['opening_time'])); ?> - <?php echo e(formatTime($p['closing_time'])); ?>
                            </div>
                            <?php if (!empty($p['description'])): ?>
                                <p class="text-muted small mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    <?php echo e($p['description']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="mb-3">
                                <?php if (!empty($p['grass_quality'])): ?>
                                    <span class="pitch-feature">🌱 <?php echo e($p['grass_quality']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['has_lighting'])): ?>
                                    <span class="pitch-feature">💡 Đèn chiếu sáng</span>
                                <?php endif; ?>
                                <?php if (!empty($p['has_changing_room'])): ?>
                                    <span class="pitch-feature">🚿 Phòng thay đồ</span>
                                <?php endif; ?>
                                <?php if (!empty($p['has_parking'])): ?>
                                    <span class="pitch-feature">🅿️ Bãi gửi xe</span>
                                <?php endif; ?>
                                <?php if (!empty($p['extra_services'])): ?>
                                    <span class="pitch-feature">🎽 <?php echo e($p['extra_services']); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo e($detailUrl); ?>"
                               class="btn <?php echo $p['availability_status'] === 'available' ? 'btn-success' : 'btn-outline-success'; ?> w-100 mt-auto">
                                <?php echo $p['availability_status'] === 'available' ? 'Đặt sân' : 'Xem chi tiết / lịch trống'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
            <?php endif; ?>
        </main>
    </div>

</div>

<footer class="border-top py-4 bg-light mt-5">
    <div class="container text-center text-secondary small">
        © <?php echo date('Y'); ?> SânBóng.Pro — Hệ thống đặt sân bóng online
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
