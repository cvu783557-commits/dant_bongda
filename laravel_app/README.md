# Hệ thống đặt sân bóng (Laravel)

Ứng dụng Laravel được dựng song song với bản PHP MVC cũ để có thể kiểm tra trước khi chuyển hẳn.

## Chạy ứng dụng

1. Sao chép `.env.example` thành `.env`, sau đó điền cấu hình MySQL phù hợp.
2. Tạo khóa ứng dụng và chạy migration:

   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   php artisan storage:link
   ```

   Migration kiểm tra bảng trước khi tạo và bổ sung trường còn thiếu; không xóa các bảng hoặc dữ liệu của bản cũ. Seeder chỉ thêm bốn sân mẫu khi bảng `pitches` đang trống, không ghi đè dữ liệu hiện có.
3. Chạy Laravel:

   ```bash
   php artisan serve --host=127.0.0.1 --port=8001
   ```

   Trang Laravel: `http://127.0.0.1:8001`. Bản PHP cũ vẫn chạy độc lập tại cổng `8000`.

## Chức năng

- Danh sách sân, thông tin chi tiết, bảng giá và nút liên hệ.
- Chọn ngày, sân con, giờ bắt đầu và thời lượng; kiểm tra lịch trống và tính tổng tiền.
- Đặt sân và xem xác nhận đơn.
- Đăng nhập quản trị, quản lý sân, đơn đặt và dashboard.
- Chính sách đặt cọc/hủy sân và lưu ý an toàn khi đặt sân.
- Quản trị sân và đơn đặt; cập nhật địa chỉ, giá thuê, giờ hoạt động và số sân con.

## Kiểm thử

Bật các extension PHP `pdo_sqlite` và `sqlite3`, sau đó chạy:

```bash
php artisan test
```
