<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
| Kantin Digital Kampus REST API
| Response bisa JSON atau XML, ditentukan oleh header Accept.
| Endpoint POST, PUT, DELETE dilindungi Basic Auth (middleware basic.auth),
| login memakai username dan password customer sendiri.
*/

$router->get('/', function (Request $request) {
    return ApiResponse::success($request, 'Kantin Digital Kampus REST API', [
        'service_name' => 'Kantin Digital Kampus',
        'status' => 'Running',
    ]);
});

// Registrasi akun customer (publik, karena customer belum punya akun untuk login)
$router->post('/auth/register', 'AuthController@register');

// Endpoint publik (hanya membaca data)
$router->get('/customers', 'CustomersController@index');
$router->get('/customers/{customerId}', 'CustomersController@show');
$router->get('/orders', 'OrdersController@index');
$router->get('/orders/{orderId}', 'OrdersController@show');

// Endpoint yang mengubah database harus login (Basic Auth customer)
$router->group(['middleware' => 'basic.auth'], function () use ($router) {
    $router->get('/auth/me', 'AuthController@me');

    $router->post('/customers', 'CustomersController@store');
    $router->put('/customers/{customerId}', 'CustomersController@update');
    $router->delete('/customers/{customerId}', 'CustomersController@destroy');

    $router->post('/orders', 'OrdersController@store');
    $router->put('/orders/{orderId}', 'OrdersController@update');
    $router->delete('/orders/{orderId}', 'OrdersController@destroy');
});
