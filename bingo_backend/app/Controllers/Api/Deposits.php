<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;

class Deposits extends Controller
{
    public function __construct()
    {
        header('Access-Control-Allow-Origin: http://localhost:5173');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate
            if (empty($data['user_id']) || empty($data['amount']) || empty($data['payment_method_id']) || empty($data['payment_account_id'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'All fields required'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Insert deposit request
            $db->table('deposits')->insert([
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'payment_method_id' => $data['payment_method_id'],
                'payment_account_id' => $data['payment_account_id'],
                'transaction_number' => $data['transaction_number'] ?? null,
                'status' => 'pending',
                'requested_at' => date('Y-m-d H:i:s')
            ]);
            
            $id = $db->insertID();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Deposit request submitted',
                'data' => ['id' => $id]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}