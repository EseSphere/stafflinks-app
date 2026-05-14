<?php
header('Content-Type: application/json');
require_once 'dbconnect.php';

// Get POST data
$id = $_POST['id'];
$uniqueId = $_POST['uniqueId'];
$uryyToeSS4 = $_POST['uryyToeSS4'];
$task = $_POST['task'];
$task_date = $_POST['task_date'];
$timeIn = $_POST['timeIn'];
$note = $_POST['note'];
$carer_Id = $_POST['carer_Id'];
$carer_name = $_POST['carer_name'];
$care_calls = $_POST['care_calls'];
$col_status = $_POST['col_status'];
$col_company_Id = $_POST['col_company_Id'];
$dateTime = $_POST['dateTime'];

// Insert or update record
$sql = "INSERT INTO tbl_finished_tasks 
(id, uniqueId, uryyToeSS4, task, task_date, timeIn, note, carer_Id, carer_name, care_calls, col_status, col_company_Id, dateTime)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE
task=VALUES(task),
note=VALUES(note),
col_status=VALUES(col_status),
timeIn=VALUES(timeIn),
dateTime=VALUES(dateTime),
carer_Id=VALUES(carer_Id),
carer_name=VALUES(carer_name),
care_calls=VALUES(care_calls),
col_company_Id=VALUES(col_company_Id)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

// Bind parameters
$stmt->bind_param(
    "issssssssssss",
    $id,
    $uniqueId,
    $uryyToeSS4,
    $task,
    $task_date,
    $timeIn,
    $note,
    $carer_Id,
    $carer_name,
    $care_calls,
    $col_status,
    $col_company_Id,
    $dateTime
);

// Execute
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
