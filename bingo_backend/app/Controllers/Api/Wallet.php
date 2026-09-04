<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;

class Wallet extends Controller
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

    public function getWallet($userId)
    {
        try {
            $db = \Config\Database::connect();
            $wallet = $db->table('wallets')
                ->where('user_id', $userId)
                ->get()
                ->getRow();
            
            if (!$wallet) {
                // Create wallet if doesn't exist
                $db->table('wallets')->insert([
                    'user_id' => $userId,
                    'balance' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $wallet = $db->table('wallets')
                    ->where('user_id', $userId)
                    ->get()
                    ->getRow();
            }
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $wallet
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getTransactions($userId)
    {
        try {
            $db = \Config\Database::connect();
            
            $wallet = $db->table('wallets')
                ->where('user_id', $userId)
                ->get()
                ->getRow();
            
            if (!$wallet) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'data' => []
                ]);
            }
            
            $transactions = $db->table('transactions')
                ->where('wallet_id', $wallet->id)
                ->orderBy('created_at', 'DESC')
                ->limit(50)
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $transactions
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}