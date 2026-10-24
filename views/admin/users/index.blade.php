@extends('layouts.admin')
@section('title', 'Quản lý tài khoản')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Tài khoản và phân quyền</h3>
    <span class="badge text-bg-danger">ADMIN</span>
</div>

<?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

<div class="alert alert-info">
    Tài khoản USER mới đăng ký được kích hoạt ngay. ADMIN vẫn có thể khóa/mở khóa tài khoản hoặc thay đổi vai trò.
    Các tài khoản OWNER cần được duyệt hồ sơ kinh doanh trước khi được cấp quyền quản lý sân.
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 fw-bold">Hồ sơ đăng ký chủ sân</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Doanh nghiệp</th>
                    <th>Chủ sân</th>
                    <th>Liên hệ</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Duyệt</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ownerProfiles as $profile): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?php echo e($profile['business_name'] ?? '—'); ?></div>
                        <div class="small text-muted"><?php echo e($profile['tax_code'] ?: 'Chưa có MST'); ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?php echo e($profile['full_name'] ?? '—'); ?></div>
                        <div class="small text-muted"><?php echo e($profile['username'] ?? '—'); ?></div>
                    </td>
                    <td><?php echo e($profile['phone'] ?: ($profile['business_phone'] ?: '—')); ?></td>
                    <td>
                        <span class="badge text-bg-<?php echo ($profile['status'] ?? 'pending') === 'approved' ? 'success' : (($profile['status'] ?? 'pending') === 'rejected' ? 'danger' : 'warning'); ?>">
                            <?php echo e(($profile['status'] ?? 'pending') === 'approved' ? 'Đã duyệt' : (($profile['status'] ?? 'pending') === 'rejected' ? 'Từ chối' : 'Chờ duyệt')); ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <?php if (($profile['status'] ?? 'pending') === 'pending'): ?>
                            <div class="d-flex justify-content-end gap-2">
                                <form method="post" action="<?php echo route('admin/users/' . (int)($profile['user_id']) . '/approve-owner'); ?>">
                                    <input type="hidden" name="_csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Duyệt</button>
                                </form>
                                <form method="post" action="<?php echo route('admin/users/' . (int)($profile['user_id']) . '/reject-owner'); ?>">
                                    <input type="hidden" name="_csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Từ chối</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="small text-muted">Xử lý xong</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$ownerProfiles): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có hồ sơ đăng ký chủ sân nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tài khoản</th>
                    <th>Liên hệ</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Cập nhật quyền</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $account): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?php echo e($account['full_name']); ?></div>
                        <div class="small text-muted">@<?php echo e($account['username']); ?></div>
                    </td>
                    <td><?php echo e($account['phone'] ?: '—'); ?></td>
                    <td><span class="badge text-bg-<?php echo $account['role'] === 'ADMIN' ? 'danger' : 'primary'; ?>"><?php echo e($account['role']); ?></span></td>
                    <td>
                        <span class="badge text-bg-<?php echo $account['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo $account['status'] === 'active' ? 'Đang hoạt động' : 'Đã khóa'; ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <?php if ((int)$account['id'] === (int)$currentUser['id']): ?>
                            <span class="small text-muted">Tài khoản đang đăng nhập</span>
                        <?php else: ?>
                            <form method="post" action="<?php echo route('admin/users/' . (int)$account['id'] . '/access'); ?>" class="d-flex gap-2 justify-content-end">
                                <input type="hidden" name="_csrf_token" value="<?php echo e($csrfToken); ?>">
                                <select name="role" class="form-select form-select-sm" aria-label="Vai trò" style="max-width: 115px;">
                                    <option value="USER" <?php echo $account['role'] === 'USER' ? 'selected' : ''; ?>>USER</option>
                                    <option value="OWNER" <?php echo $account['role'] === 'OWNER' ? 'selected' : ''; ?>>OWNER</option>
                                    <option value="ADMIN" <?php echo $account['role'] === 'ADMIN' ? 'selected' : ''; ?>>ADMIN</option>
                                </select>
                                <select name="status" class="form-select form-select-sm" aria-label="Trạng thái" style="max-width: 170px;">
                                    <option value="inactive" <?php echo $account['status'] === 'inactive' ? 'selected' : ''; ?>>Đã khóa</option>
                                    <option value="active" <?php echo $account['status'] === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                                    <option value="pending" <?php echo $account['status'] === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-success">Lưu</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có tài khoản nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
@endsection
