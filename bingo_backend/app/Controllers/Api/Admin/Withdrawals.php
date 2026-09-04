<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class Withdrawals extends Controller
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

    public function index()
    {
        try {
            $db = \Config\Database::connect();
            
            $withdrawals = $db->table('withdrawals')
                ->select('withdrawals.*, users.telegram_username, payment_methods.name as method_name')
                ->join('users', 'users.id = withdrawals.user_id', 'left')
                ->join('payment_methods', 'payment_methods.id = withdrawals.payment_method_id', 'left')
                ->orderBy('withdrawals.id', 'DESC')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $withdrawals
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateStatus($id)
    {
        try {
            $data = $this->request->getJSON(true);
            $status = $data['status'] ?? '';
            $adminId = $data['admin_id'] ?? 1;
            
            if (!in_array($status, ['approved', 'rejected'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid status'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Get withdrawal details
            $withdrawal = $db->table('withdrawals')
                ->where('id', $id)
                ->get()
                ->getRow();
                
            if (!$withdrawal) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Withdrawal not found'
                ]);
            }
            
            if ($withdrawal->status !== 'pending') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Withdrawal already processed'
                ]);
            }
            
            // Start transaction
            $db->transStart();
            
            // Update withdrawal
            $db->table('withdrawals')
                ->where('id', $id)
                ->update([
                    'status' => $status,
                    'admin_id' => $adminId,
                    'processed_at' => date('Y-m-d H:i:s')
                ]);
            
            // If approved, deduct from user wallet
            if ($status === 'approved') {
                $wallet = $db->table('wallets')
                    ->where('user_id', $withdrawal->user_id)
                    ->get()
                    ->getRow();
                    
                if ($wallet) {
                    $newBalance = $wallet->balance - $withdrawal->requested_amount;
                    
                    $db->table('wallets')
                        ->where('id', $wallet->id)
                        ->update([
                            'balance' => $newBalance,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    // Add transaction record
                    $db->table('transactions')->insert([
                        'wallet_id' => $wallet->id,
                        'transaction_type' => 'withdrawal',
                        'amount' => $withdrawal->requested_amount,
                        'reference_type' => 'withdrawal',
                        'reference_id' => $withdrawal->id,
                        'description' => 'Withdrawal approved',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            $db->transComplete();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Withdrawal ' . $status . ' successfully'
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
}