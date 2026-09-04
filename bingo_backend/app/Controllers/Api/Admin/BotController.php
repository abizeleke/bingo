<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class BotController extends Controller
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

    // Get all bots
    public function index()
    {
        try {
            $db = \Config\Database::connect();
            
            $bots = $db->table('bots')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $bots
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Create bot
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);
            
            if (empty($data['bot_name'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Bot name required'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Check if bot exists
            $existing = $db->table('bots')
                ->where('bot_name', $data['bot_name'])
                ->get()
                ->getRow();
                
            if ($existing) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Bot name already exists'
                ]);
            }
            
            // Insert bot
            $db->table('bots')->insert([
                'bot_name' => $data['bot_name'],
                'status' => $data['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $id = $db->insertID();
            
            // Create wallet for bot
            $db->table('wallets')->insert([
                'bot_id' => $id,
                'balance' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Log activity
            $this->logActivity($data['admin_id'] ?? 1, 'Created bot: ' . $data['bot_name'], 'bot', $id);
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Bot created successfully',
                'data' => ['id' => $id]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Update bot status
    public function updateStatus($id)
    {
        try {
            $data = $this->request->getJSON(true);
            $status = $data['status'] ?? 'inactive';
            
            $db = \Config\Database::connect();
            
            $db->table('bots')
                ->where('id', $id)
                ->update(['status' => $status]);
            
            $this->logActivity($data['admin_id'] ?? 1, 'Updated bot status to ' . $status, 'bot', $id);
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Bot status updated'
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Delete bot
    public function delete($id)
    {
        try {
            $db = \Config\Database::connect();
            
            $db->table('bots')
                ->where('id', $id)
                ->delete();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Bot deleted'
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