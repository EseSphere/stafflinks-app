<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconnection.php';
header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        throw new Exception("Invalid data: missing id");
    }

    $id = intval($data['id']);

    $fields = [
        'shift_status',
        'shift_date',
        'planned_timeIn',
        'planned_timeOut',
        'shift_start_time',
        'shift_end_time',
        'client_name',
        'uryyToeSS4',
        'col_care_call',
        'client_group',
        'carer_Name',
        'task_note',
        'col_carer_Id',
        'timesheet_date',
        'col_area_Id',
        'col_company_Id',
        'col_call_status',
        'col_carecall_rate',
        'col_miles',
        'col_mileage',
        'col_worked_time',
        'col_client_rate',
        'col_client_payer',
        'col_visit_status',
        'col_visit_confirmation',
        'col_care_call_Id',
        'col_postcode',
        'dateTime'
    ];

    $insertFields = ['id'];
    $insertPlaceholders = ['?'];
    $insertTypes = ['i'];
    $insertValues = [$id];
    $updateParts = [];

    foreach ($fields as $f) {
        $val = $data[$f] ?? null;
        $insertFields[] = $f;
        $insertPlaceholders[] = '?';
        $insertValues[] = $val;
        $insertTypes[] = 's';
        $updateParts[] = "$f = VALUES($f)";
    }

    $sql = "INSERT INTO tbl_daily_shift_records (" . implode(',', $insertFields) . ") 
            VALUES (" . implode(',', $insertPlaceholders) . ") 
            ON DUPLICATE KEY UPDATE " . implode(',', $updateParts);

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->bind_param(implode('', $insertTypes), ...$insertValues);
    $stmt->execute();

    if ($stmt->error) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $recordId = $stmt->insert_id ?: $id;

    if (!empty($data['col_care_call_Id'])) {
        $col_care_call_Id = intval($data['col_care_call_Id']);

        $sqlUpdate = "UPDATE tbl_schedule_calls SET call_status = 'In progress' WHERE id = ?";

        $stmtUpdate = $db->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            throw new Exception("Update prepare failed: " . $db->error);
        }

        $stmtUpdate->bind_param('i', $col_care_call_Id);
        $stmtUpdate->execute();

        if ($stmtUpdate->error) {
            throw new Exception("Update execute failed: " . $stmtUpdate->error);
        }

        $stmtUpdate->close();
    }

    echo json_encode([
        'success' => true,
        'id' => $recordId
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
