@extends('layouts.admin')
@section('title', 'Sửa dịch vụ')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Sửa dịch vụ #<?php echo (int)$service['id']; ?></h3>
    <a href="<?php echo route('admin/services'); ?>" class="btn btn-outline-secondary">← Danh sách dịch vụ</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?php echo route('admin/services/' . $service['id']); ?>" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên dịch vụ <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="150"
                               value="<?php echo htmlspecialchars($old['name'] ?? $service['name']); ?>">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nhóm dịch vụ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($categories as $key => $name): ?>
                                    <?php $cur = $old['category'] ?? $service['category'] ?? 'other'; ?>
                                    <option value="<?php echo $key; ?>"
                                        <?php echo ((string)$cur === (string)$key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Đơn vị tính <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" required maxlength="30"
                                   value="<?php echo htmlspecialchars($old['unit'] ?? $service['unit']); ?>">
                        </div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold">Đơn giá (₫) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" min="0" step="1000" required
                                       value="<?php echo htmlspecialchars($old['price'] ?? $service['price']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold">Số lượng tồn kho</label>
                                <input type="number" name="stock" class="form-control" min="0"
                                       value="<?php echo htmlspecialchars($old['stock'] ?? $service['stock']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <?php $curStatus = $old['status'] ?? $service['status']; ?>
                        <select name="status" class="form-select">
                            <option value="active"   <?php echo $curStatus === 'active'   ? 'selected' : ''; ?>>Đang bán (Hoạt động)</option>
                            <option value="inactive" <?php echo $curStatus === 'inactive' ? 'selected' : ''; ?>>Chưa bán (Tạm dừng)</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh hiện tại</label>
                        <div class="mb-2">
                            <?php
                                $imgSrc = $service['image']
                                    ? (str_starts_with($service['image'], 'http') ? $service['image'] : file_url($service['image']))
                                    : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=' . urlencode(($categories[$service['category'] ?? 'other'] ?? 'product') . ' product simple') . '&image_size=square';
                            ?>
                            <img src="<?php echo $imgSrc; ?>"
                                 class="rounded border"
                                 style="width:100%;max-width:280px;height:200px;object-fit:cover;"
                                 alt="service">
                        </div>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <small class="text-muted d-block mt-1">Để trống nếu không muốn thay ảnh</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả chi tiết</label>
                        <textarea name="description" rows="6" class="form-control" maxlength="2000"><?php echo htmlspecialchars($old['description'] ?? $service['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 justify-content-end">
                <a href="<?php echo route('admin/services'); ?>" class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-success px-4">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

@endsection
