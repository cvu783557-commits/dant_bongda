@extends('layouts.admin')
@section('title', 'Chi tiết sân bóng')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Chi tiết sân bóng</h3>
    <div class="d-flex gap-2">
        <a href="<?php echo route('admin/pitches/' . $pitch['id'] . '/edit'); ?>" class="btn btn-outline-primary">Sửa sân</a>
        <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">← Danh sách sân</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <img src="<?php echo $pitch['image'] ? (str_starts_with($pitch['image'], 'http') ? $pitch['image'] : file_url($pitch['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                     class="img-fluid rounded border" style="width:100%;max-height:380px;object-fit:cover;" alt="Ảnh sân">
            </div>

            <div class="col-lg-7">
                <div class="mb-3">
                    <span class="text-muted small text-uppercase">Tên sân</span>
                    <h4 class="fw-bold mb-0 mt-1"><?php echo htmlspecialchars($pitch['name']); ?></h4>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Loại sân</div>
                            <div class="fw-semibold fs-5 mt-1">Sân <?php echo (int)$pitch['type']; ?> người</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Giá thuê / giờ</div>
                            <div class="fw-semibold fs-5 text-success mt-1"><?php echo formatMoney($pitch['price_per_hour']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Trạng thái</div>
                            <div class="mt-1">
                                <?php if ($pitch['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Tạm dừng</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Ngày tạo</div>
                            <div class="fw-semibold mt-1"><?php echo formatDate($pitch['created_at']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3">
                    <div class="text-muted small mb-2">Mô tả</div>
                    <p class="mb-0 text-secondary"><?php echo nl2br(htmlspecialchars($pitch['description'] ?? 'Chưa có mô tả cho sân này.')); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
