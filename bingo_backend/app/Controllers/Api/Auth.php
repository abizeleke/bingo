<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;
use App\Models\UserModel;

class Auth extends Controller
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

    public function register()
{
    try {
        $data = $this->request->getJSON(true);
        
        if (empty($data['phone_number']) || empty($data['password']) || empty($data['telegram_username'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'All fields required'
            ]);
        }
        
        $userModel = new UserModel();
        
        // Check existing - using 'phone' column
        $existing = $userModel->where('phone', $data['phone_number'])->first();
        if ($existing) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Phone number already registered'
            ]);
        }
        
        // Insert user - using correct column names
        $userData = [
    'phone' => $data['phone_number'],
    'telegram_username' => $data['telegram_username'],
    'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
    'is_bot' => false,
    'is_active' => true
];
        
        $userId = $userModel->insert($userData);
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'user_id' => $userId,
                'username' => $data['telegram_username'],
                'phone' => $data['phone_number']
            ]
        ]);
        
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

    public function login()
{
    try {
        $data = $this->request->getJSON(true);
        
        if (empty($data['phone_number']) || empty($data['password'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Phone and password required'
            ]);
        }
        
        $userModel = new UserModel();
        // Using 'phone' column
        $user = $userModel->where('phone', $data['phone_number'])->first();
        
        if (!$user) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'User not found'
            ]);
        }
        
        if (!password_verify($data['password'], $user['password_hash'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid password'
            ]);
        }
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['telegram_username'],
                'phone' => $user['phone'],
                'token' => 'simple-token-' . $user['id']
            ]
        ]);
        
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
}