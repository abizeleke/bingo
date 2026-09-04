<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;

class BotScheduler extends Controller
{
    // This should be called by a cron job every 10 minutes
    public function run()
    {
        try {
            $db = \Config\Database::connect();
            
            // Get active bots
            $bots = $db->table('bots')
                ->where('status', 'active')
                ->get()
                ->getResult();
            
            if (empty($bots)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'No active bots available'
                ]);
            }
            
            // Get current entry-open game
            $game = $db->table('games')
                ->where('status', 'entry_open')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRow();
            
            if (!$game) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No active game in entry phase'
                ]);
            }
            
            // Get game config
            $config = $db->table('game_configurations')
                ->where('id', $game->configuration_id)
                ->get()
                ->getRow();
            
            $requiredCards = $config ? $config->required_cards : 10;
            
            // Get current cards
            $currentCards = $db->table('game_cards')
                ->where('game_id', $game->id)
                ->where('status', 'reserved')
                ->countAllResults();
            
            $needed = $requiredCards - $currentCards;
            
            if ($needed <= 0) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Enough players already'
                ]);
            }
            
            // Get existing card numbers
            $existingCards = $db->table('game_cards')
                ->where('game_id', $game->id)
                ->select('card_number')
                ->get()
                ->getResult();
            
            $occupiedNumbers = array_column($existingCards, 'card_number');
            
            // Select bots to participate
            $botsAdded = 0;
            $cardNumberMax = $config ? $config->card_number_max : 500;
            
            foreach ($bots as $bot) {
                if ($botsAdded >= $needed) break;
                
                // Find available card number
                $availableNumber = null;
                for ($i = 1; $i <= $cardNumberMax; $i++) {
                    if (!in_array($i, $occupiedNumbers)) {
                        $availableNumber = $i;
                        break;
                    }
                }
                
                if (!$availableNumber) {
                    break;
                }
                
                // Add bot card
                $db->table('game_cards')->insert([
                    'game_id' => $game->id,
                    'card_number' => $availableNumber,
                    'bot_id' => $bot->id,
                    'status' => 'reserved',
                    'selected_at' => date('Y-m-d H:i:s')
                ]);
                
                $occupiedNumbers[] = $availableNumber;
                $botsAdded++;
                
                // Record bot transaction
                $db->table('bot_transactions')->insert([
                    'bot_id' => $bot->id,
                    'type' => 'entry',
                    'amount' => $config ? $config->entry_fee : 10,
                    'game_id' => $game->id,
                    'description' => 'Auto-entry for game #' . $game->game_number,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Bots added to game',
                'data' => [
                    'game_id' => $game->id,
                    'bots_added' => $botsAdded,
                    'needed' => $needed
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}