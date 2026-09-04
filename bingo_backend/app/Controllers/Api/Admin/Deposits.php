<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class Deposits extends Controller
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
            
            $deposits = $db->table('deposits')
                ->select('deposits.*, users.telegram_username, payment_methods.name as method_name, payment_accounts.account_name, payment_accounts.account_number')
                ->join('users', 'users.id = deposits.user_id', 'left')
                ->join('payment_methods', 'payment_methods.id = deposits.payment_method_id', 'left')
                ->join('payment_accounts', 'payment_accounts.id = deposits.payment_account_id', 'left')
                ->orderBy('deposits.id', 'DESC')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $deposits
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
            
            // Get deposit details
            $deposit = $db->table('deposits')
                ->where('id', $id)
                ->get()
                ->getRow();
                
            if (!$deposit) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Deposit not found'
                ]);
            }
            
            if ($deposit->status !== 'pending') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Deposit already processed'
                ]);
            }
            
            // Start transaction
            $db->transStart();
            
            // Update deposit
            $db->table('deposits')
                ->where('id', $id)
                ->update([
                    'status' => $status,
                    'admin_id' => $adminId,
                    'processed_at' => date('Y-m-d H:i:s')
                ]);
            
            // If approved, add to user wallet
            if ($status === 'approved') {
                // Get user wallet
                $wallet = $db->table('wallets')
                    ->where('user_id', $deposit->user_id)
                    ->get()
                    ->getRow();
                    
                if ($wallet) {
                    $newBalance = $wallet->balance + $deposit->amount;
                    
                    $db->table('wallets')
                        ->where('id', $wallet->id)
                        ->update([
                            'balance' => $newBalance,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    // Add transaction record
                    $db->table('transactions')->insert([
                        'wallet_id' => $wallet->id,
                        'transaction_type' => 'deposit',
                        'amount' => $deposit->amount,
                        'reference_type' => 'deposit',
                        'reference_id' => $deposit->id,
                        'description' => 'Deposit approved',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            $db->transComplete();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Deposit ' . $status . ' successfully'
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