# Hệ thống Đặt Sân Bóng - Implementation Plan

## Repository Research

### Kiến trúc hiện tại
- **PHP MVC tự xây dựng**: Dùng `App\Controller` (base), `App\Model` (base Doctrine DBAL connection)
- **Routing**: `bramus/router` khai báo trong `routes/web.php`
- **Template Engine**: `eftec/bladeone` (Blade syntax), helper `view($name, $data)` render từ thư mục `views/`, cache tại `storage/compiles/`
- **Database**: MySQL qua PDO (Doctrine DBAL), CSDL tên `db_football_field_booking` (đã set trong `.env`)
- **Validation**: `rakit/validation`, có method `validate()` sẵn trong base Controller
- **UI Framework**: Bootstrap 5.3.8 (CDN), đã có layout `views/layouts/admin.blade.php` (sidebar + content)
- **Helpers**: `redirect()`, `route()`, `setFlash()`, `file_url()`, `is_upload()`, `uploadFile()`

### Thiếu sót cần bổ sung
- Layout admin tham chiếu `partials.header`, `partials.aside`, `partials.footer` → chưa tồn tại, cần tạo
- `views/home.blade.php` đang trống → cần thay bằng giao diện trang đặt sân
- Chưa có bảng CSDL, model, controller, view cho sân bóng và đặt sân

---

## Files and Modules

### Database
- `database/database.sql`: Schema hiện hành + seed sân/khung giờ mẫu; không xóa dữ liệu hiện có

### Models (app/Models/)
- `app/Models/Pitch.php`: Thao tác bảng `pitches` (CRUD sân bóng)
- `app/Models/TimeSlot.php`: Thao tác bảng `time_slots` (khung giờ thuê)
- `app/Models/Booking.php`: Thao tác bảng `bookings` (CRUD đơn đặt, kiểm tra xung đột giờ)

### Controllers (app/Controllers/)
- `app/Controllers/PitchController.php`: Trang chủ - Hiển thị danh sách sân, chi tiết, form đặt sân
- `app/Controllers/Admin/BookingController.php`: Quản lý đặt sân (danh sách, duyệt, hủy, xem chi tiết)
- `app/Controllers/Admin/PitchController.php`: Quản lý sân bóng (CRUD sân)

### Views
**Frontend (trang người dùng):**
- `views/home.blade.php`: Trang chủ - Danh sách sân + tìm kiếm
- `views/pitches/show.blade.php`: Chi tiết sân + lịch trống + form đặt sân
- `views/bookings/success.blade.php`: Xác nhận đặt sân thành công

**Admin Partials (layout admin cần):**
- `views/partials/header.blade.php`: Top bar admin
- `views/partials/aside.blade.php`: Sidebar menu (Dashboard, Sân bóng, Đặt sân)
- `views/partials/footer.blade.php`: Footer

**Admin Pages:**
- `views/admin/dashboard.blade.php`: Thống kê tổng quan
- `views/admin/pitches/index.blade.php`: Danh sách sân
- `views/admin/pitches/create.blade.php`: Thêm sân
- `views/admin/pitches/edit.blade.php`: Sửa sân
- `views/admin/bookings/index.blade.php`: Danh sách đặt sân (lọc theo trạng thái/ngày)
- `views/admin/bookings/edit.blade.php`: Cập nhật trạng thái đặt sân

### Routes
- Sửa `routes/web.php`: Đăng ký route frontend + route nhóm `/admin`

---

## Implementation Steps (theo thứ tự phụ thuộc)

### Bước 1: Tạo CSDL và seed dữ liệu mẫu
- Tạo bảng `pitches` (id, name, type: 5/7/11 người, price_per_hour, description, image, status: active/inactive, created_at)
- Tạo bảng `time_slots` (id, start_time, end_time) → các khung giờ cố định 06:00→23:00 (mỗi slot 1h)
- Tạo bảng `bookings` (id, pitch_id, customer_name, customer_phone, customer_email, booking_date, time_slot_id, total_price, status: pending/confirmed/cancelled, notes, created_at)
- Foreign key: bookings.pitch_id → pitches.id, bookings.time_slot_id → time_slots.id
- Seed: 4 sân mẫu, 17 time_slots, vài đơn đặt mẫu

### Bước 2: Tạo Models (Pitch, TimeSlot, Booking)
- Mỗi model extends `App\Model`, dùng `$this->connection` (Doctrine DBAL)
- Method cơ bản: `all()`, `find($id)`, `create($data)`, `update($id, $data)`, `delete($id)`
- `Pitch`: `getAvailable()` (lọc sân active)
- `Booking`: `isSlotTaken($pitchId, $date, $slotId)` → check trùng giờ trước khi đặt
- `Booking`: `getByDate($date, $pitchId)` → xem lịch sân theo ngày

### Bước 3: Tạo partials cho layout admin
- `header.blade.php`: Navbar top
- `aside.blade.php`: Menu bên trái
- `footer.blade.php`: Copyright

### Bước 4: Frontend - Trang đặt sân (User)
- **PitchController@index**: Lấy danh sách sân → render `home.blade.php` (grid Bootstrap card: ảnh, tên, loại, giá)
- **PitchController@show**: Lấy chi tiết sân + time_slots + các slot đã đặt ngày hiện tại → render lịch dạng bảng, cho phép chọn ngày và slot
- **PitchController@bookingStore**: Nhận POST form đặt → validate → kiểm tra slot trống → tạo booking status=pending → redirect trang success với thông tin đơn hàng

