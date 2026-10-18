@extends('layouts.admin')
@section('title', 'Yêu cầu hủy / hoàn cọc')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Yêu cầu hủy và hoàn cọc</h3>
        <p class="text-secondary mb-0">Duyệt yêu cầu sẽ hủy đơn; tiền cọc VNPay được hoàn về phương thức thanh toán ban đầu.</p>
    </div>
    <a href="<?php echo route('admin/bookings'); ?>" class="btn btn-outline-success">Danh sách đặt sân</a>
</div>
<?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>
<?php if (!$requests): ?>
    <div class="alert alert-info">Chưa có yêu cầu hủy nào.</div>
<?php else: ?>
    <div class="d-grid gap-3">
        <?php foreach ($requests as $request): ?>
            <?php
                $requestLabels = [
                    'pending' => 'Chờ duyệt',
                    'processing' => 'Đang xử lý',
                    'approved' => 'Đã hủy',
                    'refunded' => 'Đã hoàn tiền',
                    'rejected' => 'Đã từ chối',
                    'manual_review' => 'Cần kiểm tra thủ công',
                ];
                $badgeColors = [
                    'pending' => 'warning',
                    'processing' => 'info',
                    'approved' => 'success',
                    'refunded' => 'success',
                    'rejected' => 'secondary',
                    'manual_review' => 'danger',
                ];
            ?>
            <article class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h4 class="h5 fw-bold mb-1"><?php echo e($request['pitch_name']); ?> · Đơn #<?php echo (int)$request['booking_id']; ?></h4>
                            <div class="text-secondary">
                                <?php echo e(formatDate($request['booking_date'])); ?>
                                · <?php echo e(formatTime($request['start_time'])); ?>–<?php echo e(formatTime($request['end_time'])); ?>
                            </div>
                        </div>
                        <span class="badge text-bg-<?php echo e($badgeColors[$request['status']] ?? 'secondary'); ?>">
                            <?php echo e($requestLabels[$request['status']] ?? $request['status']); ?>
                        </span>
                    </div>
                    <div class="row g-2 small mb-3">
                        <div class="col-md-4"><strong>Khách:</strong> <?php echo e($request['customer_name']); ?> · <?php echo e($request['customer_phone']); ?></div>
                        <div class="col-md-4"><strong>Lý do:</strong> <?php echo e($request['request_reason']); ?></div>
                        <div class="col-md-4"><strong>Tiền cọc cần hoàn:</strong> <?php echo formatMoney($request['refund_amount']); ?></div>
                        <?php if (!empty($request['refund_message'])): ?>
                            <div class="col-12"><strong>Trạng thái xử lý:</strong> <?php echo e($request['refund_message']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($request['status'] === 'pending'): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <form method="post" action="<?php echo route('admin/cancellation-requests/' . (int)$request['id'] . '/approve'); ?>"
                                  onsubmit="return confirm('Duyệt sẽ hủy đơn và gửi yêu cầu hoàn <?php echo e(formatMoney($request['refund_amount'])); ?> qua VNPay. Tiếp tục?');">
                                <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                                <button type="submit" class="btn btn-danger">Duyệt hủy<?php echo (float)$request['refund_amount'] > 0 ? ' và hoàn cọc' : ''; ?></button>
                            </form>
                            <details>
                                <summary class="btn btn-outline-secondary" style="cursor:pointer;">Từ chối</summary>
                                <form method="post" action="<?php echo route('admin/cancellation-requests/' . (int)$request['id'] . '/reject'); ?>" class="mt-2">
                                    <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                                    <textarea class="form-control mb-2" name="review_message" rows="2" maxlength="500"
                                              placeholder="Lý do từ chối gửi cho khách" required></textarea>
                                    <button type="submit" class="btn btn-outline-danger">Xác nhận từ chối</button>
                                </form>
                            </details>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
@endsection
