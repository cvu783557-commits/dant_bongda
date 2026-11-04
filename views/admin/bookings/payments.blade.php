@extends('layouts.admin')
@section('title', 'Quản lý thanh toán')
@section('content')
<?php
$summary = $summary ?? [];
$filters = $filters ?? [];
$statusLabels = [
    'success' => ['Đã thu', 'success'],
    'failed' => ['Thất bại', 'danger'],
    'refund' => ['Hoàn tiền', 'primary'],
];
$resetUrl = route('admin/payments');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">Quản lý thanh toán</h3>
        <div class="text-muted">Theo dõi các khoản thu, giao dịch thất bại và tiền hoàn theo từng đơn đặt sân.</div>
    </div>
    <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-success">📋 Danh sách đặt sân</a>
</div>

<?php if (!empty($success)): ?><div class="alert alert-success" role="alert"><?php echo $success; ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tổng đã thu<?php echo (!empty($filters['from_date']) || !empty($filters['to_date'])) ? ' trong khoảng ngày lọc' : ''; ?></div>
                <div class="fs-4 fw-bold text-success"><?php echo formatMoney($summary['total_received'] ?? 0); ?></div>
                <div class="small text-muted"><?php echo (int)($summary['success_count'] ?? 0); ?> giao dịch thành công</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tổng đã hoàn</div>
                <div class="fs-4 fw-bold text-primary"><?php echo formatMoney($summary['total_refunded'] ?? 0); ?></div>
                <div class="small text-muted"><?php echo (int)($summary['refund_count'] ?? 0); ?> giao dịch hoàn được ghi nhận</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Giao dịch thất bại</div>
                <div class="fs-4 fw-bold text-danger"><?php echo (int)($summary['failed_count'] ?? 0); ?></div>
                <div class="small text-muted">Có thể xem mã tham chiếu và lý do bên dưới</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo $resetUrl; ?>" class="row g-3 align-items-end">
            <div class="col-sm-6 col-xl-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="search" class="form-control" name="q"
                       value="<?php echo e($filters['q'] ?? ''); ?>"
                       placeholder="Mã đơn, tên, điện thoại, mã giao dịch">
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label">Loại giao dịch</label>
                <select class="form-select" name="status">
                    <option value="">Tất cả</option>
                    <option value="success" <?php echo ($filters['status'] ?? '') === 'success' ? 'selected' : ''; ?>>Đã thu</option>
                    <option value="failed" <?php echo ($filters['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                    <option value="refund" <?php echo ($filters['status'] ?? '') === 'refund' ? 'selected' : ''; ?>>Hoàn tiền</option>
                </select>
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label">Phương thức</label>
                <select class="form-select" name="method">
                    <option value="">Tất cả</option>
                    <?php foreach ($payMethods as $key => $label): ?>
                        <option value="<?php echo e($key); ?>" <?php echo ($filters['method'] ?? '') === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label">Từ ngày giao dịch</label>
                <input type="date" class="form-control" name="from_date" value="<?php echo e($filters['from_date'] ?? ''); ?>">
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label">Đến ngày giao dịch</label>
                <input type="date" class="form-control" name="to_date" value="<?php echo e($filters['to_date'] ?? ''); ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-success">🔍 Lọc giao dịch</button>
                <a class="btn btn-outline-secondary" href="<?php echo $resetUrl; ?>">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0">Lịch sử giao dịch</h5>
        <span class="text-muted small">Tối đa 1.000 giao dịch gần nhất theo bộ lọc</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
            <div class="alert alert-info m-3 mb-0">Không có giao dịch phù hợp. Hãy thử bỏ bớt điều kiện lọc.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Thời gian</th>
                            <th>Mã đơn / khách hàng</th>
                            <th>Sân / ngày đá</th>
                            <th>Loại</th>
                            <th>Phương thức</th>
                            <th>Mã tham chiếu / ghi chú</th>
                            <th class="text-end">Số tiền</th>
                            <th>Nhân viên</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php [$label, $badge] = $statusLabels[$transaction['status']] ?? ['Không rõ', 'secondary']; ?>
                            <tr>
                                <td class="text-nowrap"><?php echo formatDateTime($transaction['created_at']); ?></td>
                                <td>
                                    <a class="fw-semibold text-decoration-none" href="<?php echo route('admin/bookings/' . (int)$transaction['booking_id']); ?>">
                                        <?php echo e(bookingCode($transaction['booking_id'])); ?>
                                    </a>
                                    <div><?php echo e($transaction['customer_name'] ?? ''); ?></div>
                                    <div class="small text-muted"><?php echo e($transaction['customer_phone'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <?php echo e($transaction['pitch_name'] ?? ''); ?>
                                    <div class="small text-muted"><?php echo e($transaction['booking_date'] ?? ''); ?></div>
                                </td>
                                <td><span class="badge text-bg-<?php echo $badge; ?>"><?php echo e($label); ?></span></td>
                                <td><?php echo e($payMethods[$transaction['payment_method']] ?? $transaction['payment_method']); ?></td>
                                <td>
                                    <?php if (!empty($transaction['reference_code'])): ?>
                                        <div class="small"><?php echo e($transaction['reference_code']); ?></div>
                                    <?php endif; ?>
                                    <div class="small text-muted"><?php echo e($transaction['note'] ?? ''); ?></div>
                                </td>
                                <td class="text-end text-nowrap fw-semibold <?php echo $transaction['status'] === 'failed' ? 'text-danger' : ($transaction['status'] === 'refund' ? 'text-primary' : 'text-success'); ?>">
                                    <?php echo $transaction['status'] === 'refund' ? '− ' : ''; ?><?php echo formatMoney($transaction['amount']); ?>
                                </td>
                                <td><?php echo e($transaction['recorder_name'] ?? '—'); ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo route('admin/bookings/' . (int)$transaction['booking_id']); ?>">Chi tiết</a>
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo route('admin/bookings/' . (int)$transaction['booking_id'] . '/invoice'); ?>">Hóa đơn</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
@endsection
