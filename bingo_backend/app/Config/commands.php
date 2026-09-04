<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class Commands extends BaseConfig
{
    public $commands = [
        'websocket:serve' => \App\Commands\WebSocketServer::class,
    ];
}