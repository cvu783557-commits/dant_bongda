@extends('layouts.admin')
@section('title', 'Thêm sân bóng')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Thêm sân bóng</h3>
    <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">← Danh sách sân</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?php echo route('admin/pitches'); ?>" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên sân <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loại sân <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <?php foreach ([5,7,11] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo (isset($old['type']) && (int)$old['type'] === $t) ? 'selected' : ''; ?>>Sân <?php echo $t; ?> người</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giá thuê / giờ (₫) <span class="text-danger">*</span></label>
                        <input type="number" name="price_per_hour" class="form-control" min="0" required
                               value="<?php echo htmlspecialchars($old['price_per_hour'] ?? '0'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo !isset($old['status']) || $old['status'] === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="inactive" <?php echo (isset($old['status']) && $old['status'] === 'inactive') ? 'selected' : ''; ?>>Tạm dừng</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh sân</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <small class="text-muted d-block mt-1">Để trống nếu chưa có ảnh</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" rows="6" class="form-control"><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 justify-content-end">
                <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-success px-4">Lưu sân</button>
            </div>
        </form>
    </div>
</div>

@endsection
