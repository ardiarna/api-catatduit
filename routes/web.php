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
    $router->post('/', 'TransaksiController@create');
    $router->put('{id}', 'TransaksiController@update');
    $router->delete('{id}', 'TransaksiController@delete');
});
