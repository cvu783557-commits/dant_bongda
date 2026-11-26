@extends('layouts.admin')
@section('title', 'Hóa đơn bán lẻ #' . (int)$sale['id'])
@section('content')
<?php $paymentMethodLabel = $sale['payment_method'] === 'banking' ? 'Chuyển khoản' : 'Tiền mặt'; ?>
<style>
    @media print {
        body { background: #fff !important; }
        .sale-invoice-no-print, .col-md-3, header, footer { display: none !important; }
        .container-fluid, .row, .col-md-9, .col-lg-10 { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .sale-invoice-paper { border: 0 !important; box-shadow: none !important; }
        .sale-invoice-paper .card-body { padding: 0 !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 sale-invoice-no-print">
    <a class="btn btn-outline-secondary" href="<?php echo route('admin/service-requests'); ?>">← Quay lại yêu cầu dịch vụ</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">🖨 In hóa đơn</button>
</div>

<div class="card border-0 shadow-sm sale-invoice-paper">
    <div class="card-body p-4 p-md-5">
        <div class="text-center border-bottom pb-3 mb-4">
            <h2 class="fw-bold mb-1">HÓA ĐƠN BÁN DỊCH VỤ</h2>
            <div class="text-muted">Mã hóa đơn: DV-<?php echo str_pad((string)$sale['id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div><strong>Ngày lập:</strong> <?php echo e(formatDateTime($sale['created_at'])); ?></div>
                <div><strong>Nhân viên:</strong> <?php echo e($sale['recorded_by_name'] ?? ''); ?></div>
            </div>
            <div class="col-sm-6">
                <div><strong>Khách hàng:</strong> <?php echo e($sale['customer_name'] ?: 'Khách mua trực tiếp'); ?></div>
                <?php if (!empty($sale['customer_phone'])): ?><div><strong>Số điện thoại:</strong> <?php echo e($sale['customer_phone']); ?></div><?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr><th>Dịch vụ</th><th class="text-end">Số lượng</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo e($sale['service_name']); ?></td>
                        <td class="text-end"><?php echo (int)$sale['quantity']; ?> <?php echo e($sale['unit']); ?></td>
                        <td class="text-end"><?php echo formatMoney($sale['unit_price']); ?></td>
                        <td class="text-end"><?php echo formatMoney($sale['total_amount']); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-end">Tổng thanh toán</th><th class="text-end"><?php echo formatMoney($sale['total_amount']); ?></th></tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-between border-bottom py-2">
            <span>Phương thức thanh toán</span><strong><?php echo e($paymentMethodLabel); ?> · Đã thanh toán</strong>
        </div>
        <p class="text-center text-muted small mt-4 mb-0">Cảm ơn quý khách!</p>
    </div>
</div>
@endsection
