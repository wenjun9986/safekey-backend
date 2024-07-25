<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('', function ($routes) {
    $routes->group('auth', ['namespace' => 'App\Controllers'], function ($routes) {
        $routes->get('generate2FASecret', 'AuthController::generate2FASecret');
        $routes->get('get2FADetails', 'AuthController::get2FADetails');
        $routes->post('validateToken', 'AuthController::validateToken');
        $routes->post('enable2FA', 'AuthController::enable2FA');
        $routes->post('disable2FA', 'AuthController::disable2FA');
        $routes->post('verify2FA', 'AuthController::verify2FAToken');
    });
    $routes->group('user', ['namespace' => 'App\Controllers'], function ($routes) {
        $routes->get('find', 'UserController::findUser');
        $routes->get('userConfigs', 'UserController::getUserConfigs');
        $routes->post('login', 'UserController::login');
        $routes->post('register', 'UserController::register');
        $routes->post('updateVaultTimeout', 'UserController::updateVaultTimeout');
    });
    $routes->group('vault', ['namespace' => 'App\Controllers'], function ($routes) {
        $routes->get('list', 'VaultItemController::getVaultList');
        $routes->post('createItem', 'VaultItemController::createVaultItem');
        $routes->post('updateItem', 'VaultItemController::updateVaultItem');
    });
});