@extends('owner.layout')

@section('title', 'Doanh thu')

@section('content')
<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-success fw-semibold mb-1">Doanh thu</p>
            <h2 class="fw-bold mb-0">Báo cáo lợi nhuận</h2>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Doanh thu hôm nay</div>
                    <div class="display-6 fw-bold mt-2"><?php echo e(formatMoney($summary['day'])); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Doanh thu tuần</div>
                    <div class="display-6 fw-bold mt-2"><?php echo e(formatMoney($summary['week'])); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Doanh thu tháng</div>
                    <div class="display-6 fw-bold mt-2"><?php echo e(formatMoney($summary['month'])); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="alert alert-light border mb-0">
                Đây là phần báo cáo doanh thu dành cho chủ sân. Bạn có thể mở rộng để hiển thị biểu đồ, doanh thu theo ngày, theo sân, theo dịch vụ và chi phí vận hành.
            </div>
        </div>
    </div>
</div>
@endsection
