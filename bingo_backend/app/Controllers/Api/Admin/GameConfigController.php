<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class GameConfigController extends Controller
{
    public function __construct()
    {
        header('Access-Control-Allow-Origin: http://localhost:5174');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

    // Get current game config
    public function index()
    {
        try {
            $db = \Config\Database::connect();
            
            $config = $db->table('game_configurations')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRow();
            
            if (!$config) {
                // Create default config
                $db->table('game_configurations')->insert([
                    'card_number_max' => 500,
                    'entry_fee' => 10,
                    'required_cards' => 10,
                    'max_cards_per_player' => 3,
                    'entry_duration_seconds' => 50,
                    'bot_check_interval_seconds' => 10,
                    'commission_percent' => 10,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $config = $db->table('game_configurations')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRow();
            }
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $config
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Update game config
    public function update()
    {
        try {
            $data = $this->request->getJSON(true);
            
            $db = \Config\Database::connect();
            $configId = $data['id'] ?? 1;
            
            $updateData = [
                'card_number_max' => $data['card_number_max'] ?? 500,
                'entry_fee' => $data['entry_fee'] ?? 10,
                'required_cards' => $data['required_cards'] ?? 10,
                'max_cards_per_player' => $data['max_cards_per_player'] ?? 3,
                'entry_duration_seconds' => $data['entry_duration_seconds'] ?? 50,
                'bot_check_interval_seconds' => $data['bot_check_interval_seconds'] ?? 10,
                'commission_percent' => $data['commission_percent'] ?? 10,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('game_configurations')
                ->where('id', $configId)
                ->update($updateData);
            
            // Log admin action
            $this->logActivity($data['admin_id'] ?? 1, 'Updated game configuration', 'game_config', $configId);
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Game configuration updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function logActivity($adminId, $action, $entityType, $entityId)
    {
        $db = \Config\Database::connect();
        $db->table('admin_activity_logs')->insert([
            'admin_id' => $adminId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}