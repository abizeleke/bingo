<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class PaymentAccounts extends Controller
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
            
            $accounts = $db->table('payment_accounts')
                ->select('payment_accounts.*, payment_methods.name as method_name')
                ->join('payment_methods', 'payment_methods.id = payment_accounts.payment_method_id', 'left')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $accounts
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate
            if (empty($data['payment_method_id']) || empty($data['account_name']) || empty($data['account_number'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'All fields required'
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Check if account already exists
            $existing = $db->table('payment_accounts')
                ->where('account_number', $data['account_number'])
                ->get()
                ->getRow();
                
            if ($existing) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Account number already exists'
                ]);
            }
            
            // Insert
            $db->table('payment_accounts')->insert([
                'payment_method_id' => $data['payment_method_id'],
                'account_name' => $data['account_name'],
                'account_number' => $data['account_number'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $id = $db->insertID();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Payment account created successfully',
                'data' => ['id' => $id]
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
            $db = \Config\Database::connect();
            
            $db->table('payment_accounts')
                ->where('id', $id)
                ->update(['status' => $data['status']]);
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Status updated'
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}