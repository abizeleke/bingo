<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

class TestDb extends Controller
{
    public function index()
    {
        try {
            $db = Database::connect();
            
            // Test connection
            $query = $db->query("SELECT NOW() as current_time, version() as pg_version");
            $result = $query->getRow();
            
            // Get list of tables
            $tables = $db->listTables();
            
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Database connected successfully!',
                'data' => [
                    'server_time' => $result->current_time ?? null,
                    'postgres_version' => $result->pg_version ?? null,
                    'total_tables' => count($tables),
                    'tables' => array_slice($tables, 0, 10) // Show first 10 tables
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Database connection failed!',
                'error' => $e->getMessage()
            ]);
        }
    }
}