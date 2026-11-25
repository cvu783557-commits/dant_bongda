<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Service;
use App\Models\User;
use Rakit\Validation\Validator;

class ServiceController extends Controller
{
    protected $service;
    protected $validator;
    protected $user;

    public function __construct()
    {
        $this->service   = new Service();
        $this->validator = new Validator();
        $this->user      = new User();

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!$this->user->canAccessAdmin()) {
            if (!$this->user->getActiveUser()) {
                setFlash('error', 'Vui lòng đăng nhập bằng tài khoản ADMIN để truy cập trang quản lý');
                redirect('login');
            }
            setFlash('error', 'Tài khoản USER không có quyền truy cập trang quản trị.');
            redirect('/');
        }
    }

    public function index()
    {
        $category = $_GET['category'] ?? null;
        $status   = $_GET['status']   ?? null;
        $q        = trim($_GET['q'] ?? '');

        if ($q !== '') {
            $services = $this->service->search($q, $status);
            if ($category) {
                $services = array_values(array_filter(
                    $services,
                    static fn ($s) => ($s['category'] ?? '') === $category
                ));
            }
        } else {
            $services = $this->service->all($status, $category);
        }

        $totalActive   = $this->service->count('active');
        $totalInactive = $this->service->count('inactive');
        $lowStock      = $this->service->lowStock(10);
        $categoryStats = $this->service->countByCategory();
        $categories    = Service::CATEGORIES;

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.services.index', [
            'services'      => $services,
            'categories'    => $categories,
            'categoryStats' => $categoryStats,
            'filterCategory'=> $category,
            'filterStatus'  => $status,
            'filterQ'       => $q,
            'totalActive'   => $totalActive,
            'totalInactive' => $totalInactive,
            'lowStock'      => $lowStock,
            'success'       => $success,
            'error'         => $error,
            'user'          => $this->user->getActiveUser(),
            'canDelete'     => $this->user->canDelete(),
        ]);
    }

    public function show($id)
    {
        $service = $this->service->find($id);
        if (!$service) {
            redirect404();
            return;
        }

        $stats   = $this->service->getSalesStats($id);
        $related = $this->service->getRelatedServices(
            (int)$id,
            $service['category'] ?? 'other',
            6
        );

        return view('admin.services.show', [
            'service'       => $service,
            'categoryName'  => Service::getCategoryName($service['category'] ?? 'other'),
            'categoryColor' => Service::getCategoryColor($service['category'] ?? 'other'),
            'stats'         => $stats,
            'related'       => $related,
            'success'       => getFlash('success'),
            'error'         => getFlash('error'),
        ]);
    }

    public function create()
    {
        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.services.create', [
            'old'        => $old,
            'error'      => $error,
            'categories' => Service::CATEGORIES,
        ]);
    }

    public function store()
    {
        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'        => 'required|min:2|max:150',
            'category'    => 'required|in:drink,food,equipment,service,other',
            'price'       => 'required|numeric|min:0',
            'unit'        => 'required|min:1|max:30',
            'stock'       => 'nullable|numeric|min:0',
            'description' => 'nullable|max:2000',
            'status'      => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        $imagePath = null;
        if (is_upload('image')) {
            try {
                $imagePath = $this->uploadFile($_FILES['image'], 'services');
            } catch (\Exception $e) {
                $errors['image'] = 'Lỗi upload ảnh: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/services/create');
            return;
        }

        $id = $this->service->create([
            'name'        => $data['name'],
            'category'    => $data['category'],
            'price'       => $data['price'],
            'unit'        => $data['unit'],
            'stock'       => $data['stock'] ?? 0,
            'description' => $data['description'] ?? '',
            'image'       => $imagePath,
            'status'      => $data['status'],
        ]);

        unset($_SESSION['old']);
        setFlash('success', 'Thêm dịch vụ thành công');
        redirect('admin/services');
    }

    public function edit($id)
    {
        $service = $this->service->find($id);
        if (!$service) {
            redirect404();
            return;
        }

        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.services.edit', [
            'service'    => $service,
            'old'        => $old,
            'error'      => $error,
            'categories' => Service::CATEGORIES,
        ]);
    }

    public function update($id)
    {
        $service = $this->service->find($id);
        if (!$service) {
            redirect404();
            return;
        }

        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'        => 'required|min:2|max:150',
            'category'    => 'required|in:drink,food,equipment,service,other',
            'price'       => 'required|numeric|min:0',
            'unit'        => 'required|min:1|max:30',
            'description' => 'nullable|max:2000',
            'status'      => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        $imagePath   = null;
        $changeImage = false;
        if (is_upload('image')) {
            try {
                $imagePath   = $this->uploadFile($_FILES['image'], 'services');
                $changeImage = true;
            } catch (\Exception $e) {
                $errors['image'] = 'Lỗi upload ảnh: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/services/' . $id . '/edit');
            return;
        }

        $updateData = [
            'name'        => $data['name'],
            'category'    => $data['category'],
            'price'       => $data['price'],
            'unit'        => $data['unit'],
            'description' => $data['description'] ?? '',
            'status'      => $data['status'],
        ];
        if ($changeImage) {
            $updateData['image'] = $imagePath;
        }

        $this->service->update($id, $updateData);

        unset($_SESSION['old']);
        setFlash('success', 'Cập nhật dịch vụ thành công');
        redirect('admin/services');
    }

    public function toggleStatus($id)
    {
        $service = $this->service->find($id);
        if (!$service) {
            redirect404();
            return;
        }

        $newStatus = $service['status'] === 'active' ? 'inactive' : 'active';

        $this->service->update($id, [
            'name'        => $service['name'],
            'category'    => $service['category'],
            'price'       => $service['price'],
            'unit'        => $service['unit'],
            'description' => $service['description'] ?? '',
            'status'      => $newStatus,
        ]);

        setFlash('success', $newStatus === 'active' ? 'Kích hoạt dịch vụ thành công' : 'Tạm dừng dịch vụ thành công');
        redirect('admin/services');
    }

    public function adjustStock($id)
    {
        $service = $this->service->find($id);
        if (!$service) {
            redirect404();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST'
            || !isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
            setFlash('error', 'Phiên yêu cầu không hợp lệ hoặc đã hết hạn.');
            redirect('admin/services/' . $id);
            return;
        }

        $deltaValue = $_POST['delta'] ?? null;
        $delta = is_string($deltaValue) || is_int($deltaValue)
            ? filter_var($deltaValue, FILTER_VALIDATE_INT)
            : false;
        $noteValue = $_POST['note'] ?? '';
        $note = is_string($noteValue) ? trim($noteValue) : '';

        if ($delta === false || $delta === 0) {
            setFlash('error', 'Số lượng điều chỉnh không hợp lệ');
            redirect('admin/services/' . $id);
            return;
        }
        if (mb_strlen($note) > 250) {
            setFlash('error', 'Ghi chú không được vượt quá 250 ký tự.');
            redirect('admin/services/' . $id);
            return;
        }

        if (!$this->service->adjustStock((int)$id, $delta)) {
            setFlash('error', 'Số lượng xuất vượt tồn kho hiện tại hoặc tồn kho vừa thay đổi. Vui lòng kiểm tra lại.');
            redirect('admin/services/' . $id);
            return;
        }

        $msg = $delta > 0
            ? 'Nhập kho thành công (+' . $delta . ' ' . htmlspecialchars($service['unit']) . ')'
            : 'Xuất kho thành công (' . abs($delta) . ' ' . htmlspecialchars($service['unit']) . ')';

        setFlash('success', $msg . ($note === '' ? '' : ' - Ghi chú: ' . $note));
        redirect('admin/services/' . $id);
    }

    public function destroy($id)
    {
        if (!$this->user->canDelete()) {
            setFlash('error', 'Chỉ ADMIN mới có quyền xóa dịch vụ.');
            redirect('admin/services');
            return;
        }

        $service = $this->service->find($id);
        if (!$service) {
            redirect404();
            return;
        }

        try {
            $this->service->delete($id);
            setFlash('success', 'Xóa dịch vụ thành công');
        } catch (\Exception $e) {
            setFlash('error', 'Không thể xóa dịch vụ này (đã có hóa đơn liên quan)');
        }

        redirect('admin/services');
    }
}
