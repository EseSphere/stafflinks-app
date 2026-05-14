<?php
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

try {

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data || !isset($data['col_care_call_Id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input: missing col_care_call_Id']);
        exit;
    }

    include_once('dbconnect.php');

    $conn->begin_transaction();

    $col_care_call_Id  = (string)$data['col_care_call_Id'];
    $shift_end_time    = $data['shift_end_time'] ?? null;
    $col_call_status   = $data['col_call_status'] ?? null;
    $col_worked_time   = $data['col_worked_time'] ?? null;
    $col_carecall_rate = $data['col_carecall_rate'] ?? 0;
    $col_client_rate   = $data['col_client_rate'] ?? 0;
    $task_note         = $data['task_note'] ?? null;

    $stmt = $conn->prepare("UPDATE tbl_daily_shift_records SET shift_end_time = ?, col_call_status = ?, 
    col_worked_time = ?, col_carecall_rate = ?, col_client_rate = ?, task_note = ?, sync_status = 'synced' WHERE col_care_call_Id = ?");

    if (!$stmt) {
        throw new Exception('Prepare failed for tbl_daily_shift_records');
    }

    $stmt->bind_param(
        "sssddss",
        $shift_end_time,
        $col_call_status,
        $col_worked_time,
        $col_carecall_rate,
        $col_client_rate,
        $task_note,
        $col_care_call_Id
    );

    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No matching shift record found to update');
    }

    $stmt->close();

    $stmt2 = $conn->prepare("UPDATE tbl_schedule_calls SET call_status = 'Completed' WHERE id = ?");

    if (!$stmt2) {
        throw new Exception('Prepare failed for tbl_schedule_calls');
    }

    $stmt2->bind_param("s", $col_care_call_Id);
    $stmt2->execute();

    if ($stmt2->affected_rows === 0) {
        throw new Exception('No matching schedule call found to update');
    }

    $stmt2->close();

    $conn->commit();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Shift updated and call marked as Completed'
    ]);

} catch (Exception $e) {

    if (isset($conn) && $conn->errno === 0) {
        $conn->rollback();
        $conn->close();
    }

    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
