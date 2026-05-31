<?php
$monthlyHours     = 0;
$monthlyMinutes   = 0;
$monthlyDisplay   = '0h 00m';
$progressPct      = 0;
$monthlyShiftDays = 0;
$monthlyPay       = '£0.00';
$leaveRemaining   = 0;
$weekShiftDays    = [];
$openShifts       = [];
$recentShifts     = [];
$todayWellbeing   = '';

$carerId   = isset($_GET['carer_id'])      ? trim($_GET['carer_id'])      : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';

if ($carerId !== '') {
    include_once 'dbconnect.php';

    if (!$conn->connect_error) {

        // ── Wellbeing POST handler ─────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wellbeing_mood'])) {
            $mood = trim($_POST['wellbeing_mood']);
            $allowed = ['Good', 'Okay', 'Need support'];
            if (in_array($mood, $allowed, true)) {
                $wStmt = $conn->prepare("
                    INSERT INTO tbl_wellbeing (col_carer_Id, col_company_Id, mood, checked_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $wStmt->bind_param('sss', $carerId, $companyId, $mood);
                $wStmt->execute();
                $wStmt->close();
            }
        }

        // ── Check if carer already submitted wellbeing today ──────────────────
        $todayOnly = date('Y-m-d');
        $wChk = $conn->prepare("
            SELECT mood FROM tbl_wellbeing
            WHERE  col_carer_Id  = ?
              AND  col_company_Id = ?
              AND  DATE(checked_at) = ?
            ORDER  BY checked_at DESC
            LIMIT  1
        ");
        $wChk->bind_param('sss', $carerId, $companyId, $todayOnly);
        $wChk->execute();
        $wChk->bind_result($todayWellbeing);
        $wChk->fetch();
        $wChk->close();
        $todayWellbeing = $todayWellbeing ?: '';

        // ── Monthly hours (tbl_daily_shift_records) ────────────────────────
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $stmt = $conn->prepare("
            SELECT planned_timeIn, planned_timeOut
            FROM   tbl_daily_shift_records
            WHERE  col_carer_Id = ?
              AND  col_call_status = 'Completed'
              AND  shift_date BETWEEN ? AND ?
        ");
        $stmt->bind_param('sss', $carerId, $monthStart, $monthEnd);
        $stmt->execute();
        $result = $stmt->get_result();

        $totalMins = 0;

        while ($row = $result->fetch_assoc()) {
            $in  = $row['planned_timeIn'];
            $out = $row['planned_timeOut'];

            if ($in && $out) {
                $tIn  = DateTime::createFromFormat('H:i', $in);
                $tOut = DateTime::createFromFormat('H:i', $out);

                if ($tOut < $tIn) {
                    $tOut->modify('+1 day');
                }

                if ($tIn && $tOut) {
                    $diff       = $tOut->diff($tIn);
                    $totalMins += ($diff->h * 60) + $diff->i;
                }
            }
        }

        $stmt->close();

        $monthlyHours   = intdiv($totalMins, 60);
        $monthlyMinutes = $totalMins % 60;
        $monthlyDisplay = $monthlyHours . 'h ' . str_pad($monthlyMinutes, 2, '0', STR_PAD_LEFT) . 'm';

        $maxMonthMins = 160 * 60;
        $progressPct  = min(100, round(($totalMins / $maxMonthMins) * 100));

        // ── Monthly shift days (tbl_schedule_calls) ────────────────────────
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT Clientshift_Date) AS shift_days
            FROM   tbl_schedule_calls
            WHERE  first_carer_Id = ?
              AND  call_status = 'Scheduled'
              AND  STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') BETWEEN ? AND ?
        ");
        $stmt->bind_param('sss', $carerId, $monthStart, $monthEnd);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $monthlyShiftDays = (int) $row['shift_days'];
        }

        $stmt->close();

        // ── Monthly pay (tbl_schedule_calls) ───────────────────────────────
        $stmt = $conn->prepare("
            SELECT dateTime_in, dateTime_out, pay_rate
            FROM   tbl_schedule_calls
            WHERE  first_carer_Id = ?
              AND  call_status = 'Completed'
              AND  STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') BETWEEN ? AND ?
              AND  pay_rate IS NOT NULL
              AND  pay_rate != ''
        ");
        $stmt->bind_param('sss', $carerId, $monthStart, $monthEnd);
        $stmt->execute();
        $result = $stmt->get_result();

        $totalPay = 0.0;

        while ($row = $result->fetch_assoc()) {
            $in      = $row['dateTime_in'];
            $out     = $row['dateTime_out'];
            $rawRate = $row['pay_rate'];

            $rate = (float) preg_replace('/[^0-9.]/', '', $rawRate);

            if (!$in || !$out || $rate <= 0) continue;

            $tIn  = DateTime::createFromFormat('H:i', $in);
            $tOut = DateTime::createFromFormat('H:i', $out);

            if (!$tIn || !$tOut) continue;

            if ($tOut < $tIn) {
                $tOut->modify('+1 day');
            }

            $diff       = $tOut->diff($tIn);
            $shiftHours = $diff->h + ($diff->i / 60);

            $totalPay += $shiftHours * $rate;
        }

        $stmt->close();

        $monthlyPay = '£' . number_format($totalPay, 2);

        // ── Leave days remaining (tbl_team_status) ─────────────────────────
        $totalLeaveHours = 28 * 7.5;
        $usedLeaveHours  = 0.0;
        $yearStart       = date('Y-01-01');
        $yearEnd         = date('Y-12-31');

        $stmt = $conn->prepare("
            SELECT col_startDate, col_endDate
            FROM   tbl_team_status
            WHERE  uryyTteamoeSS4 = ?
              AND  col_approval = 'Approved'
              AND  STR_TO_DATE(col_startDate, '%Y-%m-%d') BETWEEN ? AND ?
        ");
        $stmt->bind_param('sss', $carerId, $yearStart, $yearEnd);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $start = $row['col_startDate'];
            $end   = $row['col_endDate'];

            if (!$start || !$end) continue;

            $dStart = DateTime::createFromFormat('Y-m-d', $start);
            $dEnd   = DateTime::createFromFormat('Y-m-d', $end);

            if (!$dStart || !$dEnd) continue;

            $diff           = $dEnd->diff($dStart);
            $days           = $diff->days + 1;
            $usedLeaveHours += $days * 7.5;
        }

        $stmt->close();

        $remainingLeaveHours = max(0, $totalLeaveHours - $usedLeaveHours);
        $leaveRemaining      = number_format($remainingLeaveHours / 7.5, 1);

        // ── Current week shift days for mini-calendar ──────────────────────
        $calWeekStart = date('Y-m-d', strtotime('monday this week'));
        $calWeekEnd   = date('Y-m-d', strtotime('sunday this week'));

        $stmt = $conn->prepare("
            SELECT DISTINCT Clientshift_Date
            FROM   tbl_schedule_calls
            WHERE  first_carer_Id = ?
              AND  call_status IN ('Scheduled', 'Completed')
              AND  STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') BETWEEN ? AND ?
        ");
        $stmt->bind_param('sss', $carerId, $calWeekStart, $calWeekEnd);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $d = DateTime::createFromFormat('Y-m-d', $row['Clientshift_Date']);
            if ($d) {
                $weekShiftDays[$d->format('Y-m-d')] = true;
            }
        }

        $stmt->close();

        // ── Open Shifts Marketplace ────────────────────────────────────────
        $todayStr = date('Y-m-d');

        if ($companyId !== '') {
            $stmt = $conn->prepare("
                SELECT
                    col_run_name,
                    MIN(client_area)                                  AS area_label,
                    Clientshift_Date,
                    MIN(dateTime_in)                                  AS earliest_in,
                    MAX(dateTime_out)                                 AS latest_out
                FROM   tbl_schedule_calls
                WHERE  col_company_Id = ?
                  AND  (first_carer              IS NULL OR first_carer              = '')
                  AND  (first_carer_Id           IS NULL OR first_carer_Id           = '')
                  AND  (assigned_carer_unique_id IS NULL OR assigned_carer_unique_id = '')
                  AND  call_status = 'Scheduled'
                  AND  STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') >= ?
                GROUP BY col_run_name, Clientshift_Date
                ORDER BY STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') ASC, col_run_name ASC
                LIMIT 6
            ");
            $stmt->bind_param('ss', $companyId, $todayStr);
        } else {
            $stmt = $conn->prepare("
                SELECT
                    col_run_name,
                    MIN(client_area)                                  AS area_label,
                    Clientshift_Date,
                    MIN(dateTime_in)                                  AS earliest_in,
                    MAX(dateTime_out)                                 AS latest_out
                FROM   tbl_schedule_calls
                WHERE  (first_carer              IS NULL OR first_carer              = '')
                  AND  (first_carer_Id           IS NULL OR first_carer_Id           = '')
                  AND  (assigned_carer_unique_id IS NULL OR assigned_carer_unique_id = '')
                  AND  call_status = 'Scheduled'
                  AND  STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') >= ?
                GROUP BY col_run_name, Clientshift_Date
                ORDER BY STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') ASC, col_run_name ASC
                LIMIT 6
            ");
            $stmt->bind_param('s', $todayStr);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $openShifts[] = $row;
        }

        $stmt->close();

        // ── 3 most recent completed shifts for the dashboard widget ────────────
        $stmt = $conn->prepare("
            SELECT client_name, shift_date, planned_timeIn, planned_timeOut, col_call_status
            FROM   tbl_daily_shift_records
            WHERE  col_carer_Id = ?
              AND  col_company_Id = ?
            ORDER  BY shift_date DESC, planned_timeIn DESC
            LIMIT  3
        ");
        $stmt->bind_param('ss', $carerId, $companyId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentShifts[] = $row;
        }
        $stmt->close();

        $conn->close();
    }
}

// ── Helper: format shift date for display ─────────────────────────────────────
function formatShiftDate(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('D, j M') : htmlspecialchars($raw);
}

// ── Helper: format time range label ──────────────────────────────────────────
function formatTimeRange(?string $in, ?string $out): string {
    if (!$in && !$out) return '—';
    $fmt = fn($t) => $t ? htmlspecialchars(substr($t, 0, 5)) : '?';
    return $fmt($in) . ' – ' . $fmt($out);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>StaffLinks Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
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

                    <div class="app-mark">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>

                    <div>
                        <h5 class="mb-0 fw-bold">StaffLinks</h5>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button class="chip border-0 position-relative" type="button" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="alert-dot">4</span>
                    </button>

                    <button class="chip border-0" id="darkModeBtn" type="button" aria-label="Toggle dark mode">
                        <i class="bi bi-moon-stars"></i>
                    </button>

                    <img class="profile-avatar" id="topbarAvatar"
                        src="https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg" alt="Profile Picture">
                </div>
            </div>

            <div class="mt-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h2 class="mb-1 fw-bold" id="greetingText">Good morning 👋</h2>
                        <div class="text-white-50">Here's your care work overview for today.</div>
                    </div>

                    <span class="chip">
                        <i class="bi bi-shield-check me-1"></i> NHS DBS Verified
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-container">
        <!--Hours this month-->
        <section class="stats-grid mb-4 fade-in-up">
            <article class="stat-card">
                <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
                <div class="stat-value" id="monthlyHoursValue">
                    <?= htmlspecialchars($monthlyDisplay) ?>
                </div>
                <div class="stat-label">Hours this month</div>
                <div class="progress mt-3" style="height:8px;background:rgba(201,74,87,.12)">
                    <div class="progress-bar" id="monthlyHoursBar"
                        style="width:<?= (int)($progressPct ?? 0) ?>%;background:linear-gradient(90deg,var(--accent),var(--accent2))">
                    </div>
                </div>
                <div class="small mt-1" style="color:var(--accent);opacity:.75">
                    <?= (int)($progressPct ?? 0) ?>% of 160h target
                </div>
            </article>

            <!--Total numbers of days shifts this month-->
            <article class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-calendar-event-fill"></i></div>
                <div class="stat-value"><?= htmlspecialchars($monthlyShiftDays) ?> days</div>
                <div class="stat-label">Shift days this month</div>
                <a href="calendar.php" class="small text-decoration-none mt-2 d-inline-block"
                    style="color:var(--accent)">
                    View calendar <i class="bi bi-arrow-right"></i>
                </a>
            </article>

            <!--Estimated monthly pay-->
            <article class="stat-card">
                <div class="stat-icon success"><i class="bi bi-wallet2"></i></div>
                <div class="stat-value"><?= htmlspecialchars($monthlyPay) ?></div>
                <div class="stat-label">Estimated monthly pay</div>
                <a href="calculator.php" class="small text-decoration-none mt-2 d-inline-block"
                    style="color:var(--accent)">
                    Open calculator <i class="bi bi-arrow-right"></i>
                </a>
            </article>

            <!--Leave days remaining-->
            <article class="stat-card">
                <div class="stat-icon info"><i class="bi bi-umbrella-fill"></i></div>
                <div class="stat-value"><?= htmlspecialchars($leaveRemaining) ?> days</div>
                <div class="stat-label">Leave days remaining</div>
                <a href="leave.php" class="small text-decoration-none mt-2 d-inline-block" style="color:var(--accent)">
                    Request leave <i class="bi bi-arrow-right"></i>
                </a>
            </article>
        </section>

        <section class="row g-4">
            <div class="col-12 col-xl-7">

                <!--Shift days calendar-->
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h4 class="fw-bold mb-0">Shift Days Calendar</h4>
                                <div class="small-muted">Your working days and urgent opportunities.</div>
                            </div>
                            <a href="calendar.php" class="text-decoration-none" style="color:var(--accent)">Open</a>
                        </div>

                        <div class="mini-calendar">
                            <?php
                            $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                            $today    = new DateTime();
                            $monday   = new DateTime('monday this week');

                            for ($i = 0; $i < 7; $i++):
                                $current  = clone $monday;
                                $current->modify("+{$i} days");
                                $dateKey  = $current->format('Y-m-d');
                                $isToday  = ($dateKey === $today->format('Y-m-d'));
                                $hasShift = isset($weekShiftDays[$dateKey]);

                                if ($isToday && $hasShift):
                                    $class = 'calendar-day alert-day';
                                elseif ($hasShift):
                                    $class = 'calendar-day active';
                                else:
                                    $class = 'calendar-day';
                                endif;
                            ?>
                            <div class="<?= $class ?>">
                                <?= $dayNames[$i] ?><br>
                                <strong><?= $current->format('j') ?></strong>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!--Open Shifts Marketplace-->
                <div class="card feature-card mb-4 fade-in-up">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div>
                                <h4 class="fw-bold mb-0">Open Shifts Marketplace</h4>
                                <div class="small-muted">Available shifts matched to your skills, location and
                                    availability.</div>
                            </div>
                            <a href="open-shifts.php" class="btn btn-sm text-white"
                                style="background:var(--accent);border-radius:999px">
                                View all shifts
                            </a>
                        </div>

                        <!--Dynamic open shifts grouped by col_run_name + Clientshift_Date-->
                        <div class="row g-3">
                            <?php if (empty($openShifts)): ?>
                            <div class="col-12">
                                <div class="text-center py-4 small-muted">
                                    <i class="bi bi-calendar-check fs-2 d-block mb-2 opacity-50"></i>
                                    No open shifts available right now. Check back soon.
                                </div>
                            </div>
                            <?php else: ?>
                            <?php foreach ($openShifts as $shift):
                                    $runName      = htmlspecialchars($shift['col_run_name']    ?? 'Unnamed Run');
                                    $areaLabel    = htmlspecialchars($shift['area_label']       ?? '');
                                    $shiftDateFmt = formatShiftDate($shift['Clientshift_Date']  ?? '');
                                    $timeRange    = formatTimeRange($shift['earliest_in'], $shift['latest_out']);

                                    $acceptUrl = 'accept-shift.php'
                                               . '?run='      . urlencode($shift['col_run_name']    ?? '')
                                               . '&date='     . urlencode($shift['Clientshift_Date'] ?? '')
                                               . '&carer_id=' . urlencode($carerId)
                                               . ($companyId !== ''
                                                   ? '&col_company_Id=' . urlencode($companyId)
                                                   : '');
                                ?>
                            <div class="col-md-6">
                                <div class="shift-market-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="fw-bold mb-1"><?= $runName ?></h5>
                                            <?php if ($areaLabel): ?>
                                            <div class="small-muted"><?= $areaLabel ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-success">Open shift</span>
                                    </div>

                                    <div class="visit-meta mb-3">
                                        <span class="meta-pill">
                                            <i class="bi bi-clock me-1"></i><?= $timeRange ?>
                                        </span>
                                        <span class="meta-pill">
                                            <i class="bi bi-calendar me-1"></i><?= $shiftDateFmt ?>
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?= $acceptUrl ?>" class="btn btn-sm text-white"
                                            style="background:var(--accent);border-radius:999px">
                                            Accept Shift
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <div class="emergency-alert fade-in-up mb-4">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                        <div>
                            <strong class="text-danger">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Emergency Shift Alert
                            </strong>
                            <div class="small-muted mt-1">
                                Night nurse urgently required at Willow Lodge, 22:00 – 06:00. Enhanced rate available.
                            </div>
                        </div>
                        <a href="emergency-shifts.php" class="btn btn-danger btn-sm" style="border-radius:999px">
                            Respond Now
                        </a>
                    </div>
                </div>

                <!-- ── UK Healthcare News (replaces Training & Compliance) ── -->
                <div class="card feature-card mb-4 fade-in-up">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">
                                    <i class="bi bi-newspaper me-2"></i>UK Healthcare News
                                </h4>
                                <div class="small-muted">Latest updates for carers &amp; nurses.</div>
                            </div>
                            <button class="btn btn-sm" id="newsRefreshBtn"
                                style="border-radius:999px;border:1px solid var(--bs-border-color);font-size:.78rem"
                                onclick="loadHealthcareNews()">
                                <i class="bi bi-arrow-clockwise me-1" id="newsRefreshIcon"></i>Refresh
                            </button>
                        </div>

                        <div id="healthcareNewsContainer">
                            <!-- Skeleton placeholders shown while news loads -->
                            <?php for ($s = 0; $s < 3; $s++): ?>
                            <div class="d-flex gap-3 p-3 mb-2 rounded-3 border placeholder-glow">
                                <span class="placeholder rounded-3" style="width:40px;height:40px;flex-shrink:0"></span>
                                <div class="flex-grow-1">
                                    <span class="placeholder col-3 mb-2 d-block" style="height:12px"></span>
                                    <span class="placeholder col-12 mb-1 d-block" style="height:14px"></span>
                                    <span class="placeholder col-6 d-block" style="height:11px"></span>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <!-- ── End UK Healthcare News ── -->

            </div>

            <div class="col-12 col-xl-5">

                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">My Profile</h4>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img id="profileCardPic"
                                src="https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg"
                                alt="Profile Picture"
                                style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid var(--accent)">
                            <div>
                                <div class="fw-bold fs-6" id="profileCardName">—</div>
                                <div class="small-muted" id="profileCardSpecialId"></div>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-envelope-fill" style="color:var(--accent);width:18px"></i>
                                <span class="small" id="profileCardEmail">—</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-telephone-fill" style="color:var(--accent);width:18px"></i>
                                <span class="small" id="profileCardPhone">—</span>
                            </div>
                        </div>

                        <a href="settings.php" class="btn btn-sm mt-3 text-white"
                            style="background:var(--accent);border-radius:999px">
                            <i class="bi bi-pencil me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">Quick Actions</h4>

                        <div class="row g-3">
                            <div class="col-6">
                                <a href="timesheet.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:var(--accent);border-radius:14px">
                                    <i class="bi bi-clock-fill d-block fs-3 mb-2"></i>
                                    <strong>Timesheet</strong>
                                    <small class="d-block opacity-75">Log hours</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="calendar.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:var(--accent2);border-radius:14px">
                                    <i class="bi bi-calendar2-week-fill d-block fs-3 mb-2"></i>
                                    <strong>Calendar</strong>
                                    <small class="d-block opacity-75">View events</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="leave.php" class="btn w-100 py-3 text-start text-dark"
                                    style="background:var(--warning);border-radius:14px">
                                    <i class="bi bi-umbrella-fill d-block fs-3 mb-2"></i>
                                    <strong>Leave</strong>
                                    <small class="d-block opacity-75">Request time off</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="calculator.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:#4A90E2;border-radius:14px">
                                    <i class="bi bi-calculator-fill d-block fs-3 mb-2"></i>
                                    <strong>Calculator</strong>
                                    <small class="d-block opacity-75">Tax estimate</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="open-shifts.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:#198754;border-radius:14px">
                                    <i class="bi bi-briefcase-fill d-block fs-3 mb-2"></i>
                                    <strong>Open Shifts</strong>
                                    <small class="d-block opacity-75">Find extra work</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="service-users.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:#fd7e14;border-radius:14px">
                                    <i class="bi bi-people d-block fs-3 mb-2"></i>
                                    <strong>Service Users</strong>
                                    <small class="d-block opacity-75">Manage service users</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="fw-bold mb-0">Staff Wellbeing</h4>
                            <span class="small-muted" style="font-size:.72rem">
                                <?= date('D, j M') ?>
                            </span>
                        </div>
                        <div class="wellbeing-card">
                            <?php if ($todayWellbeing !== ''): ?>
                            <?php
                                $wbConfig = [
                                    'Good'         => ['icon' => 'bi-emoji-smile-fill',  'colour' => '#198754', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'msg' => "Great keep it up! Your wellbeing matters."],
                                    'Okay'         => ['icon' => 'bi-emoji-neutral-fill', 'colour' => '#f59e0b', 'bg' => '#fffbeb', 'border' => '#fde68a', 'msg' => "Thanks for checking in. Take it one step at a time."],
                                    'Need support' => ['icon' => 'bi-emoji-frown-fill',   'colour' => '#dc2626', 'bg' => '#fff1f2', 'border' => '#fecdd3', 'msg' => "We're here for you. Please speak to your manager or wellbeing lead."],
                                ];
                                $wbCfg = $wbConfig[$todayWellbeing] ?? $wbConfig['Okay'];
                                ?>
                            <div
                                style="background:<?= $wbCfg['bg'] ?>;border:1.5px solid <?= $wbCfg['border'] ?>;border-radius:12px;padding:.9rem 1rem;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi <?= $wbCfg['icon'] ?>"
                                        style="color:<?= $wbCfg['colour'] ?>;font-size:1.4rem"></i>
                                    <strong style="color:<?= $wbCfg['colour'] ?>">
                                        Feeling <?= htmlspecialchars($todayWellbeing) ?> today
                                    </strong>
                                </div>
                                <div class="small-muted"><?= htmlspecialchars($wbCfg['msg']) ?></div>
                                <?php if ($todayWellbeing === 'Need support'): ?>
                                <a href="mailto:support@stafflinks.co.uk" class="btn btn-sm mt-2 text-white"
                                    style="background:#dc2626;border-radius:999px;font-size:.78rem">
                                    <i class="bi bi-envelope me-1"></i> Contact support
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="small-muted mt-2 text-center" style="font-size:.72rem">
                                <i class="bi bi-check-circle me-1 text-success"></i>
                                Check-in recorded for today. Come back tomorrow!
                            </div>
                            <?php else: ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <i class="bi bi-heart-fill text-danger fs-3"></i>
                                <div>
                                    <strong>How are you feeling today?</strong>
                                    <div class="small-muted">Check in after long or difficult shifts.</div>
                                </div>
                            </div>
                            <form method="POST"
                                action="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId ? '&col_company_Id='.urlencode($companyId) : '' ?>"
                                id="wellbeingForm">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" name="wellbeing_mood" value="Good"
                                        class="btn btn-sm btn-outline-success wellbeing-btn"
                                        style="border-radius:999px">
                                        <i class="bi bi-emoji-smile me-1"></i>Good
                                    </button>
                                    <button type="submit" name="wellbeing_mood" value="Okay"
                                        class="btn btn-sm btn-outline-warning wellbeing-btn"
                                        style="border-radius:999px">
                                        <i class="bi bi-emoji-neutral me-1"></i>Okay
                                    </button>
                                    <button type="submit" name="wellbeing_mood" value="Need support"
                                        class="btn btn-sm btn-outline-danger wellbeing-btn" style="border-radius:999px">
                                        <i class="bi bi-emoji-frown me-1"></i>Need support
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="fw-bold mb-0">Past Shift Records</h4>
                            <a href="past-shifts.php?carer_id=<?= urlencode($carerId) ?><?= $companyId ? '&col_company_Id='.urlencode($companyId) : '' ?>"
                                class="text-decoration-none" style="color:var(--accent)">View all</a>
                        </div>
                        <?php if (empty($recentShifts)): ?>
                        <div class="text-center py-3 small-muted">
                            <i class="bi bi-clock-history d-block fs-4 mb-1 opacity-50"></i>
                            No shift records yet.
                        </div>
                        <?php else: ?>
                        <?php foreach ($recentShifts as $idx => $rs):
                                $rsStatus = strtolower(trim($rs['col_call_status'] ?? ''));
                                $rsIcon   = $rsStatus === 'completed'
                                            ? 'bi-check-circle-fill text-success'
                                            : ($rsStatus === 'not completed'
                                               ? 'bi-x-circle-fill text-danger'
                                               : 'bi-clock-fill text-primary');

                                $rsDate = '';
                                $d = DateTime::createFromFormat('Y-m-d', $rs['shift_date'] ?? '');
                                if ($d) $rsDate = $d->format('D, j M');

                                $rsIn  = $rs['planned_timeIn']  ? substr($rs['planned_timeIn'],  0, 5) : '';
                                $rsOut = $rs['planned_timeOut'] ? substr($rs['planned_timeOut'], 0, 5) : '';
                                $rsTime = ($rsIn && $rsOut) ? "$rsIn – $rsOut" : ($rsIn ?: ($rsOut ?: '—'));

                                $rsDuration = '';
                                if ($rsIn && $rsOut) {
                                    $tI = DateTime::createFromFormat('H:i', $rsIn);
                                    $tO = DateTime::createFromFormat('H:i', $rsOut);
                                    if ($tI && $tO) {
                                        if ($tO < $tI) $tO->modify('+1 day');
                                        $diff = $tO->diff($tI);
                                        $rsDuration = $diff->h . 'h'
                                            . ($diff->i > 0 ? ' ' . $diff->i . 'm' : '');
                                    }
                                }

                                $isLast = ($idx === count($recentShifts) - 1);
                            ?>
                        <div class="d-flex align-items-center gap-3 py-2 <?= $isLast ? '' : 'border-bottom' ?>">
                            <i class="bi <?= $rsIcon ?>"></i>
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($rs['client_name'] ?? 'Unknown') ?></strong>
                                <div class="small-muted">
                                    <?= htmlspecialchars($rsDate) ?>
                                    <?= $rsDate && $rsTime !== '—' ? ' • ' : '' ?>
                                    <?= htmlspecialchars($rsTime) ?>
                                </div>
                            </div>
                            <?php if ($rsDuration): ?>
                            <span class="small-muted"><?= htmlspecialchars($rsDuration) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="alerts-container fade-in-up">
                    <div class="alert-item">
                        <strong>
                            <i class="bi bi-megaphone-fill me-1" style="color:var(--accent2)"></i>
                            Announcement
                        </strong>
                        <div class="small-muted">
                            Don't forget to log your timesheet at the end of each shift.
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <?php include 'new-footer.php'; ?>

    <script>
    // ── UK Healthcare News loader ─────────────────────────────────────────────
    const NEWS_TAG_MAP = {
        nhs: {
            label: 'NHS',
            bg: '#E6F1FB',
            color: '#0C447C',
            iconBg: '#E6F1FB',
            iconColor: '#185FA5',
            icon: 'bi-building'
        },
        workforce: {
            label: 'Workforce',
            bg: '#E1F5EE',
            color: '#085041',
            iconBg: '#E1F5EE',
            iconColor: '#0F6E56',
            icon: 'bi-people-fill'
        },
        policy: {
            label: 'Policy',
            bg: '#FAEEDA',
            color: '#633806',
            iconBg: '#FAEEDA',
            iconColor: '#854F0B',
            icon: 'bi-file-text-fill'
        },
        safety: {
            label: 'Safety',
            bg: '#FCEBEB',
            color: '#791F1F',
            iconBg: '#FCEBEB',
            iconColor: '#A32D2D',
            icon: 'bi-shield-fill-check'
        },
        pay: {
            label: 'Pay',
            bg: '#EEEDFE',
            color: '#3C3489',
            iconBg: '#EEEDFE',
            iconColor: '#534AB7',
            icon: 'bi-cash-stack'
        },
    };

    function escH(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    async function loadHealthcareNews() {
        const container = document.getElementById('healthcareNewsContainer');
        const btn = document.getElementById('newsRefreshBtn');
        const icon = document.getElementById('newsRefreshIcon');

        btn.disabled = true;
        icon.className = 'bi bi-arrow-clockwise me-1 news-spin';

        // Inject spin keyframe once
        if (!document.getElementById('newsSpinStyle')) {
            const s = document.createElement('style');
            s.id = 'newsSpinStyle';
            s.textContent =
                '.news-spin{animation:newsSpin .7s linear infinite}@keyframes newsSpin{to{transform:rotate(360deg)}}';
            document.head.appendChild(s);
        }

        // Show skeleton while loading
        container.innerHTML = `
            ${[1,2,3].map(() => `
            <div class="d-flex gap-3 p-3 mb-2 rounded-3 border placeholder-glow">
                <span class="placeholder rounded-3" style="width:40px;height:40px;flex-shrink:0"></span>
                <div class="flex-grow-1">
                    <span class="placeholder col-3 mb-2 d-block" style="height:12px"></span>
                    <span class="placeholder col-12 mb-1 d-block" style="height:14px"></span>
                    <span class="placeholder col-6 d-block" style="height:11px"></span>
                </div>
            </div>`).join('')}`;

        try {
            const resp = await fetch('https://api.anthropic.com/v1/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    model: 'claude-sonnet-4-20250514',
                    max_tokens: 1000,
                    tools: [{
                        type: 'web_search_20250305',
                        name: 'web_search'
                    }],
                    messages: [{
                        role: 'user',
                        content: `Search for the 3 most recent UK healthcare news stories (published within the last 7 days) specifically relevant to carers and nurses in the UK. Focus on topics like NHS staffing, pay, working conditions, training requirements, CQC updates, or healthcare policy changes. Return ONLY valid JSON (no markdown, no preamble, no backticks) in this exact format:
[
  { "title": "...", "summary": "One sentence summary max 120 chars.", "source": "Source name", "date": "e.g. 28 May 2025", "url": "https://...", "category": "nhs|workforce|policy|safety|pay" },
  { ... },
  { ... }
]
Use category values exactly as listed. If no URL is available use "".`
                    }]
                })
            });

            const data = await resp.json();
            const fullTxt = data.content.map(i => i.text || '').filter(Boolean).join('');
            const clean = fullTxt.replace(/```json|```/g, '').trim();
            const s = clean.indexOf('[');
            const e = clean.lastIndexOf(']');
            const articles = JSON.parse(clean.slice(s, e + 1));

            container.innerHTML = articles.map(item => {
                const cat = NEWS_TAG_MAP[item.category] || NEWS_TAG_MAP.nhs;
                return `
                <div class="d-flex gap-3 p-3 mb-2 rounded-3 border"
                     style="border-color:rgba(0,0,0,.07)!important;transition:background .15s"
                     onmouseover="this.style.background='rgba(0,0,0,.025)'"
                     onmouseout="this.style.background=''">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:40px;height:40px;background:${cat.iconBg}">
                        <i class="bi ${cat.icon}" style="color:${cat.iconColor};font-size:1.1rem"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="badge mb-1"
                              style="background:${cat.bg};color:${cat.color};font-size:.68rem;font-weight:500;border-radius:999px;padding:3px 9px">
                            ${escH(cat.label)}
                        </span>
                        <div class="fw-bold" style="font-size:.85rem;line-height:1.35;margin-bottom:3px">
                            ${escH(item.title)}
                        </div>
                        <div class="small-muted mb-1" style="font-size:.78rem">${escH(item.summary)}</div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                            <span style="font-size:.72rem;color:#888">${escH(item.source)} &bull; ${escH(item.date)}</span>
                            ${item.url
                                ? `<a href="${escH(item.url)}" target="_blank" rel="noopener"
                                      class="text-decoration-none"
                                      style="font-size:.72rem;color:var(--accent)">
                                      Read <i class="bi bi-box-arrow-up-right" style="font-size:.65rem"></i>
                                   </a>`
                                : ''}
                        </div>
                    </div>
                </div>`;
            }).join('');

        } catch (err) {
            container.innerHTML = `
            <div class="text-center py-4 small-muted">
                <i class="bi bi-wifi-off d-block fs-4 mb-2 opacity-50"></i>
                Could not load news right now.
                <a href="#" onclick="loadHealthcareNews();return false;" style="color:var(--accent)">Try again</a>
            </div>`;
        }

        btn.disabled = false;
        icon.className = 'bi bi-arrow-clockwise me-1';
    }

    // ── User session & profile ────────────────────────────────────────────────
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

        // ── Ensure ?carer_id= and ?col_company_Id= params match the logged-in user ──
        (function ensureParams() {
            const specialId = user.user_special_Id;
            const companyId = user.col_company_Id;
            const url = new URL(window.location.href);
            let changed = false;

            if (specialId && url.searchParams.get('carer_id') !== String(specialId)) {
                url.searchParams.set('carer_id', specialId);
                changed = true;
            }
            if (companyId && url.searchParams.get('col_company_Id') !== String(companyId)) {
                url.searchParams.set('col_company_Id', companyId);
                changed = true;
            }
            if (changed) {
                window.location.replace(url.toString());
            }
        })();

        function firstName(fullName) {
            if (!fullName) return 'there';
            return fullName.trim().split(/\s+/)[0];
        }

        function timeGreeting() {
            const h = new Date().getHours();
            if (h < 12) return 'Good morning';
            if (h < 18) return 'Good afternoon';
            return 'Good evening';
        }

        function avatarSrc(dp) {
            if (!dp || !dp.trim()) return DEFAULT_AVATAR;
            const val = dp.trim();
            if (val.startsWith('http://') || val.startsWith('https://')) return val;
            if (val.startsWith('uploads/') || val.startsWith('/uploads/')) {
                return 'https://admin.stafflinks.co.uk/' + val.replace(/^\//, '');
            }
            return UPLOAD_BASE + val;
        }

        function withFallback(el) {
            if (!el) return;
            el.onerror = function() {
                if (this.src !== DEFAULT_AVATAR) this.src = DEFAULT_AVATAR;
            };
        }

        function setText(id, value, fallback) {
            const el = document.getElementById(id);
            if (el) el.textContent = value || fallback || '—';
        }

        function setSrc(id, src, altText) {
            const el = document.getElementById(id);
            if (!el) return;
            el.src = src;
            if (altText) el.alt = altText;
        }

        const avatar = avatarSrc(user.team_dp);
        const name = user.user_fullname || '';
        const email = user.user_email_address || '';
        const phone = user.user_phone_number || '';

        setSrc('navProfilePic', avatar, name);
        withFallback(document.getElementById('navProfilePic'));
        setText('navFullName', name);
        setText('navEmail', email);
        setText('navPhone', phone);

        setSrc('topbarAvatar', avatar, name);
        withFallback(document.getElementById('topbarAvatar'));

        const greetingEl = document.getElementById('greetingText');
        if (greetingEl) {
            greetingEl.textContent = `${timeGreeting()}, ${firstName(name)} 👋`;
        }

        setSrc('profileCardPic', avatar, name);
        withFallback(document.getElementById('profileCardPic'));
        setText('profileCardName', name);
        setText('profileCardSpecialId', user.user_special_Id ? `Your ID: ${user.user_special_Id}` : '', '');
        setText('profileCardEmail', email);
        setText('profileCardPhone', phone);

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sessionStorage.removeItem('loggedInUser');
                sessionStorage.removeItem('loggedInUserId');
                window.location.href = './';
            });
        }

        // Load healthcare news after session is confirmed valid
        loadHealthcareNews();
    })();
    </script>

</body>

</html>