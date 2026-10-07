<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Customer;
use App\Models\User;

class CustomerController extends Controller
{
    protected $customer;
    protected $user;

    public function __construct()
    {
        $this->customer = new Customer();
        $this->user     = new User();

        if (!$this->user->getActiveUser()) {
            setFlash('error', 'Vui lòng đăng nhập để truy cập quản lý');
            redirect('admin/login');
        }
    }

    public function index()
    {
        $search = trim($_GET['q'] ?? '');

        return view('admin.customers.index', [
            'customers' => $this->customer->all($search),
            'search'    => $search,
            'user'      => $this->user->getActiveUser(),
        ]);
    }

    public function show()
    {
        $id    = (int)($_GET['id'] ?? 0);
        $phone = trim($_GET['phone'] ?? '');

        $customer = null;
        if ($id > 0) {
            $customer = $this->customer->findWithSummary($id);
        } elseif ($phone !== '') {
            $found = $this->customer->findByPhone($phone);
            $customer = $found ? $this->customer->findWithSummary((int)$found['id']) : null;
        }

        if (!$customer) {
            setFlash('error', 'Khách hàng không tồn tại');
            redirect('admin/customers');
            return;
        }

        return view('admin.customers.show', [
            'customer' => $customer,
            'bookings' => $this->customer->bookings((int)$customer['id']),
            'user'     => $this->user->getActiveUser(),
        ]);
    }
}