### Bước 5: Admin - Quản lý sân bóng
- **Admin/PitchController@index**: Danh sách sân (bảng Bootstrap, nút sửa/xóa, badge trạng thái)
- **Admin/PitchController@create** + **store**: Form thêm sân (tên, loại, giá, ảnh, mô tả, trạng thái) - upload ảnh dùng `uploadFile()`
- **Admin/PitchController@edit** + **update**: Form sửa sân
- **Admin/PitchController@destroy**: Xóa sân (chỉ cho xóa nếu chưa có booking)

### Bước 6: Admin - Quản lý đặt sân (chức năng chính)
- **Admin/BookingController@index**: 
  - Danh sách booking dạng bảng (tên KH, sân, ngày, giờ, tiền, trạng thái)
  - Bộ lọc: theo trạng thái (pending/confirmed/cancelled), theo ngày, theo sân
  - Badge màu theo trạng thái (xanh=confirmed, vàng=pending, đỏ=cancelled)
- **Admin/BookingController@edit** + **update**: Đổi trạng thái đơn (duyệt/hủy), thêm ghi chú
- **Admin/BookingController@destroy**: Xóa đơn
- **Admin/Dashboard (tùy chọn)**: Thống kê số đơn hôm nay, tổng doanh thu, số sân đang hoạt động

### Bước 7: Routes
- Frontend:
  - `GET /` → PitchController@index
  - `GET /pitches/{id}` → PitchController@show
  - `POST /bookings` → PitchController@bookingStore
  - `GET /bookings/success/{id}` → PitchController@bookingSuccess
- Admin (prefix `/admin`):
  - `GET /admin` → Admin\BookingController@index (trang mặc định quản lý)
  - `GET /admin/pitches` → Admin\PitchController@index
  - `GET /admin/pitches/create` → Admin\PitchController@create
  - `POST /admin/pitches` → Admin\PitchController@store
  - `GET /admin/pitches/{id}/edit` → Admin\PitchController@edit
  - `POST /admin/pitches/{id}` → Admin\PitchController@update
  - `POST /admin/pitches/{id}/delete` → Admin\PitchController@destroy
  - `GET /admin/bookings` → Admin\BookingController@index
  - `GET /admin/bookings/{id}/edit` → Admin\BookingController@edit
  - `POST /admin/bookings/{id}` → Admin\BookingController@update
  - `POST /admin/bookings/{id}/delete` → Admin\BookingController@destroy

### Bước 8: Giao diện Bootstrap 5 tối giản
- Không dùng viền/khung thừa, khoảng trắng hợp lý
- Trang chủ: hero banner + grid card sân
- Lịch đặt sân: bảng ngày-giờ, slot trống (màu xanh lá) / đã đặt (xám) / đang chọn (vàng)
- Form đặt: các field rõ ràng, validate client-side (required, pattern)
- Admin table: hoverable, striped, responsive

---

## Dependencies and Considerations
- **Ảnh sân**: Lưu vào `storage/uploads/pitches/` (dùng sẵn method `Controller::uploadFile()`)
- **Validate**: Dùng Rakit (sẵn có)
  - Booking: customer_name required, phone required/phone regex, email email, date required/future date, slot required, pitch exists
  - Pitch: name required, type in [5,7,11], price required/numeric/min:0
- **Tránh xung đột giờ**: Trước khi INSERT booking, `SELECT COUNT(*) FROM bookings WHERE pitch_id=? AND booking_date=? AND time_slot_id=? AND status IN ('pending','confirmed')` > 0 → báo lỗi
- **Trạng thái đặt sân**: `pending` (chờ duyệt) → `confirmed` (đã thu tiền/đặt xong) → `cancelled` (hủy)
- **Tổng tiền**: `price_per_hour * 1` (mỗi booking một slot 1h), tự tính trên server không tin client

---

## Validation (sau khi code xong)
1. Import `database/database.sql` vào MySQL kiểm tra tạo bảng + seed thành công
2. Chạy app với Laragon (`http://localhost:81/BASE_AGILE/`)
   - Trang chủ hiển thị danh sách sân (dữ liệu seed)
   - Click sân → xem lịch, chọn ngày + slot trống → submit đặt → trang success hiện mã đơn
3. Truy cập `/admin` → menu sidebar hoạt động
   - `/admin/pitches`: xem/sửa/xóa/thêm sân, upload ảnh
   - `/admin/bookings`: lọc theo trạng thái/ngày, click "Duyệt" → status đổi confirmed, "Hủy" → cancelled
4. Kiểm tra xung đột: Cố tình đặt slot đã có → báo lỗi "Khung giờ này đã được đặt"
5. Check lỗi PHP trong `storage/logs/` (gọi `setFlash` + show message sau redirect)

---

## Risks
- **Doctrine DBAL fetchAll/fetchAssoc**: Doctrine 3.x dùng `fetchAllAssociative()` / `fetchAssociative()` (không dùng `fetchAll()`) → dùng đúng method tránh lỗi
- **Chưa có auth admin**: Giai đoạn này không yêu cầu đăng nhập (theo scope "quản lý đặt sân"), ai vào `/admin` cũng xem/điều chỉnh được (tương lai có thể thêm login)
- **Slot kéo dài nhiều giờ**: Hiện tại chỉ cho đặt 1 giờ/slot (đơn giản). Nên ghi rõ scope này trong giao diện. Nếu user cần nhiều giờ thì đặt nhiều đơn hoặc cải tiến sau.
- **CSRF**: Chưa có token CSRT cho form POST (chỉ phù hợp demo/internal). Production cần thêm.
- **Date format**: Dùng `Y-m-d` trên CSDL, hiển thị `d/m/Y` cho người dùng, convert ở controller tránh lỗi SQL.
