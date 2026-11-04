@extends('layouts.admin')
@section('title', 'Hóa đơn ' . bookingCode($booking['id']))
@section('content')
<?php
$invoicePaymentMethods = \App\Models\Booking::getPaymentMethods();
$serviceTotal = array_sum(array_map(
    static fn ($item) => (float)$item['subtotal'],
    $services
));
$received = (float)$received;
$refunded = (float)$refunded;
$netPaid = (float)$netPaid;
$remaining = (float)$remaining;
?>
<style>
    @media print {
        body { background: #fff !important; }
        .invoice-no-print, .col-md-3, header, footer { display: none !important; }
        .container-fluid, .row, .col-md-9, .col-lg-10 { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .invoice-paper { border: 0 !important; box-shadow: none !important; }
        .invoice-paper .card-body { padding: 0 !important; }
        a { color: #000 !important; text-decoration: none !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 invoice-no-print">
    <a class="btn btn-outline-secondary" href="<?php echo route('admin/bookings/' . (int)$booking['id']); ?>">← Quay lại đơn</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">🖨 In hóa đơn</button>
</div>

<div class="card border-0 shadow-sm invoice-paper">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex justify-content-between flex-wrap gap-3 border-bottom pb-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1">HÓA ĐƠN ĐẶT SÂN</h2>
                <div class="text-muted">Mã hóa đơn: <?php echo e(bookingCode($booking['id'])); ?></div>
            </div>
            <div class="text-md-end">
                <div><strong>Ngày lập:</strong> <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></div>
                <div><strong>Ngày đặt:</strong> <?php echo e($booking['booking_date']); ?></div>
                <div><strong>Trạng thái:</strong> <?php echo e($booking['status']); ?></div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <h6 class="fw-bold">Khách hàng</h6>
                <div><?php echo e($booking['customer_name'] ?? ''); ?></div>
                <div><?php echo e($booking['customer_phone'] ?? ''); ?></div>
                <?php if (!empty($booking['customer_email'])): ?>
                    <div><?php echo e($booking['customer_email']); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">Thông tin sân</h6>
                <div><?php echo e($booking['pitch_name'] ?? 'Sân bóng'); ?><?php if (!empty($booking['court_number'])): ?> · Sân số <?php echo (int)$booking['court_number']; ?><?php endif; ?></div>
                <div><?php echo e($booking['booking_date']); ?> · <?php echo e(substr((string)$booking['start_time'], 0, 5)); ?>–<?php echo e(substr((string)$booking['end_time'], 0, 5)); ?></div>
            </div>
        </div>

        <h6 class="fw-bold">Chi tiết dịch vụ</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr><th>Hạng mục</th><th class="text-end">Số lượng / thời lượng</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Thuê sân</td>
                        <td class="text-end"><?php echo e(substr((string)$booking['start_time'], 0, 5)); ?>–<?php echo e(substr((string)$booking['end_time'], 0, 5)); ?></td>
                        <td class="text-end"><?php echo formatMoney($pitchTotal); ?></td>
                        <td class="text-end"><?php echo formatMoney($pitchTotal); ?></td>
                    </tr>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?php echo e($service['service_name'] ?? 'Dịch vụ'); ?></td>
                            <td class="text-end"><?php echo (int)$service['quantity']; ?> <?php echo e($service['service_unit'] ?? ''); ?></td>
                            <td class="text-end"><?php echo formatMoney($service['unit_price']); ?></td>
                            <td class="text-end"><?php echo formatMoney($service['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-end">Tổng cộng</th><th class="text-end"><?php echo formatMoney($booking['total_price']); ?></th></tr>
                </tfoot>
            </table>
        </div>

        <div class="row justify-content-end mb-4">
            <div class="col-md-7 col-lg-6">
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Tiền đặt cọc</span><strong><?php echo formatMoney($booking['deposit']); ?></strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Tổng tiền đã thu</span><strong><?php echo formatMoney($received); ?></strong></div>
                <?php if ($refunded > 0): ?>
                    <div class="d-flex justify-content-between py-2 border-bottom text-primary"><span>Đã hoàn (VNPay đã tiếp nhận)</span><strong>− <?php echo formatMoney($refunded); ?></strong></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Đã thanh toán ròng</span><strong><?php echo formatMoney($netPaid); ?></strong></div>
                <div class="d-flex justify-content-between py-2 fs-5"><span>Còn phải trả</span><strong><?php echo formatMoney($remaining); ?></strong></div>
            </div>
        </div>

        <h6 class="fw-bold">Lịch sử giao dịch</h6>
        <?php if (empty($transactions)): ?>
            <p class="text-muted">Chưa ghi nhận giao dịch thanh toán.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>Thời gian</th><th>Trạng thái</th><th>Phương thức</th><th>Mã giao dịch / ghi chú</th><th class="text-end">Số tiền</th></tr></thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            $invoiceStatusLabels = ['success' => 'Đã thu', 'failed' => 'Thất bại', 'refund' => 'Hoàn tiền đã tiếp nhận'];
                            ?>
                            <tr>
                                <td><?php echo formatDateTime($transaction['created_at']); ?></td>
                                <td><?php echo e($invoiceStatusLabels[$transaction['status']] ?? 'Không rõ'); ?></td>
                                <td><?php echo e($invoicePaymentMethods[$transaction['payment_method']] ?? $transaction['payment_method']); ?></td>
                                <td><?php echo e($transaction['reference_code'] ?? ''); ?> <?php echo e($transaction['note'] ?? ''); ?></td>
                                <td class="text-end"><?php echo formatMoney($transaction['amount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="text-muted small mt-4 mb-0">Hóa đơn được lập từ dữ liệu đặt sân và lịch sử giao dịch lưu trên hệ thống.</p>
    </div>
</div>
@endsection
