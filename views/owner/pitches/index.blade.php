@extends('owner.layout')

@section('title', 'Sân của tôi')

@section('content')
<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-success fw-semibold mb-1">Sân của tôi</p>
            <h2 class="fw-bold mb-0">Danh sách sân đang quản lý</h2>
        </div>
        <button class="btn btn-success" type="button">+ Thêm sân</button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="alert alert-light border mb-0">
                Chức năng quản lý sân cho chủ sân đã được khởi tạo. Bạn có thể mở rộng với: thêm sân, giờ hoạt động, hình ảnh, giá theo khung giờ, cập nhật trạng thái.
            </div>
        </div>
    </div>
</div>
@endsection
