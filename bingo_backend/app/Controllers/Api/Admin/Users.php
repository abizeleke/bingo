<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class Users extends Controller
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
            
            $users = $db->table('users')
                ->orderBy('id', 'DESC')
                ->get()
                ->getResult();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $users
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}