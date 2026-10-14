@extends('layouts.admin')
@section('title', 'Sửa sân bóng')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Sửa sân bóng</h3>
    <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">← Danh sách sân</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?php echo route('admin/pitches/' . $pitch['id']); ?>" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên sân <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($old['name'] ?? $pitch['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loại sân <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <?php foreach ([5,7,11] as $t): ?>
                                <option value="<?php echo $t; ?>"
                                    <?php
                                        $cur = $old['type'] ?? $pitch['type'];
                                        echo ((int)$cur === $t) ? 'selected' : '';
                                    ?>>Sân <?php echo $t; ?> người</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giá thuê / giờ (₫) <span class="text-danger">*</span></label>
                        <input type="number" name="price_per_hour" class="form-control" min="0" required
                               value="<?php echo htmlspecialchars($old['price_per_hour'] ?? $pitch['price_per_hour']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ sân</label>
                        <input type="text" name="address" class="form-control"
                               value="<?php echo htmlspecialchars($old['address'] ?? $pitch['address'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giờ mở cửa</label>
                        <input type="time" name="opening_time" class="form-control"
                               value="<?php echo htmlspecialchars($old['opening_time'] ?? $pitch['opening_time'] ?? '06:00'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giờ đóng cửa</label>
                        <input type="time" name="closing_time" class="form-control"
                               value="<?php echo htmlspecialchars($old['closing_time'] ?? $pitch['closing_time'] ?? '23:00'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hotline</label>
                        <input type="text" name="hotline" class="form-control"
                               value="<?php echo htmlspecialchars($old['hotline'] ?? $pitch['hotline'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kích thước / quy mô</label>
                        <input type="text" name="pitch_size" class="form-control"
                               value="<?php echo htmlspecialchars($old['pitch_size'] ?? $pitch['pitch_size'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số sân</label>
                        <input type="number" name="field_count" class="form-control" min="1"
                               value="<?php echo htmlspecialchars($old['field_count'] ?? $pitch['field_count'] ?? '1'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" class="form-select">
                            <?php $curStatus = $old['status'] ?? $pitch['status']; ?>
                            <option value="active"   <?php echo $curStatus === 'active'   ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="inactive" <?php echo $curStatus === 'inactive' ? 'selected' : ''; ?>>Tạm dừng</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mở cửa ngay</label>
                        <select name="is_open" class="form-select">
                            <?php $curOpen = $old['is_open'] ?? $pitch['is_open'] ?? 1; ?>
                            <option value="1" <?php echo (int)$curOpen === 1 ? 'selected' : ''; ?>>Có</option>
                            <option value="0" <?php echo (int)$curOpen === 0 ? 'selected' : ''; ?>>Không</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh sân hiện tại</label>
                        <div class="mb-2">
                            <img src="<?php echo $pitch['image'] ? (str_starts_with($pitch['image'], 'http') ? $pitch['image'] : file_url($pitch['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                                 class="rounded border" style="width:100%;max-width:320px;height:180px;object-fit:cover;" alt="pitch">
                        </div>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <small class="text-muted d-block mt-1">Để trống nếu không muốn thay ảnh</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" rows="6" class="form-control"><?php echo htmlspecialchars($old['description'] ?? $pitch['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 justify-content-end">
                <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-success px-4">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

@endsection
