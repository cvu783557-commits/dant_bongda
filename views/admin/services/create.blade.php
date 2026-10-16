@extends('layouts.admin')
@section('title', 'Thêm dịch vụ')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Thêm dịch vụ mới</h3>
    <a href="<?php echo route('admin/services'); ?>" class="btn btn-outline-secondary">← Danh sách dịch vụ</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?php echo route('admin/services'); ?>" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên dịch vụ <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="150"
                               placeholder="Ví dụ: Nước suối 500ml"
                               value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nhóm dịch vụ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($categories as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"
                                        <?php echo ((string)($old['category'] ?? 'other') === (string)$key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Đơn vị tính <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" required maxlength="30"
                                   placeholder="Ví dụ: chai, ly, quả, bộ, giờ"
                                   value="<?php echo htmlspecialchars($old['unit'] ?? 'cái'); ?>">
                        </div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold">Đơn giá (₫) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" min="0" step="1000" required
                                       placeholder="Ví dụ: 15000"
                                       value="<?php echo htmlspecialchars($old['price'] ?? '0'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold">Số lượng tồn ban đầu</label>
                                <input type="number" name="stock" class="form-control" min="0"
                                       placeholder="Mặc định 0"
                                       value="<?php echo htmlspecialchars($old['stock'] ?? '0'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?php echo !isset($old['status']) || $old['status'] === 'active'   ? 'selected' : ''; ?>>Đang bán (Hoạt động)</option>
                            <option value="inactive" <?php echo (isset($old['status']) && $old['status'] === 'inactive') ? 'selected' : ''; ?>>Chưa bán (Tạm dừng)</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh sản phẩm / dịch vụ</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <small class="text-muted d-block mt-1">Để trống nếu chưa có ảnh, hệ thống sẽ hiển thị ảnh mặc định theo nhóm</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả chi tiết</label>
                        <textarea name="description" rows="7" class="form-control"
                                  placeholder="Mô tả ngắn gọn về dịch vụ, lưu ý khi sử dụng..."
                                  maxlength="2000"><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                        <small class="text-muted d-block mt-1">Tối đa 2000 ký tự</small>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 justify-content-end">
                <a href="<?php echo route('admin/services'); ?>" class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-success px-4">Lưu dịch vụ</button>
            </div>
        </form>
    </div>
</div>

@endsection
