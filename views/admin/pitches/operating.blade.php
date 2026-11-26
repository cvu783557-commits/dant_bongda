@extends('layouts.admin')
@section('title', 'Sân đang hoạt động')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-0">Sân đang hoạt động</h3>
        <small class="text-muted">
            Cập nhật lúc <?php echo date('H:i:s'); ?> ngày <?php echo formatDate($date); ?>
        </small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="autoRefresh">
            <label class="form-check-label small text-muted" for="autoRefresh">Tự động làm mới (30s)</label>
        </div>
        <button type="button" class="btn btn-outline-success" onclick="location.reload()">⟳ Làm mới</button>
        <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-success">Quản lý sân</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-success text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Sân hoạt động</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$totalPitches; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-secondary text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Tổng sân con</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$totalCourts; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-danger text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Đang sử dụng</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$busyCourts; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Đang trống</div>
                <div class="display-6 fw-bold mt-1"><?php echo (int)$freeCourts; ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (count($board) === 0): ?>
    <div class="alert alert-info">Hiện chưa có sân nào đang hoạt động.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($board as $item): ?>
            <?php $p = $item['pitch']; ?>
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?php echo $p['image'] ? (str_starts_with($p['image'], 'http') ? $p['image'] : file_url($p['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                                 class="rounded" style="width:64px;height:44px;object-fit:cover;" alt="img">
                            <div>
                                <div class="fw-bold"><?php echo e($p['name']); ?></div>
                                <small class="text-muted">
                                    Sân <?php echo (int)$p['type']; ?> người ·
                                    <?php echo formatTime($p['opening_time']); ?>-<?php echo formatTime($p['closing_time']); ?>
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?php echo (int)$item['busy_count']; ?> đang đá</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo (int)$item['free_count']; ?> trống</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($item['courts'] as $court): ?>
                                <div class="col-12 col-sm-6">
                                    <div class="border rounded p-3 h-100 <?php echo $court['busy'] ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : 'border-success-subtle bg-success-subtle bg-opacity-10'; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">Sân <?php echo (int)$court['number']; ?></span>
                                            <?php if ($court['busy']): ?>
                                                <span class="badge bg-danger">Đang sử dụng</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Trống</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($court['busy']): ?>
                                            <?php $b = $court['booking']; ?>
                                            <div class="small">
                                                <div class="fw-semibold text-truncate"><?php echo e($b['customer_name'] ?: 'Khách lẻ'); ?></div>
                                                <?php if (!empty($b['customer_phone'])): ?>
                                                    <div class="text-muted"><?php echo e($b['customer_phone']); ?></div>
                                                <?php endif; ?>
                                                <div class="text-muted">
                                                    <?php echo formatTime($b['start_time']); ?> - <?php echo formatTime($b['end_time']); ?>
                                                </div>
                                                <div class="mt-2 d-flex gap-1 flex-wrap">
                                                    <?php echo bookingStatusBadge($b['status']); ?>
                                                    <?php echo paymentStatusBadge($b['payment_status']); ?>
                                                </div>
                                                <a href="<?php echo route('admin/bookings/' . $b['id']); ?>" class="btn btn-sm btn-outline-danger mt-2 w-100">Xem đơn</a>
                                                <form method="post" action="<?php echo route('admin/bookings/' . (int)$b['id'] . '/services'); ?>" class="mt-2">
                                                    <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                                                    <label class="form-label mb-1">Thêm dịch vụ vào hóa đơn</label>
                                                    <div class="input-group input-group-sm">
                                                        <select name="service_id" class="form-select" required <?php echo empty($availableServices) ? 'disabled' : ''; ?>>
                                                            <option value="">Chọn dịch vụ</option>
                                                            <?php foreach ($availableServices as $service): ?>
                                                                <option value="<?php echo (int)$service['id']; ?>">
                                                                    <?php echo e($service['name']); ?> · <?php echo formatMoney($service['price']); ?> / <?php echo e($service['unit']); ?> (còn <?php echo (int)$service['stock']; ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="20" required aria-label="Số lượng" style="max-width:76px;" <?php echo empty($availableServices) ? 'disabled' : ''; ?>>
                                                        <button type="submit" class="btn btn-success" <?php echo empty($availableServices) ? 'disabled' : ''; ?>>Thêm</button>
                                                    </div>
                                                    <?php if (empty($availableServices)): ?>
                                                        <div class="text-muted mt-1">Không có dịch vụ còn hàng.</div>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        <?php elseif (!empty($court['upcoming'])): ?>
                                            <?php $u = $court['upcoming']; ?>
                                            <div class="small text-muted">
                                                Ca kế tiếp: <span class="fw-semibold text-dark"><?php echo formatTime($u['start_time']); ?> - <?php echo formatTime($u['end_time']); ?></span><br>
                                                <?php echo e($u['customer_name'] ?: 'Khách lẻ'); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="small text-muted">Không còn ca đặt nào hôm nay.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    (function () {
        var toggle = document.getElementById('autoRefresh');
        if (!toggle) return;
        if (localStorage.getItem('pitchBoardAutoRefresh') === '1') {
            toggle.checked = true;
        }
        var timer = null;
        function schedule() {
            if (timer) clearInterval(timer);
            if (toggle.checked) {
                timer = setInterval(function () { location.reload(); }, 30000);
            }
        }
        toggle.addEventListener('change', function () {
            localStorage.setItem('pitchBoardAutoRefresh', toggle.checked ? '1' : '0');
            schedule();
        });
        schedule();
    })();
</script>

@endsection
