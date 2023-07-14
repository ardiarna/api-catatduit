<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('login', 'AuthController@login');
$router->post('register', 'UserController@create');
$router->post('resetpwd', 'UserController@resetPassword');

$router->get('phonecode', 'JsonController@findAll');

$router->group(['middleware' => 'auth:api'], function () use ($router) {
    $router->get('logout', 'AuthController@logout');
    $router->get('refresh', 'AuthController@refresh');
});

$router->group(['prefix' => 'user', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'UserController@view');
    $router->get('children', 'UserController@children');
    $router->get('parent', 'UserController@parent');
    $router->post('/', 'UserController@create');
    $router->put('/', 'UserController@update');
    $router->put('editpwd', 'UserController@editPassword');
    $router->put('tokenpush', 'UserController@tokenPush');
    $router->post('photo', 'UserController@photo');
    $router->delete('/', 'UserController@delete');
});

$router->group(['prefix' => 'bank', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'BankController@findAll');
    $router->get('{id}', 'BankController@findById');
    $router->post('/', 'BankController@create');
    $router->put('{id}', 'BankController@update');
    $router->delete('{id}', 'BankController@delete');
});

$router->group(['prefix' => 'rekening', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'RekeningController@findAll');
    $router->get('{id}', 'RekeningController@findById');
    $router->get('view/saldo', 'RekeningController@getTotalSaldo');
    $router->post('/', 'RekeningController@create');
    $router->put('{id}', 'RekeningController@update');
    $router->put('{id}/adjust', 'RekeningController@adjust');
    $router->delete('{id}', 'RekeningController@delete');
});

$router->group(['prefix' => 'adjust', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'AdjustController@findAll');
    $router->get('{id}', 'AdjustController@findById');
    $router->post('/', 'AdjustController@create');
});

$router->group(['prefix' => 'kategori', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'KategoriController@findAll');
    $router->get('{id}', 'KategoriController@findById');
    $router->post('/', 'KategoriController@create');
    $router->put('{id}', 'KategoriController@update');
    $router->delete('{id}', 'KategoriController@delete');
});

$router->group(['prefix' => 'anggaran', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'AnggaranController@findAll');
    $router->get('{id}', 'AnggaranController@findById');
    $router->post('/', 'AnggaranController@create');
    $router->put('{id}', 'AnggaranController@update');
    $router->delete('{id}', 'AnggaranController@delete');
});

$router->group(['prefix' => 'transaksi', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'TransaksiController@findAll');
    $router->get('{id}', 'TransaksiController@findById');
    $router->get('view/summary', 'TransaksiController@summaryPeriode');
    $router->post('/', 'TransaksiController@create');
    $router->put('{id}', 'TransaksiController@update');
    $router->post('{id}/photo', 'TransaksiController@addFoto');
    $router->delete('{id}', 'TransaksiController@delete');
    $router->delete('{nama}/photo', 'TransaksiController@deleteFoto');
});

$router->group(['prefix' => 'transfer', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'TransferController@findAll');
    $router->get('{id}', 'TransferController@findById');
    $router->post('/', 'TransferController@create');
    $router->put('{id}', 'TransferController@update');
    $router->delete('{id}', 'TransferController@delete');
});

$router->group(['prefix' => 'piutang', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PiutangController@findAll');
    $router->get('{id}', 'PiutangController@findById');
    $router->get('{id}/detil', 'PiutangController@findDetilById');
    $router->post('/', 'PiutangController@create');
    $router->post('{id}', 'PiutangController@createDetil');
    $router->put('{id}', 'PiutangController@update');
    $router->put('{id}/detil', 'PiutangController@updateDetil');
    $router->delete('{id}', 'PiutangController@delete');
    $router->delete('{id}/detil', 'PiutangController@deleteDetil');
});

$router->group(['prefix' => 'pinjaman', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PinjamanController@findAll');
    $router->get('{id}', 'PinjamanController@findById');
    $router->get('{id}/detil', 'PinjamanController@findDetilById');
    $router->post('/', 'PinjamanController@create');
    $router->post('{id}', 'PinjamanController@createDetil');
    $router->put('{id}', 'PinjamanController@update');
    $router->put('{id}/detil', 'PinjamanController@updateDetil');
    $router->delete('{id}', 'PinjamanController@delete');
    $router->delete('{id}/detil', 'PinjamanController@deleteDetil');
});
