<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return str_random(32);
});

$app->group([
    'prefix' => 'customers',
    'namespace' => 'App\Http\Controllers'
], function($app) {

    $app->get('/', 'CustomerController@getCustomersSummary');

});