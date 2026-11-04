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
