@extends('owner.layout')

@section('title', 'Dashboard chủ sân')

@section('content')
<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-success fw-semibold mb-1">Owner Dashboard</p>
            <h2 class="fw-bold mb-0">Tổng quan kinh doanh</h2>
        </div>
        <a href="<?php echo route('/'); ?>" class="btn btn-outline-success">Xem website khách hàng</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Đặt sân hôm nay</div>
                    <div class="display-6 fw-bold mt-2"><?php echo e($todayBookings); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Sân đang hoạt động</div>
                    <div class="display-6 fw-bold mt-2"><?php echo e($activePitches); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Doanh thu tháng</div>
                    <div class="display-6 fw-bold mt-2"><?php echo e(formatMoney($monthlyRevenue)); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 fw-bold">Đánh giá nhanh</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Sân của bạn</span>
                            <span class="badge text-bg-success">Khởi tạo</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Đặt sân trong ngày</span>
                            <span class="badge text-bg-primary">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Yêu cầu hủy mới</span>
                            <span class="badge text-bg-warning">0</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 fw-bold">Các chức năng chính</div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo route('owner/pitches'); ?>" class="btn btn-success">Quản lý sân</a>
                        <a href="<?php echo route('owner/bookings'); ?>" class="btn btn-outline-success">Xem lịch đặt sân</a>
                        <a href="<?php echo route('owner/revenue'); ?>" class="btn btn-outline-success">Báo cáo doanh thu</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
