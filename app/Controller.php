<?php
namespace App;

class Controller
{
    public function validate($validator, $data, $rules)
    {
        $messages = [
            'required' => 'Trường này là bắt buộc.',
            'integer' => 'Vui lòng nhập số nguyên hợp lệ.',
            'numeric' => 'Vui lòng nhập số hợp lệ.',
            'date' => 'Vui lòng nhập ngày hợp lệ.',
            'email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'max' => 'Giá trị không được vượt quá :max ký tự.',
            'min' => 'Giá trị phải có ít nhất :min ký tự.',
            'in' => 'Giá trị đã chọn không hợp lệ.',
            'regex' => 'Giá trị không đúng định dạng.',
            'customer_name:required' => 'Vui lòng nhập họ tên khách hàng.',
            'customer_name:min' => 'Họ tên khách hàng phải có ít nhất :min ký tự.',
            'customer_name:max' => 'Họ tên khách hàng không được vượt quá :max ký tự.',
            'customer_phone:required' => 'Vui lòng nhập số điện thoại.',
            'customer_phone:regex' => 'Số điện thoại phải gồm 10 hoặc 11 chữ số và bắt đầu bằng số 0.',
            'customer_email:email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'newsletter_email:required' => 'Vui lòng nhập email để đăng ký nhận ưu đãi.',
            'newsletter_email:email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
            'newsletter_email:max' => 'Email không được vượt quá :max ký tự.',
            'newsletter_consent:required' => 'Vui lòng xác nhận đồng ý nhận email ưu đãi.',
            'newsletter_consent:in' => 'Vui lòng xác nhận đồng ý nhận email ưu đãi.',
            'booking_date:required' => 'Vui lòng chọn ngày đặt sân.',
            'booking_date:date' => 'Ngày đặt sân không hợp lệ.',
            'time_slot_id:required' => 'Vui lòng chọn khung giờ.',
            'pitch_id:required' => 'Sân bóng không hợp lệ.',
            'name:required' => 'Vui lòng nhập tên sân.',
            'name:min' => 'Tên sân phải có ít nhất :min ký tự.',
            'price_per_hour:required' => 'Vui lòng nhập giá thuê sân.',
            'type:required' => 'Vui lòng chọn loại sân.',
            'status:required' => 'Vui lòng chọn trạng thái.',
        ];

        $validation = $validator->make($data, $rules, $messages);
        $validation->setAliases([
            'customer_name' => 'họ tên khách hàng',
            'customer_phone' => 'số điện thoại',
            'customer_email' => 'email',
            'newsletter_email' => 'email đăng ký ưu đãi',
            'newsletter_consent' => 'xác nhận nhận ưu đãi',
            'booking_date' => 'ngày đặt sân',
            'time_slot_id' => 'khung giờ',
            'pitch_id' => 'sân bóng',
            'price_per_hour' => 'giá thuê',
            'field_count' => 'số lượng sân',
            'opening_time' => 'giờ mở cửa',
            'closing_time' => 'giờ đóng cửa',
        ]);

        $validation->validate();

        if ($validation->fails()) {
            return $validation->errors()->firstOfAll();
        }

        return [];
    }

    public function logError($message)
    {
        $date = date('d-m-Y');

        $message = date('d-m-Y H:i:s') . ' - ' . $message . PHP_EOL;

        // Type: 3 - Ghi vào file
        error_log($message, 3, "storage/logs/$date.log");
    }

    public function uploadFile(array $file, $folder = null)
    {
        // Thông tin về file
        $fileTmpPath = $file['tmp_name']; // Đường dẫn tạm thời của file
        $fileName = time() . '-' . $file['name']; // Tên file chống trùng bằng timestamp

        $uploadDir = 'storage/uploads/' . $folder . '/';

        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Đường dẫn đầy đủ để lưu file
        $destPath = $uploadDir . $fileName;

        // Di chuyển file từ thư mục tạm thời đến thư mục đích
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            return $destPath;
        }

        throw new \Exception('Lỗi upload file!');
    }
}
