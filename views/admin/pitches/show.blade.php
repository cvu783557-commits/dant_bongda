@extends('layouts.admin')
@section('title', 'Chi tiết sân bóng')
@section('content')

<style>
    .admin-pitch-detail-wrap {
        background: #f4f6f4;
        border-radius: 14px;
        padding: 18px 20px 24px;
        border: 1px solid #dfe7df;
    }
    .admin-pitch-header {
        background: linear-gradient(180deg, #ebf5ee 0%, #dff2e3 100%);
        border: 1px solid #cfe5d6;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 18px;
    }
    .admin-pitch-header h2 {
        margin: 0;
        font-weight: 800;
        color: #1a1a1a;
        font-size: 2rem;
    }
    .admin-pitch-header .meta-code {
        color: #2f3d35;
        font-weight: 600;
        font-size: 1rem;
        margin-top: 4px;
    }
    .admin-pitch-actions .btn {
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 14px;
    }
    .admin-order-card {
        background: #fff;
        border: 1px solid #e7ebea;
        border-radius: 12px;
        overflow: hidden;
    }
    .admin-order-card .card-header {
        background: #f8f9f8;
        border-bottom: 1px solid #e7ebea;
        padding: 18px 20px;
    }
    .admin-order-card .card-header h5 {
        margin: 0;
        font-weight: 800;
        font-size: 1.05rem;
    }
    .admin-order-card .table thead th {
        background: #eff3f0;
        border-bottom: 1px solid #e2e6e3;
        color: #2f2f2f;
        font-weight: 700;
        font-size: 0.92rem;
    }
    .admin-order-card .table td {
        background: #fff;
        vertical-align: middle;
        font-size: 0.92rem;
        color: #2d2d2d;
    }
    .admin-order-card .status-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .admin-detail-card {
        background: #fff;
        border: 1px solid #e2e8e2;
        border-radius: 12px;
        overflow: hidden;
        margin-top: 22px;
    }
    .admin-detail-card .card-header {
        font-weight: 800;
        font-size: 1.1rem;
        padding: 18px 20px;
        border-bottom: 1px solid #e7ebea;
        background: #f9faf9;
    }
    .pitch-image-panel {
        border-radius: 12px;
        overflow: hidden;
        background: #e9eee9;
        border: 1px solid #d9e3d9;
        min-height: 420px;
    }
    .pitch-image-panel img {
        width: 100%;
        height: 100%;
        min-height: 420px;
        object-fit: cover;
        display: block;
    }
    .info-pill {
        background: #f4f7f5;
        border: 1px solid #e2e7e3;
        border-radius: 10px;
        padding: 14px 16px;
        height: 100%;
    }
    .info-label {
        color: #67726c;
        font-size: 0.82rem;
        margin-bottom: 8px;
    }
    .info-value {
        font-weight: 700;
        font-size: 1.03rem;
        color: #212121;
    }
    .green-tag {
        display: inline-block;
        background: #e9f8ee;
        color: #1b8d51;
        border: 1px solid #bfe5cc;
        border-radius: 8px;
        padding: 6px 10px;
        font-weight: 700;
        font-size: 0.82rem;
    }
    .muted-tag {
        display: inline-block;
        background: #eef1f0;
        color: #5e6462;
        border: 1px solid #dfe5e2;
        border-radius: 8px;
        padding: 6px 10px;
        font-weight: 700;
        font-size: 0.82rem;
    }
</style>

<div class="admin-pitch-detail-wrap">
    <div class="admin-pitch-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2>Chi tiết sân bóng</h2>
            <div class="meta-code">Mã sân #<?php echo (int)($pitch['id'] ?? 0); ?></div>
        </div>

        <div class="admin-pitch-actions d-flex gap-2 flex-wrap">
            <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-success">Xem đơn đặt sân</a>
            <a href="<?php echo route('admin/pitches/' . $pitch['id'] . '/edit'); ?>" class="btn btn-success">Sửa sân</a>
            <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">Danh sách sân</a>
        </div>
    </div>

    <div class="admin-order-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5>Đơn đặt sắp tới</h5>
            <button type="button" class="btn btn-sm btn-light border">Tắt cả đơn</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Ngày / giờ</th>
                        <th>Sân con</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#000001</td>
                        <td>
                            <div class="fw-semibold">Nguyễn Văn A</div>
                            <small class="text-muted">0901234567</small>
                        </td>
                        <td>
                            <div>14/10/2026</div>
                            <small class="text-muted">20:00 - 21:00 (1 giờ)</small>
                        </td>
                        <td>PB 1</td>
                        <td><span class="status-badge green-tag">Đã duyệt</span></td>
                        <td><a href="#" class="btn btn-sm btn-outline-primary">Xem đơn</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-detail-card">
        <div class="card-header">Thông tin sân</div>
        <div class="card-body p-4">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-6">
                    <div class="pitch-image-panel">
                        <img src="<?php echo !empty($pitch['image']) ? (str_starts_with($pitch['image'], 'http') ? $pitch['image'] : file_url($pitch['image'])) : 'https://images.unsplash.com/photo-1518604666860-9ed391f76460?auto=format&fit=crop&w=1200&q=80'; ?>" alt="Ảnh sân <?php echo e($pitch['name']); ?>">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mb-4">
                        <div class="text-muted small text-uppercase">Tên sân</div>
                        <h3 class="fw-bold mb-0 mt-2"><?php echo e($pitch['name']); ?></h3>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Loại sân</div>
                                <div class="info-value">Sân <?php echo (int)($pitch['type'] ?? 5); ?> người</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Quy mô sân</div>
                                <div class="info-value">Sân <?php echo (int)($pitch['type'] ?? 5); ?> người</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Địa chỉ</div>
                                <div class="info-value"><?php echo !empty($pitch['address']) ? e($pitch['address']) : 'Chưa cập nhật'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Hotline</div>
                                <div class="info-value"><?php echo !empty($pitch['hotline']) ? e($pitch['hotline']) : '0901 234 567'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Giờ hoạt động</div>
                                <div class="info-value"><?php echo e(formatTime($pitch['opening_time'] ?? '06:00:00')); ?> - <?php echo e(formatTime($pitch['closing_time'] ?? '23:00:00')); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Số sân con</div>
                                <div class="info-value"><?php echo !empty($pitch['field_count']) ? (int)$pitch['field_count'] : 1; ?> sân</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Trạng thái hoạt động</div>
                                <div class="info-value">
                                    <?php if (($pitch['status'] ?? 'active') === 'active'): ?>
                                        <span class="green-tag">Đang hoạt động</span>
                                    <?php else: ?>
                                        <span class="muted-tag">Tạm đóng</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-pill">
                                <div class="info-label">Giá thuê</div>
                                <div class="info-value"><?php echo formatMoney($pitch['price_per_hour'] ?? 0); ?>/giờ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
