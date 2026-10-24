@extends('owner.layout')

@section('title', 'Đặt sân của tôi')

@section('content')
<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-success fw-semibold mb-1">Đặt sân</p>
            <h2 class="fw-bold mb-0">Lịch đặt sân trên khu sân của bạn</h2>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="alert alert-light border mb-0">
                Đây là khu vực hiển thị lịch đặt sân của chủ sân. Bạn có thể tiếp tục bổ sung: danh sách booking, xác nhận đặt, hủy đơn, thống kê theo sân và khung giờ.
            </div>
        </div>
    </div>
</div>
@endsection
