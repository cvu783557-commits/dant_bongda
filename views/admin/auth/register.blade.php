<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản — SânBóng.Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            min-height: 100vh;
            color: #1f2937;
        }
        .register-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }
    </style>
    <link href="<?php echo route('site-theme'); ?>" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-lg-5 col-md-7">
            <div class="register-card card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="fs-1">⚽</div>
                    <h3 class="fw-bold mb-1">Đăng ký tài khoản</h3>
                    <p class="text-muted mb-0">Đăng ký xong là có thể đăng nhập ngay với quyền USER.</p>
                </div>

                <?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

                <form method="post" action="<?php echo route('register'); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo e($csrfToken); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="full_name">Họ và tên</label>
                        <input id="full_name" name="full_name" class="form-control" maxlength="100" required
                               value="<?php echo e($old['full_name'] ?? ''); ?>" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="username">Tên đăng nhập</label>
                        <input id="username" name="username" class="form-control" minlength="3" maxlength="30"
                               pattern="[A-Za-z0-9_.\x2d]{3,30}" required
                               value="<?php echo e($old['username'] ?? ''); ?>" autocomplete="username">
                        <div class="form-text">3–30 ký tự: chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="phone">Số điện thoại</label>
                        <input id="phone" name="phone" class="form-control" maxlength="20"
                               value="<?php echo e($old['phone'] ?? ''); ?>" autocomplete="tel">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password">Mật khẩu</label>
                        <input id="password" type="password" name="password" class="form-control" minlength="8"
                               required autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="password_confirmation">Xác nhận mật khẩu</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               class="form-control" minlength="8" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">Tạo tài khoản USER</button>
                </form>

                <div class="text-center mt-4">
                    <a href="<?php echo route('login'); ?>" class="text-decoration-none text-success">Đã có tài khoản? Đăng nhập</a>
                </div>
                <div class="text-center mt-2">
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
