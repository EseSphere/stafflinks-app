<?php
require_once('dbconnection.php');
header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$conn = $db->getConnection();
$companyId = $_GET['company_id'] ?? '';
if (empty($companyId)) {
    echo json_encode(["error" => "Missing company_id"]);
    exit;
}

$tablesToSync = [
    'tbl_cancelled_call' => ['date_column' => 'col_date', 'range' => 'one_month'],
    'tbl_client_status_records' => ['date_column' => 'col_start_date', 'range' => 'one_month'],
    'tbl_schedule_calls' => ['date_column' => 'Clientshift_Date', 'range' => 'two_months']
];

$currentMonthStart = date('Y-m-01 00:00:00');
$currentMonthEnd = date('Y-m-t 23:59:59');
$previousMonthStart = date('Y-m-01 00:00:00', strtotime('-1 month'));
$tablesData = [];

foreach ($tablesToSync as $tableName => $options) {
    $dateColumn = $options['date_column'] ?? null;
    $rangeType = $options['range'] ?? null;

    $sql = "SELECT * FROM `$tableName` WHERE col_company_Id = ?";
    $params = [$companyId];
    $types = "s";

    if ($dateColumn) {
        if ($rangeType === 'one_month') {
            $sql .= " AND `$dateColumn` BETWEEN ? AND ?";
            $params[] = $currentMonthStart;
            $params[] = $currentMonthEnd;
            $types .= "ss";
        } elseif ($rangeType === 'two_months') {
            $sql .= " AND `$dateColumn` BETWEEN ? AND ?";
            $params[] = $previousMonthStart;
            $params[] = $currentMonthEnd;
            $types .= "ss";
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
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
}

$conn->close();
echo json_encode($tablesData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
