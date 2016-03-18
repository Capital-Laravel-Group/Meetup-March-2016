<?php
namespace App\Services;

class Customer {

    public function __construct() {
        //
    }

    public function getCustomersSummary($input) {
        $return = [];

        $customersInfo = app('db')->table('customers')
            ->select([
                'id',
                'name',
                'state',
                'zip_code'
            ])
            ->get();
        //We want our API to look like zipCode not zip_code which our DB uses
        foreach ($customersInfo as $customerInfo) {
            $returnCustomer = [
                'id' => $customerInfo->id,
                'name' => $customerInfo->name,
                'state' => $customerInfo->state,
                'zipCode' => $customerInfo->zip_code,
                'numProducts' => 0
            ];

            $products = app('db')
                ->table('products')
                ->where('customerid', $customerInfo->id)
                ->select([
                    app('db')->raw('COUNT(*) as num_products')
                ])
                ->first();
            if (isset($products->num_products)) {
                $returnCustomer['numProducts'] = $products->num_products;
            }
            $return[] = $returnCustomer;
        }

        return $return;
    }
}