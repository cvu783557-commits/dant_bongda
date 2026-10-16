@extends('layouts.admin')
@section('title', 'Lịch sân bóng')
@section('content')

<?php
function findBookingAtSlot($slotStart, $slotEnd, $bookings) {
    foreach ($bookings as $b) {
        if ($slotStart < $b['end_time'] && $slotEnd > $b['start_time']) {
            return $b;
        }
    }
    return null;
}
function findLockAtSlot($slotStart, $slotEnd, $locks) {
    foreach ($locks as $l) {
        if ($slotStart < $l['end_time'] && $slotEnd > $l['start_time']) {
            return $l;
        }
    }
    return null;
}

$today = date('Y-m-d');
$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold mb-0">📅 Lịch sân bóng</h3>
    <form method="get" action="" class="row g-2 align-items-end flex-wrap">
        <div class="col-auto">
            <input type="date" name="date" class="form-control" value="<?php echo e($date); ?>">
        </div>
        <div class="col-auto">
            <select name="pitch_id" class="form-select">
                <option value="">-- Tất cả sân --</option>
                <?php foreach ($pitches as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo (string)$pitchId === (string)$p['id'] ? 'selected' : ''; ?>>
                        <?php echo e($p['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <a href="<?php echo route('admin/bookings/calendar') . '?date=' . $prevDate . ($pitchId ? '&pitch_id=' . $pitchId : ''); ?>" class="btn btn-outline-secondary">← Hôm qua</a>
            <button type="button" onclick="location.href='<?php echo route('admin/bookings/calendar') . ($pitchId ? '?pitch_id=' . $pitchId : ''); ?>'" class="btn btn-outline-success">Hôm nay</button>
            <a href="<?php echo route('admin/bookings/calendar') . '?date=' . $nextDate . ($pitchId ? '&pitch_id=' . $pitchId : ''); ?>" class="btn btn-outline-secondary">Ngày mai →</a>
            <button type="submit" class="btn btn-success">🔍 Xem</button>
            <?php if (!empty($canCreate)): ?>
                <a href="<?php echo route('admin/bookings/create') . '?date=' . $date; ?>" class="btn btn-primary">➕ Thêm lịch</a>
            <?php endif; ?>
            <?php if (!empty($canLock)): ?>
                <a href="<?php echo route('admin/pitch-lock/create') . '?date=' . $date . ($pitchId ? '&pitch_id=' . $pitchId : ''); ?>" class="btn btn-warning text-dark fw-semibold" title="Khóa 1 hoặc nhiều khung giờ liên tục">🔒 Khóa khung giờ</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="mb-3 d-flex flex-wrap align-items-center gap-3">
    <h4 class="fw-bold mb-0"><?php echo formatDate($date); ?> <span class="fs-6 fw-normal text-muted">(<?php echo $date === $today ? 'Hôm nay' : ''; ?>)</span></h4>
    <div class="d-flex gap-3 small flex-wrap">
        <span class="badge border bg-success-subtle text-success border-success-subtle">✅ Trống (có thể đặt)</span>
        <span class="badge border bg-warning-subtle text-warning-emphasis border-warning-subtle">⏳ Chờ xác nhận</span>
        <span class="badge border bg-primary-subtle text-primary border-primary-subtle">✔ Đã xác nhận</span>
        <span class="badge border bg-info-subtle text-info-emphasis border-info-subtle">⚡ Đang dùng</span>
        <span class="badge border bg-danger-subtle text-danger border-danger-subtle">🔒 Đã khóa</span>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<?php if (count($pitchMap) === 0): ?>
    <div class="alert alert-warning">Không có sân nào được chọn. Vui lòng thêm sân hoặc bỏ lọc sân.</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" style="font-size: 0.88rem;">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 220px;" class="sticky-column-left bg-light">Sân \ Giờ</th>
                        <?php foreach ($slots as $s): ?>
                            <th class="text-nowrap text-center" style="min-width: 110px;">
                                <?php echo substr($s['start_time'],0,5) . '<br><small class="text-muted fw-normal">' . substr($s['end_time'],0,5) . '</small>'; ?>
                            </th>
                        <?php endforeach; ?>
                        <?php if (!empty($canLock)): ?>
                            <th class="text-nowrap text-center bg-warning bg-opacity-10" style="min-width: 120px;">
                                🔒<br><small class="text-muted fw-normal">Hành động</small>
                            </th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pitchMap as $pid => $info):
                        $p = $info['pitch'];
                        $listBookings = $info['booking'];
                        $listLocks    = $info['locks'];

                        $used = [];
                        $skipMap = [];
                        $slotBookings = [];
                        $slotLocks = [];

                        foreach ($slots as $idx => $s) {
                            $slotLocks[$idx]    = findLockAtSlot($s['start_time'], $s['end_time'], $listLocks);
                            $slotBookings[$idx] = $slotLocks[$idx] ? null : findBookingAtSlot($s['start_time'], $s['end_time'], $listBookings);
                        }

                        for ($i = 0; $i < count($slots); $i++) {
                            $l = $slotLocks[$i];
                            $b = $slotBookings[$i];

                            if ($l) {
                                if (isset($used['lock_' . $l['id']])) { $skipMap[$i] = -1; continue; }
                                $used['lock_' . $l['id']] = true;
                                $span = 1;
                                for ($j = $i + 1; $j < count($slots); $j++) {
                                    $ll = $slotLocks[$j];
                                    if ($ll && (int)$ll['id'] === (int)$l['id']) $span++;
                                    else break;
                                }
                                $skipMap[$i] = ['type' => 'lock', 'span' => $span, 'obj' => $l];
                                continue;
                            }

                            if ($b) {
                                if (isset($used['bk_' . $b['id']])) { $skipMap[$i] = -1; continue; }
                                $used['bk_' . $b['id']] = true;
                                $span = 1;
                                for ($j = $i + 1; $j < count($slots); $j++) {
                                    $bb = $slotBookings[$j];
                                    if ($bb && (int)$bb['id'] === (int)$b['id']) $span++;
                                    else break;
                                }
                                $skipMap[$i] = ['type' => 'booking', 'span' => $span, 'obj' => $b];
                                continue;
                            }

                            $skipMap[$i] = ['type' => 'empty', 'span' => 1, 'slotIdx' => $i, 'obj' => $slots[$i]];
                        }
                    ?>
                        <tr>
                            <td class="sticky-column-left bg-white fw-semibold align-middle">
                                <div><?php echo e($p['name']); ?></div>
                                <div class="small text-muted fw-normal">Sân <?php echo (int)$p['type']; ?> người • <?php echo formatMoney($p['price_per_hour']); ?>/h</div>
                            </td>
                            <?php for ($i = 0; $i < count($slots); $i++):
                                if ($skipMap[$i] === -1) continue;
                                $infoCell = $skipMap[$i];
                                $type = $infoCell['type'];
                                $span = (int)$infoCell['span'];
                            ?>
                                <?php if ($type === 'empty'):
                                    $s = $infoCell['obj'];
                                ?>
                                    <td class="text-center text-muted align-middle <?php echo $date === $today && substr($s['end_time'],0,5) < date('H:i') ? 'bg-light opacity-50' : ''; ?>"
                                        style="min-width: 110px; height: 66px; <?php echo $date >= $today ? 'cursor:pointer;' : ''; ?>"
                                        <?php if ($date >= $today && !empty($canCreate)): ?>
                                            onclick="location.href='<?php echo route('admin/bookings/create') . '?date=' . $date . '&pitch_id=' . $p['id'] . '&start_time=' . substr($s['start_time'],0,5); ?>'"
                                            title="Click để đặt giờ này (hoặc dùng nút 🔒 để khóa)"
                                            ondblclick="if(confirm('🔒 Khóa khung giờ <?php echo substr($s['start_time'],0,5); ?> - <?php echo substr($s['end_time'],0,5); ?> của sân này?')){location.href='<?php echo route('admin/pitch-lock/create') . '?date=' . $date . '&pitch_id=' . $p['id'] . '&start_time=' . substr($s['start_time'],0,5) . '&end_time=' . substr($s['end_time'],0,5); ?>';}"
                                        <?php endif; ?>>
                                        <?php if ($date >= $today): ?>
                                            <span class="small text-success fw-semibold">Trống</span>
                                        <?php else: ?>
                                            <span class="small text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($type === 'lock'):
                                    $l = $infoCell['obj'];
                                ?>
                                    <td colspan="<?php echo $span; ?>"
                                        class="bg-danger bg-opacity-10 border-danger align-middle"
                                        style="min-width: <?php echo (110 * $span); ?>px; cursor: pointer;"
                                        onclick="<?php if (!empty($canLock)): ?>location.href='<?php echo route('admin/pitch-lock/' . (int)$l['id'] . '/unlock'); ?>'<?php endif; ?>"
                                        title="<?php echo !empty($canLock) ? 'Click để MỞ KHÓA #' . $l['id'] : 'Khung giờ đã khóa'; ?>">
                                        <div class="px-2 py-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="small text-danger">🔒 ĐÃ KHÓA #<?php echo str_pad((string)$l['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                            </div>
                                            <div class="fw-semibold mb-0 lh-1 text-danger-emphasis">
                                                <?php echo !empty($l['reason']) ? e(mb_substr($l['reason'], 0, 22)) : 'Không có ghi chú'; ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?php echo formatTime($l['start_time']) . ' → ' . formatTime($l['end_time']); ?>
                                            </div>
                                            <?php if (!empty($canLock)): ?>
                                                <div class="small fw-semibold text-danger mt-1">(Click để mở khóa)</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php else:
                                    $b = $infoCell['obj'];
                                    $color = match ($b['status']) {
                                        'pending'     => 'warning',
                                        'confirmed'   => 'primary',
                                        'in_progress' => 'info',
                                        'completed'   => 'success',
                                        'cancelled'   => 'secondary',
                                        default       => 'secondary',
                                    };
                                ?>
                                    <td colspan="<?php echo $span; ?>"
                                        class="bg-<?php echo $color; ?> bg-opacity-10 border-<?php echo $color; ?> align-middle"
                                        style="min-width: <?php echo (110 * $span); ?>px; cursor: pointer;"
                                        onclick="location.href='<?php echo route('admin/bookings/' . $b['id']); ?>'"
                                        title="Xem chi tiết đơn <?php echo bookingCode($b['id']); ?>">
                                        <div class="px-2 py-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="small text-<?php echo $color; ?>"><?php echo bookingCode($b['id']); ?></strong>
                                                <?php echo bookingStatusBadge($b['status']); ?>
                                            </div>
                                            <div class="fw-semibold mb-0 lh-1"><?php echo e($b['customer_name']); ?></div>
                                            <div class="small text-muted">📞 <?php echo e($b['customer_phone']); ?></div>
                                            <div class="small">
                                                <?php echo formatTime($b['start_time']) . ' → ' . formatTime($b['end_time']); ?>
                                            </div>
                                            <div class="small fw-semibold text-success"><?php echo formatMoney($b['total_price']); ?></div>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if (!empty($canLock)): ?>
                                <td class="text-center align-middle bg-warning bg-opacity-10" style="min-width: 120px;">
                                    <?php if ($date >= $today): ?>
                                        <a href="<?php echo route('admin/pitch-lock/create') . '?date=' . $date . '&pitch_id=' . $p['id']; ?>"
                                           class="btn btn-sm btn-warning text-dark fw-semibold w-100 mb-1" title="Khóa 1 hoặc nhiều giờ liên tục của sân <?php echo e($p['name']); ?>">
                                            🔒 Khóa sân này
                                        </a>
                                    <?php else: ?>
                                        <span class="small text-muted">— Quá khứ —</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($canLock) && $date >= $today): ?>
<div class="mt-3 small text-muted">
    💡 <strong>Mẹo:</strong> Click đúp (double-click) vào ô <span class="text-success fw-semibold">Trống</span> để khóa nhanh đúng 1 khung giờ đó.
    Hoặc dùng nút <span class="badge bg-warning text-dark">🔒 Khóa khung giờ</span> trên cùng để khóa nhiều giờ liên tục.
</div>
<?php endif; ?>
<?php endif; ?>

<style>
.sticky-column-left {
    position: sticky;
    left: 0;
    z-index: 2;
    box-shadow: 2px 0 4px rgba(0,0,0,0.05);
}
</style>
@endsection
