<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class CustomerController extends Controller
{
    public function getCustomersSummary() {
        $customers = app('db')
            ->table('customers')
            ->select([
                'id',
                'name'
            ])
            ->get();
        return response()
            ->view('customers.index', [
                'customers' => $customers
            ]);
    }
}
