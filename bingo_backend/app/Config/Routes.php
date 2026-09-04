<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

$routes->post('api/auth/register', 'Api\Auth::register');
$routes->post('api/auth/login', 'Api\Auth::login');

// Admin Auth Routes
$routes->post('api/admin/login', 'Api\Admin\Auth::login');
$routes->post('api/admin/logout', 'Api\Admin\Auth::logout');
$routes->get('api/admin/verify', 'Api\Admin\Auth::verify');

// Admin Payment Method Routes
$routes->get('api/admin/payment-methods', 'Api\Admin\PaymentMethods::index');
$routes->post('api/admin/payment-methods', 'Api\Admin\PaymentMethods::create');
$routes->put('api/admin/payment-methods/(:num)/status', 'Api\Admin\PaymentMethods::updateStatus/$1');

// Admin Payment Account Routes
$routes->get('api/admin/payment-accounts', 'Api\Admin\PaymentAccounts::index');
$routes->post('api/admin/payment-accounts', 'Api\Admin\PaymentAccounts::create');
$routes->put('api/admin/payment-accounts/(:num)/status', 'Api\Admin\PaymentAccounts::updateStatus/$1');

// Admin Payment Account Routes
$routes->get('api/admin/payment-accounts', 'Api\Admin\PaymentAccounts::index');
$routes->post('api/admin/payment-accounts', 'Api\Admin\PaymentAccounts::create');
$routes->put('api/admin/payment-accounts/(:num)/status', 'Api\Admin\PaymentAccounts::updateStatus/$1');


// Wallet Routes
$routes->get('api/wallet/(:num)', 'Api\Wallet::getWallet/$1');
$routes->get('api/wallet/(:num)/transactions', 'Api\Wallet::getTransactions/$1');

// Payment Methods (for users)
$routes->get('api/payment-methods', 'Api\PaymentMethods::index');
$routes->get('api/payment-methods/(:num)/accounts', 'Api\PaymentMethods::getAccounts/$1');

// Deposits
$routes->post('api/deposits', 'Api\Deposits::create');

// Withdrawals
$routes->post('api/withdrawals', 'Api\Withdrawals::create');

// Deposits
$routes->post('api/deposits', 'Api\Deposits::create');

// Withdrawals
$routes->post('api/withdrawals', 'Api\Withdrawals::create');


// Admin Deposits
$routes->get('api/admin/deposits', 'Api\Admin\Deposits::index');
$routes->put('api/admin/deposits/(:num)/status', 'Api\Admin\Deposits::updateStatus/$1');

// Admin Withdrawals
$routes->get('api/admin/withdrawals', 'Api\Admin\Withdrawals::index');
$routes->put('api/admin/withdrawals/(:num)/status', 'Api\Admin\Withdrawals::updateStatus/$1');

// Admin Users
$routes->get('api/admin/users', 'Api\Admin\Users::index');


// Game Routes
$routes->get('api/game/get-state', 'Api\GameController::getGameState');
$routes->post('api/game/select-card', 'Api\GameController::selectCard');
$routes->post('api/game/unselect-card', 'Api\GameController::unselectCard');
$routes->get('api/game/card-configs', 'Api\GameController::getCardConfigs');
$routes->post('api/game/generate-configs', 'Api\GameController::generateCardConfigs');
$routes->post('api/game/start/(:num)', 'Api\GameController::startGame/$1');
$routes->post('api/game/claim-winner', 'Api\GameController::claimWinner');
$routes->put('api/game/complete/(:num)', 'Api\GameController::completeGame/$1');

// Admin Game Config Routes
$routes->get('api/admin/game-config', 'Api\Admin\GameConfigController::index');
$routes->put('api/admin/game-config', 'Api\Admin\GameConfigController::update');

// Admin Bot Routes
$routes->get('api/admin/bots', 'Api\Admin\BotController::index');
$routes->post('api/admin/bots', 'Api\Admin\BotController::create');
$routes->put('api/admin/bots/(:num)/status', 'Api\Admin\BotController::updateStatus/$1');
$routes->delete('api/admin/bots/(:num)', 'Api\Admin\BotController::delete/$1');

// Bot Scheduler (for cron job)
$routes->get('api/bot-scheduler/run', 'Api\BotScheduler::run');

;