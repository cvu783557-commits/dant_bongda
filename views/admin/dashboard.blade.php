@extends('layouts.admin')
@section('title', 'Dashboard - Quản lý đặt sân')
@section('content')

<h3 class="fw-bold mb-4">Tổng quan</h3>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="fs-5 opacity-75">Đơn đặt hôm nay</div>
                <div class="display-5 fw-bold mt-2"><?php echo (int)$totalToday; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="fs-5 opacity-75">Doanh thu đã thu</div>
                <div class="display-5 fw-bold mt-2"><?php echo formatMoney($totalRevenue); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body">
                <div class="fs-5 opacity-75">Sân đang hoạt động</div>
                <div class="display-5 fw-bold mt-2"><?php echo (int)$totalActive; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Đơn đặt gần đây</h5>
                <?php if (count($recentBookings) === 0): ?>
                    <div class="alert alert-info">Chưa có đơn đặt nào</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Khách hàng</th>
                                    <th>Sân</th>
                                    <th>Ngày</th>
                                    <th>Giờ</th>
                                    <th>Tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $i => $b): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($b['customer_phone']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($b['pitch_name']); ?></td>
                                        <td><?php echo formatDate($b['booking_date']); ?></td>
                                        <td><?php echo substr($b['start_time'],0,5) . '-' . substr($b['end_time'],0,5); ?></td>
                                        <td class="fw-semibold text-success"><?php echo formatMoney($b['total_price']); ?></td>
                                        <td>
                                            <?php if ($b['status'] === 'confirmed'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Đã duyệt</span>
                                            <?php elseif ($b['status'] === 'pending'): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Chờ duyệt</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Đã hủy</span>
                                            <?php endif; ?>
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
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Sân đang hoạt động</h5>
                <?php if (count($pitches) === 0): ?>
                    <div class="alert alert-info">Chưa có sân</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pitches as $p): if ($p['status'] !== 'active') continue; ?>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <small class="text-muted">Sân <?php echo (int)$p['type']; ?> người</small>
                                </div>
                                <div class="text-success fw-bold"><?php echo formatMoney($p['price_per_hour']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

@endsection
