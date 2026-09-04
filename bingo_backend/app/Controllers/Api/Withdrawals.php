<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;

class Withdrawals extends Controller
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
            if (empty($data['user_id']) || empty($data['amount']) || empty($data['payment_method_id']) || empty($data['account_number'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'All fields required'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Check user balance
            $wallet = $db->table('wallets')
                ->where('user_id', $data['user_id'])
                ->get()
                ->getRow();
                
            if (!$wallet || $wallet->balance < $data['amount']) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Insufficient balance'
                ]);
            }
            
            // Calculate fee (2%)
            $feePercent = 2;
            $feeAmount = $data['amount'] * ($feePercent / 100);
            $receivedAmount = $data['amount'] - $feeAmount;
            
            // Insert withdrawal request
            $db->table('withdrawals')->insert([
                'user_id' => $data['user_id'],
                'payment_method_id' => $data['payment_method_id'],
                'account_number' => $data['account_number'],
                'requested_amount' => $data['amount'],
                'fee_percent' => $feePercent,
                'fee_amount' => $feeAmount,
                'received_amount' => $receivedAmount,
                'status' => 'pending',
                'requested_at' => date('Y-m-d H:i:s')
            ]);
            
            $id = $db->insertID();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Withdrawal request submitted',
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