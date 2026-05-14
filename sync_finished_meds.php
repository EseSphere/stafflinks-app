<?php
header('Content-Type: application/json');
require_once 'dbconnect.php';

$id = $_POST['id'];
$uniqueId = $_POST['uniqueId'];
$uryyToeSS4 = $_POST['uryyToeSS4'];
$meds = $_POST['meds'];
$med_date = $_POST['med_date'];
$timeIn = $_POST['timeIn'];
$note = $_POST['note'];
$carer_Id = $_POST['carer_Id'];
$carer_name = $_POST['carer_name'];
$care_calls = $_POST['care_calls'];
$col_status = $_POST['col_status'];
$col_company_Id = $_POST['col_company_Id'];
$dateTime = $_POST['dateTime'];

// Insert or update (upsert)
$sql = "INSERT INTO tbl_finished_meds
(id, uniqueId, uryyToeSS4, meds, med_date, timeIn, note, carer_Id, carer_name, care_calls, col_status, col_company_Id, dateTime)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE
meds=VALUES(meds), note=VALUES(note), col_status=VALUES(col_status), timeIn=VALUES(timeIn), dateTime=VALUES(dateTime)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issssssssssss", $id, $uniqueId, $uryyToeSS4, $meds, $med_date, $timeIn, $note, $carer_Id, $carer_name, $care_calls, $col_status, $col_company_Id, $dateTime);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
