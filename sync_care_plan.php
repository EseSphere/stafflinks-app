<?php
require_once('dbconnection.php');
header('Content-Type: application/json; charset=utf-8');

$companyId = $_GET['company_id'] ?? '';
if (empty($companyId)) {
    echo json_encode(["error" => "Missing company_id"]);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$tableName = 'tbl_general_client_form';
$tablesData = [];

$sql = "SELECT * FROM `$tableName` WHERE col_company_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $companyId);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($r = $result->fetch_assoc()) $rows[] = $r;

if (empty($rows)) {
    $desc = $conn->query("DESCRIBE `$tableName`");
    $structure = [];
    while ($col = $desc->fetch_assoc()) $structure[$col['Field']] = null;
    $rows[] = $structure;
}

$tablesData[$tableName] = $rows;

$conn->close();
echo json_encode($tablesData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
