<?php
// sync_shift.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconnection.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT * FROM tbl_daily_shift_records");

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $records
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
