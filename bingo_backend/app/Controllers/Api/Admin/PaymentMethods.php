<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class PaymentMethods extends Controller
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
        $db = \Config\Database::connect();
        $methods = $db->table('payment_methods')->get()->getResult();
        
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $methods
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        $db = \Config\Database::connect();
        
        $db->table('payment_methods')->insert([
            'name' => $data['name'],
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Payment method created'
        ]);
    }

    public function updateStatus($id)
    {
        $data = $this->request->getJSON(true);
        $db = \Config\Database::connect();
        
        $db->table('payment_methods')
           ->where('id', $id)
           ->update(['status' => $data['status']]);
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Status updated'
        ]);
    }
}