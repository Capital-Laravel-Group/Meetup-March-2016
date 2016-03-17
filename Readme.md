## Install Lumen
You can follow the guide in the [Lumen Docs](https://lumen.laravel.com/docs/5.2).

First, download the Lumen installer using Composer:
```sh
composer global require "laravel/lumen-installer"
```

Then run Composer `create-project` - we will call our project `ReportingApp`
```sh
composer create-project --prefer-dist laravel/lumen ReportingApp
```

Copy `.env.example` to `.env` for your application to use
```sh
cd ReportingApp
cp .env.example .env
```

Edit `.env` `APP_KEY` setting to a random 32 character string. An easy way to generate this is to edit `/app/Http/routes.php` and put `return str_random(32);` inside of a route, and hit that route in your browser.

Edit `.env` to use your existing Laravel database host. You'll want to create a new user `reportingapp` with `read` and `write` permissions and add these credentials as your `DB_USERNAME` and `DB_PASSWORD`. Change `CACHE_DRIVER` to `file` if you like.

Lumen is now fully installed! Your local index page for Lumen (like `http://lumenmar2016:8888/`) should still be accessible. If not, double check your `.env` settings.

## Add AfterMiddleware to allow cross-domain calls to your Lumen app
Create `app/Http/Middleware/AfterMiddleware.php`
Partially taken from: (https://gist.github.com/danharper/06d2386f0b826b669552#file-corsmiddleware-php)
```php
<?php namespace App\Http\Middleware;

use Closure;

class AfterMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE');
        $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Origin', '*');

        if (last(explode('\\',get_class($response))) != 'RedirectResponse') {
            $response->headers->set('P3P', 'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
        }

        return $response;
    }

}
```
And then register it in `app/bootstrap/app.php`
```php
$app->middleware([
    App\Http\Middleware\AfterMiddleware::class
]);
```
## Set up a route in Lumen for your Laravel app to call
Add a new route to `/app/Http/routes.php` that your Laravel app will call. This endpoint will return just data in JSON format - no view. Lumen [does not use the Route facade that you're used to](https://lumen.laravel.com/docs/5.2/routing) in Laravel. You'll need to instead use `$app->get()` instead of `Route::get()`.

Lumen also treats Route groups a little differently than Laravel. For example, you can't nest groups. You'll also need to add a `namespace` in your group definition, such as:
```php
$app->group(['prefix' => 'myprefix',  'middleware' => ['access'], 'namespace' => 'App\Http\Controllers'], function($app) {
  //
}
```

So for our demo, lets make a "all customers summary" route under a group. We'll access this at `http://lumenmar2016:8888/customers`:
```php
$app->group([
    'prefix' => 'customers',
    'namespace' => 'App\Http\Controllers'
], function($app) {

    $app->get('/', 'CustomerController@getCustomersSummary');

});
```

Now lets create our `CustomerController`. If you run `php artisan` on the command line, you'll notice that Lumen does not ship with a `php artisan make:controller` command which you may be used to in Laravel. Let's create the Controller by hand.
```sh
cp app/Http/Controllers/ExampleController.php app/Http/Controllers/CustomerController.php
```
Open up `CustomerController` and change the class name from `ExampleController` to `CustomerController`. Add a function `getCustomersSummary` - the function our route is calling. You'll want to inject the `$request`. `$request` contains all of your request data, including any inputs. If you used to do `$input = \Input::all();` in your classes, you'll want to do `$input = $request->all()` now. Let's have this function call a Customer service class.

```php
<?php

namespace App\Http\Controllers;

class CustomerController extends Controller
{
    public $service;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
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
```

Create the Customer service class we are calling.
```php
mkdir app/Services && touch app/Services/Customer.php
```

Open up `app/Services/Customer.php`
```php
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
            $return[] = [
                'id' => $customerInfo->id,
                'name' => $customerInfo->name,
                'state' => $customerInfo->state,
                'zipCode' => $customerInfo->zip_code,
            ];
        }

        return $return;
    }
}
```

Now if we hit `http://lumenmar2016:8888/customers`, we should see our Customers summary in JSON format. It's ready for our Laravel app to use.

## Create Laravel page to read Customers summary
Edit `/PlatformApp/config/services.php` to look at your `our-apis.reportingapp.domain` `.env` setting.
````php
'our-apis' => [
    'reportingapp' => [
        'domain' => env('OUR-APIS_REPORTINGAPP_DOMAIN', 'http://lumenmar2016:8888')
    ]
]
```

Create a Laravel route to show our customer summary
```php
Route::get('customers', 'CustomerController@getCustomersSummary');
```

Inside of getCustomersSummary, we want to get some preliminary Customer data. This data is cheap for us to retrieve - we are only looking at rows that are inside of `customers`. Our `ReportingApp` is going to be doing calculations - we aren't doing those here.
```php
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
```

Now display our placeholder data in our view, and run an AJAX call to get the calculated summary data and display it in our table.
```php
<!DOCTYPE html>
<html>
<head>
    <title>Customers Summary</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

    <style type="text/css">
        #customers-summary-table {
            min-width: 400px;
        }
        #customers-summary-table td {
            padding: 3px;
        }
        #customers-summary-table .example-row .content-placeholder {
            width: 125px;
            height: 20px;
            background-color: #CCC;
        }
    </style>

    <script type="text/javascript">
        $(function() {
            //We delay 1.5secs just for demo purposes
            setTimeout(function() {
                $.ajax({
                    url: '{{ app('config')->get('services.our-apis.reportingapp.domain') }}/customers',
                    success: function (returnData) {
                        var tableCode = '';
                        var numCustomers = returnData.length;
                        for (var i = 0; i < numCustomers; i++) {
                            var customer = returnData[i];
                            tableCode += '<tr>';
                            tableCode += '<td>' + customer.id + '</td>';
                            tableCode += '<td>' + customer.name + '</td>';
                            tableCode += '<td>' + customer.state + '</td>';
                            tableCode += '<td>' + customer.zipCode + '</td>';
                            tableCode += '<td>' + customer.numProducts.toLocaleString() + '</td>'
                            tableCode += '</tr>';
                        }
                        $('#customers-summary-table tbody').html(tableCode);
                    }
                });
            }, 1500);
        });
    </script>
</head>
<body>
<div class="container">
    <div class="content">
        <div class="title">Customers Summary</div>

        <table id="customers-summary-table">
            <thead>
                <th>ID</th>
                <th>Name</th>
                <th>State</th>
                <th>Zip Code</th>
                <th># of Products</th>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr class="example-row">
                    <td>{{ $customer->id }}</td>
                    <td>{{ $customer->name }}</td>
                    <td><i class="fa fa-spinner fa-spin"></i></td>
                    <td><div class="content-placeholder"></div></td>
                    <td><div class="content-placeholder"></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
```
