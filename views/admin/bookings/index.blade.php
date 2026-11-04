@extends('layouts.admin')
@section('title', 'Danh sách đặt sân')
@section('content')

<style>
    .booking-list-table {
        min-width: 1120px;
        table-layout: fixed;
        font-size: .88rem;
    }
    .booking-list-table th,
    .booking-list-table td {
        padding: .65rem .55rem;
        vertical-align: middle;
    }
    .booking-list-table th { white-space: nowrap; }
    .booking-list-table .booking-customer { width: 185px; }
    .booking-list-table .booking-pitch { width: 155px; }
    .booking-list-table .booking-date { width: 105px; }
    .booking-list-table .booking-time { width: 125px; }
    .booking-list-table .booking-money { width: 120px; }
    .booking-list-table .booking-status { width: 125px; }
    .booking-list-table .booking-actions { width: 210px; }
    .booking-list-table .booking-customer-name,
    .booking-list-table .booking-pitch-name {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .booking-list-table .booking-customer small,
    .booking-list-table .booking-pitch small {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .booking-list-table .booking-actions-group {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .3rem;
    }
    .booking-list-table .booking-actions-group .btn {
        padding: .25rem .45rem;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        .booking-list-table { min-width: 1050px; }
    }
    @media print {
        .booking-list-table { min-width: 0; font-size: 9px; }
        .booking-list-table .booking-actions { display: none; }
    }
</style>

<?php
$uri_query = $_SERVER['QUERY_STRING'] ?? '';
$reset_url = route('admin/bookings');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Danh sách đặt sân</h3>
    <div class="d-flex gap-2">
        <a href="<?php echo route('admin/bookings/calendar'); ?>" class="btn btn-outline-success">📅 Xem lịch</a>
        <?php if (!empty($canCreate)): ?>
            <a href="<?php echo route('admin/bookings/create'); ?>" class="btn btn-success">➕ Thêm lịch đặt</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" action="" class="row g-2 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small mb-1">Tìm kiếm</label>
                <input type="text" name="q" class="form-control"
                       placeholder="Mã đơn, Tên KH, Số điện thoại..."
                       value="<?php echo e($filters['q']); ?>">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1">Trạng thái lịch</label>
                <select name="status" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($statuses as $k => $v): ?>
                        <option value="<?php echo $k; ?>" <?php echo $filters['status'] === $k ? 'selected' : ''; ?>>
                            <?php echo e($v); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1">Trạng thái thanh toán</label>
                <select name="payment_status" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($payStatuses as $k => $v): ?>
                        <option value="<?php echo $k; ?>" <?php echo $filters['payment_status'] === $k ? 'selected' : ''; ?>>
                            <?php echo e($v); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1">Sân</label>
                <select name="pitch_id" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($pitches as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo (string)$filters['pitch_id'] === (string)$p['id'] ? 'selected' : ''; ?>>
                            <?php echo e($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1">Ngày đặt</label>
                <input type="date" name="booking_date" class="form-control"
                       value="<?php echo e($filters['booking_date']); ?>">
            </div>
            <div class="col-sm-6 col-lg-3 d-none">
                <label class="form-label small mb-1">Từ ngày</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo e($filters['from_date']); ?>">
            </div>
            <div class="col-sm-6 col-lg-3 d-none">
                <label class="form-label small mb-1">Đến ngày</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo e($filters['to_date']); ?>">
            </div>
            <div class="col-sm-6 col-lg-12 d-flex gap-2 pt-1">
                <button type="button" class="btn btn-link text-muted ps-0 py-1" id="toggleDateRangeBtn">
                    ⏬ Bộ lọc khoảng ngày
                </button>
            </div>
            <div class="col-12 d-flex gap-2 pt-1 border-top pt-2">
                <button type="submit" class="btn btn-success">🔍 Tìm kiếm / Lọc</button>
                <a href="<?php echo $reset_url; ?>" class="btn btn-outline-secondary">🔄 Đặt lại</a>
                <?php if (!empty($uri_query)): ?>
                    <button type="button" onclick="window.print()" class="btn btn-outline-primary ms-auto">🖨 In</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (count($bookings) === 0): ?>
    <div class="alert alert-info">Không có đơn đặt nào phù hợp điều kiện.</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 booking-list-table">
                <thead class="table-light">
                    <tr>
                        <th class="text-nowrap" style="width:48px">STT</th>
                        <th class="text-nowrap" style="width:95px">Mã lịch</th>
                        <th class="text-nowrap booking-customer">Khách hàng</th>
                        <th class="text-nowrap booking-pitch">Sân</th>
                        <th class="text-nowrap booking-date">Ngày</th>
                        <th class="text-nowrap booking-time">Giờ</th>
                        <th class="text-end text-nowrap booking-money">Tổng tiền</th>
                        <th class="text-end text-nowrap booking-money">Đã thanh toán</th>
                        <th class="text-end text-nowrap booking-money">Còn lại</th>
                        <th class="text-nowrap booking-status">Thanh toán</th>
                        <th class="text-nowrap booking-status">Lịch</th>
                        <th class="text-end text-nowrap booking-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $remain = function ($total, $paid) {
                        $r = (float)$total - (float)$paid;
                        return $r < 0 ? 0 : $r;
                    };
                    $stt = 0;
                    foreach ($bookings as $b): $stt++;
                        $r = $remain($b['total_price'], $b['paid_amount']);
                    ?>
                        <tr>
                            <td><?php echo $stt; ?></td>
                            <td class="fw-semibold text-nowrap"><?php echo bookingCode($b['id']); ?></td>
                            <td class="booking-customer">
                                <div class="fw-semibold booking-customer-name" title="<?php echo e($b['customer_name']); ?>"><?php echo e($b['customer_name']); ?></div>
                                <small class="text-muted">📞 <?php echo e($b['customer_phone']); ?></small>
                                <?php if (!empty($b['customer_email'])): ?>
                                    <small class="text-muted" title="<?php echo e($b['customer_email']); ?>">✉️ <?php echo e($b['customer_email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="booking-pitch">
                                <div class="fw-semibold booking-pitch-name" title="<?php echo e($b['pitch_name']); ?>"><?php echo e($b['pitch_name']); ?></div>
                                <small class="text-muted">Sân con <?php echo (int)($b['court_number'] ?? 1); ?> · <?php echo (int)$b['pitch_type']; ?> người</small>
                            </td>
                            <td class="text-nowrap"><?php echo formatDate($b['booking_date']); ?></td>
                            <td class="text-nowrap fw-semibold">
                                <?php echo formatTime($b['start_time']).' → '.formatTime($b['end_time']); ?>
                            </td>
                            <td class="text-end text-nowrap fw-semibold booking-money"><?php echo formatMoney($b['total_price']); ?></td>
                            <td class="text-end text-nowrap text-success booking-money"><?php echo formatMoney($b['paid_amount']); ?></td>
                            <td class="text-end text-nowrap text-<?php echo $r > 0 ? 'danger' : 'success'; ?> booking-money">
                                <?php echo formatMoney($r); ?>
                            </td>
                            <td class="text-nowrap"><?php echo paymentStatusBadge($b['payment_status']); ?></td>
                            <td class="text-nowrap"><?php echo bookingStatusBadge($b['status']); ?>
                                <?php if (!empty($b['note'])): ?>
                                    <div><small class="text-muted" title="<?php echo e($b['note']); ?>">📝 Ghi chú</small></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end booking-actions">
                                <div class="booking-actions-group">
                                <a href="<?php echo route('admin/bookings/' . $b['id']); ?>" class="btn btn-sm btn-outline-primary">Xem</a>

                                <?php if (!empty($canEdit)): ?>
                                    <a href="<?php echo route('admin/bookings/' . $b['id'] . '/edit'); ?>" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                <?php endif; ?>

                                <?php if (!empty($canConfirm) && $b['status'] === 'pending'): ?>
                                    <form method="post" action="<?php echo route('admin/bookings/' . $b['id'] . '/confirm'); ?>" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-success">Xác nhận</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (!empty($canConfirm) && $b['status'] === 'confirmed'): ?>
                                    <a href="<?php echo route('admin/check-in/' . (int)$b['id']); ?>"
                                       class="btn btn-sm btn-info text-white">Check-in</a>
                                <?php endif; ?>
                                <?php if (!empty($canConfirm) && $b['status'] === 'in_progress'): ?>
                                    <form method="post" action="<?php echo route('admin/bookings/' . $b['id'] . '/complete'); ?>" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-success">Hoàn thành</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!empty($canCancel) && $b['status'] !== 'cancelled'): ?>
                                    <a href="<?php echo route('admin/bookings/' . $b['id'] . '/cancel'); ?>" class="btn btn-sm btn-outline-danger">Hủy</a>
                                <?php endif; ?>

                                <?php if (!empty($canDelete)): ?>
                                    <a href="<?php echo route('admin/bookings/' . $b['id'] . '/delete'); ?>" class="btn btn-sm btn-outline-dark">Xóa</a>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="text-end fw-semibold">Tổng cộng (<?php echo count($bookings); ?> đơn):</td>
                        <td class="text-end fw-bold">
                            <?php
                            $sumTotal = 0; $sumPaid = 0; $sumRemain = 0;
                            foreach ($bookings as $b) {
                                $sumTotal += (float)$b['total_price'];
                                $sumPaid += (float)$b['paid_amount'];
                                $sumRemain += max(0, (float)$b['total_price'] - (float)$b['paid_amount']);
                            }
                            echo formatMoney($sumTotal);
                            ?>
                        </td>
                        <td class="text-end fw-bold text-success"><?php echo formatMoney($sumPaid); ?></td>
                        <td class="text-end fw-bold text-danger"><?php echo formatMoney($sumRemain); ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function(){
    var btn = document.getElementById('toggleDateRangeBtn');
    var r1 = document.querySelector('input[name="from_date"]').closest('.col-sm-6');
    var r2 = document.querySelector('input[name="to_date"]').closest('.col-sm-6');
    if (btn && r1 && r2) {
        r1.classList.add('d-none'); r2.classList.add('d-none');
        btn.addEventListener('click', function(){
            r1.classList.toggle('d-none');
            r2.classList.toggle('d-none');
        });
    }
})();
</script>
@endsection
