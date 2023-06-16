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

$router->group(['prefix' => 'user'], function () use ($router) {
    $router->get('{id}', 'UserController@view');
    $router->get('{id}/children', 'UserController@children');
    $router->post('/', 'UserController@add');
    $router->put('{id}', 'UserController@edit');
    $router->put('{id}/pwd', 'UserController@changePassword');
    $router->put('{id}/resetpwd', 'UserController@resetPassword');
    $router->put('{id}/tokenpush', 'UserController@tokenPush');
    $router->post('{id}/photo', 'UserController@photo');
    $router->delete('{id}', 'UserController@delete');
});
