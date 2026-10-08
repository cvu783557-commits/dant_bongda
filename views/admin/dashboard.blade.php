@extends('layouts.admin')
@section('title', 'Tổng quan - Quản lý đặt sân')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Tổng quan <span class="text-muted fs-6 fw-normal">(Hôm nay: <?php echo formatDate($today); ?>)</span></h3>
    <div class="d-flex gap-2">
        <a href="<?php echo route('admin/bookings/calendar'); ?>" class="btn btn-outline-success">📅 Xem lịch sân</a>
        <?php if (!empty($canCreate)): ?>
            <a href="<?php echo route('admin/bookings/create'); ?>" class="btn btn-success">➕ Thêm lịch đặt</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-2">
        <div class="card border-0 shadow-sm bg-info text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Lịch hôm nay</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$totalToday; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card border-0 shadow-sm bg-warning text-dark h-100">
            <div class="card-body">
                <div class="small opacity-75">Chờ xác nhận</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$countPending; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Đang sử dụng</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$countInProgress; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card border-0 shadow-sm bg-success text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Hoàn thành</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$countCompleted; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card border-0 shadow-sm bg-danger text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Đã hủy</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$countCancelled; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card border-0 shadow-sm bg-secondary text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Sân đang trống</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$countFreeNow; ?>/<?php echo (int)$totalActive; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-6">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body">
                <div class="small text-muted mb-1">Doanh thu hôm nay (đã thu)</div>
                <div class="h2 fw-bold text-success"><?php echo formatMoney($revenueToday['total_revenue']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-6">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body">
                <div class="small text-muted mb-1">Tổng giá trị đặt hôm nay (chưa hủy)</div>
                <div class="h2 fw-bold text-primary"><?php echo formatMoney($revenueToday['total_booked']); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Đơn đặt hôm nay / gần đây nhất</h5>
                <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-sm btn-outline-success">Xem tất cả →</a>
            </div>
            <div class="card-body p-0">
                <?php if (count($recentBookings) === 0): ?>
                    <div class="p-4 text-center text-muted">Chưa có đơn đặt nào</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Sân / Ngày</th>
                                    <th>Giờ</th>
                                    <th>Tiền</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $b): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo bookingCode($b['id']); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($b['customer_name']); ?></div>
                                            <small class="text-muted"><?php echo e($b['customer_phone']); ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo e($b['pitch_name']); ?></div>
                                            <small class="text-muted"><?php echo formatDate($b['booking_date']); ?></small>
                                        </td>
                                        <td><span class="fw-semibold"><?php echo formatTime($b['start_time']).' - '.formatTime($b['end_time']); ?></span></td>
                                        <td class="fw-semibold text-success"><?php echo formatMoney($b['total_price']); ?></td>
                                        <td><?php echo bookingStatusBadge($b['status']); ?><div class="mt-1"><?php echo paymentStatusBadge($b['payment_status']); ?></div></td>
                                        <td class="text-end">
                                            <a href="<?php echo route('admin/bookings/' . $b['id']); ?>" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="fw-bold mb-0">Danh sách sân</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($pitches) === 0): ?>
                    <div class="p-4 text-center text-muted">Chưa có sân</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pitches as $p): ?>
                            <div class="list-group-item px-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?php echo e($p['name']); ?></div>
                                    <small class="text-muted">Sân <?php echo (int)$p['type']; ?> người</small>
                                </div>
                                <div class="text-success fw-bold"><?php echo formatMoney($p['price_per_hour']); ?><span class="text-muted fw-normal small">/giờ</span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

@endsection
