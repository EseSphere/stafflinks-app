<?php
$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';
$search    = isset($_GET['search'])         ? trim($_GET['search'])         : '';
$statusFilter = isset($_GET['status'])      ? trim($_GET['status'])         : '';
$dateFrom  = isset($_GET['date_from'])      ? trim($_GET['date_from'])      : '';
$dateTo    = isset($_GET['date_to'])        ? trim($_GET['date_to'])        : '';
$page      = max(1, (int)($_GET['page']    ?? 1));
$perPage   = 15;

$shifts      = [];
$totalShifts = 0;
$totalPages  = 1;
$totalHours  = 0.0;
$totalMiles  = 0.0;

$baseUrl = 'past-shifts.php?carer_id=' . urlencode($carerId)
         . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');

if ($companyId !== '' && $carerId !== '') {
    include_once 'dbconnect.php';

    if (!$conn->connect_error) {

        $whereParts   = ['col_company_Id = ?', 'col_carer_Id = ?'];
        $bindTypes    = 'ss';
        $bindValues   = [$companyId, $carerId];

        if ($search !== '') {
            $whereParts[] = '(client_name LIKE ? OR col_care_call LIKE ? OR client_group LIKE ?)';
            $bindTypes   .= 'sss';
            $like         = '%' . $search . '%';
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
        $cntStmt->bind_result($totalShifts);
        $cntStmt->fetch();
        $cntStmt->close();

        $totalPages = max(1, (int)ceil($totalShifts / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $dataTypes  = $bindTypes . 'ii';
        $dataValues = array_merge($bindValues, [$perPage, $offset]);

        $datStmt = $conn->prepare("
            SELECT id, shift_date, planned_timeIn, planned_timeOut,
                   shift_start_time, client_name, col_care_call,
                   client_group, carer_Name, col_call_status,
                   col_miles, col_mileage, col_postcode, col_visit_status,
                   col_visit_confirmation, dateTime
            FROM   tbl_daily_shift_records
            $whereSQL
            ORDER  BY shift_date DESC, planned_timeIn DESC
            LIMIT  ? OFFSET ?
        ");
        $datStmt->bind_param($dataTypes, ...$dataValues);
        $datStmt->execute();
        $res = $datStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $shifts[] = $row;
        }
        $datStmt->close();

        $sumStmt = $conn->prepare("
            SELECT SUM(
                       CASE WHEN planned_timeIn != '' AND planned_timeOut != ''
                            THEN TIME_TO_SEC(TIMEDIFF(
                                     CASE WHEN planned_timeOut < planned_timeIn
                                          THEN ADDTIME(planned_timeOut, '24:00:00')
                                          ELSE planned_timeOut END,
                                     planned_timeIn
                                 )) / 3600
                            ELSE 0 END
                   ) AS total_hours,
                   SUM(CAST(REPLACE(col_miles, ',', '') AS DECIMAL(10,2))) AS total_miles
            FROM   tbl_daily_shift_records
            $whereSQL
        ");
        $sumStmt->bind_param($bindTypes, ...$bindValues);
        $sumStmt->execute();
        $sumResult = $sumStmt->get_result();
        if ($sumRow = $sumResult->fetch_assoc()) {
            $totalHours = (float)($sumRow['total_hours'] ?? 0);
            $totalMiles = (float)($sumRow['total_miles'] ?? 0);
        }
        $sumStmt->close();
        $conn->close();
    }
}

function fmtDate(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('D, j M Y') : htmlspecialchars($raw);
}

function fmtTime(?string $t): string {
    if (!$t || trim($t) === '') return '—';
    return htmlspecialchars(substr(trim($t), 0, 5));
}

function shiftDuration(?string $in, ?string $out): string {
    if (!$in || !$out) return '—';
    $tIn  = DateTime::createFromFormat('H:i', substr($in,  0, 5));
    $tOut = DateTime::createFromFormat('H:i', substr($out, 0, 5));
    if (!$tIn || !$tOut) return '—';
    if ($tOut < $tIn) $tOut->modify('+1 day');
    $diff = $tOut->diff($tIn);
    return $diff->h . 'h ' . str_pad($diff->i, 2, '0', STR_PAD_LEFT) . 'm';
}

function statusClass(string $s): string {
    return match (strtolower(trim($s))) {
        'completed'     => 'sc-done',
        'not completed' => 'sc-missed',
        default         => 'sc-sched',
    };
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

function pgUrl(int $p, string $base, string $search, string $status,
               string $dateFrom, string $dateTo): string {
    $u = $base . '&page=' . $p;
    if ($search)   $u .= '&search='    . urlencode($search);
    if ($status)   $u .= '&status='    . urlencode($status);
    if ($dateFrom) $u .= '&date_from=' . urlencode($dateFrom);
    if ($dateTo)   $u .= '&date_to='   . urlencode($dateTo);
    return $u;
}

$totalHoursDisplay = floor($totalHours) . 'h '
    . str_pad((int)round(fmod($totalHours, 1) * 60), 2, '0', STR_PAD_LEFT) . 'm';
$totalMilesDisplay = number_format($totalMiles, 1);

$activeFilters = $search || $statusFilter || $dateFrom || $dateTo;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Past Shifts – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }

        .page-container {
            max-width: 980px;
            margin: 0 auto;
            padding: 1.25rem 1rem 5rem;
        }

        .stats-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: .85rem;
            margin-bottom: 1.5rem;
        }
        .strip-card {
            background: var(--card-bg, #fff);
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            padding: .9rem 1rem;
            display: flex; align-items: center; gap: .65rem;
        }
        .strip-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem; flex-shrink: 0;
        }
        .ic-red    { background: rgba(201,74,87,.12);  color: var(--accent, #c94a57); }
        .ic-green  { background: rgba(25,135,84,.12);  color: #198754; }
        .ic-blue   { background: rgba(13,110,253,.12); color: #0d6efd; }
        .ic-orange { background: rgba(253,126,20,.12); color: #fd7e14; }
        .strip-val   { font-weight: 800; font-size: 1.1rem; line-height: 1; }
        .strip-label { font-size: .7rem; color: var(--text-muted, #888); margin-top: .1rem; }

        .filter-card {
            background: var(--card-bg, #fff);
            border-radius: var(--radius, 18px);
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
        }
        .filter-row {
            display: flex; flex-wrap: wrap; gap: .65rem; align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: .3rem; flex: 1 1 180px; }
        .filter-label {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: var(--text-muted, #888);
        }
        .filter-input, .filter-select {
            border: 1.5px solid var(--border-color, #e8e8e8);
            border-radius: 10px;
            padding: .5rem .85rem;
            font-size: .87rem;
            background: var(--input-bg, #fafafa);
            color: var(--text-main, #1a1a1a);
            outline: none; transition: border-color .18s;
            width: 100%;
        }
        .filter-input:focus, .filter-select:focus { border-color: var(--accent, #c94a57); }
        .filter-search-wrap { position: relative; flex: 1 1 220px; }
        .filter-search-wrap i {
            position: absolute; left: .85rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted, #bbb); pointer-events: none; font-size: .88rem;
        }
        .filter-search-wrap .filter-input { padding-left: 2.35rem; }
        .btn-filter {
            background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
            color: #fff; border: none; border-radius: 10px;
            padding: .55rem 1.25rem; font-size: .87rem; font-weight: 700;
            cursor: pointer; transition: opacity .18s; white-space: nowrap; align-self: flex-end;
        }
        .btn-filter:hover { opacity: .88; }
        .btn-clear {
            background: transparent;
            border: 1.5px solid var(--border-color, #e8e8e8);
            border-radius: 10px; padding: .55rem 1rem;
            font-size: .87rem; color: var(--text-muted, #777);
            text-decoration: none; white-space: nowrap; align-self: flex-end;
            transition: border-color .18s, color .18s;
        }
        .btn-clear:hover { border-color: var(--accent, #c94a57); color: var(--accent, #c94a57); }

        .results-bar {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem;
        }
        .results-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.05rem; font-weight: 700; color: var(--text-main, #1a1a1a);
        }
        .results-meta { font-size: .75rem; color: var(--text-muted, #aaa); }

        .shifts-list { display: flex; flex-direction: column; gap: .85rem; }

        .shift-card {
            background: var(--card-bg, #fff);
            border-radius: var(--radius, 18px);
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            overflow: hidden;
            transition: transform .18s, box-shadow .18s;
            cursor: pointer;
            position: relative;
            animation: cardIn .3s ease both;
        }
        .shift-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.1); }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .shift-card:nth-child(1)  { animation-delay: .04s; }
        .shift-card:nth-child(2)  { animation-delay: .08s; }
        .shift-card:nth-child(3)  { animation-delay: .12s; }
        .shift-card:nth-child(4)  { animation-delay: .16s; }
        .shift-card:nth-child(5)  { animation-delay: .20s; }

        .shift-card-inner {
            display: grid;
            grid-template-columns: 4px 1fr;
        }
        .shift-accent-bar { width: 4px; }
        .shift-content { padding: 1rem 1.15rem; }

        .shift-top {
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: .75rem; margin-bottom: .65rem;
        }
        .shift-client { font-weight: 800; font-size: 1rem; color: var(--text-main, #1a1a1a); }
        .shift-care-call {
            font-size: .78rem; color: var(--text-muted, #888); margin-top: .15rem;
            display: flex; align-items: center; gap: .3rem;
        }
        .shift-status-pill {
            font-size: .7rem; font-weight: 700;
            padding: .25rem .7rem; border-radius: 999px; flex-shrink: 0;
        }

        .shift-meta-row {
            display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .55rem;
        }
        .shift-meta-pill {
            display: inline-flex; align-items: center; gap: .3rem;
            background: var(--pill-bg, #f5f5f5);
            border-radius: 999px; padding: .22rem .65rem;
            font-size: .76rem; color: var(--text-muted, #666); font-weight: 500;
        }
        .shift-meta-pill i { font-size: .72rem; color: var(--accent, #c94a57); }

        .shift-bottom {
            display: flex; align-items: center;
            justify-content: space-between; gap: .5rem;
            padding-top: .6rem;
            border-top: 1px solid var(--border-color, #f0f0f0);
            flex-wrap: wrap;
        }
        .shift-area {
            font-size: .78rem; color: var(--text-muted, #888);
            display: flex; align-items: center; gap: .3rem;
        }
        .shift-area i { font-size: .72rem; color: var(--accent, #c94a57); }
        .shift-mileage {
            font-size: .78rem; font-weight: 700;
            color: var(--accent, #c94a57);
            display: flex; align-items: center; gap: .3rem;
        }
        .shift-duration {
            font-size: .78rem; font-weight: 700;
            color: #0d6efd;
            display: flex; align-items: center; gap: .3rem;
        }

        .empty-state {
            text-align: center; padding: 4rem 2rem;
            background: var(--card-bg, #fff);
            border-radius: var(--radius, 18px);
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }
        .empty-icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: var(--pill-bg, #f5f5f5);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: var(--text-muted, #ccc); margin: 0 auto 1.1rem;
        }

        .pagination-wrap {
            display: flex; justify-content: center;
            gap: .4rem; flex-wrap: wrap; margin-top: 1.5rem;
        }
        .pg-btn {
            width: 36px; height: 36px; border-radius: 50%;
            border: 1.5px solid var(--border-color, #e8e8e8);
            background: var(--card-bg, #fff); color: var(--text-main, #444);
            font-size: .83rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: background .18s, border-color .18s, color .18s;
        }
        .pg-btn:hover, .pg-btn.active {
            background: var(--accent, #c94a57);
            border-color: var(--accent, #c94a57); color: #fff;
        }
        .pg-btn.disabled { opacity: .35; pointer-events: none; }

        .drawer-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.4); z-index: 1040;
            opacity: 0; pointer-events: none; transition: opacity .28s;
        }
        .drawer-overlay.open { opacity: 1; pointer-events: all; }
        .drawer {
            position: fixed; right: 0; top: 0; bottom: 0;
            width: min(440px, 100vw);
            background: var(--card-bg, #fff); z-index: 1050;
            transform: translateX(100%);
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column; overflow: hidden;
            box-shadow: -8px 0 40px rgba(0,0,0,.15);
        }
        .drawer.open { transform: translateX(0); }
        .drawer-header {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--border-color, #f0f0f0);
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: .75rem; flex-shrink: 0;
        }
        .drawer-title { font-weight: 800; font-size: 1rem; color: var(--text-main, #1a1a1a); }
        .drawer-subtitle { font-size: .78rem; color: var(--text-muted, #aaa); margin-top: .15rem; }
        .drawer-close {
            width: 34px; height: 34px; border-radius: 50%;
            border: 1.5px solid var(--border-color, #e8e8e8);
            background: transparent; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted, #888); transition: background .15s, color .15s; flex-shrink: 0;
        }
        .drawer-close:hover { background: #fee2e2; color: #dc2626; border-color: #fecdd3; }
        .drawer-body { flex: 1; overflow-y: auto; padding: 1.25rem 1.25rem 2rem; }

        .detail-section { margin-bottom: 1.25rem; }
        .detail-section-title {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: var(--text-muted, #aaa);
            margin-bottom: .65rem; display: flex; align-items: center; gap: .4rem;
        }
        .detail-section-title i { color: var(--accent, #c94a57); }
        .detail-row {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: .5rem; padding: .55rem 0;
            border-bottom: 1px solid var(--border-color, #f5f5f5);
            font-size: .87rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-key { color: var(--text-muted, #888); font-weight: 500; flex-shrink: 0; }
        .detail-val { font-weight: 600; text-align: right; color: var(--text-main, #1a1a1a); }
        .detail-badge {
            display: inline-block; padding: .2rem .65rem;
            border-radius: 999px; font-size: .72rem; font-weight: 700;
        }

        @media(max-width:560px) {
            .shift-top { flex-direction: column; gap: .4rem; }
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
                        <a href="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId ? '&col_company_Id='.urlencode($companyId) : '' ?>"
                           class="chip border-0 text-white text-decoration-none d-flex align-items-center gap-1"
                           style="font-size:.85rem">
                            <i class="bi bi-arrow-left"></i> Dashboard
                        </a>
                        <div>
                            <h2 class="mb-0 fw-bold">Past Shifts</h2>
                            <div class="text-white-50" style="font-size:.85rem">Your complete shift history</div>
                        </div>
                    </div>
                    <span class="chip">
                        <i class="bi bi-clock-history me-1"></i>
                        <?= $totalShifts ?> record<?= $totalShifts !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="page-container">

        <div class="stats-strip fade-in-up">
            <div class="strip-card">
                <div class="strip-icon ic-red"><i class="bi bi-calendar-check-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalShifts ?></div>
                    <div class="strip-label">Total shifts</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-orange"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalHoursDisplay ?></div>
                    <div class="strip-label">Hours logged</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-green"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="strip-val">
                        <?= count(array_filter($shifts, fn($s) => strtolower($s['col_call_status'] ?? '') === 'completed')) ?>
                    </div>
                    <div class="strip-label">Completed</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-blue"><i class="bi bi-car-front-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalMilesDisplay ?></div>
                    <div class="strip-label">Miles logged</div>
                </div>
            </div>
        </div>

        <div class="filter-card fade-in-up">
            <form method="GET" action="past-shifts.php">
                <input type="hidden" name="carer_id"       value="<?= htmlspecialchars($carerId) ?>">
                <?php if ($companyId): ?>
                    <input type="hidden" name="col_company_Id" value="<?= htmlspecialchars($companyId) ?>">
                <?php endif; ?>
                <div class="filter-row">
                    <div class="filter-group filter-search-wrap">
                        <label class="filter-label">Search</label>
                        <i class="bi bi-search"></i>
                        <input type="text" class="filter-input" name="search"
                               placeholder="Client, care call, area…"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="filter-group" style="flex:0 1 160px">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" name="status">
                            <option value="">All statuses</option>
                            <option value="Completed"     <?= $statusFilter === 'Completed'     ? 'selected' : '' ?>>Completed</option>
                            <option value="Not completed" <?= $statusFilter === 'Not completed' ? 'selected' : '' ?>>Not Completed</option>
                            <option value="Scheduled"     <?= $statusFilter === 'Scheduled'     ? 'selected' : '' ?>>Scheduled</option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex:0 1 155px">
                        <label class="filter-label">From</label>
                        <input type="date" class="filter-input" name="date_from"
                               value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="filter-group" style="flex:0 1 155px">
                        <label class="filter-label">To</label>
                        <input type="date" class="filter-input" name="date_to"
                               value="<?= htmlspecialchars($dateTo) ?>">
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
                <?php if ($activeFilters): ?>
                    Filtered Results
                <?php else: ?>
                    All Shift Records
                <?php endif; ?>
            </div>
            <?php if ($totalShifts > 0): ?>
                <div class="results-meta">
                    Showing <?= count($shifts) ?> of <?= $totalShifts ?>
                    &nbsp;·&nbsp; Page <?= $page ?> of <?= $totalPages ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($shifts)): ?>
            <div class="empty-state fade-in-up">
                <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                <h5 class="fw-bold mb-2">
                    <?= $activeFilters ? 'No shifts match your filters' : 'No shift records found' ?>
                </h5>
                <p style="font-size:.88rem;color:var(--text-muted,#888)" class="mb-3">
                    <?= $activeFilters
                        ? 'Try adjusting your search or date range.'
                        : 'Your completed and scheduled shifts will appear here.' ?>
                </p>
                <?php if ($activeFilters): ?>
                    <a href="<?= $baseUrl ?>" class="btn btn-sm text-white"
                       style="background:var(--accent);border-radius:999px;padding:.5rem 1.25rem">
                        Clear filters
                    </a>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="shifts-list" id="shiftsList">
                <?php foreach ($shifts as $i => $sh):
                    $status   = $sh['col_call_status'] ?? 'Scheduled';
                    $sBg      = statusBg($status);
                    $sFg      = statusFg($status);
                    $icon     = careCallIcon($sh['col_care_call'] ?? '');
                    $duration = shiftDuration($sh['planned_timeIn'], $sh['planned_timeOut']);
                    $timeIn   = fmtTime($sh['planned_timeIn']);
                    $timeOut  = fmtTime($sh['planned_timeOut']);
                    $miles    = !empty($sh['col_miles']) ? htmlspecialchars($sh['col_miles']) . ' mi' : '';
                    $mileage  = !empty($sh['col_mileage']) ? '£' . htmlspecialchars($sh['col_mileage']) . '/mi' : '';
                ?>
                    <div class="shift-card" style="animation-delay:<?= min($i,4)*0.04 ?>s"
                         onclick="openDrawer(<?= $i ?>)">
                        <div class="shift-card-inner">
                            <div class="shift-accent-bar" style="background:<?= $sFg ?>"></div>
                            <div class="shift-content">
                                <div class="shift-top">
                                    <div>
                                        <div class="shift-client">
                                            <?= htmlspecialchars($sh['client_name'] ?? 'Unknown Client') ?>
                                        </div>
                                        <div class="shift-care-call">
                                            <i class="bi <?= $icon ?>"></i>
                                            <?= htmlspecialchars(ucfirst($sh['col_care_call'] ?? '')) ?>
                                        </div>
                                    </div>
                                    <span class="shift-status-pill"
                                          style="background:<?= $sBg ?>;color:<?= $sFg ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </div>

                                <div class="shift-meta-row">
                                    <span class="shift-meta-pill">
                                        <i class="bi bi-calendar3"></i>
                                        <?= fmtDate($sh['shift_date'] ?? '') ?>
                                    </span>
                                    <span class="shift-meta-pill">
                                        <i class="bi bi-clock"></i>
                                        <?= $timeIn ?> – <?= $timeOut ?>
                                    </span>
                                    <?php if ($duration !== '—'): ?>
                                        <span class="shift-meta-pill">
                                            <i class="bi bi-hourglass-split"></i>
                                            <?= $duration ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($sh['shift_start_time'] ?? ''): ?>
                                        <span class="shift-meta-pill">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                            In: <?= fmtTime($sh['shift_start_time']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="shift-bottom">
                                    <div class="shift-area">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars($sh['client_group'] ?? '—') ?>
                                        <?php if ($sh['col_postcode'] ?? ''): ?>
                                            &nbsp;·&nbsp; <?= htmlspecialchars($sh['col_postcode']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($miles): ?>
                                            <span class="shift-mileage">
                                                <i class="bi bi-car-front"></i> <?= $miles ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($mileage): ?>
                                            <span class="shift-mileage">
                                                <i class="bi bi-currency-pound"></i> <?= $mileage ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($duration !== '—'): ?>
                                            <span class="shift-duration">
                                                <i class="bi bi-clock-fill"></i> <?= $duration ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1):
                $start = max(1, min($page - 2, $totalPages - 4));
                $end   = min($totalPages, $start + 4);
            ?>
                <nav class="pagination-wrap fade-in-up">
                    <a href="<?= $page > 1
                        ? pgUrl($page-1,$baseUrl,$search,$statusFilter,$dateFrom,$dateTo)
                        : '#' ?>"
                       class="pg-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <?php for ($p = $start; $p <= $end; $p++): ?>
                        <a href="<?= pgUrl($p,$baseUrl,$search,$statusFilter,$dateFrom,$dateTo) ?>"
                           class="pg-btn <?= $p === $page ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                    <a href="<?= $page < $totalPages
                        ? pgUrl($page+1,$baseUrl,$search,$statusFilter,$dateFrom,$dateTo)
                        : '#' ?>"
                       class="pg-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
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
    const SHIFTS_DATA = <?= json_encode(array_values($shifts), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    function fmtDate(d) {
        if (!d) return '—';
        const dt   = new Date(d + 'T00:00:00');
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        const mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return `${days[dt.getDay()]}, ${dt.getDate()} ${mons[dt.getMonth()]} ${dt.getFullYear()}`;
    }

    function statusStyle(s) {
        const l = (s || '').toLowerCase().trim();
        if (l === 'completed')     return { bg: '#dcfce7', fg: '#15803d' };
        if (l === 'not completed') return { bg: '#fee2e2', fg: '#dc2626' };
        return { bg: '#dbeafe', fg: '#1d4ed8' };
    }

    function row(key, val, isLast) {
        if (!val || val === '—') return '';
        return `<div class="detail-row">
            <span class="detail-key">${key}</span>
            <span class="detail-val">${val}</span>
        </div>`;
    }

    function badgeRow(key, val) {
        if (!val) return '';
        const st = statusStyle(val);
        return `<div class="detail-row">
            <span class="detail-key">${key}</span>
            <span class="detail-badge" style="background:${st.bg};color:${st.fg}">${val}</span>
        </div>`;
    }

    function section(title, icon, rows) {
        const content = rows.filter(Boolean).join('');
        if (!content) return '';
        return `<div class="detail-section">
            <div class="detail-section-title"><i class="bi ${icon}"></i> ${title}</div>
            ${content}
        </div>`;
    }

    function openDrawer(idx) {
        const sh = SHIFTS_DATA[idx];
        if (!sh) return;

        const st = statusStyle(sh.col_call_status || '');

        document.getElementById('drawerTitle').textContent =
            sh.client_name || 'Unknown Client';
        document.getElementById('drawerSubtitle').textContent =
            fmtDate(sh.shift_date);

        const shiftSection = section('Shift Info', 'bi-calendar3', [
            badgeRow('Status',        sh.col_call_status),
            row('Date',               fmtDate(sh.shift_date)),
            row('Planned In',         sh.planned_timeIn  ? sh.planned_timeIn.slice(0,5)  : ''),
            row('Planned Out',        sh.planned_timeOut ? sh.planned_timeOut.slice(0,5) : ''),
            row('Actual Check-in',    sh.shift_start_time ? sh.shift_start_time.slice(0,5) : ''),
            row('Care Call',          sh.col_care_call ? sh.col_care_call.charAt(0).toUpperCase() + sh.col_care_call.slice(1) : ''),
        ]);

        const clientSection = section('Client', 'bi-person-fill', [
            row('Client Name',        sh.client_name),
            row('Area / Group',       sh.client_group),
            row('Postcode',           sh.col_postcode),
        ]);

        const travelSection = section('Travel & Pay', 'bi-car-front-fill', [
            row('Miles',              sh.col_miles    ? sh.col_miles + ' miles' : ''),
            row('Mileage Rate',       sh.col_mileage  ? '£' + sh.col_mileage + ' per mile' : ''),
        ]);

        const adminSection = section('Admin', 'bi-clipboard-check-fill', [
            row('Visit Status',       sh.col_visit_status),
            row('Confirmation',       sh.col_visit_confirmation),
            row('Record Updated',     sh.dateTime),
        ]);

        document.getElementById('drawerBody').innerHTML =
            shiftSection + clientSection + travelSection + adminSection;

        document.getElementById('drawer').classList.add('open');
        document.getElementById('drawerOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        document.getElementById('drawer').classList.remove('open');
        document.getElementById('drawerOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

    (function () {
        const DEFAULT_AVATAR = 'https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg';
        const UPLOAD_BASE    = 'https://admin.stafflinks.co.uk/uploads/team_dp/';

        const raw = sessionStorage.getItem('loggedInUser');
        if (!raw) { window.location.href = './'; return; }
        let user;
        try { user = JSON.parse(raw); }
        catch (e) { sessionStorage.clear(); window.location.href = './'; return; }

        (function ensureParams() {
            const url    = new URL(window.location.href);
            let changed  = false;
            const sessId = String(user.user_special_Id || '');
            const sessC  = String(user.col_company_Id  || '');
            if (sessId && url.searchParams.get('carer_id')       !== sessId) { url.searchParams.set('carer_id', sessId);   changed = true; }
            if (sessC  && url.searchParams.get('col_company_Id') !== sessC)  { url.searchParams.set('col_company_Id', sessC); changed = true; }
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
        const wFb    = el => { if (el) el.onerror = function () { if (this.src !== DEFAULT_AVATAR) this.src = DEFAULT_AVATAR; }; };
        const setSrc = (id, src, alt) => { const el = document.getElementById(id); if (el) { el.src = src; if (alt) el.alt = alt; } };
        const setText= (id, v)        => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };

        const avatar = avatarSrc(user.team_dp);
        const name   = user.user_fullname || '';
        setSrc('topbarAvatar',  avatar, name); wFb(document.getElementById('topbarAvatar'));
        setSrc('navProfilePic', avatar, name); wFb(document.getElementById('navProfilePic'));
        setText('navFullName', name);
        setText('navEmail', user.user_email_address || '');
        setText('navPhone', user.user_phone_number  || '');

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