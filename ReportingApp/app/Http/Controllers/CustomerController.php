<?php

namespace App\Http\Controllers;

class CustomerController extends Controller
{
    public function __construct(
        \App\Services\Customer $customerService
    )
    {
        $this->service = new \stdClass();

        $this->service->Customer = $customerService;
    }

    public function getCustomersSummary(\Illuminate\Http\Request $request) {
        $input = $request->all();

        $return = $this->service->Customer->getCustomersSummary($input);

        return response()->json($return);
    }
}
