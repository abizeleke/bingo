<?php
try {
    $conn = pg_connect("host=localhost port=5432 dbname=bingo_db user=postgres password=your_password");
    if ($conn) {
        echo json_encode(['status' => 'success', 'message' => 'Database connected!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Connection failed']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}