<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;

class PaymentMethods extends Controller
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

    public function index()
    {
        try {
            $db = \Config\Database::connect();
            $methods = $db->table('payment_methods')
                ->where('status', 'active')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $methods
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getAccounts($methodId)
    {
        try {
            $db = \Config\Database::connect();
            $accounts = $db->table('payment_accounts')
                ->where('payment_method_id', $methodId)
                ->where('status', 'active')
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
}