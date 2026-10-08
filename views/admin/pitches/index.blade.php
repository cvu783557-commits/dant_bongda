@extends('layouts.admin')
@section('title', 'Quản lý sân bóng')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Danh sách sân bóng</h3>
    <a href="<?php echo route('admin/pitches/create'); ?>" class="btn btn-success">+ Thêm sân</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (count($pitches) === 0): ?>
    <div class="alert alert-info">Chưa có sân nào. Click "Thêm sân" để tạo sân đầu tiên.</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên sân</th>
                        <th>Loại</th>
                        <th>Giá / giờ</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pitches as $p): ?>
                        <tr>
                            <td><?php echo (int)$p['id']; ?></td>
                            <td style="width:120px;">
                                <img src="<?php echo $p['image'] ? (str_starts_with($p['image'], 'http') ? $p['image'] : file_url($p['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                                     class="rounded" style="width:100px;height:64px;object-fit:cover;" alt="img">
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($p['name']); ?></div>
                                <?php if (!empty($p['description'])): ?>
                                    <small class="text-muted d-block" style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?php echo htmlspecialchars($p['description']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>Sân <?php echo (int)$p['type']; ?> người</td>
                            <td class="fw-semibold text-success"><?php echo formatMoney($p['price_per_hour']); ?></td>
                            <td>
                                <?php if ($p['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Tạm dừng</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <a href="<?php echo route('admin/pitches/' . $p['id']); ?>" class="btn btn-sm btn-outline-info">Chi tiết</a>
                                    <a href="<?php echo route('admin/pitches/' . $p['id'] . '/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <form method="post" action="<?php echo route('admin/pitches/' . $p['id'] . '/toggle-status'); ?>" class="d-inline">
                                        <button type="submit" class="btn btn-sm <?php echo $p['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                                            <?php echo $p['status'] === 'active' ? 'Tạm dừng' : 'Kích hoạt'; ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo route('admin/pitches/' . $p['id'] . '/delete'); ?>" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa sân này?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

@endsection
