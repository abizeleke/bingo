<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;

class Test extends Controller
{
    public function index()
    {
        header('Access-Control-Allow-Origin: http://localhost:5173');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'API is working!'
        ]);
    }
}