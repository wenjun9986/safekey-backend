<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('', function ($routes) {
    $routes->group('user', ['namespace' => 'App\Controllers'], function ($routes) {
        $routes->get('find', 'UserController::findUser');
        $routes->post('login', 'UserController::login');
        $routes->post('register', 'UserController::register');
    });
    $routes->group('vault', ['namespace' => 'App\Controllers'], function ($routes) {
        $routes->get('list', 'VaultItemController::getVaultList');
        $routes->post('createItem', 'VaultItemController::createVaultItem');
        $routes->post('updateItem', 'VaultItemController::updateVaultItem');
    });
});