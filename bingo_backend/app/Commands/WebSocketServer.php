<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Controllers\WebSocket\GameSocket;

class WebSocketServer extends BaseCommand
{
    protected $group = 'websocket';
    protected $name = 'websocket:serve';
    protected $description = 'Start WebSocket server';

    public function run(array $params)
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new GameSocket()
                )
            ),
            8081
        );
        
        CLI::write('WebSocket server started on port 8081', 'green');
        $server->run();
    }
}