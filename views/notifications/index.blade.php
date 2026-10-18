<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo route('/'); ?>">⚽ SânBóng.Pro</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link" href="<?php echo route('my-bookings'); ?>">Lịch sử đặt sân</a>
            <a class="nav-link active" href="<?php echo route('notifications'); ?>">Thông báo</a>
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
            <h1 class="h2 fw-bold mb-1">Thông báo<?php if ($unreadCount > 0): ?> <span class="badge text-bg-danger"><?php echo (int)$unreadCount; ?> mới</span><?php endif; ?></h1>
            <p class="text-secondary mb-0">Cập nhật về thanh toán, đơn đặt sân và yêu cầu hủy.</p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form method="post" action="<?php echo route('notifications/read-all'); ?>">
                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                <button type="submit" class="btn btn-outline-success">Đánh dấu tất cả đã đọc</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>
    <?php if (!$notifications): ?>
        <div class="card border-0 shadow-sm"><div class="card-body p-5 text-center">
            <h2 class="h5 fw-bold">Chưa có thông báo</h2>
            <p class="text-secondary mb-0">Thông báo về đơn đặt sân sẽ xuất hiện tại đây.</p>
        </div></div>
    <?php else: ?>
        <div class="d-grid gap-3">
            <?php foreach ($notifications as $notification): ?>
                <article class="card border-0 shadow-sm <?php echo empty($notification['is_read']) ? 'border-start border-4 border-success' : ''; ?>">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h2 class="h6 fw-bold mb-1"><?php echo e($notification['title']); ?></h2>
                                <?php if (empty($notification['is_read'])): ?><span class="badge text-bg-success">Mới</span><?php endif; ?>
                            </div>
                            <p class="mb-1"><?php echo e($notification['message']); ?></p>
                            <time class="small text-secondary"><?php echo e($notification['created_at']); ?></time>
                            <div><a href="<?php echo e($notification['url']); ?>" class="small">Xem đơn đặt sân</a></div>
                        </div>
                        <?php if (empty($notification['is_read'])): ?>
                            <form method="post" action="<?php echo route('notifications/' . (int)$notification['id'] . '/read'); ?>">
                                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Đánh dấu đã đọc</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
