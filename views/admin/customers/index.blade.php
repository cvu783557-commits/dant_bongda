@extends('layouts.admin')
@section('title', 'Quản lý khách hàng')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Quản lý khách hàng</h3>
    <a href="<?php echo route('admin/dashboard'); ?>" class="btn btn-outline-secondary">Xem dashboard</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo route('admin/customers'); ?>" class="row g-2 align-items-end">
            <div class="col-sm-10">
                <label for="customer-search" class="form-label small mb-1">Tìm theo tên, số điện thoại hoặc email</label>
                <input id="customer-search" type="search" name="q" class="form-control" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nhập thông tin khách hàng">
            </div>
            <div class="col-sm-2 d-flex gap-2">
                <button type="submit" class="btn btn-success flex-grow-1">Tìm kiếm</button>
                <a href="<?php echo route('admin/customers'); ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (count($customers) === 0): ?>
    <div class="alert alert-info mb-0">Chưa có khách hàng phù hợp.</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th class="text-center">Số đơn</th>
                        <th>Tổng tiền đã duyệt</th>
                        <th>Lần đặt gần nhất</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($customer['customer_phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($customer['customer_email'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-center"><?php echo (int)$customer['booking_count']; ?></td>
                            <td class="fw-semibold text-success"><?php echo formatMoney($customer['confirmed_total']); ?></td>
                            <td><?php echo formatDate($customer['last_booking_date']); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo route('admin/customers/detail?phone=' . rawurlencode($customer['customer_phone'])); ?>">Xem chi tiết</a>
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