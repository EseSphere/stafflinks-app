<?php
$userId    = isset($_GET['user_special_Id']) ? trim($_GET['user_special_Id']) : '';
$companyId = isset($_GET['col_company_Id'])  ? trim($_GET['col_company_Id'])  : '';
$search    = isset($_GET['search'])          ? trim($_GET['search'])          : '';
$statusFilter = isset($_GET['status'])       ? trim($_GET['status'])          : '';
$dateFrom  = isset($_GET['date_from'])       ? trim($_GET['date_from'])       : '';
$dateTo    = isset($_GET['date_to'])         ? trim($_GET['date_to'])         : '';
$page      = max(1, (int)($_GET['page']     ?? 1));
$perPage   = 20;

$records       = [];
$totalRecords  = 0;
$totalPages    = 1;
$totalHours    = 0.0;
$totalMiles    = 0.0;
$totalEstPay   = 0.0;
$carerName     = '';

$baseUrl = 'timesheet.php?user_special_Id=' . urlencode($userId)
         . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');

if ($userId !== '' && $companyId !== '') {
    include_once 'dbconnect.php';

    if (!$conn->connect_error) {

        $nameStmt = $conn->prepare("
            SELECT user_fullname FROM tbl_team_account WHERE user_special_Id = ? LIMIT 1
        ");
        $nameStmt->bind_param('s', $userId);
        $nameStmt->execute();
        $nameStmt->bind_result($carerName);
        $nameStmt->fetch();
        $nameStmt->close();

        $whereParts = ['col_company_Id = ?', 'col_carer_Id = ?'];
        $bindTypes  = 'ss';
        $bindValues = [$companyId, $userId];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $whereParts[] = '(client_name LIKE ? OR col_care_call LIKE ? OR client_group LIKE ?)';
            $bindTypes   .= 'sss';
            array_push($bindValues, $like, $like, $like);
        }
        if ($statusFilter !== '') {
            $whereParts[] = 'col_call_status = ?';
            $bindTypes   .= 's';
            $bindValues[] = $statusFilter;
        }
        if ($dateFrom !== '') {
            $whereParts[] = 'shift_date >= ?';
            $bindTypes   .= 's';
            $bindValues[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $whereParts[] = 'shift_date <= ?';
            $bindTypes   .= 's';
            $bindValues[] = $dateTo;
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $whereParts);

        $cntStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_daily_shift_records $whereSQL");
        $cntStmt->bind_param($bindTypes, ...$bindValues);
        $cntStmt->execute();
        $cntStmt->bind_result($totalRecords);
        $cntStmt->fetch();
        $cntStmt->close();

        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $dataTypes  = $bindTypes . 'ii';
        $dataValues = array_merge($bindValues, [$perPage, $offset]);

        $datStmt = $conn->prepare("
            SELECT id, shift_date, planned_timeIn, planned_timeOut,
                   shift_start_time, client_name, col_care_call,
                   client_group, carer_Name, col_call_status,
                   col_miles, col_mileage, col_postcode, col_visit_status,
                   col_visit_confirmation, col_care_call_Id, dateTime
            FROM   tbl_daily_shift_records
            $whereSQL
            ORDER  BY shift_date DESC, planned_timeIn ASC
            LIMIT  ? OFFSET ?
        ");
        $datStmt->bind_param($dataTypes, ...$dataValues);
        $datStmt->execute();
        $res = $datStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }
        $datStmt->close();

        $sumStmt = $conn->prepare("
            SELECT
                SUM(
                    CASE WHEN planned_timeIn != '' AND planned_timeOut != ''
                         THEN TIME_TO_SEC(TIMEDIFF(
                                  CASE WHEN planned_timeOut < planned_timeIn
                                       THEN ADDTIME(planned_timeOut,'24:00:00')
                                       ELSE planned_timeOut END,
                                  planned_timeIn
                              )) / 3600
                         ELSE 0 END
                ) AS total_hours,
                SUM(CAST(REPLACE(IFNULL(col_miles,'0'),   ',','') AS DECIMAL(10,2))) AS total_miles,
                SUM(
                    CASE WHEN planned_timeIn != '' AND planned_timeOut != ''
                              AND col_mileage != '' AND col_mileage IS NOT NULL
                         THEN (TIME_TO_SEC(TIMEDIFF(
                                  CASE WHEN planned_timeOut < planned_timeIn
                                       THEN ADDTIME(planned_timeOut,'24:00:00')
                                       ELSE planned_timeOut END,
                                  planned_timeIn
                              )) / 3600)
                              * CAST(REPLACE(IFNULL(col_mileage,'0'), '£','') AS DECIMAL(10,2))
                         ELSE 0 END
                ) AS total_est_pay
            FROM tbl_daily_shift_records
            $whereSQL
        ");
        $sumStmt->bind_param($bindTypes, ...$bindValues);
        $sumStmt->execute();
        $sumRes = $sumStmt->get_result();
        if ($sumRow = $sumRes->fetch_assoc()) {
            $totalHours  = (float)($sumRow['total_hours']   ?? 0);
            $totalMiles  = (float)($sumRow['total_miles']   ?? 0);
            $totalEstPay = (float)($sumRow['total_est_pay'] ?? 0);
        }
        $sumStmt->close();
        $conn->close();
    }
}

function shiftHours(?string $in, ?string $out): float {
    if (!$in || !$out) return 0.0;
    $tIn  = DateTime::createFromFormat('H:i', substr($in,  0, 5));
    $tOut = DateTime::createFromFormat('H:i', substr($out, 0, 5));
    if (!$tIn || !$tOut) return 0.0;
    if ($tOut < $tIn) $tOut->modify('+1 day');
    return round($tOut->diff($tIn)->h + ($tOut->diff($tIn)->i / 60), 2);
}

function fmtHours(float $h): string {
    return floor($h) . 'h ' . str_pad((int)round(fmod($h, 1) * 60), 2, '0', STR_PAD_LEFT) . 'm';
}

function fmtDate(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('D, j M Y') : htmlspecialchars($raw);
}

function fmtDateShort(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('j M') : htmlspecialchars($raw);
}

function fmtTime(?string $t): string {
    if (!$t || trim($t) === '') return '—';
    return htmlspecialchars(substr(trim($t), 0, 5));
}

function statusBg(string $s): string {
    return match (strtolower(trim($s))) {
        'completed'     => '#dcfce7',
        'not completed' => '#fee2e2',
        default         => '#dbeafe',
    };
}
function statusFg(string $s): string {
    return match (strtolower(trim($s))) {
        'completed'     => '#15803d',
        'not completed' => '#dc2626',
        default         => '#1d4ed8',
    };
}

function careCallIcon(string $call): string {
    $map = [
        'morning'       => 'bi-sunrise-fill',
        'lunch'         => 'bi-cup-hot-fill',
        'tea'           => 'bi-cup-fill',
        'bed'           => 'bi-moon-stars-fill',
        'extra morning' => 'bi-sunrise',
        'extra lunch'   => 'bi-cup-hot',
        'extra tea'     => 'bi-cup',
        'extra bed'     => 'bi-moon-stars',
    ];
    return $map[strtolower(trim($call))] ?? 'bi-heart-pulse-fill';
}

function weekLabel(string $mon, string $sun): string {
    $m = DateTime::createFromFormat('Y-m-d', $mon);
    $s = DateTime::createFromFormat('Y-m-d', $sun);
    if (!$m || !$s) return '';
    if ($m->format('M Y') === $s->format('M Y')) {
        return $m->format('j') . ' – ' . $s->format('j M Y');
    }
    return $m->format('j M') . ' – ' . $s->format('j M Y');
}

function pgUrl(int $p, string $base, string $search, string $status,
               string $df, string $dt): string {
    $u = $base . '&page=' . $p;
    if ($search) $u .= '&search='    . urlencode($search);
    if ($status) $u .= '&status='    . urlencode($status);
    if ($df)     $u .= '&date_from=' . urlencode($df);
    if ($dt)     $u .= '&date_to='   . urlencode($dt);
    return $u;
}

$totalHoursDisplay = fmtHours($totalHours);
$totalMilesDisplay = number_format($totalMiles, 1);
$totalPayDisplay   = '£' . number_format($totalEstPay, 2);
$activeFilters     = $search || $statusFilter || $dateFrom || $dateTo;

$weekGroups = [];
foreach ($records as $idx => $rec) {
    $d = $rec['shift_date'] ?? '';
    if (!$d) { $weekGroups['unknown'][] = ['idx' => $idx, 'rec' => $rec]; continue; }
    $dt  = DateTime::createFromFormat('Y-m-d', $d);
    if (!$dt) { $weekGroups['unknown'][] = ['idx' => $idx, 'rec' => $rec]; continue; }
    $dow = (int)$dt->format('N');
    $mon = clone $dt; $mon->modify('-' . ($dow - 1) . ' days');
    $sun = clone $mon; $sun->modify('+6 days');
    $key = $mon->format('Y-m-d');
    $weekGroups[$key][] = ['idx' => $idx, 'rec' => $rec, 'mon' => $mon->format('Y-m-d'), 'sun' => $sun->format('Y-m-d')];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Timesheet – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,700;9..144,800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap"
        rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
    body {
        font-family: 'DM Sans', sans-serif;
    }

    .page-container {
        max-width: 980px;
        margin: 0 auto;
        padding: 1.25rem 1rem 5rem;
    }

    .summary-hero {
        background: linear-gradient(135deg, var(--accent, #c94a57) 0%, var(--accent2, #e05c6e) 100%);
        border-radius: var(--radius, 18px);
        color: #fff;
        padding: 1.75rem 1.5rem;
        margin-bottom: 1.25rem;
        position: relative;
        overflow: hidden;
    }

    .summary-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='40' cy='40' r='36' fill='none' stroke='%23fff' stroke-opacity='.04' stroke-width='2'/%3E%3C/svg%3E") repeat;
        pointer-events: none;
    }

    .hero-label {
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        opacity: .7;
        margin-bottom: .25rem;
    }

    .hero-name {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: .5rem;
    }

    .hero-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: .75rem;
        margin-top: 1.1rem;
    }

    .hero-stat {
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 12px;
        padding: .65rem .85rem;
    }

    .hero-stat-val {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1;
    }

    .hero-stat-lbl {
        font-size: .68rem;
        opacity: .75;
        margin-top: .15rem;
    }

    .stats-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: .85rem;
        margin-bottom: 1.25rem;
    }

    .strip-card {
        background: var(--card-bg, #fff);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .65rem;
    }

    .strip-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }

    .ic-red {
        background: rgba(201, 74, 87, .12);
        color: var(--accent, #c94a57);
    }

    .ic-green {
        background: rgba(25, 135, 84, .12);
        color: #198754;
    }

    .ic-blue {
        background: rgba(13, 110, 253, .12);
        color: #0d6efd;
    }

    .ic-orange {
        background: rgba(253, 126, 20, .12);
        color: #fd7e14;
    }

    .ic-purple {
        background: rgba(111, 66, 193, .12);
        color: #6f42c1;
    }

    .strip-val {
        font-weight: 800;
        font-size: 1.05rem;
        line-height: 1;
    }

    .strip-label {
        font-size: .7rem;
        color: var(--text-muted, #888);
        margin-top: .1rem;
    }

    .filter-card {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }

    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        flex: 1 1 180px;
    }

    .filter-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-muted, #888);
    }

    .filter-input,
    .filter-select {
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 10px;
        padding: .5rem .85rem;
        font-size: .87rem;
        background: var(--input-bg, #fafafa);
        color: var(--text-main, #1a1a1a);
        outline: none;
        transition: border-color .18s;
        width: 100%;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: var(--accent, #c94a57);
    }

    .filter-search-wrap {
        position: relative;
        flex: 1 1 220px;
    }

    .filter-search-wrap i {
        position: absolute;
        left: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #bbb);
        pointer-events: none;
        font-size: .88rem;
    }

    .filter-search-wrap .filter-input {
        padding-left: 2.35rem;
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: .55rem 1.25rem;
        font-size: .87rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .18s;
        white-space: nowrap;
        align-self: flex-end;
    }

    .btn-filter:hover {
        opacity: .88;
    }

    .btn-clear {
        background: transparent;
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 10px;
        padding: .55rem 1rem;
        font-size: .87rem;
        color: var(--text-muted, #777);
        text-decoration: none;
        white-space: nowrap;
        align-self: flex-end;
        transition: border-color .18s, color .18s;
    }

    .btn-clear:hover {
        border-color: var(--accent, #c94a57);
        color: var(--accent, #c94a57);
    }

    .results-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1rem;
    }

    .results-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-main, #1a1a1a);
    }

    .results-meta {
        font-size: .75rem;
        color: var(--text-muted, #aaa);
    }

    .week-group {
        margin-bottom: 1.5rem;
    }

    .week-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
        background: var(--card-bg, #fff);
        border-radius: 14px 14px 0 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        padding: .85rem 1.15rem;
        cursor: pointer;
        border-bottom: 1.5px solid var(--border-color, #f0f0f0);
        transition: background .15s;
    }

    .week-header:hover {
        background: var(--hover-bg, #fafafa);
    }

    .week-header.collapsed {
        border-radius: 14px;
        border-bottom: none;
    }

    .week-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: .95rem;
        font-weight: 700;
        color: var(--text-main, #1a1a1a);
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .week-title i {
        color: var(--accent, #c94a57);
        font-size: .88rem;
    }

    .week-pills {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        align-items: center;
    }

    .week-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        background: var(--pill-bg, #f5f5f5);
        border-radius: 999px;
        padding: .22rem .65rem;
        font-size: .73rem;
        font-weight: 600;
        color: var(--text-muted, #666);
    }

    .week-pill i {
        font-size: .68rem;
        color: var(--accent, #c94a57);
    }

    .week-pill.green {
        background: #dcfce7;
        color: #15803d;
    }

    .week-pill.green i {
        color: #15803d;
    }

    .week-pill.blue {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .week-pill.blue i {
        color: #1d4ed8;
    }

    .week-chevron {
        color: var(--text-muted, #aaa);
        transition: transform .25s;
        font-size: .85rem;
    }

    .week-chevron.up {
        transform: rotate(180deg);
    }

    .week-body {
        background: var(--card-bg, #fff);
        border-radius: 0 0 14px 14px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        overflow: hidden;
    }

    .week-body.collapsed {
        display: none;
    }

    .ts-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .83rem;
    }

    .ts-table th {
        padding: .55rem .9rem;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--text-muted, #aaa);
        background: var(--pill-bg, #f8f8f8);
        border-bottom: 1.5px solid var(--border-color, #f0f0f0);
        text-align: left;
        white-space: nowrap;
    }

    .ts-table td {
        padding: .65rem .9rem;
        border-bottom: 1px solid var(--border-color, #f5f5f5);
        color: var(--text-main, #1a1a1a);
        vertical-align: middle;
    }

    .ts-table tr:last-child td {
        border-bottom: none;
    }

    .ts-table tr {
        cursor: pointer;
        transition: background .13s;
    }

    .ts-table tr:hover td {
        background: var(--hover-bg, #fafafa);
    }

    .ts-client {
        font-weight: 700;
    }

    .ts-sub {
        font-size: .73rem;
        color: var(--text-muted, #aaa);
        margin-top: .08rem;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    .ts-status-pill {
        display: inline-block;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .ts-time {
        font-weight: 600;
        white-space: nowrap;
    }

    .ts-dur {
        color: #0d6efd;
        font-weight: 700;
        white-space: nowrap;
    }

    .ts-pay {
        color: #198754;
        font-weight: 700;
        white-space: nowrap;
    }

    .ts-miles {
        color: var(--accent, #c94a57);
        font-weight: 600;
        white-space: nowrap;
    }

    .week-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1.25rem;
        flex-wrap: wrap;
        padding: .65rem 1rem;
        background: var(--pill-bg, #f8f8f8);
        border-top: 1.5px solid var(--border-color, #f0f0f0);
        font-size: .78rem;
    }

    .wf-item {
        display: flex;
        align-items: center;
        gap: .3rem;
        color: var(--text-muted, #666);
    }

    .wf-item strong {
        color: var(--text-main, #1a1a1a);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: var(--pill-bg, #f5f5f5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--text-muted, #ccc);
        margin: 0 auto 1.1rem;
    }

    .pagination-wrap {
        display: flex;
        justify-content: center;
        gap: .4rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
    }

    .pg-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        background: var(--card-bg, #fff);
        color: var(--text-main, #444);
        font-size: .83rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background .18s, border-color .18s, color .18s;
    }

    .pg-btn:hover,
    .pg-btn.active {
        background: var(--accent, #c94a57);
        border-color: var(--accent, #c94a57);
        color: #fff;
    }

    .pg-btn.disabled {
        opacity: .35;
        pointer-events: none;
    }

    .drawer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        z-index: 1040;
        opacity: 0;
        pointer-events: none;
        transition: opacity .28s;
    }

    .drawer-overlay.open {
        opacity: 1;
        pointer-events: all;
    }

    .drawer {
        position: fixed;
        right: 0;
        top: 0;
        bottom: 0;
        width: min(440px, 100vw);
        background: var(--card-bg, #fff);
        z-index: 1050;
        transform: translateX(100%);
        transition: transform .3s cubic-bezier(.4, 0, .2, 1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: -8px 0 40px rgba(0, 0, 0, .15);
    }

    .drawer.open {
        transform: translateX(0);
    }

    .drawer-header {
        padding: 1.25rem 1.25rem 1rem;
        border-bottom: 1px solid var(--border-color, #f0f0f0);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        flex-shrink: 0;
    }

    .drawer-title {
        font-weight: 800;
        font-size: 1rem;
        color: var(--text-main, #1a1a1a);
    }

    .drawer-subtitle {
        font-size: .78rem;
        color: var(--text-muted, #aaa);
        margin-top: .15rem;
    }

    .drawer-close {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        background: transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted, #888);
        transition: background .15s, color .15s;
        flex-shrink: 0;
    }

    .drawer-close:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecdd3;
    }

    .drawer-body {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem 1.25rem 2rem;
    }

    .detail-section {
        margin-bottom: 1.25rem;
    }

    .detail-section-title {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--text-muted, #aaa);
        margin-bottom: .65rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }

    .detail-section-title i {
        color: var(--accent, #c94a57);
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .5rem;
        padding: .55rem 0;
        border-bottom: 1px solid var(--border-color, #f5f5f5);
        font-size: .87rem;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-key {
        color: var(--text-muted, #888);
        font-weight: 500;
        flex-shrink: 0;
    }

    .detail-val {
        font-weight: 600;
        text-align: right;
        color: var(--text-main, #1a1a1a);
    }

    .detail-badge {
        display: inline-block;
        padding: .2rem .65rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
    }

    @media(max-width:640px) {

        .ts-table th:nth-child(n+5),
        .ts-table td:nth-child(n+5) {
            display: none;
        }

        .hero-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <header class="topbar">
        <div class="container-fluid px-3">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="menu-btn" id="menuBtn" type="button" aria-label="Open menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="app-mark"><i class="bi bi-heart-pulse-fill"></i></div>
                    <h5 class="mb-0 fw-bold">StaffLinks</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="chip border-0 position-relative" type="button">
                        <i class="bi bi-bell"></i><span class="alert-dot">4</span>
                    </button>
                    <button class="chip border-0" id="darkModeBtn" type="button">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <img class="profile-avatar" id="topbarAvatar"
                        src="https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg" alt="Profile">
                </div>
            </div>
            <div class="mt-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="dashboard.php?carer_id=<?= urlencode($userId) ?><?= $companyId ? '&col_company_Id='.urlencode($companyId) : '' ?>"
                            class="chip border-0 text-white text-decoration-none d-flex align-items-center gap-1"
                            style="font-size:.85rem">
                            <i class="bi bi-arrow-left"></i> Dashboard
                        </a>
                        <div>
                            <h2 class="mb-0 fw-bold">Timesheet</h2>
                            <div class="text-white-50" style="font-size:.85rem">Your logged hours and earnings</div>
                        </div>
                    </div>
                    <span class="chip">
                        <i class="bi bi-file-earmark-text me-1"></i>
                        <?= $totalRecords ?> record<?= $totalRecords !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="page-container">

        <div class="summary-hero fade-in-up">
            <div class="hero-label">Timesheet Summary</div>
            <div class="hero-name" id="heroName">
                <?= $carerName ? htmlspecialchars($carerName) : '—' ?>
            </div>
            <div style="opacity:.75;font-size:.82rem">ID: <?= htmlspecialchars($userId) ?></div>
            <div class="hero-grid">
                <div class="hero-stat">
                    <div class="hero-stat-val"><?= $totalHoursDisplay ?></div>
                    <div class="hero-stat-lbl">Total hours</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-val"><?= $totalPayDisplay ?></div>
                    <div class="hero-stat-lbl">Est. earnings</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-val"><?= $totalMilesDisplay ?></div>
                    <div class="hero-stat-lbl">Miles logged</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-val"><?= $totalRecords ?></div>
                    <div class="hero-stat-lbl">Total visits</div>
                </div>
            </div>
        </div>

        <div class="stats-strip fade-in-up">
            <div class="strip-card">
                <div class="strip-icon ic-red"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalHoursDisplay ?></div>
                    <div class="strip-label">Hours logged</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-green"><i class="bi bi-currency-pound"></i></div>
                <div>
                    <div class="strip-val"><?= $totalPayDisplay ?></div>
                    <div class="strip-label">Est. pay</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-blue"><i class="bi bi-car-front-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalMilesDisplay ?></div>
                    <div class="strip-label">Miles</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-orange"><i class="bi bi-calendar-check-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalRecords ?></div>
                    <div class="strip-label">Visits</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-purple"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="strip-val">
                        <?= count(array_filter($records, fn($r) => strtolower($r['col_call_status'] ?? '') === 'completed')) ?>
                    </div>
                    <div class="strip-label">Completed</div>
                </div>
            </div>
        </div>

        <div class="filter-card fade-in-up">
            <form method="GET" action="timesheet.php">
                <input type="hidden" name="user_special_Id" value="<?= htmlspecialchars($userId) ?>">
                <?php if ($companyId): ?>
                <input type="hidden" name="col_company_Id" value="<?= htmlspecialchars($companyId) ?>">
                <?php endif; ?>
                <div class="filter-row">
                    <div class="filter-group filter-search-wrap">
                        <label class="filter-label">Search</label>
                        <i class="bi bi-search"></i>
                        <input type="text" class="filter-input" name="search" placeholder="Client, care call, area…"
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="filter-group" style="flex:0 1 160px">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" name="status">
                            <option value="">All statuses</option>
                            <option value="Completed" <?= $statusFilter==='Completed'?'selected':'' ?>>Completed
                            </option>
                            <option value="Not completed" <?= $statusFilter==='Not completed'?'selected':'' ?>>Not
                                Completed</option>
                            <option value="Scheduled" <?= $statusFilter==='Scheduled'?'selected':'' ?>>Scheduled
                            </option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex:0 1 150px">
                        <label class="filter-label">From</label>
                        <input type="date" class="filter-input" name="date_from"
                            value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="filter-group" style="flex:0 1 150px">
                        <label class="filter-label">To</label>
                        <input type="date" class="filter-input" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <button type="submit" class="btn-filter">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <?php if ($activeFilters): ?>
                    <a href="<?= $baseUrl ?>" class="btn-clear">
                        <i class="bi bi-x me-1"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="results-bar fade-in-up">
            <div class="results-title">
                <?= $activeFilters ? 'Filtered Records' : 'All Timesheet Entries' ?>
            </div>
            <?php if ($totalRecords > 0): ?>
            <div class="results-meta">
                Showing <?= count($records) ?> of <?= $totalRecords ?>
                &nbsp;·&nbsp; Page <?= $page ?> of <?= $totalPages ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($records)): ?>
        <div class="empty-state fade-in-up">
            <div class="empty-icon"><i class="bi bi-file-earmark-text"></i></div>
            <h5 class="fw-bold mb-2">
                <?= $activeFilters ? 'No records match your filters' : 'No timesheet records found' ?>
            </h5>
            <p style="font-size:.88rem;color:var(--text-muted,#888)" class="mb-3">
                <?= $activeFilters
                        ? 'Try adjusting your search or date range.'
                        : 'Your logged shifts will appear here once you have completed visits.' ?>
            </p>
            <?php if ($activeFilters): ?>
            <a href="<?= $baseUrl ?>" class="btn btn-sm text-white"
                style="background:var(--accent);border-radius:999px;padding:.5rem 1.25rem">
                Clear filters
            </a>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <?php foreach ($weekGroups as $weekKey => $weekItems):
                $weekHours  = 0.0;
                $weekPay    = 0.0;
                $weekMiles  = 0.0;
                $weekDone   = 0;
                $monLabel   = $weekItems[0]['mon'] ?? $weekKey;
                $sunLabel   = $weekItems[0]['sun'] ?? $weekKey;

                foreach ($weekItems as $wi) {
                    $r = $wi['rec'];
                    $h = shiftHours($r['planned_timeIn'], $r['planned_timeOut']);
                    $weekHours += $h;
                    $rate = (float)preg_replace('/[^0-9.]/', '', $r['col_mileage'] ?? '');
                    if ($h > 0 && $rate > 0) $weekPay += $h * $rate;
                    $weekMiles += (float)preg_replace('/[^0-9.]/', '', $r['col_miles'] ?? '');
                    if (strtolower($r['col_call_status'] ?? '') === 'completed') $weekDone++;
                }
                $groupId = 'wg-' . preg_replace('/[^a-z0-9]/i', '-', $weekKey);
            ?>
        <div class="week-group fade-in-up">
            <div class="week-header" onclick="toggleWeek('<?= $groupId ?>', this)">
                <div class="week-title">
                    <i class="bi bi-calendar3-week"></i>
                    <?= $weekKey === 'unknown'
                                ? 'Undated Records'
                                : htmlspecialchars(weekLabel($monLabel, $sunLabel)) ?>
                </div>
                <div class="week-pills">
                    <span class="week-pill">
                        <i class="bi bi-list-check"></i>
                        <?= count($weekItems) ?> visit<?= count($weekItems) !== 1 ? 's' : '' ?>
                    </span>
                    <span class="week-pill blue">
                        <i class="bi bi-clock"></i>
                        <?= fmtHours($weekHours) ?>
                    </span>
                    <?php if ($weekPay > 0): ?>
                    <span class="week-pill green">
                        <i class="bi bi-currency-pound"></i>
                        £<?= number_format($weekPay, 2) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($weekMiles > 0): ?>
                    <span class="week-pill">
                        <i class="bi bi-car-front"></i>
                        <?= number_format($weekMiles, 1) ?> mi
                    </span>
                    <?php endif; ?>
                    <i class="bi bi-chevron-down week-chevron up" id="chev-<?= $groupId ?>"></i>
                </div>
            </div>

            <div class="week-body" id="<?= $groupId ?>">
                <div style="overflow-x:auto">
                    <table class="ts-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Time</th>
                                <th>Duration</th>
                                <th>Est. Pay</th>
                                <th>Miles</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekItems as $wi):
                                        $r    = $wi['rec'];
                                        $idx  = $wi['idx'];
                                        $h    = shiftHours($r['planned_timeIn'], $r['planned_timeOut']);
                                        $rate = (float)preg_replace('/[^0-9.]/', '', $r['col_mileage'] ?? '');
                                        $pay  = ($h > 0 && $rate > 0) ? '£' . number_format($h * $rate, 2) : '—';
                                        $dur  = $h > 0 ? fmtHours($h) : '—';
                                        $mi   = !empty($r['col_miles']) ? htmlspecialchars($r['col_miles']) . ' mi' : '—';
                                        $sBg  = statusBg($r['col_call_status'] ?? '');
                                        $sFg  = statusFg($r['col_call_status'] ?? '');
                                        $ico  = careCallIcon($r['col_care_call'] ?? '');
                                    ?>
                            <tr onclick="openDrawer(<?= $idx ?>)">
                                <td>
                                    <div style="font-weight:600;font-size:.82rem">
                                        <?= fmtDateShort($r['shift_date'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="ts-client">
                                        <?= htmlspecialchars($r['client_name'] ?? '—') ?>
                                    </div>
                                    <div class="ts-sub">
                                        <i class="bi <?= $ico ?>" style="color:var(--accent)"></i>
                                        <?= htmlspecialchars(ucfirst($r['col_care_call'] ?? '')) ?>
                                    </div>
                                </td>
                                <td class="ts-time">
                                    <?= fmtTime($r['planned_timeIn']) ?> – <?= fmtTime($r['planned_timeOut']) ?>
                                </td>
                                <td class="ts-dur"><?= $dur ?></td>
                                <td class="ts-pay"><?= $pay ?></td>
                                <td class="ts-miles"><?= $mi ?></td>
                                <td>
                                    <span class="ts-status-pill" style="background:<?= $sBg ?>;color:<?= $sFg ?>">
                                        <?= htmlspecialchars($r['col_call_status'] ?? 'Scheduled') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="week-footer">
                    <div class="wf-item">
                        <i class="bi bi-clock" style="color:var(--accent)"></i>
                        <strong><?= fmtHours($weekHours) ?></strong> this week
                    </div>
                    <?php if ($weekPay > 0): ?>
                    <div class="wf-item">
                        <i class="bi bi-currency-pound" style="color:#198754"></i>
                        <strong>£<?= number_format($weekPay, 2) ?></strong> est. pay
                    </div>
                    <?php endif; ?>
                    <?php if ($weekMiles > 0): ?>
                    <div class="wf-item">
                        <i class="bi bi-car-front" style="color:#0d6efd"></i>
                        <strong><?= number_format($weekMiles, 1) ?> mi</strong> driven
                    </div>
                    <?php endif; ?>
                    <div class="wf-item">
                        <i class="bi bi-check-circle" style="color:#198754"></i>
                        <strong><?= $weekDone ?></strong> / <?= count($weekItems) ?> completed
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1):
                $start = max(1, min($page - 2, $totalPages - 4));
                $end   = min($totalPages, $start + 4);
            ?>
        <nav class="pagination-wrap fade-in-up">
            <a href="<?= $page>1 ? pgUrl($page-1,$baseUrl,$search,$statusFilter,$dateFrom,$dateTo) : '#' ?>"
                class="pg-btn <?= $page<=1?'disabled':'' ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
            <?php for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?= pgUrl($p,$baseUrl,$search,$statusFilter,$dateFrom,$dateTo) ?>"
                class="pg-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="<?= $page<$totalPages ? pgUrl($page+1,$baseUrl,$search,$statusFilter,$dateFrom,$dateTo) : '#' ?>"
                class="pg-btn <?= $page>=$totalPages?'disabled':'' ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </nav>
        <?php endif; ?>

        <?php endif; ?>

    </main>

    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="drawer" role="dialog" aria-label="Shift details">
        <div class="drawer-header">
            <div>
                <div class="drawer-title" id="drawerTitle">Shift Details</div>
                <div class="drawer-subtitle" id="drawerSubtitle"></div>
            </div>
            <button class="drawer-close" onclick="closeDrawer()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="drawer-body" id="drawerBody"></div>
    </div>

    <?php include 'new-footer.php'; ?>

    <script>
    const RECORDS = <?= json_encode(array_values($records), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    function toggleWeek(id, header) {
        const body = document.getElementById(id);
        const chev = document.getElementById('chev-' + id);
        const isOpen = !body.classList.contains('collapsed');
        if (isOpen) {
            body.classList.add('collapsed');
            header.classList.add('collapsed');
            chev.classList.remove('up');
        } else {
            body.classList.remove('collapsed');
            header.classList.remove('collapsed');
            chev.classList.add('up');
        }
    }

    function fmtDateJS(d) {
        if (!d) return '—';
        const dt = new Date(d + 'T00:00:00');
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const mons = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${days[dt.getDay()]}, ${dt.getDate()} ${mons[dt.getMonth()]} ${dt.getFullYear()}`;
    }

    function statusStyle(s) {
        const l = (s || '').toLowerCase().trim();
        if (l === 'completed') return {
            bg: '#dcfce7',
            fg: '#15803d'
        };
        if (l === 'not completed') return {
            bg: '#fee2e2',
            fg: '#dc2626'
        };
        return {
            bg: '#dbeafe',
            fg: '#1d4ed8'
        };
    }

    function shiftHoursJS(inT, outT) {
        if (!inT || !outT) return 0;
        const [ih, im] = inT.slice(0, 5).split(':').map(Number);
        const [oh, om] = outT.slice(0, 5).split(':').map(Number);
        let mins = (oh * 60 + om) - (ih * 60 + im);
        if (mins <= 0) mins += 24 * 60;
        return mins / 60;
    }

    function fmtHoursJS(h) {
        const hh = Math.floor(h);
        const mm = Math.round((h - hh) * 60);
        return `${hh}h ${String(mm).padStart(2,'0')}m`;
    }

    function dRow(k, v) {
        if (!v || v === '—') return '';
        return `<div class="detail-row">
            <span class="detail-key">${k}</span>
            <span class="detail-val">${v}</span>
        </div>`;
    }

    function dBadge(k, v) {
        if (!v) return '';
        const st = statusStyle(v);
        return `<div class="detail-row">
            <span class="detail-key">${k}</span>
            <span class="detail-badge" style="background:${st.bg};color:${st.fg}">${v}</span>
        </div>`;
    }

    function dSection(title, icon, rows) {
        const inner = rows.filter(Boolean).join('');
        if (!inner) return '';
        return `<div class="detail-section">
            <div class="detail-section-title"><i class="bi ${icon}"></i> ${title}</div>
            ${inner}
        </div>`;
    }

    function openDrawer(idx) {
        const r = RECORDS[idx];
        if (!r) return;

        document.getElementById('drawerTitle').textContent = r.client_name || 'Unknown Client';
        document.getElementById('drawerSubtitle').textContent = fmtDateJS(r.shift_date);

        const h = shiftHoursJS(r.planned_timeIn, r.planned_timeOut);
        const rate = parseFloat((r.col_mileage || '').replace(/[^0-9.]/g, '')) || 0;
        const pay = (h > 0 && rate > 0) ? '£' + (h * rate).toFixed(2) : '';

        const s1 = dSection('Shift Info', 'bi-calendar3', [
            dBadge('Status', r.col_call_status),
            dRow('Date', fmtDateJS(r.shift_date)),
            dRow('Planned In', r.planned_timeIn ? r.planned_timeIn.slice(0, 5) : ''),
            dRow('Planned Out', r.planned_timeOut ? r.planned_timeOut.slice(0, 5) : ''),
            dRow('Actual Check-in', r.shift_start_time ? r.shift_start_time.slice(0, 5) : ''),
            dRow('Duration', h > 0 ? fmtHoursJS(h) : ''),
            dRow('Care Call', r.col_care_call ? r.col_care_call.charAt(0).toUpperCase() + r.col_care_call.slice(
                1) : ''),
        ]);
        const s2 = dSection('Client', 'bi-person-fill', [
            dRow('Client Name', r.client_name),
            dRow('Area / Group', r.client_group),
            dRow('Postcode', r.col_postcode),
        ]);
        const s3 = dSection('Pay & Travel', 'bi-currency-pound', [
            dRow('Est. Pay', pay),
            dRow('Mileage Rate', r.col_mileage ? '£' + r.col_mileage.replace(/[^0-9.]/g, '') + '/hr' : ''),
            dRow('Miles Driven', r.col_miles ? r.col_miles + ' miles' : ''),
        ]);
        const s4 = dSection('Admin', 'bi-clipboard-check-fill', [
            dRow('Visit Status', r.col_visit_status),
            dRow('Confirmation', r.col_visit_confirmation),
            dRow('Reference', r.col_care_call_Id),
            dRow('Record Updated', r.dateTime),
        ]);

        document.getElementById('drawerBody').innerHTML = s1 + s2 + s3 + s4;
        document.getElementById('drawer').classList.add('open');
        document.getElementById('drawerOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        document.getElementById('drawer').classList.remove('open');
        document.getElementById('drawerOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeDrawer();
    });

    (function() {
        const DEFAULT_AVATAR = 'https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg';
        const UPLOAD_BASE = 'https://admin.stafflinks.co.uk/uploads/team_dp/';

        const raw = sessionStorage.getItem('loggedInUser');
        if (!raw) {
            window.location.href = './';
            return;
        }
        let user;
        try {
            user = JSON.parse(raw);
        } catch (e) {
            sessionStorage.clear();
            window.location.href = './';
            return;
        }

        (function ensureParams() {
            const url = new URL(window.location.href);
            let changed = false;
            const sessId = String(user.user_special_Id || '');
            const sessC = String(user.col_company_Id || '');
            if (sessId && url.searchParams.get('user_special_Id') !== sessId) {
                url.searchParams.set('user_special_Id', sessId);
                changed = true;
            }
            if (sessC && url.searchParams.get('col_company_Id') !== sessC) {
                url.searchParams.set('col_company_Id', sessC);
                changed = true;
            }
            if (changed) window.location.replace(url.toString());
        })();

        function avatarSrc(dp) {
            if (!dp || !dp.trim()) return DEFAULT_AVATAR;
            const v = dp.trim();
            if (v.startsWith('http://') || v.startsWith('https://')) return v;
            if (v.startsWith('uploads/') || v.startsWith('/uploads/'))
                return 'https://admin.stafflinks.co.uk/' + v.replace(/^\//, '');
            return UPLOAD_BASE + v;
        }
        const wFb = el => {
            if (el) el.onerror = function() {
                if (this.src !== DEFAULT_AVATAR) this.src = DEFAULT_AVATAR;
            };
        };
        const setSrc = (id, src, alt) => {
            const el = document.getElementById(id);
            if (el) {
                el.src = src;
                if (alt) el.alt = alt;
            }
        };
        const setText = (id, v) => {
            const el = document.getElementById(id);
            if (el) el.textContent = v || '—';
        };

        const avatar = avatarSrc(user.team_dp);
        const name = user.user_fullname || '';
        setSrc('topbarAvatar', avatar, name);
        wFb(document.getElementById('topbarAvatar'));
        setSrc('navProfilePic', avatar, name);
        wFb(document.getElementById('navProfilePic'));
        setText('navFullName', name);
        setText('navEmail', user.user_email_address || '');
        setText('navPhone', user.user_phone_number || '');

        if (name && document.getElementById('heroName')) {
            document.getElementById('heroName').textContent = name;
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', e => {
            e.preventDefault();
            sessionStorage.removeItem('loggedInUser');
            sessionStorage.removeItem('loggedInUserId');
            window.location.href = './';
        });
    })();
    </script>
</body>

</html>