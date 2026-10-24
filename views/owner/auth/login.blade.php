<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập chủ sân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo route('site-theme'); ?>" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #198754 0%, #157347 100%); min-height: 100vh;">
<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-lg-5 col-md-7">
            <div class="card border-0 shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="fs-1">🏟️</div>
                    <h3 class="fw-bold mb-1">Đăng nhập chủ sân</h3>
                    <p class="text-muted mb-0">Quản lý sân, đặt sân và doanh thu</p>
                </div>

                <?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

                <form method="post" action="<?php echo route('owner/login'); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo e($csrfToken); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên đăng nhập</label>
                        <input type="text" name="username" class="form-control form-control-lg" value="<?php echo e($old['username'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Mật khẩu</label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Đăng nhập</button>
                </form>

                <div class="text-center mt-4">
                    <a href="<?php echo route('owner/register'); ?>" class="text-decoration-none text-success">Đăng ký làm chủ sân</a>
                </div>
                <div class="text-center mt-2">
                    <a href="<?php echo route('/'); ?>" class="text-decoration-none small text-muted">← Về trang chủ</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
