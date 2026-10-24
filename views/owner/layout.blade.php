<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Owner Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo route('site-theme'); ?>" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo route('owner/dashboard'); ?>">⚽ Owner Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ownerNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="ownerNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo route('owner/dashboard'); ?>">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo route('owner/pitches'); ?>">Sân của tôi</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo route('owner/bookings'); ?>">Đặt sân</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo route('owner/revenue'); ?>">Doanh thu</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-2">
                <li class="nav-item text-white-50 small">Xin chào, <?php echo e($currentUser['full_name'] ?? 'Owner'); ?></li>
                <li class="nav-item">
                    <form method="post" action="<?php echo route('logout'); ?>" class="d-inline">
                        <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                        <button type="submit" class="btn btn-outline-light btn-sm">Đăng xuất</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php if ($success = getFlash('success')): ?>
        <div class="alert alert-success" role="alert"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($error = getFlash('error')): ?>
        <div class="alert alert-danger" role="alert"><?php echo e($error); ?></div>
    <?php endif; ?>
</div>

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
