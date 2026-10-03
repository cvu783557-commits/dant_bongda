@extends('layouts.admin')
@section('title', 'Quản lý đặt sân')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Danh sách đặt sân</h3>
    <a href="<?php echo route('admin/dashboard'); ?>" class="btn btn-outline-secondary">Xem dashboard</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" action="" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <option value="pending"   <?php echo ($filters['status'] === 'pending')   ? 'selected' : ''; ?>>Chờ duyệt</option>
                    <option value="confirmed" <?php echo ($filters['status'] === 'confirmed') ? 'selected' : ''; ?>>Đã duyệt</option>
                    <option value="cancelled" <?php echo ($filters['status'] === 'cancelled') ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Ngày đặt</label>
                <input type="date" name="booking_date" class="form-control" value="<?php echo htmlspecialchars($filters['booking_date']); ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Sân</label>
                <select name="pitch_id" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($pitches as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($filters['pitch_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-success flex-grow-1">Lọc</button>
                <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (count($bookings) === 0): ?>
    <div class="alert alert-info">Không có đơn đặt nào.</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Sân</th>
                        <th>Ngày / Giờ</th>
                        <th>Tiền</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo str_pad($b['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($b['customer_phone']); ?></small>
                                <?php if (!empty($b['customer_email'])): ?>
                                    <div><small class="text-muted"><?php echo htmlspecialchars($b['customer_email']); ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($b['pitch_name']); ?></div>
                                <small class="text-muted">Sân <?php echo (int)$b['pitch_type']; ?> người</small>
                            </td>
                            <td>
                                <div><?php echo formatDate($b['booking_date']); ?></div>
                                <small class="text-muted"><?php echo substr($b['start_time'],0,5) . ' - ' . substr($b['end_time'],0,5); ?></small>
                            </td>
                            <td class="fw-semibold text-success"><?php echo formatMoney($b['total_price']); ?></td>
                            <td>
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Đã duyệt</span>
                                <?php elseif ($b['status'] === 'pending'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Chờ duyệt</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Đã hủy</span>
                                <?php endif; ?>
                                <?php if (!empty($b['notes'])): ?>
                                    <div><small class="text-muted" title="<?php echo htmlspecialchars($b['notes']); ?>">📝 Ghi chú</small></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="post" action="<?php echo route('admin/bookings/' . $b['id']); ?>" class="d-inline">
                                        <input type="hidden" name="status" value="confirmed">
                                        <input type="hidden" name="notes" value="<?php echo htmlspecialchars($b['notes'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Duyệt</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($b['status'] !== 'cancelled'): ?>
                                    <form method="post" action="<?php echo route('admin/bookings/' . $b['id']); ?>" class="d-inline" onsubmit="return confirm('Hủy đơn đặt này?');">
                                        <input type="hidden" name="status" value="cancelled">
                                        <input type="hidden" name="notes" value="<?php echo htmlspecialchars($b['notes'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hủy</button>
                                    </form>
                                <?php endif; ?>
                                <a href="<?php echo route('admin/bookings/' . $b['id'] . '/edit'); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form method="post" action="<?php echo route('admin/bookings/' . $b['id'] . '/delete'); ?>" class="d-inline" onsubmit="return confirm('Xóa đơn đặt này?');">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Xóa</button>
                                </form>
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
