<?php
namespace App\Controllers\Api\Admin;

use CodeIgniter\Controller;

class Auth extends Controller
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

    public function login()
    {
        try {
            $data = $this->request->getJSON(true);
            
            if (empty($data['username']) || empty($data['password'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Username and password required'
                ]);
            }
            
            $db = \Config\Database::connect();
            $admin = $db->table('administrators')
                ->where('username', $data['username'])
                ->get()
                ->getRowArray();
            
            if (!$admin) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ]);
            }
            
            // Check if password matches (using bcrypt)
            if (!password_verify($data['password'], $admin['password_hash'])) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ]);
            }
            
            // Generate simple token (in production, use JWT)
            $token = 'admin_' . bin2hex(random_bytes(32));
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'admin_id' => $admin['id'],
                    'username' => $admin['username'],
                    'token' => $token
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function logout()
    {
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Logged out'
        ]);
    }

    public function verify()
    {
        $token = $this->request->getHeader('Authorization');
        
        if (!$token) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No token provided'
            ]);
        }
        
        // Simple token verification (in production, verify JWT)
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Token valid'
        ]);
    }
}