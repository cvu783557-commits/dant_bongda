@extends('layouts.admin')
@section('title', 'Chi tiết dịch vụ')
@section('content')

<!-- HEADER TIÊU ĐỀ LỚN - Gradient nhẹ -->
<div class="service-detail-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <span class="text-success opacity-75 me-1">#<?php echo (int)$service['id']; ?></span>
                <?php echo htmlspecialchars($service['name']); ?>
            </h3>
            <nav style="--bs-breadcrumb-divider: '›';" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a class="text-success text-decoration-none" href="<?php echo route('admin/services'); ?>">Quản lý dịch vụ</a></li>
                    <li class="breadcrumb-item text-muted" aria-current="page">
                        <?php echo htmlspecialchars($categoryName); ?>
                    </li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo route('admin/services/' . $service['id'] . '/edit'); ?>"
               class="btn btn-success px-3 d-inline-flex align-items-center gap-2 shadow-sm">
                ✏️ Sửa thông tin
            </a>
            <a href="<?php echo route('admin/services'); ?>"
               class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-2">
                ← Danh sách
            </a>
        </div>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?php echo e($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="row g-4 service-detail-grid">
    <!-- ===================================== CỘT TRÁI: Thông tin chính ===================================== -->
    <div class="col-lg-4">
        <div class="card service-detail-card border-0 shadow-sm h-100 overflow-hidden">
            <!-- ẢNH DỊCH VỤ -->
            <?php
                $imgSrc = $service['image']
                    ? (str_starts_with($service['image'], 'http') ? $service['image'] : file_url($service['image']))
                    : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=' . urlencode(($categoryName ?? 'product') . ' commercial product modern clean') . '&image_size=square_hd';
            ?>
            <div class="position-relative">
                <img src="<?php echo $imgSrc; ?>"
                     class="w-100"
                     style="height:260px;object-fit:cover;" alt="service">
                <!-- badge trạng thái overlay góc phải trên -->
                <span class="position-absolute top-3 end-3 badge rounded-pill px-3 py-2 shadow-sm
                    <?php echo $service['status'] === 'active' ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                    <?php echo $service['status'] === 'active' ? '● Đang bán' : '● Tạm dừng'; ?>
                </span>
            </div>

            <div class="p-4">
                <!-- Badge nhóm + mã -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge rounded-pill bg-<?php echo $categoryColor; ?> bg-opacity-10 text-<?php echo $categoryColor; ?> border border-<?php echo $categoryColor; ?> border-opacity-25 px-3 py-2 fw-medium">
                        <?php echo htmlspecialchars($categoryName); ?>
                    </span>
                </div>

                <!-- Giá lớn -->
                <div class="mb-4">
                    <div class="small text-muted mb-1">Đơn giá bán lẻ</div>
                    <div class="display-6 fw-bold text-success" style="line-height:1;letter-spacing:-0.5px;">
                        <?php echo formatMoney($service['price']); ?>
                        <span class="fs-6 text-muted fw-normal opacity-75 ms-2">/ <?php echo htmlspecialchars($service['unit']); ?></span>
                    </div>
                </div>

                <!-- 2 thẻ đơn vị + tồn kho (đẹp hơn) -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="rounded-3 border p-3 bg-white h-100">
                            <div class="small text-muted mb-1">Đơn vị</div>
                            <div class="fw-bold fs-5 text-primary"><?php echo htmlspecialchars($service['unit']); ?></div>
                        </div>
                    </div>
                    <?php
                        $stock = (int)$service['stock'];
                        if ($stock === 0) {
                            $stockBg = 'bg-danger bg-opacity-10'; $stockTx = 'text-danger'; $stockIcon = '⛔';
                        } elseif ($stock < 10) {
                            $stockBg = 'bg-warning bg-opacity-15'; $stockTx = 'text-warning-emphasis'; $stockIcon = '⚠️';
                        } else {
                            $stockBg = 'bg-success bg-opacity-10'; $stockTx = 'text-success'; $stockIcon = '✓';
                        }
                    ?>
                    <div class="col-6">
                        <div class="stock-summary rounded-3 <?php echo $stockBg; ?> p-3 h-100">
                            <div class="small text-muted mb-2">Tồn kho hiện tại</div>
                            <div class="stock-summary__value-row <?php echo $stockTx; ?>">
                                <span class="stock-summary__icon"><?php echo $stockIcon; ?></span>
                                <span class="stock-summary__value"><?php echo $stock; ?></span>
                                <span class="stock-summary__unit"><?php echo htmlspecialchars($service['unit']); ?></span>
                                <?php if ($stock === 0): ?>
                                    <span class="badge rounded-pill text-bg-danger">Hết hàng</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mô tả (nếu có) -->
                <?php if (!empty($service['description'])): ?>
                    <div class="border-top pt-4 mb-3">
                        <div class="small text-muted fw-semibold text-uppercase mb-2" style="letter-spacing:0.5px;">Mô tả / Ghi chú nội bộ</div>
                        <div class="text-dark" style="white-space:pre-wrap;line-height:1.7;"><?php echo htmlspecialchars($service['description']); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Meta footer nhỏ gọn -->
                <div class="border-top pt-3 small text-muted row g-2">
                    <div class="col-6"><span class="opacity-50">Mã DV:</span> <strong class="text-dark">#<?php echo (int)$service['id']; ?></strong></div>
                    <div class="col-6 text-end">
                        <span class="opacity-50">Tạo:</span>
                        <strong class="text-dark"><?php echo date('d/m/y', strtotime($service['created_at'])); ?></strong>
                    </div>
                    <?php if (!empty($service['updated_at'])): ?>
                        <div class="col-12 border-top pt-2 mt-1">
                            <span class="opacity-50">Cập nhật cuối:</span>
                            <strong class="text-dark"><?php echo date('d/m/Y H:i', strtotime($service['updated_at'])); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================== CỘT PHẢI: Thao tác + Thống kê + Liên quan ===================================== -->
    <div class="col-lg-8">

        <!-- 1. ĐIỀU CHỈNH TỒN KHO (đẹp lại) -->
        <div id="stockManagement" class="card stock-management-card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="stock-management-header p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">📦 Điều chỉnh tồn kho</h5>
                            <div class="small text-muted">Nhập hoặc xuất hàng, số lượng không được vượt quá tồn kho.</div>
                        </div>
                        <div class="stock-current rounded-pill px-3 py-2 border bg-white">
                            <span class="small text-muted me-1">Đang có</span>
                            <strong class="<?php echo $stockTx; ?>"><?php echo $stock; ?> <?php echo htmlspecialchars($service['unit']); ?></strong>
                        </div>
                    </div>
                </div>

                <form method="post" action="<?php echo route('admin/services/' . $service['id'] . '/adjust-stock'); ?>"
                      class="stock-adjustment-form p-4">
                    <input type="hidden" name="_csrf_token" value="<?php echo e(csrfToken()); ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-success mb-2" for="quickPlus">＋ Nhập kho</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-success-subtle text-success fw-bold">+</span>
                                <input type="number" id="quickPlus" class="form-control" min="1" step="1"
                                       placeholder="Số lượng nhập" onchange="syncDelta(this.value)">
                                <span class="input-group-text bg-light text-muted"><?php echo htmlspecialchars($service['unit']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-danger mb-2" for="quickMinus">－ Xuất kho</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-danger-subtle text-danger fw-bold">−</span>
                                <input type="number" id="quickMinus" class="form-control" min="1" step="1"
                                       placeholder="Số lượng xuất" onchange="syncDelta('-' + this.value)">
                                <span class="input-group-text bg-light text-muted"><?php echo htmlspecialchars($service['unit']); ?></span>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <label class="form-label fw-semibold mb-2" for="deltaReal">🔢 Điều chỉnh trực tiếp</label>
                            <div class="input-group">
                                <input type="number" name="delta" id="deltaReal" class="form-control form-control-lg fw-semibold"
                                       step="1" required placeholder="Ví dụ: +5 nhập hoặc -5 xuất">
                                <span class="input-group-text bg-light text-muted"><?php echo htmlspecialchars($service['unit']); ?></span>
                            </div>
                            <small class="text-muted d-block mt-2">Số dương để nhập kho, số âm để xuất kho.</small>
                        </div>
                        <div class="col-lg-4 d-grid">
                            <button type="submit" class="btn btn-success btn-lg fw-semibold">✓ Cập nhật tồn kho</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold mb-2" for="stock-note">📝 Ghi chú</label>
                            <input type="text" id="stock-note" name="note" class="form-control" maxlength="250"
                                   placeholder="Ví dụ: Nhập hàng từ nhà cung cấp, hàng hỏng, điều chỉnh kiểm kê...">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. 📊 THỐNG KÊ BÁN HÀNG (THAY CHO PHẦN SƠ ĐỒ LUỒNG CŨ) -->
        <div class="card service-detail-card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">📊 Thống kê bán hàng</h5>
                <span class="badge bg-light text-dark border small">Tính trên toàn bộ đơn đặt sân</span>
            </div>
            <div class="card-body pt-0 px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="service-stat-card service-stat-card--orders h-100">
                            <div class="service-stat-card__label">Số đơn</div>
                            <div class="d-flex align-items-baseline flex-wrap gap-2">
                                <div class="service-stat-card__value text-primary">
                                    <?php echo (int)($stats['total_orders'] ?? 0); ?>
                                </div>
                                <div class="small text-muted">lần xuất hiện</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-stat-card service-stat-card--quantity h-100">
                            <div class="service-stat-card__label">Tổng số lượng đã bán</div>
                            <div class="d-flex align-items-baseline flex-wrap gap-2">
                                <div class="service-stat-card__value text-warning-emphasis">
                                    <?php echo (int)($stats['total_qty_sold'] ?? 0); ?>
                                </div>
                                <div class="small text-muted"><?php echo htmlspecialchars($service['unit']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-stat-card service-stat-card--revenue h-100">
                            <div class="service-stat-card__label">Doanh thu</div>
                            <div class="service-stat-card__revenue">
                                <?php echo formatMoney($stats['total_revenue'] ?? 0); ?>
                            </div>
                            <div class="small mt-2">Từ dịch vụ này</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. 🔗 DỊCH VỤ CÙNG PHÂN LOẠI (THAY CHO BOX LƯU Ý XANH CŨ) -->
        <div class="card service-detail-card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">🔗 Dịch vụ cùng nhóm "<?php echo htmlspecialchars($categoryName); ?>"</h5>
                <span class="badge bg-<?php echo $categoryColor; ?> bg-opacity-10 text-<?php echo $categoryColor; ?> border">
                    <?php echo count($related); ?> liên quan
                </span>
            </div>
            <div class="card-body pt-0 px-4 pb-4">
                <?php if (count($related) === 0): ?>
                    <div class="text-center py-5 text-muted bg-light bg-opacity-50 rounded-4 border border-dashed">
                        <div style="font-size:2rem;opacity:0.3;">✨</div>
                        <div class="mt-2 small">Đây là dịch vụ đầu tiên trong nhóm <strong><?php echo htmlspecialchars($categoryName); ?></strong>.</div>
                        <a href="<?php echo route('admin/services/create'); ?>" class="btn btn-sm btn-success mt-3">+ Thêm dịch vụ cùng nhóm</a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($related as $r): ?>
                            <?php
                                $rImg = $r['image']
                                    ? (str_starts_with($r['image'], 'http') ? $r['image'] : file_url($r['image']))
                                    : 'https://coresg-normal.trae.ai/api/ide/v1/text_to_image?prompt=' . urlencode($r['name'] . ' product') . '&image_size=square';
                                $rStock = (int)$r['stock'];
                                $rStockCls = 'success';
                                if ($rStock === 0) $rStockCls = 'danger';
                                elseif ($rStock < 10) $rStockCls = 'warning';
                            ?>
                            <div class="col-sm-6 col-lg-4">
                                <a href="<?php echo route('admin/services/' . $r['id']); ?>"
                                   class="text-decoration-none text-dark d-block rounded-3 border p-3 bg-white h-100 hover-lift">
                                    <div class="d-flex gap-3 align-items-center">
                                        <img src="<?php echo $rImg; ?>"
                                             class="rounded-3 border" style="width:64px;height:64px;object-fit:cover;flex-shrink:0;" alt="r">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold mb-1 text-truncate" title="<?php echo htmlspecialchars($r['name']); ?>">
                                                <?php echo htmlspecialchars($r['name']); ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap small">
                                                <span class="fw-bold text-success"><?php echo formatMoney($r['price']); ?></span>
                                                <span class="text-muted">/ <?php echo htmlspecialchars($r['unit']); ?></span>
                                                <span class="badge bg-<?php echo $rStockCls; ?> bg-opacity-10 text-<?php echo $rStockCls; ?> border ms-auto">
                                                    Tồn <?php echo $rStock; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
    .service-detail-header {
        padding: 1.15rem 1.35rem;
        border: 1px solid #e8edf2;
        border-radius: 1rem;
        background: linear-gradient(135deg, #fff 0%, #f6fbf8 100%);
    }

    .service-detail-card,
    .stock-management-card {
        border-radius: 1rem;
    }

    .stock-summary__value-row {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        column-gap: 0.35rem;
        row-gap: 0.4rem;
        line-height: 1.2;
    }

    .stock-summary__icon {
        font-size: 1rem;
    }

    .stock-summary__value {
        font-size: clamp(1.2rem, 2vw, 1.55rem);
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .stock-summary__unit {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .stock-management-header {
        background: linear-gradient(135deg, #ecfdf5 0%, #f5fbf7 100%);
    }

    .stock-current {
        white-space: nowrap;
    }

    .stock-adjustment-form .form-control,
    .stock-adjustment-form .input-group-text,
    .stock-adjustment-form .btn {
        min-height: 48px;
    }

    .stock-adjustment-form .input-group-text {
        white-space: nowrap;
    }

    .stock-adjustment-form .btn {
        box-shadow: 0 0.25rem 0.75rem rgba(25, 135, 84, 0.16);
    }

    .stock-adjustment-form .btn:hover {
        box-shadow: 0 0.35rem 1rem rgba(25, 135, 84, 0.22);
        transform: translateY(-1px);
    }

    .service-stat-card {
        min-height: 132px;
        padding: 1.25rem;
        border: 1px solid #e6ebf0;
        border-radius: 1rem;
        background: #fff;
    }

    .service-stat-card--orders {
        background: linear-gradient(135deg, #f3f8ff 0%, #fff 100%);
    }

    .service-stat-card--quantity {
        background: linear-gradient(135deg, #fff9e8 0%, #fff 100%);
    }

    .service-stat-card--revenue {
        color: #fff;
        border-color: #059669;
        background: linear-gradient(135deg, #10b981 0%, #047857 100%);
        box-shadow: 0 0.4rem 1rem rgba(5, 150, 105, 0.16);
    }

    .service-stat-card__label {
        margin-bottom: 0.65rem;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.055em;
        text-transform: uppercase;
    }

    .service-stat-card__value {
        font-size: 2rem;
        font-weight: 750;
        line-height: 1;
    }

    .service-stat-card__revenue {
        font-size: clamp(1.25rem, 2vw, 1.65rem);
        font-weight: 750;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .service-stat-card--revenue .service-stat-card__label,
    .service-stat-card--revenue .small {
        color: rgba(255, 255, 255, 0.82);
    }

    .hover-lift { transition: all 0.18s ease; }
    .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,0.12); border-color: rgba(16,185,129,0.35); }
    .border-dashed { border-style: dashed !important; }
    .top-3 { top: 0.75rem; } .end-3 { right: 0.75rem; }

    @media (max-width: 575.98px) {
        .service-detail-header {
            padding: 1rem;
        }

        .service-detail-header > .d-flex {
            align-items: stretch !important;
        }

        .service-detail-header .btn {
            flex: 1 1 auto;
            justify-content: center;
        }

        .stock-management-header,
        .stock-adjustment-form {
            padding: 1rem !important;
        }

        .stock-current {
            width: 100%;
            text-align: center;
        }
    }
</style>

<script>
    // Đồng bộ 2 ô nhanh (Nhập + / Xuất -) xuống ô delta thật chính
    function syncDelta(val) {
        var n = parseFloat(val) || 0;
        document.getElementById('deltaReal').value = n;
        // Clear cái kia để người dùng không nhầm lẫn
        if (n > 0) document.getElementById('quickMinus').value = '';
        if (n < 0) document.getElementById('quickPlus').value = '';
    }
    // Luôn đồng bộ delta thật ngược lại (nếu user sửa tay)
    document.addEventListener('DOMContentLoaded', function () {
        var dr = document.getElementById('deltaReal');
        if (!dr) return;
        dr.addEventListener('input', function () {
            var v = parseFloat(dr.value) || 0;
            document.getElementById('quickPlus').value  = v > 0 ? v : '';
            document.getElementById('quickMinus').value = v < 0 ? Math.abs(v) : '';
        });
    });
</script>
@endsection
