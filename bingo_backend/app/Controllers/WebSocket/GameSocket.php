<?php
namespace App\Controllers\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class GameSocket implements MessageComponentInterface
{
    protected $clients;
    protected $games;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->games = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'subscribe') {
            $gameId = $data['game_id'];
            $this->games[$gameId][] = $from;
            
            // Send sequence when game starts
            if ($data['action'] === 'start_game') {
                $sequence = $this->generateSequence();
                $from->send(json_encode([
                    'type' => 'game_start',
                    'sequence' => $sequence,
                    'game_id' => $gameId
                ]));
            }
        }
        
        if ($data['type'] === 'winner_found') {
            // Broadcast winner to all players
            foreach ($this->games[$data['game_id']] as $client) {
                $client->send(json_encode([
                    'type' => 'winner',
                    'winner' => $data['winner'],
                    'prize' => $data['prize'],
                    'game_id' => $data['game_id']
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        // Remove from games
        foreach ($this->games as $gameId => $clients) {
            foreach ($clients as $key => $client) {
                if ($client === $conn) {
                    unset($this->games[$gameId][$key]);
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function generateSequence()
    {
        $numbers = range(1, 75);
        shuffle($numbers);
        return $numbers;
    }
}