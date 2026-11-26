@extends('layouts.admin')
@section('title', 'Yêu cầu dịch vụ khách hàng')
@section('content')
<?php
$statusLabels = [
    'pending' => ['Đang chờ xử lý', 'warning'],
    'accepted' => ['Đã xác nhận', 'success'],
    'rejected' => ['Đã từ chối', 'danger'],
];
$pendingCount = count(array_filter(
    $requests,
    static fn ($request) => $request['status'] === 'pending'
));
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">Yêu cầu dịch vụ khách hàng</h3>
        <div class="text-muted">Khách đang sử dụng sân gửi yêu cầu đồ uống, đồ ăn hoặc thiết bị đến nhân viên.</div>
    </div>
    <span class="badge text-bg-warning fs-6"><?php echo $pendingCount; ?> yêu cầu đang chờ</span>
</div>

<?php if (!empty($success)): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-1">Bán dịch vụ ngoài đơn đặt sân</h5>
        <p class="text-muted mb-3">Ghi nhận khách mua trực tiếp, thanh toán tiền mặt và xuất hóa đơn riêng; giao dịch không cộng vào hóa đơn đặt sân.</p>
        <?php if (empty($services)): ?>
            <div class="alert alert-info mb-0">Hiện không có dịch vụ nào còn hàng để bán.</div>
        <?php else: ?>
            <form method="post" action="<?php echo route('admin/service-requests/sales'); ?>" class="row g-3 align-items-end">
                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                <div class="col-md-5">
                    <label class="form-label" for="sale-service">Dịch vụ</label>
                    <select class="form-select" id="sale-service" name="service_id" required>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo (int)$service['id']; ?>">
                                <?php echo e($service['name']); ?> · <?php echo formatMoney($service['price']); ?> / <?php echo e($service['unit']); ?> · Còn <?php echo (int)$service['stock']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sale-quantity">Số lượng</label>
                    <input class="form-control" id="sale-quantity" type="number" name="quantity" min="1" max="20" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sale-customer-name">Tên khách (không bắt buộc)</label>
                    <input class="form-control" id="sale-customer-name" name="customer_name" maxlength="150">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sale-customer-phone">Số điện thoại (không bắt buộc)</label>
                    <input class="form-control" id="sale-customer-phone" name="customer_phone" maxlength="30">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sale-payment-method">Thanh toán</label>
                    <select class="form-select" id="sale-payment-method" name="payment_method" required>
                        <option value="cash">Tiền mặt</option>
                        <option value="banking">Chuyển khoản</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-success" type="submit">Bán</button>
                </div>
                <div class="col-12 small text-muted">Sau khi ghi nhận, hệ thống mở hóa đơn riêng để in.</div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($sales)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Giao dịch bán lẻ gần đây</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Thời gian</th><th>Khách hàng</th><th>Dịch vụ</th><th>Thanh toán</th><th class="text-end">Thành tiền</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?php echo e(formatDateTime($sale['created_at'])); ?></td>
                            <td><?php echo e($sale['customer_name'] ?: 'Khách mua trực tiếp'); ?><?php if (!empty($sale['customer_phone'])): ?><div class="small text-muted"><?php echo e($sale['customer_phone']); ?></div><?php endif; ?></td>
                            <td><?php echo e($sale['service_name']); ?> × <?php echo (int)$sale['quantity']; ?> <?php echo e($sale['unit']); ?></td>
                            <td><?php echo $sale['payment_method'] === 'banking' ? 'Chuyển khoản' : 'Tiền mặt'; ?></td>
                            <td class="text-end"><?php echo formatMoney($sale['total_amount']); ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo route('admin/service-requests/sales/' . (int)$sale['id'] . '/invoice'); ?>">Hóa đơn</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($requests)): ?>
    <div class="alert alert-info">Chưa có yêu cầu dịch vụ nào.</div>
<?php else: ?>
    <div class="d-grid gap-3">
        <?php foreach ($requests as $request): ?>
            <?php [$statusLabel, $statusColor] = $statusLabels[$request['status']] ?? ['Không rõ', 'secondary']; ?>
            <article class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">
                                Đơn #<?php echo (int)$request['booking_id']; ?> · <?php echo e($request['customer_name'] ?? 'Khách hàng'); ?>
                            </h5>
                            <div class="text-secondary">
                                <?php echo e($request['customer_phone'] ?? ''); ?> ·
                                <?php echo e($request['pitch_name'] ?? 'Sân'); ?> ·
                                Sân số <?php echo (int)$request['court_number']; ?> ·
                                <?php echo e(formatDate($request['booking_date'])); ?>
                                <?php if (!empty($request['start_time'])): ?>
                                    · <?php echo e(formatTime($request['start_time'])); ?>–<?php echo e(formatTime($request['end_time'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge text-bg-<?php echo $statusColor; ?>"><?php echo $statusLabel; ?></span>
                            <div class="small text-secondary mt-1"><?php echo e(formatDateTime($request['created_at'])); ?></div>
                        </div>
                    </div>

                    <div class="rounded bg-light p-3 mb-2">
                        <div class="fw-semibold">Dịch vụ yêu cầu</div>
                        <div><?php echo e($request['item_summary'] ?? ''); ?></div>
                        <div class="text-success fw-semibold mt-1">Tổng cộng: <?php echo formatMoney($request['request_total'] ?? 0); ?></div>
                    </div>
                    <?php if (!empty($request['customer_note'])): ?>
                        <div class="small mb-2"><strong>Ghi chú khách:</strong> <?php echo e($request['customer_note']); ?></div>
                    <?php endif; ?>

                    <?php if ($request['status'] === 'pending'): ?>
                        <form method="post" class="mt-3" action="<?php echo route('admin/service-requests/' . (int)$request['id'] . '/accept'); ?>">
                            <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                            <label class="form-label small" for="staff-note-<?php echo (int)$request['id']; ?>">Ghi chú cho khách (không bắt buộc)</label>
                            <input class="form-control mb-2" id="staff-note-<?php echo (int)$request['id']; ?>"
                                   name="staff_note" maxlength="500" placeholder="Ví dụ: Nhân viên đang mang ra sân">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-success">✓ Xác nhận và cộng vào hóa đơn</button>
                                <button type="submit" class="btn btn-outline-danger"
                                        formaction="<?php echo route('admin/service-requests/' . (int)$request['id'] . '/reject'); ?>"
                                        formnovalidate>Từ chối yêu cầu</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="small text-secondary mt-2">
                            Xử lý bởi <?php echo e($request['handled_by_name'] ?? 'nhân viên'); ?>
                            <?php if (!empty($request['handled_at'])): ?> · <?php echo e(formatDateTime($request['handled_at'])); ?><?php endif; ?>
                            <?php if (!empty($request['staff_note'])): ?> · Ghi chú: <?php echo e($request['staff_note']); ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
@endsection
