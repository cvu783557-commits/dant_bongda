<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Customer;

class CustomerController extends Controller
{
    protected $customer;

    public function __construct()
    {
        $this->customer = new Customer();
    }

    public function index()
    {
        $search = trim($_GET['q'] ?? '');

        return view('admin.customers.index', [
            'customers' => $this->customer->all($search),
            'search' => $search,
        ]);
    }

    public function show()
    {
        $phone = trim($_GET['phone'] ?? '');
        if ($phone === '') {
            redirect404();
            return;
        }

        $customer = $this->customer->findByPhone($phone);
        if (!$customer) {
            redirect404();
            return;
        }

        return view('admin.customers.show', [
            'customer' => $customer,
            'bookings' => $this->customer->bookingsByPhone($phone),
        ]);
    }
}