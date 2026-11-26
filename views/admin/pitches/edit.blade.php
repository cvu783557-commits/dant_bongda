@extends('layouts.admin')
@section('title', 'Sửa sân bóng')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Sửa sân bóng</h3>
    <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">← Danh sách sân</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?php echo route('admin/pitches/' . $pitch['id']); ?>" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên sân <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($old['name'] ?? $pitch['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loại sân <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <?php foreach ([5,7,11] as $t): ?>
                                <option value="<?php echo $t; ?>"
                                    <?php
                                        $cur = $old['type'] ?? $pitch['type'];
                                        echo ((int)$cur === $t) ? 'selected' : '';
                                    ?>>Sân <?php echo $t; ?> người</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Giá thuê / giờ (₫) <span class="text-danger">*</span></label>
                        <input type="number" name="price_per_hour" class="form-control" min="0" required
                               value="<?php echo htmlspecialchars($old['price_per_hour'] ?? $pitch['price_per_hour']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số sân con</label>
                        <input type="number" name="field_count" class="form-control" min="1" max="50" required
                               value="<?php echo e($old['field_count'] ?? $pitch['field_count'] ?? 1); ?>">
                        <div class="form-text">Giảm số lượng sẽ không xóa lịch sử các sân đã đặt.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ sân</label>
                        <input type="text" name="address" class="form-control" maxlength="255"
                               value="<?php echo e($old['address'] ?? $pitch['address'] ?? ''); ?>" placeholder="Số nhà, đường, phường/xã, quận/huyện, Hà Nội">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Mở cửa</label>
                            <?php $openingValue = substr($old['opening_time'] ?? formatTime($pitch['opening_time'] ?? '06:00'), 0, 5); ?>
                            <select name="opening_time" class="form-select" required>
                                <?php for ($hour = 0; $hour <= 23; $hour++): ?>
                                    <?php $timeValue = sprintf('%02d:00', $hour); ?>
                                    <option value="<?php echo $timeValue; ?>" <?php echo $openingValue === $timeValue ? 'selected' : ''; ?>>
                                        <?php echo $timeValue; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Đóng cửa</label>
                            <?php $closingValue = substr($old['closing_time'] ?? formatTime($pitch['closing_time'] ?? '23:00'), 0, 5); ?>
                            <select name="closing_time" class="form-select" required>
                                <?php for ($hour = 1; $hour <= 23; $hour++): ?>
                                    <?php $timeValue = sprintf('%02d:00', $hour); ?>
                                    <option value="<?php echo $timeValue; ?>" <?php echo $closingValue === $timeValue ? 'selected' : ''; ?>>
                                        <?php echo $timeValue; ?>
                                    </option>
                                <?php endfor; ?>
                                <option value="00:00" <?php echo $closingValue === '00:00' ? 'selected' : ''; ?>>00:00 (24:00)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" class="form-select">
                            <?php $curStatus = $old['status'] ?? $pitch['status']; ?>
                            <option value="active"   <?php echo $curStatus === 'active'   ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="inactive" <?php echo $curStatus === 'inactive' ? 'selected' : ''; ?>>Tạm dừng</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh sân hiện tại</label>
                        <div class="mb-2">
                            <img src="<?php echo $pitch['image'] ? (str_starts_with($pitch['image'], 'http') ? $pitch['image'] : file_url($pitch['image'])) : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=Football%20field%20green%20grass%20aerial&image_size=square'; ?>"
                                 class="rounded border" style="width:100%;max-width:320px;height:180px;object-fit:cover;" alt="pitch">
                        </div>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <small class="text-muted d-block mt-1">Để trống nếu không muốn thay ảnh</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" rows="6" class="form-control"><?php echo htmlspecialchars($old['description'] ?? $pitch['description'] ?? ''); ?></textarea>
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-3">Tiện ích sân</h6>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chất lượng mặt cỏ</label>
                        <input type="text" name="grass_quality" class="form-control" maxlength="100"
                               value="<?php echo e($old['grass_quality'] ?? $pitch['grass_quality'] ?? ''); ?>" placeholder="Ví dụ: Cỏ nhân tạo, mới thay">
                    </div>
                    <div class="row g-3">
                        <?php foreach ([
                            'has_lighting' => 'Hệ thống đèn chiếu sáng',
                            'has_changing_room' => 'Phòng thay đồ / nhà vệ sinh',
                            'has_parking' => 'Bãi gửi xe',
                        ] as $field => $label): ?>
                            <?php $selectedValue = (string)($old[$field] ?? $pitch[$field] ?? '0'); ?>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="<?php echo e($field); ?>"><?php echo e($label); ?></label>
                                <select id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" class="form-select">
                                    <option value="1" <?php echo $selectedValue === '1' ? 'selected' : ''; ?>>Có</option>
                                    <option value="0" <?php echo $selectedValue !== '1' ? 'selected' : ''; ?>>Không</option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Dịch vụ đi kèm</label>
                        <textarea name="extra_services" rows="3" maxlength="1000" class="form-control"
                                  placeholder="Ví dụ: Thuê áo, thuê bóng, nước uống"><?php echo e($old['extra_services'] ?? $pitch['extra_services'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Giá theo khung giờ</h5>
                    <div class="text-muted small">Chỉnh sửa giá từng giờ cho thứ 2-6 và thứ 7-chủ nhật.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm" id="fillTimePrices">Áp dụng giá mặc định</button>
                    <button type="button" class="btn btn-success btn-sm" id="addTimePrice">+ Thêm khung giờ</button>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" id="timePriceList">
                <?php foreach ($timePrices as $slot): ?>
                    <?php
                        $start = substr($slot['start_time'], 0, 5);
                        $end = substr($slot['end_time'], 0, 5);
                        $weekday = $slot['weekday_price'];
                        $weekend = $slot['weekend_price'];
                    ?>
                    <div class="col time-price-row">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="d-flex align-items-end gap-2 mb-2">
                                <div class="flex-fill">
                                    <label class="form-label small mb-1">Giờ bắt đầu</label>
                                    <input type="time" name="time_prices[start][]" class="form-control time-start" value="<?php echo e($start); ?>" required>
                                </div>
                                <div class="flex-fill">
                                    <label class="form-label small mb-1">Giờ kết thúc</label>
                                    <input type="time" name="time_prices[end][]" class="form-control time-end" value="<?php echo e($end); ?>" required>
                                </div>
                                <button type="button" class="btn btn-outline-danger remove-time-price" title="Xóa khung giờ">×</button>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Thứ 2 - 6</label>
                                    <input type="number" name="time_prices[weekday][]" class="form-control time-weekday" min="0" value="<?php echo (int)$weekday; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">Thứ 7 - CN</label>
                                    <input type="number" name="time_prices[weekend][]" class="form-control time-weekend" min="0" value="<?php echo (int)$weekend; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2 mt-4 justify-content-end">
                <a href="<?php echo route('admin/pitches'); ?>" class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-success px-4">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var list = document.getElementById('timePriceList');
    var fillButton = document.getElementById('fillTimePrices');
    var addButton = document.getElementById('addTimePrice');

    function applyDefaults() {
        document.querySelectorAll('.time-price-row').forEach(function (row) {
            var startInput = row.querySelector('.time-start');
            var hour = parseInt(startInput.value.substring(0, 2), 10);
            row.querySelector('.time-weekday').value =
                hour >= 17 && hour < 20 ? 600000 :
                (hour >= 12 && hour < 17 ? 275000 :
                (hour >= 20 ? 325000 : 250000));
            row.querySelector('.time-weekend').value =
                hour >= 17 && hour < 20 ? 600000 :
                (hour >= 12 && hour < 17 ? 325000 :
                (hour >= 20 ? 375000 : 300000));
        });
    }

    function bindRemove(row) {
        row.querySelector('.remove-time-price').addEventListener('click', function () {
            row.remove();
        });
    }

    if (fillButton) fillButton.addEventListener('click', applyDefaults);
    document.querySelectorAll('.time-price-row').forEach(bindRemove);

    if (addButton) addButton.addEventListener('click', function () {
        var source = document.querySelector('.time-price-row');
        var row = source ? source.cloneNode(true) : null;
        if (!row) return;
        row.querySelector('.time-start').value = '23:00';
        row.querySelector('.time-end').value = '23:30';
        row.querySelector('.time-weekday').value = '250000';
        row.querySelector('.time-weekend').value = '300000';
        bindRemove(row);
        list.appendChild(row);
    });
})();
</script>

@endsection
