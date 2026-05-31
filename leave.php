<?php
$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';
$page      = max(1, (int)($_GET['page']    ?? 1));
$perPage   = 10;

$leaveHistory = [];
$totalLeave   = 0;
$totalPages   = 1;
$submitError  = '';
$submitSuccess= '';
$carerName    = '';

$pendingCount  = 0;
$approvedCount = 0;
$rejectedCount = 0;
$usedDays      = 0.0;
$totalAllowance= 28;

$baseUrl = 'leave.php?carer_id=' . urlencode($carerId)
         . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');

$leaveTypes = [
    'Annual Leave'       => '#0d6efd',
    'Sick Leave'         => '#dc2626',
    'Emergency Leave'    => '#f59e0b',
    'Compassionate Leave'=> '#6f42c1',
    'Study Leave'        => '#0dcaf0',
    'Unpaid Leave'       => '#6c757d',
    'Other'              => '#fd7e14',
];

if ($carerId !== '' && $companyId !== '') {
    include_once 'dbconnect.php';

    if (!$conn->connect_error) {

        $nameStmt = $conn->prepare("
            SELECT user_fullname FROM tbl_team_account
            WHERE  user_special_Id = ? LIMIT 1
        ");
        $nameStmt->bind_param('s', $carerId);
        $nameStmt->execute();
        $nameStmt->bind_result($carerName);
        $nameStmt->fetch();
        $nameStmt->close();
        $carerName = $carerName ?: '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
            $fullName  = trim($_POST['full_name']   ?? $carerName);
            $startDate = trim($_POST['start_date']  ?? '');
            $endDate   = trim($_POST['end_date']    ?? '');
            $reason    = trim($_POST['leave_type']  ?? '');
            $note      = trim($_POST['note']        ?? '');
            $colorMap  = $leaveTypes;
            $color     = $colorMap[$reason] ?? '#6c757d';

            if ($fullName === '' || $startDate === '' || $endDate === '' || $reason === '') {
                $submitError = 'Please fill in all required fields.';
            } elseif ($endDate < $startDate) {
                $submitError = 'End date cannot be before the start date.';
            } else {
                $ins = $conn->prepare("
                    INSERT INTO tbl_team_status
                           (col_full_name, col_startDate, col_endDate,
                            col_team_condition, col_note, col_approval,
                            col_is_read, col_color_code,
                            uryyTteamoeSS4, col_company_Id)
                    VALUES (?, ?, ?, ?, ?, 'Pending', 'Unread', ?, ?, ?)
                ");
                $ins->bind_param('ssssssss',
                    $fullName, $startDate, $endDate,
                    $reason, $note, $color,
                    $carerId, $companyId
                );
                if ($ins->execute()) {
                    $submitSuccess = 'Your leave request has been submitted and is awaiting approval.';
                } else {
                    $submitError = 'Could not submit your request. Please try again.';
                }
                $ins->close();
            }
        }

        $cntStmt = $conn->prepare("
            SELECT COUNT(*) FROM tbl_team_status
            WHERE uryyTteamoeSS4 = ? AND col_company_Id = ?
        ");
        $cntStmt->bind_param('ss', $carerId, $companyId);
        $cntStmt->execute();
        $cntStmt->bind_result($totalLeave);
        $cntStmt->fetch();
        $cntStmt->close();

        $totalPages = max(1, (int)ceil($totalLeave / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $listStmt = $conn->prepare("
            SELECT userId, col_full_name, col_startDate, col_endDate,
                   col_team_condition, col_note, col_approval,
                   col_color_code, dateTime
            FROM   tbl_team_status
            WHERE  uryyTteamoeSS4 = ? AND col_company_Id = ?
            ORDER  BY dateTime DESC
            LIMIT  ? OFFSET ?
        ");
        $listStmt->bind_param('ssii', $carerId, $companyId, $perPage, $offset);
        $listStmt->execute();
        $res = $listStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $leaveHistory[] = $row;
        }
        $listStmt->close();

        $statStmt = $conn->prepare("
            SELECT col_approval, col_startDate, col_endDate
            FROM   tbl_team_status
            WHERE  uryyTteamoeSS4 = ? AND col_company_Id = ?
        ");
        $statStmt->bind_param('ss', $carerId, $companyId);
        $statStmt->execute();
        $statRes = $statStmt->get_result();
        while ($sRow = $statRes->fetch_assoc()) {
            $ap = strtolower(trim($sRow['col_approval']));
            if ($ap === 'pending')  $pendingCount++;
            if ($ap === 'rejected') $rejectedCount++;
            if ($ap === 'approved') {
                $approvedCount++;
                $s = DateTime::createFromFormat('Y-m-d', $sRow['col_startDate']);
                $e = DateTime::createFromFormat('Y-m-d', $sRow['col_endDate']);
                if ($s && $e) {
                    $diff = $e->diff($s);
                    $usedDays += $diff->days + 1;
                }
            }
        }
        $statStmt->close();
        $conn->close();
    }
}

function fmtDate(string $raw): string {
    $fmts = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
    foreach ($fmts as $fmt) {
        $d = DateTime::createFromFormat($fmt, $raw);
        if ($d) return $d->format('D, j M Y');
    }
    return htmlspecialchars($raw);
}

function fmtDateShort(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('j M Y') : htmlspecialchars($raw);
}

function leaveDays(string $s, string $e): int {
    $ds = DateTime::createFromFormat('Y-m-d', $s);
    $de = DateTime::createFromFormat('Y-m-d', $e);
    if (!$ds || !$de) return 0;
    return $de->diff($ds)->days + 1;
}

function approvalConfig(string $status): array {
    return match (strtolower(trim($status))) {
        'approved' => ['bg' => '#dcfce7', 'fg' => '#15803d', 'icon' => 'bi-check-circle-fill',   'label' => 'Approved'],
        'rejected' => ['bg' => '#fee2e2', 'fg' => '#dc2626', 'icon' => 'bi-x-circle-fill',        'label' => 'Rejected'],
        default    => ['bg' => '#fef9c3', 'fg' => '#a16207', 'icon' => 'bi-hourglass-split',       'label' => 'Pending'],
    };
}

$remaining = max(0, $totalAllowance - $usedDays);
$usedPct   = $totalAllowance > 0 ? min(100, round(($usedDays / $totalAllowance) * 100)) : 0;
$today     = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Leave Request – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }

        .leave-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 1.25rem 1rem 5rem;
        }

        .allowance-hero {
            background: linear-gradient(135deg, var(--accent, #c94a57) 0%, var(--accent2, #e05c6e) 100%);
            border-radius: var(--radius, 18px);
            color: #fff;
            padding: 1.75rem 1.75rem 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .allowance-hero::after {
            content: '';
            position: absolute;
            right: -30px; top: -30px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            pointer-events: none;
        }
        .allowance-label {
            font-size: .78rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; opacity: .75; margin-bottom: .35rem;
        }
        .allowance-days {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 3rem; font-weight: 700; line-height: 1; margin-bottom: .2rem;
        }
        .allowance-sub { opacity: .75; font-size: .88rem; }
        .allowance-bar-wrap { margin-top: 1.1rem; }
        .allowance-bar-track {
            height: 8px; border-radius: 999px;
            background: rgba(255,255,255,.2);
            overflow: hidden;
        }
        .allowance-bar-fill {
            height: 100%; border-radius: 999px;
            background: rgba(255,255,255,.85);
            transition: width .6s ease;
        }
        .allowance-bar-meta {
            display: flex; justify-content: space-between;
            font-size: .72rem; opacity: .7; margin-top: .35rem;
        }

        .stats-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: .85rem; margin-bottom: 1.5rem;
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
        .ic-yellow { background: rgba(245,158,11,.12); color: #f59e0b; }
        .ic-green  { background: rgba(25,135,84,.12);  color: #198754; }
        .ic-red    { background: rgba(220,38,38,.12);  color: #dc2626; }
        .ic-blue   { background: rgba(13,110,253,.12); color: #0d6efd; }
        .strip-val   { font-weight: 800; font-size: 1.1rem; line-height: 1; }
        .strip-label { font-size: .7rem; color: var(--text-muted, #888); margin-top: .1rem; }

        .form-card {
            background: var(--card-bg, #fff);
            border-radius: var(--radius, 18px);
            box-shadow: 0 2px 16px rgba(0,0,0,.07);
            padding: 1.5rem 1.5rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        .form-card-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.15rem; font-weight: 700;
            color: var(--text-main, #1a1a1a);
            margin-bottom: 1.1rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .form-card-title i { color: var(--accent, #c94a57); }

        .field-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media(max-width:560px) { .field-grid { grid-template-columns: 1fr; } }
        .field-full { grid-column: 1 / -1; }

        .field-group { display: flex; flex-direction: column; gap: .3rem; }
        .field-label {
            font-size: .73rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: var(--text-muted, #888);
        }
        .field-input, .field-select, .field-textarea {
            border: 1.5px solid var(--border-color, #e8e8e8);
            border-radius: 10px;
            padding: .6rem .9rem;
            font-size: .9rem;
            background: var(--input-bg, #fafafa);
            color: var(--text-main, #1a1a1a);
            outline: none; transition: border-color .18s;
            font-family: 'DM Sans', sans-serif;
            width: 100%;
        }
        .field-input:focus, .field-select:focus, .field-textarea:focus {
            border-color: var(--accent, #c94a57);
            box-shadow: 0 0 0 3px rgba(201,74,87,.08);
        }
        .field-textarea { resize: vertical; min-height: 90px; }

        .leave-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: .6rem;
        }
        .leave-type-chip {
            border: 1.5px solid var(--border-color, #e8e8e8);
            border-radius: 10px;
            padding: .6rem .75rem;
            cursor: pointer;
            transition: border-color .18s, background .18s, transform .12s;
            display: flex; align-items: center; gap: .5rem;
            font-size: .83rem; font-weight: 600;
            color: var(--text-main, #444);
            background: var(--card-bg, #fff);
            user-select: none;
        }
        .leave-type-chip:hover { transform: translateY(-1px); }
        .leave-type-chip.selected {
            color: #fff; border-color: transparent;
        }
        .leave-type-chip input { display: none; }
        .leave-dot {
            width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
            color: #fff; border: none; border-radius: 999px;
            padding: .7rem 2.25rem; font-size: .95rem; font-weight: 700;
            cursor: pointer; transition: opacity .18s, transform .15s;
            box-shadow: 0 4px 16px rgba(201,74,87,.28);
        }
        .btn-submit:hover { opacity: .88; transform: translateY(-1px); }

        .flash-success {
            background: #f0fdf4; border: 1.5px solid #bbf7d0;
            border-radius: 12px; padding: .85rem 1rem; font-size: .88rem;
            color: #166534; display: flex; align-items: center; gap: .5rem;
            margin-bottom: 1.25rem;
        }
        .flash-error {
            background: #fff1f2; border: 1.5px solid #fecdd3;
            border-radius: 12px; padding: .85rem 1rem; font-size: .88rem;
            color: #991b1b; display: flex; align-items: center; gap: .5rem;
            margin-bottom: 1.25rem;
        }

        .history-card {
            background: var(--card-bg, #fff);
            border-radius: var(--radius, 18px);
            box-shadow: 0 2px 16px rgba(0,0,0,.07);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .history-header {
            padding: 1.1rem 1.25rem .9rem;
            border-bottom: 1px solid var(--border-color, #f0f0f0);
            display: flex; align-items: center; justify-content: space-between;
        }
        .history-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1rem; font-weight: 700; color: var(--text-main, #1a1a1a);
        }
        .history-count { font-size: .75rem; color: var(--text-muted, #aaa); }

        .leave-row {
            display: flex; align-items: center; gap: 1rem;
            padding: .85rem 1.25rem;
            border-bottom: 1px solid var(--border-color, #f5f5f5);
            cursor: pointer; transition: background .15s;
        }
        .leave-row:last-child { border-bottom: none; }
        .leave-row:hover { background: var(--hover-bg, #fafafa); }

        .leave-row-accent {
            width: 4px; height: 42px; border-radius: 4px; flex-shrink: 0;
        }
        .leave-row-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .leave-row-info { flex: 1; min-width: 0; }
        .leave-row-type {
            font-weight: 700; font-size: .9rem;
            color: var(--text-main, #1a1a1a); white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }
        .leave-row-dates {
            font-size: .75rem; color: var(--text-muted, #888); margin-top: .1rem;
        }
        .leave-row-right { display: flex; flex-direction: column; align-items: flex-end; gap: .3rem; }
        .leave-row-days {
            font-size: .75rem; font-weight: 700; color: var(--text-muted, #666);
        }
        .status-pill {
            font-size: .68rem; font-weight: 700;
            padding: .2rem .6rem; border-radius: 999px; white-space: nowrap;
        }

        .empty-state {
            text-align: center; padding: 3rem 2rem;
        }
        .empty-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: var(--pill-bg, #f5f5f5);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.75rem; color: var(--text-muted, #ccc);
            margin: 0 auto 1rem;
        }

        .pagination-wrap {
            display: flex; justify-content: center;
            gap: .4rem; flex-wrap: wrap;
            padding: .85rem 1.25rem;
            border-top: 1px solid var(--border-color, #f0f0f0);
        }
        .pg-btn {
            width: 34px; height: 34px; border-radius: 50%;
            border: 1.5px solid var(--border-color, #e8e8e8);
            background: var(--card-bg, #fff); color: var(--text-main, #444);
            font-size: .8rem; font-weight: 700;
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
            font-size: .7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: var(--text-muted, #aaa);
            margin-bottom: .6rem; display: flex; align-items: center; gap: .4rem;
        }
        .detail-section-title i { color: var(--accent, #c94a57); }
        .detail-row {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: .5rem; padding: .5rem 0;
            border-bottom: 1px solid var(--border-color, #f5f5f5);
            font-size: .87rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-key { color: var(--text-muted, #888); font-weight: 500; flex-shrink: 0; }
        .detail-val { font-weight: 600; text-align: right; color: var(--text-main, #1a1a1a); }
        .detail-note {
            background: var(--pill-bg, #f5f5f5);
            border-radius: 10px; padding: .75rem 1rem;
            font-size: .85rem; line-height: 1.6;
            color: var(--text-main, #2a2a2a); margin-top: .5rem;
        }

        .status-banner {
            border-radius: 12px; padding: 1rem 1.1rem;
            display: flex; align-items: center; gap: .75rem;
            margin-bottom: 1rem;
        }
        .status-banner i { font-size: 1.4rem; flex-shrink: 0; }
        .status-banner-text { font-size: .88rem; }
        .status-banner-label { font-weight: 700; margin-bottom: .1rem; }

        .timeline-note {
            background: #fffbeb; border: 1.5px solid #fde68a;
            border-radius: 10px; padding: .75rem 1rem;
            font-size: .8rem; color: #78350f;
            display: flex; align-items: flex-start; gap: .5rem;
            margin-top: 1rem;
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
                            <h2 class="mb-0 fw-bold">Leave Requests</h2>
                            <div class="text-white-50" style="font-size:.85rem">Request time off and track your leave</div>
                        </div>
                    </div>
                    <?php if ($pendingCount > 0): ?>
                        <span class="chip">
                            <i class="bi bi-hourglass-split me-1"></i>
                            <?= $pendingCount ?> pending
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="leave-container">

        <div class="allowance-hero fade-in-up">
            <div class="allowance-label">Annual Leave Allowance</div>
            <div class="allowance-days"><?= $remaining ?> days</div>
            <div class="allowance-sub">
                remaining of <?= $totalAllowance ?> days total
                &nbsp;·&nbsp; <?= number_format($usedDays, 0) ?> used
            </div>
            <div class="allowance-bar-wrap">
                <div class="allowance-bar-track">
                    <div class="allowance-bar-fill" style="width:<?= $usedPct ?>%"></div>
                </div>
                <div class="allowance-bar-meta">
                    <span>0 days</span>
                    <span><?= $usedPct ?>% used</span>
                    <span><?= $totalAllowance ?> days</span>
                </div>
            </div>
        </div>

        <div class="stats-strip fade-in-up">
            <div class="strip-card">
                <div class="strip-icon ic-yellow"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="strip-val"><?= $pendingCount ?></div>
                    <div class="strip-label">Pending</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-green"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $approvedCount ?></div>
                    <div class="strip-label">Approved</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-red"><i class="bi bi-x-circle-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $rejectedCount ?></div>
                    <div class="strip-label">Rejected</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon ic-blue"><i class="bi bi-calendar-range-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalLeave ?></div>
                    <div class="strip-label">Total requests</div>
                </div>
            </div>
        </div>

        <?php if ($submitSuccess): ?>
            <div class="flash-success fade-in-up">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($submitSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($submitError): ?>
            <div class="flash-error fade-in-up">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($submitError) ?>
            </div>
        <?php endif; ?>

        <div class="form-card fade-in-up">
            <div class="form-card-title">
                <i class="bi bi-plus-circle-fill"></i>
                New Leave Request
            </div>

            <form method="POST" action="<?= $baseUrl ?>&page=<?= $page ?>" id="leaveForm">
                <input type="hidden" name="submit_leave" value="1">

                <div class="field-grid">

                    <div class="field-group">
                        <label class="field-label">Full Name <span style="color:var(--accent)">*</span></label>
                        <input type="text" class="field-input" name="full_name"
                               value="<?= htmlspecialchars($carerName) ?>"
                               id="fieldFullName" required placeholder="Your full name">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Start Date <span style="color:var(--accent)">*</span></label>
                        <input type="date" class="field-input" name="start_date"
                               id="startDate" min="<?= $today ?>" required
                               value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">End Date <span style="color:var(--accent)">*</span></label>
                        <input type="date" class="field-input" name="end_date"
                               id="endDate" min="<?= $today ?>" required
                               value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Duration</label>
                        <div class="field-input" id="durationDisplay"
                             style="background:var(--pill-bg,#f5f5f5);color:var(--text-muted,#888);cursor:default">
                            Select dates above
                        </div>
                    </div>

                    <div class="field-group field-full">
                        <label class="field-label">Leave Type <span style="color:var(--accent)">*</span></label>
                        <input type="hidden" name="leave_type" id="leaveTypeVal"
                               value="<?= htmlspecialchars($_POST['leave_type'] ?? '') ?>" required>
                        <div class="leave-type-grid" id="leaveTypeGrid">
                            <?php foreach ($leaveTypes as $type => $colour):
                                $sel = ($_POST['leave_type'] ?? '') === $type;
                            ?>
                                <label class="leave-type-chip <?= $sel ? 'selected' : '' ?>"
                                       style="<?= $sel ? "background:{$colour};border-color:{$colour}" : '' ?>"
                                       data-colour="<?= $colour ?>">
                                    <input type="radio" name="_leave_type_display" value="<?= htmlspecialchars($type) ?>"
                                           <?= $sel ? 'checked' : '' ?>>
                                    <span class="leave-dot" style="background:<?= $colour ?>"></span>
                                    <?= htmlspecialchars($type) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="field-group field-full">
                        <label class="field-label">Additional Note</label>
                        <textarea class="field-textarea" name="note"
                                  placeholder="Any additional context for your manager…"
                                  maxlength="1000"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                    </div>

                </div>

                <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-send me-2"></i>Submit Request
                    </button>
                    <span class="small-muted" style="font-size:.78rem">
                        <i class="bi bi-info-circle me-1"></i>
                        Your manager will be notified and respond within 2 working days.
                    </span>
                </div>
            </form>
        </div>

        <div class="history-card fade-in-up">
            <div class="history-header">
                <div class="history-title">Leave History</div>
                <div class="history-count">
                    <?php if ($totalLeave > 0): ?>
                        <?= count($leaveHistory) ?> of <?= $totalLeave ?>
                        &nbsp;·&nbsp; Page <?= $page ?> of <?= $totalPages ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($leaveHistory)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
                    <h6 class="fw-bold mb-1">No leave requests yet</h6>
                    <p class="small-muted mb-0">Submit your first request using the form above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($leaveHistory as $i => $lr):
                    $colour = $lr['col_color_code'] ?: '#6c757d';
                    $cfg    = approvalConfig($lr['col_approval'] ?? '');
                    $days   = leaveDays($lr['col_startDate'], $lr['col_endDate']);
                    $sDate  = fmtDateShort($lr['col_startDate']);
                    $eDate  = fmtDateShort($lr['col_endDate']);
                    $dLabel = $sDate === $eDate ? $sDate : "$sDate – $eDate";
                ?>
                    <div class="leave-row" onclick="openDrawer(<?= $i ?>)">
                        <div class="leave-row-accent" style="background:<?= $colour ?>"></div>
                        <div class="leave-row-icon"
                             style="background:<?= $colour ?>18;color:<?= $colour ?>">
                            <i class="bi bi-umbrella-fill"></i>
                        </div>
                        <div class="leave-row-info">
                            <div class="leave-row-type">
                                <?= htmlspecialchars($lr['col_team_condition']) ?>
                            </div>
                            <div class="leave-row-dates">
                                <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($dLabel) ?>
                            </div>
                        </div>
                        <div class="leave-row-right">
                            <span class="status-pill"
                                  style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['fg'] ?>">
                                <i class="bi <?= $cfg['icon'] ?> me-1"></i><?= $cfg['label'] ?>
                            </span>
                            <span class="leave-row-days">
                                <?= $days ?> day<?= $days !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1):
                    $pgStart = max(1, min($page - 2, $totalPages - 4));
                    $pgEnd   = min($totalPages, $pgStart + 4);
                ?>
                    <nav class="pagination-wrap">
                        <a href="<?= $page > 1 ? $baseUrl.'&page='.($page-1) : '#' ?>"
                           class="pg-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <?php for ($p = $pgStart; $p <= $pgEnd; $p++): ?>
                            <a href="<?= $baseUrl.'&page='.$p ?>"
                               class="pg-btn <?= $p === $page ? 'active' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                        <a href="<?= $page < $totalPages ? $baseUrl.'&page='.($page+1) : '#' ?>"
                           class="pg-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </main>

    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="drawer" role="dialog" aria-label="Leave details">
        <div class="drawer-header">
            <div>
                <div id="drawerTitle" style="font-weight:800;font-size:1rem"></div>
                <div id="drawerSub" style="font-size:.78rem;color:var(--text-muted,#aaa);margin-top:.15rem"></div>
            </div>
            <button class="drawer-close" onclick="closeDrawer()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="drawer-body" id="drawerBody"></div>
    </div>

    <?php include 'new-footer.php'; ?>

    <script>
    const LEAVE_DATA = <?= json_encode(array_values($leaveHistory), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    function approvalCfg(s) {
        const l = (s || '').toLowerCase().trim();
        if (l === 'approved') return { bg: '#dcfce7', fg: '#15803d', icon: 'bi-check-circle-fill',  label: 'Approved' };
        if (l === 'rejected') return { bg: '#fee2e2', fg: '#dc2626', icon: 'bi-x-circle-fill',       label: 'Rejected' };
        return                       { bg: '#fef9c3', fg: '#a16207', icon: 'bi-hourglass-split',      label: 'Pending'  };
    }

    function fmtDate(d) {
        if (!d) return '—';
        const parts = d.split('-');
        if (parts.length === 3) {
            const dt   = new Date(+parts[0], +parts[1] - 1, +parts[2]);
            const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            const mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return `${days[dt.getDay()]}, ${dt.getDate()} ${mons[dt.getMonth()]} ${dt.getFullYear()}`;
        }
        return d;
    }

    function leaveDays(s, e) {
        if (!s || !e) return 0;
        const ds = new Date(s), de = new Date(e);
        return Math.max(1, Math.round((de - ds) / 86400000) + 1);
    }

    function openDrawer(idx) {
        const lr  = LEAVE_DATA[idx];
        if (!lr) return;
        const cfg  = approvalCfg(lr.col_approval);
        const colour = lr.col_color_code || '#6c757d';
        const days   = leaveDays(lr.col_startDate, lr.col_endDate);

        document.getElementById('drawerTitle').textContent = lr.col_team_condition || 'Leave Request';
        document.getElementById('drawerSub').textContent   =
            fmtDate(lr.col_startDate) + (lr.col_startDate !== lr.col_endDate
                ? ' – ' + fmtDate(lr.col_endDate) : '');

        const statusBanner = `
            <div class="status-banner"
                 style="background:${cfg.bg};border:1.5px solid ${cfg.fg}22">
                <i class="bi ${cfg.icon}" style="color:${cfg.fg}"></i>
                <div class="status-banner-text">
                    <div class="status-banner-label" style="color:${cfg.fg}">${cfg.label}</div>
                    <div style="font-size:.8rem;color:var(--text-muted,#666)">
                        ${cfg.label === 'Pending'
                            ? 'Awaiting your manager\'s response.'
                            : cfg.label === 'Approved'
                                ? 'Your leave has been approved. Enjoy your time off!'
                                : 'This request was not approved. Contact your manager for details.'}
                    </div>
                </div>
            </div>`;

        const detailRows = [
            ['Leave Type',  lr.col_team_condition],
            ['Start Date',  fmtDate(lr.col_startDate)],
            ['End Date',    fmtDate(lr.col_endDate)],
            ['Duration',    `${days} day${days !== 1 ? 's' : ''}`],
            ['Submitted',   lr.dateTime ? lr.dateTime.slice(0, 16).replace('T',' ') : '—'],
        ].map(([k, v]) => v ? `<div class="detail-row">
            <span class="detail-key">${k}</span>
            <span class="detail-val">${v}</span>
        </div>` : '').join('');

        const noteSection = lr.col_note ? `
            <div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-sticky-fill"></i> Note</div>
                <div class="detail-note">${lr.col_note.replace(/</g,'&lt;')}</div>
            </div>` : '';

        const timelineNote = cfg.label === 'Pending' ? `
            <div class="timeline-note">
                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                <div>Requests are usually reviewed within 2 working days.
                If you need to withdraw this request, contact your manager directly.</div>
            </div>` : '';

        document.getElementById('drawerBody').innerHTML =
            statusBanner +
            `<div class="detail-section">
                <div class="detail-section-title"><i class="bi bi-calendar3"></i> Details</div>
                ${detailRows}
            </div>` +
            noteSection +
            timelineNote;

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
        const start = document.getElementById('startDate');
        const end   = document.getElementById('endDate');
        const disp  = document.getElementById('durationDisplay');

        function updateDuration() {
            if (!start.value || !end.value) {
                disp.textContent = 'Select dates above';
                return;
            }
            const ds = new Date(start.value), de = new Date(end.value);
            if (de < ds) { disp.textContent = 'End date before start'; return; }
            const days = Math.round((de - ds) / 86400000) + 1;
            disp.textContent = `${days} day${days !== 1 ? 's' : ''}`;
            if (end.value < start.value) end.value = start.value;
            end.min = start.value;
        }

        start.addEventListener('change', updateDuration);
        end.addEventListener('change', updateDuration);
        updateDuration();
    })();

    (function () {
        const chips = document.querySelectorAll('.leave-type-chip');
        const hidden = document.getElementById('leaveTypeVal');
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => {
                    c.classList.remove('selected');
                    c.style.background = '';
                    c.style.borderColor = '';
                });
                const colour = chip.dataset.colour;
                chip.classList.add('selected');
                chip.style.background   = colour;
                chip.style.borderColor  = colour;
                hidden.value = chip.querySelector('input').value;
            });
        });

        document.getElementById('leaveForm').addEventListener('submit', function (e) {
            if (!hidden.value) {
                e.preventDefault();
                alert('Please select a leave type.');
                document.getElementById('leaveTypeGrid').scrollIntoView({ behavior: 'smooth' });
            }
        });
    })();

    (function () {
        const DEFAULT_AVATAR = 'https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg';
        const UPLOAD_BASE    = 'https://admin.stafflinks.co.uk/uploads/team_dp/';
        const raw = sessionStorage.getItem('loggedInUser');
        if (!raw) { window.location.href = './'; return; }
        let user;
        try { user = JSON.parse(raw); } catch(e) { window.location.href = './'; return; }

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

        const nameField = document.getElementById('fieldFullName');
        if (nameField && !nameField.value && name) nameField.value = name;

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