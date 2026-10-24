<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký chủ sân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo route('site-theme'); ?>" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #198754 0%, #157347 100%); min-height: 100vh;">
<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="fs-1">🏟️</div>
                    <h3 class="fw-bold mb-1">Đăng ký làm chủ sân</h3>
                    <p class="text-muted mb-0">Tạo tài khoản chủ sân và chờ admin duyệt</p>
                </div>

                <?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

                <form method="post" action="<?php echo route('owner/register'); ?>">
                    <input type="hidden" name="_csrf_token" value="<?php echo e($csrfToken); ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Họ và tên chủ sân</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo e($old['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control" value="<?php echo e($old['username'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Số điện thoại cá nhân</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo e($old['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mật khẩu</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tên doanh nghiệp</label>
                            <input type="text" name="business_name" class="form-control" value="<?php echo e($old['business_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Số điện thoại doanh nghiệp</label>
                            <input type="text" name="business_phone" class="form-control" value="<?php echo e($old['business_phone'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mã số thuế</label>
                            <input type="text" name="tax_code" class="form-control" value="<?php echo e($old['tax_code'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Địa chỉ kinh doanh</label>
                            <input type="text" name="business_address" class="form-control" value="<?php echo e($old['business_address'] ?? ''); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Mô tả doanh nghiệp / thông tin sân</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo e($old['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-4 mb-3 small">
                        Sau khi gửi đăng ký, tài khoản sẽ ở trạng thái chờ duyệt, và admin sẽ xác nhận quyền chủ sân cho bạn.
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold">Gửi đăng ký chủ sân</button>
                </form>

                <div class="text-center mt-4">
                    <a href="<?php echo route('owner/login'); ?>" class="text-decoration-none text-success">Đã có tài khoản? Đăng nhập</a>
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
