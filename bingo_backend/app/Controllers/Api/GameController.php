<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;

class GameController extends Controller
{
    public function __construct()
    {
        $allowed_origins = ['http://localhost:5173', 'http://localhost:5174'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

    // ============================================================
    // GET GAME STATE
    // ============================================================
    public function getGameState()
    {
        try {
            $db = \Config\Database::connect();
            
            // Get waiting game (entry phase)
            $game = $db->table('games')
                ->where('status', 'waiting')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRow();
            
            if (!$game) {
                // Create a new game
                $game = $this->createNewGame();
            }
            
            // Get game cards
            $cards = $db->table('game_cards')
                ->select('game_cards.*, users.telegram_username')
                ->join('users', 'users.id = game_cards.user_id', 'left')
                ->where('game_id', $game->id)
                ->where('status', 'reserved')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'game' => $game,
                    'cards' => $cards,
                    'total_players' => count($cards)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // CREATE NEW GAME
    // ============================================================
    private function createNewGame()
    {
        $db = \Config\Database::connect();
        
        // Get latest game number
        $latest = $db->table('games')
            ->orderBy('game_number', 'DESC')
            ->get()
            ->getRow();
            
        $gameNumber = $latest ? $latest->game_number + 1 : 100;
        
        // Get config from game_configurations table
        $config = $db->table('game_configurations')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
        
        // Insert game with 'waiting' status (entry phase)
        $gameData = [
            'game_number' => $gameNumber,
            'status' => 'waiting',
            'entry_fee' => $config ? $config->entry_fee : 10,
            'max_card_number' => $config ? $config->card_number_max : 500,
            'max_cards_per_player' => $config ? $config->max_cards_per_player : 3,
            'required_players' => $config ? $config->required_cards : 10,
            'commission_rate' => $config ? $config->commission_percent : 10,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->table('games')->insert($gameData);
        $gameId = $db->insertID();
        
        // Return the new game
        return $db->table('games')
            ->where('id', $gameId)
            ->get()
            ->getRow();
    }

    // ============================================================
    // SELECT CARD
    // ============================================================
    public function selectCard()
    {
        try {
            $data = $this->request->getJSON(true);
            
            if (empty($data['game_id']) || empty($data['card_number']) || empty($data['user_id'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Game ID, card number and user ID required'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Check if game is in entry phase (status = 'waiting')
            $game = $db->table('games')
                ->where('id', $data['game_id'])
                ->where('status', 'waiting')
                ->get()
                ->getRow();
                
            if (!$game) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Game is not in entry phase'
                ]);
            }
            
            // Check if card is available
            $existing = $db->table('game_cards')
                ->where('game_id', $data['game_id'])
                ->where('card_number', $data['card_number'])
                ->where('status', 'reserved')
                ->get()
                ->getRow();
                
            if ($existing) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Card already taken'
                ]);
            }
            
            // Check user balance
            $wallet = $db->table('wallets')
                ->where('user_id', $data['user_id'])
                ->get()
                ->getRow();
                
            $entryFee = $game->entry_fee ?? 10;
            
            if (!$wallet || $wallet->balance < $entryFee) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Insufficient balance'
                ]);
            }
            
            // Start transaction
            $db->transStart();
            
            // Deduct balance
            $newBalance = $wallet->balance - $entryFee;
            $db->table('wallets')
                ->where('id', $wallet->id)
                ->update([
                    'balance' => $newBalance,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            // Reserve card
            $db->table('game_cards')->insert([
                'game_id' => $data['game_id'],
                'card_number' => $data['card_number'],
                'user_id' => $data['user_id'],
                'status' => 'reserved',
                'selected_at' => date('Y-m-d H:i:s')
            ]);
            
            $cardId = $db->insertID();
            
            // Add transaction
            $db->table('transactions')->insert([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'entry_fee',
                'amount' => $entryFee,
                'reference_type' => 'game_card',
                'reference_id' => $cardId,
                'description' => 'Entry fee for card #' . $data['card_number'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $db->transComplete();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Card selected successfully',
                'data' => [
                    'card_id' => $cardId,
                    'balance' => $newBalance
                ]
            ]);
            
        } catch (\Exception $e) {
            $db = \Config\Database::connect();
            $db->transRollback();
            
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // UNSELECT CARD
    // ============================================================
    public function unselectCard()
    {
        try {
            $data = $this->request->getJSON(true);
            
            if (empty($data['game_id']) || empty($data['card_number']) || empty($data['user_id'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Game ID, card number and user ID required'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Check if game is in entry phase
            $game = $db->table('games')
                ->where('id', $data['game_id'])
                ->where('status', 'waiting')
                ->get()
                ->getRow();
                
            if (!$game) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Game is not in entry phase'
                ]);
            }
            
            // Get card
            $card = $db->table('game_cards')
                ->where('game_id', $data['game_id'])
                ->where('card_number', $data['card_number'])
                ->where('user_id', $data['user_id'])
                ->where('status', 'reserved')
                ->get()
                ->getRow();
                
            if (!$card) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Card not found or not yours'
                ]);
            }
            
            // Get wallet
            $wallet = $db->table('wallets')
                ->where('user_id', $data['user_id'])
                ->get()
                ->getRow();
                
            $entryFee = $game->entry_fee ?? 10;
            
            // Start transaction
            $db->transStart();
            
            // Refund balance
            $newBalance = $wallet->balance + $entryFee;
            $db->table('wallets')
                ->where('id', $wallet->id)
                ->update([
                    'balance' => $newBalance,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            // Release card
            $db->table('game_cards')
                ->where('id', $card->id)
                ->update([
                    'status' => 'released',
                    'released_at' => date('Y-m-d H:i:s')
                ]);
            
            // Add refund transaction
            $db->table('transactions')->insert([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'refund',
                'amount' => $entryFee,
                'reference_type' => 'game_card',
                'reference_id' => $card->id,
                'description' => 'Refund for card #' . $data['card_number'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $db->transComplete();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Card unselected',
                'data' => [
                    'balance' => $newBalance
                ]
            ]);
            
        } catch (\Exception $e) {
            $db = \Config\Database::connect();
            $db->transRollback();
            
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // GET CARD CONFIGURATIONS
    // ============================================================
    public function getCardConfigs()
    {
        try {
            $db = \Config\Database::connect();
            
            // Get current game
            $game = $db->table('games')
                ->where('status', 'waiting')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRow();
            
            if (!$game) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No active game found'
                ]);
            }
            
            // Get card configs from bingo_card_configurations
            $configs = $db->table('bingo_card_configurations')
                ->where('game_id', $game->id)
                ->orderBy('card_number', 'ASC')
                ->get()
                ->getResult();
            
            // If no configs exist, generate them
            if (empty($configs)) {
                $configs = $this->generateCardConfigs($game->id, $game->max_card_number);
            }
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $configs
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // GENERATE CARD CONFIGURATIONS
    // ============================================================
    public function generateCardConfigs($gameId = null, $maxCards = 500)
    {
        try {
            $db = \Config\Database::connect();
            
            // If gameId not provided, get current game
            if (!$gameId) {
                $game = $db->table('games')
                    ->where('status', 'waiting')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRow();
                    
                if (!$game) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'No active game found'
                    ]);
                }
                
                $gameId = $game->id;
                $maxCards = $game->max_card_number ?? 500;
            }
            
            // Delete old configs
            $db->table('bingo_card_configurations')
                ->where('game_id', $gameId)
                ->delete();
            
            // Generate for each card
            $generated = 0;
            for ($cardNumber = 1; $cardNumber <= $maxCards; $cardNumber++) {
                $seed = $gameId * 1000 + $cardNumber;
                $card = $this->generateCardArray($seed);
                
                $configData = [
                    'game_id' => $gameId,
                    'card_number' => $cardNumber,
                    'seed_value' => $seed,
                    'b1' => $card[0],
                    'b2' => $card[1],
                    'b3' => $card[2],
                    'b4' => $card[3],
                    'b5' => $card[4],
                    'i1' => $card[5],
                    'i2' => $card[6],
                    'i3' => $card[7],
                    'i4' => $card[8],
                    'i5' => $card[9],
                    'n1' => $card[10],
                    'n2' => $card[11],
                    'n3' => 'FREE',
                    'n4' => $card[13],
                    'n5' => $card[14],
                    'g1' => $card[15],
                    'g2' => $card[16],
                    'g3' => $card[17],
                    'g4' => $card[18],
                    'g5' => $card[19],
                    'o1' => $card[20],
                    'o2' => $card[21],
                    'o3' => $card[22],
                    'o4' => $card[23],
                    'o5' => $card[24],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $db->table('bingo_card_configurations')->insert($configData);
                $generated++;
            }
            
            // Return the generated configs
            $configs = $db->table('bingo_card_configurations')
                ->where('game_id', $gameId)
                ->orderBy('card_number', 'ASC')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Card configurations generated',
                'data' => $configs
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // GENERATE CARD ARRAY (Helper)
    // ============================================================
    private function generateCardArray($seed)
    {
        $card = [];
        $ranges = [
            [1, 15],
            [16, 30],
            [31, 45],
            [46, 60],
            [61, 75]
        ];
        
        foreach ($ranges as $colIndex => $range) {
            $numbers = [];
            for ($n = $range[0]; $n <= $range[1]; $n++) {
                $numbers[] = $n;
            }
            $shuffled = $this->shuffleArray($numbers, $seed + $colIndex * 100);
            
            for ($row = 0; $row < 5; $row++) {
                if ($colIndex === 2 && $row === 2) {
                    $card[] = 'FREE';
                } else {
                    $card[] = $shuffled[$row];
                }
            }
        }
        
        return $card;
    }

    private function shuffleArray($array, $seed)
    {
        $result = $array;
        for ($i = count($result) - 1; $i > 0; $i--) {
            $random = $this->seededRandom($seed + $i * 19.731);
            $j = floor($random * ($i + 1));
            $temp = $result[$i];
            $result[$i] = $result[$j];
            $result[$j] = $temp;
        }
        return $result;
    }

    private function seededRandom($seed)
    {
        $x = sin($seed) * 10000;
        return $x - floor($x);
    }

    // ============================================================
    // START GAME
    // ============================================================
    public function startGame($gameId)
    {
        try {
            $db = \Config\Database::connect();
            
            $game = $db->table('games')
                ->where('id', $gameId)
                ->where('status', 'waiting')
                ->get()
                ->getRow();
                
            if (!$game) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Game not found or not in entry phase'
                ]);
            }
            
            // Update game status to 'starting' (countdown)
            $db->table('games')
                ->where('id', $gameId)
                ->update([
                    'status' => 'starting',
                    'started_at' => date('Y-m-d H:i:s')
                ]);
            
            // Get all reserved cards
            $cards = $db->table('game_cards')
                ->where('game_id', $gameId)
                ->where('status', 'reserved')
                ->get()
                ->getResult();
            
            $totalCards = count($cards);
            
            // Count human vs bot cards
            $humanCards = 0;
            $botCards = 0;
            foreach ($cards as $card) {
                if ($card->user_id) {
                    $humanCards++;
                } else if ($card->bot_id) {
                    $botCards++;
                }
            }
            
            // Calculate revenue and prize pool
            $grossRevenue = $totalCards * $game->entry_fee;
            $commissionAmount = $grossRevenue * ($game->commission_rate / 100);
            $prizePool = $grossRevenue - $commissionAmount;
            
            // Update game stats
            $db->table('games')
                ->where('id', $gameId)
                ->update([
                    'total_players' => $totalCards,
                    'total_bets' => $grossRevenue,
                    'commission_amount' => $commissionAmount,
                    'prize_pool' => $prizePool
                ]);
            
            // Generate number sequence (1-75 shuffled)
            $numbers = range(1, 75);
            shuffle($numbers);
            
            // Store called numbers
            foreach ($numbers as $order => $number) {
                $db->table('called_numbers')->insert([
                    'game_id' => $gameId,
                    'number' => $number,
                    'call_order' => $order + 1,
                    'called_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Game started',
                'data' => [
                    'game_id' => $gameId,
                    'sequence' => $numbers,
                    'total_cards' => $totalCards,
                    'human_cards' => $humanCards,
                    'bot_cards' => $botCards,
                    'prize_pool' => $prizePool,
                    'commission' => $commissionAmount
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // CLAIM WINNER
    // ============================================================
    public function claimWinner()
    {
        try {
            $data = $this->request->getJSON(true);
            
            if (empty($data['game_id']) || empty($data['card_number']) || empty($data['user_id'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Game ID, card number and user ID required'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Verify game exists and is active
            $game = $db->table('games')
                ->where('id', $data['game_id'])
                ->where('status', 'active')
                ->get()
                ->getRow();
                
            if (!$game) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Game not found or not active'
                ]);
            }
            
            // Verify card belongs to user
            $card = $db->table('game_cards')
                ->where('game_id', $data['game_id'])
                ->where('card_number', $data['card_number'])
                ->where('user_id', $data['user_id'])
                ->get()
                ->getRow();
                
            if (!$card) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Card not found or not yours'
                ]);
            }
            
            // Get called numbers
            $called = $db->table('called_numbers')
                ->where('game_id', $data['game_id'])
                ->orderBy('call_order', 'ASC')
                ->get()
                ->getResult();
                
            $calledNumbers = array_column($called, 'number');
            
            // Get card configuration
            $cardConfig = $db->table('bingo_card_configurations')
                ->where('game_id', $data['game_id'])
                ->where('card_number', $data['card_number'])
                ->get()
                ->getRow();
                
            if (!$cardConfig) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Card configuration not found'
                ]);
            }
            
            // Reconstruct card array
            $cardArray = [
                ['number' => $cardConfig->b1, 'column' => 0, 'row' => 0],
                ['number' => $cardConfig->b2, 'column' => 0, 'row' => 1],
                ['number' => $cardConfig->b3, 'column' => 0, 'row' => 2],
                ['number' => $cardConfig->b4, 'column' => 0, 'row' => 3],
                ['number' => $cardConfig->b5, 'column' => 0, 'row' => 4],
                ['number' => $cardConfig->i1, 'column' => 1, 'row' => 0],
                ['number' => $cardConfig->i2, 'column' => 1, 'row' => 1],
                ['number' => $cardConfig->i3, 'column' => 1, 'row' => 2],
                ['number' => $cardConfig->i4, 'column' => 1, 'row' => 3],
                ['number' => $cardConfig->i5, 'column' => 1, 'row' => 4],
                ['number' => $cardConfig->n1, 'column' => 2, 'row' => 0],
                ['number' => $cardConfig->n2, 'column' => 2, 'row' => 1],
                ['number' => 'FREE', 'column' => 2, 'row' => 2],
                ['number' => $cardConfig->n4, 'column' => 2, 'row' => 3],
                ['number' => $cardConfig->n5, 'column' => 2, 'row' => 4],
                ['number' => $cardConfig->g1, 'column' => 3, 'row' => 0],
                ['number' => $cardConfig->g2, 'column' => 3, 'row' => 1],
                ['number' => $cardConfig->g3, 'column' => 3, 'row' => 2],
                ['number' => $cardConfig->g4, 'column' => 3, 'row' => 3],
                ['number' => $cardConfig->g5, 'column' => 3, 'row' => 4],
                ['number' => $cardConfig->o1, 'column' => 4, 'row' => 0],
                ['number' => $cardConfig->o2, 'column' => 4, 'row' => 1],
                ['number' => $cardConfig->o3, 'column' => 4, 'row' => 2],
                ['number' => $cardConfig->o4, 'column' => 4, 'row' => 3],
                ['number' => $cardConfig->o5, 'column' => 4, 'row' => 4],
            ];
            
            // Validate winning pattern
            $winningPattern = $this->checkWinningPattern($cardArray, $calledNumbers);
            
            if (!$winningPattern) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Not a winning card'
                ]);
            }
            
            // Check if winner already claimed
            $existingWinner = $db->table('winning_cards')
                ->where('game_id', $data['game_id'])
                ->get()
                ->getRow();
                
            if ($existingWinner) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Winner already claimed'
                ]);
            }
            
            // Calculate prize share
            $totalCards = $db->table('game_cards')
                ->where('game_id', $data['game_id'])
                ->where('status', 'reserved')
                ->countAllResults();
                
            $prizePool = $totalCards * $game->entry_fee * (1 - $game->commission_rate / 100);
            
            // Record winner
            $db->table('winning_cards')->insert([
                'game_id' => $data['game_id'],
                'game_card_id' => $card->id,
                'pattern' => $winningPattern['type'],
                'prize_share' => $prizePool,
                'winner_user_id' => $data['user_id'],
                'detected_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update game winning cards count
            $db->table('games')
                ->where('id', $data['game_id'])
                ->update([
                    'winning_cards_count' => 1
                ]);
            
            // Credit user wallet
            $wallet = $db->table('wallets')
                ->where('user_id', $data['user_id'])
                ->get()
                ->getRow();
                
            if ($wallet) {
                $newBalance = $wallet->balance + $prizePool;
                $db->table('wallets')
                    ->where('id', $wallet->id)
                    ->update([
                        'balance' => $newBalance,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                // Add transaction
                $db->table('transactions')->insert([
                    'wallet_id' => $wallet->id,
                    'transaction_type' => 'winning',
                    'amount' => $prizePool,
                    'reference_type' => 'game',
                    'reference_id' => $data['game_id'],
                    'description' => 'Bingo winner!',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Update game status to 'finished'
            $db->table('games')
                ->where('id', $data['game_id'])
                ->update([
                    'status' => 'finished',
                    'finished_at' => date('Y-m-d H:i:s')
                ]);
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Winner validated',
                'data' => [
                    'winner_user_id' => $data['user_id'],
                    'prize_amount' => $prizePool,
                    'game_id' => $data['game_id'],
                    'pattern' => $winningPattern['type']
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // ============================================================
    // CHECK WINNING PATTERN (Helper)
    // ============================================================
    private function checkWinningPattern($card, $calledNumbers)
    {
        $called = new \stdClass();
        $called->numbers = $calledNumbers;
        
        $marked = array_map(function($cell) use ($called) {
            if ($cell['number'] === 'FREE') return true;
            return in_array($cell['number'], $called->numbers);
        }, $card);

        // HORIZONTAL
        for ($row = 0; $row < 5; $row++) {
            $indexes = [];
            for ($col = 0; $col < 5; $col++) {
                $indexes[] = $col * 5 + $row;
            }
            if ($this->allMarked($indexes, $marked)) {
                return ['type' => 'HORIZONTAL'];
            }
        }

        // VERTICAL
        for ($col = 0; $col < 5; $col++) {
            $indexes = [];
            for ($row = 0; $row < 5; $row++) {
                $indexes[] = $col * 5 + $row;
            }
            if ($this->allMarked($indexes, $marked)) {
                return ['type' => 'VERTICAL'];
            }
        }

        // DIAGONAL
        $diagonal1 = [0, 6, 12, 18, 24];
        if ($this->allMarked($diagonal1, $marked)) {
            return ['type' => 'DIAGONAL'];
        }
        
        $diagonal2 = [4, 8, 12, 16, 20];
        if ($this->allMarked($diagonal2, $marked)) {
            return ['type' => 'DIAGONAL'];
        }

        // FOUR CORNERS
        $corners = [0, 4, 20, 24];
        if ($this->allMarked($corners, $marked)) {
            return ['type' => 'FOUR CORNERS'];
        }

        return null;
    }

    private function allMarked($indexes, $marked)
    {
        foreach ($indexes as $index) {
            if (!$marked[$index]) {
                return false;
            }
        }
        return true;
    }

    // ============================================================
    // COMPLETE GAME (For testing)
    // ============================================================
    public function completeGame($gameId)
    {
        try {
            $db = \Config\Database::connect();
            
            $db->table('games')
                ->where('id', $gameId)
                ->update([
                    'status' => 'finished',
                    'finished_at' => date('Y-m-d H:i:s')
                ]);
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Game completed'
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}