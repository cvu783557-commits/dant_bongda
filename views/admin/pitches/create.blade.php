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
                            <?php foreach ([5,7] as $t): ?>
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
                        <label class="form-label fw-semibold">Địa chỉ sân</label>
                        <input type="text" name="address" class="form-control" maxlength="255"
                               value="<?php echo e($old['address'] ?? ''); ?>" placeholder="Số nhà, đường, phường/xã, quận/huyện, Hà Nội">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Mở cửa</label>
                            <input type="time" name="opening_time" class="form-control" required
                                   value="<?php echo e($old['opening_time'] ?? '06:00'); ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Đóng cửa</label>
                            <input type="time" name="closing_time" class="form-control" required
                                   value="<?php echo e($old['closing_time'] ?? '23:00'); ?>">
                        </div>
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
                    <hr>
                    <h6 class="fw-bold mb-3">Tiện ích sân</h6>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chất lượng mặt cỏ</label>
                        <input type="text" name="grass_quality" class="form-control" maxlength="100"
                               value="<?php echo e($old['grass_quality'] ?? ''); ?>" placeholder="Ví dụ: Cỏ nhân tạo, mới thay">
                    </div>
                    <div class="row g-3">
                        <?php foreach ([
                            'has_lighting' => 'Hệ thống đèn chiếu sáng',
                            'has_changing_room' => 'Phòng thay đồ / nhà vệ sinh',
                            'has_parking' => 'Bãi gửi xe',
                        ] as $field => $label): ?>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="<?php echo e($field); ?>"><?php echo e($label); ?></label>
                                <select id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" class="form-select">
                                    <option value="1" <?php echo ($old[$field] ?? '0') === '1' ? 'selected' : ''; ?>>Có</option>
                                    <option value="0" <?php echo ($old[$field] ?? '0') !== '1' ? 'selected' : ''; ?>>Không</option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Dịch vụ đi kèm</label>
                        <textarea name="extra_services" rows="3" maxlength="1000" class="form-control"
                                  placeholder="Ví dụ: Thuê áo, thuê bóng, nước uống"><?php echo e($old['extra_services'] ?? ''); ?></textarea>
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
