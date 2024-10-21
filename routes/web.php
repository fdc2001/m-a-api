<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/', function () use ($router) {
    return "App for transfer tombstones to web site from orbit";
});

$router->group(['prefix' => '/api/v1'], function () use ($router){
    $router->group(['middleware' => App\Http\Middleware\AuthWordpressMiddleware::class], function () use ($router) {
        $router->group(['prefix' => '/configs'], function () use ($router){
            $router->get('/orbit_test', 'ConfigsController@orbit_test');
            $router->get('/{field}', 'ConfigsController@show');
            $router->put('/{field}', 'ConfigsController@update');
        });

        $router->group(['prefix' => '/sync'], function () use ($router){
            $router->get('/members', 'MembersController@syncMembers');
            $router->get('/region', 'RegionsController@syncRegions');
            $router->get('/industry', 'IndustrySectorsController@syncIndustry');
        });

        $router->group(['prefix' => '/transactions'], function () use ($router){
            $router->get('/latest', 'TransactionsController@showLatest');
            $router->get('/orbit/{orbitID}', 'TransactionsController@transactionFeatured');
            $router->get('/all', 'TransactionsController@transactionsPage');
            $router->get('/all/{side}', 'TransactionsController@transactionsPageLoadMore');
            $router->get('/slug/{slug}', 'TransactionsController@detailsFrontend');
            $router->get('/member/{member}', 'TransactionsController@transactionByMember');
            $router->get('/member/all/{member}/{side}', 'TransactionsController@transactionsPageLoadMoreOffice');

            $router->get('/industry/{industry}', 'TransactionsController@transactionByIndustry');
            $router->get('/industry/all/{industry}', 'TransactionsController@transactionByIndustryLoadMore');

            $router->get('/', 'TransactionsController@index');
            $router->post('/wp', 'TransactionsController@createByWp');
            $router->put('/{id}', 'TransactionsController@update');
            $router->get('/{id}', 'TransactionsController@show');
            $router->delete('/{id}', 'TransactionsController@destroy');
        });

        $router->group(['prefix' => '/members'], function () use ($router){
            $router->get('/', 'MembersController@get');
        });

        $router->group(['prefix' => '/regions'], function () use ($router){
            $router->get('/', 'RegionsController@get');
        });

        $router->group(['prefix' => '/industry'], function () use ($router){
            $router->get('/', 'IndustrySectorsController@get');
        });
    });
    $router->group(['middleware' => \App\Http\Middleware\AuthOrbitMiddleware::class],function () use ($router){
        $router->post('/transactions', 'TransactionsController@store');
    });
});
