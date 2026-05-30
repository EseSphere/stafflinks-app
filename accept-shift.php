<?php
// ─────────────────────────────────────────────────────────────────────────────
// accept-shift.php
// Reads: ?run=&date=&carer_id=&col_company_Id=
// On POST: assigns the carer to every unassigned call in the run/date group.
// ─────────────────────────────────────────────────────────────────────────────

$runName   = isset($_GET['run'])            ? trim($_GET['run'])            : '';
$shiftDate = isset($_GET['date'])           ? trim($_GET['date'])           : '';
$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';

$successMsg = '';
$errorMsg   = '';
$shiftRows  = [];
$carerName  = '';

// ── Validate required params ──────────────────────────────────────────────────
if ($runName === '' || $shiftDate === '' || $carerId === '') {
    $errorMsg = 'Invalid request. Please return to the dashboard and try again.';
} else {
    include_once 'dbconnect.php';

    if ($conn->connect_error) {
        $errorMsg = 'Database connection failed. Please try again later.';
    } else {

        // ── Fetch carer's full name from tbl_team_account ─────────────────────
        $stmt = $conn->prepare("
            SELECT user_fullname
            FROM   tbl_team_account
            WHERE  user_special_Id = ?
            LIMIT  1
        ");
        $stmt->bind_param('s', $carerId);
        $stmt->execute();
        $stmt->bind_result($carerName);
        $stmt->fetch();
        $stmt->close();

        $carerName = $carerName ? trim($carerName) : '';

        // ── POST: assign the carer to all matching unassigned calls ───────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_accept'])) {

            if ($carerName === '') {
                $errorMsg = 'Your carer profile could not be found. Please contact your manager.';
            } else {
                // Build UPDATE — scope by company if available
                if ($companyId !== '') {
                    $stmt = $conn->prepare("
                        UPDATE tbl_schedule_calls
                        SET    first_carer              = ?,
                               first_carer_Id           = ?,
                               assigned_carer_unique_id = ?
                        WHERE  col_run_name  = ?
                          AND  Clientshift_Date = ?
                          AND  col_company_Id   = ?
                          AND  (first_carer              IS NULL OR first_carer              = '')
                          AND  (first_carer_Id           IS NULL OR first_carer_Id           = '')
                          AND  (assigned_carer_unique_id IS NULL OR assigned_carer_unique_id = '')
                          AND  call_status = 'Scheduled'
                    ");
                    $stmt->bind_param('ssssss', $carerName, $carerId, $carerId, $runName, $shiftDate, $companyId);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE tbl_schedule_calls
                        SET    first_carer              = ?,
                               first_carer_Id           = ?,
                               assigned_carer_unique_id = ?
                        WHERE  col_run_name    = ?
                          AND  Clientshift_Date = ?
                          AND  (first_carer              IS NULL OR first_carer              = '')
                          AND  (first_carer_Id           IS NULL OR first_carer_Id           = '')
                          AND  (assigned_carer_unique_id IS NULL OR assigned_carer_unique_id = '')
                          AND  call_status = 'Scheduled'
                    ");
                    $stmt->bind_param('sssss', $carerName, $carerId, $carerId, $runName, $shiftDate);
                }

                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                if ($affected > 0) {
                    $successMsg = "You've successfully accepted this shift. It's now added to your rota.";
                } else {
                    $errorMsg = 'This shift may have already been taken. Please check the marketplace for other available shifts.';
                }
            }
        }

        // ── Fetch shift detail rows for the confirmation preview ──────────────
        if ($companyId !== '') {
            $stmt = $conn->prepare("
                SELECT client_name, client_area, care_calls,
                       dateTime_in, dateTime_out, col_run_name,
                       col_required_carers, Clientshift_Date, pay_rate,
                       first_carer_Id
                FROM   tbl_schedule_calls
                WHERE  col_run_name    = ?
                  AND  Clientshift_Date = ?
                  AND  col_company_Id   = ?
                ORDER BY dateTime_in ASC
            ");
            $stmt->bind_param('sss', $runName, $shiftDate, $companyId);
        } else {
            $stmt = $conn->prepare("
                SELECT client_name, client_area, care_calls,
                       dateTime_in, dateTime_out, col_run_name,
                       col_required_carers, Clientshift_Date, pay_rate,
                       first_carer_Id
                FROM   tbl_schedule_calls
                WHERE  col_run_name    = ?
                  AND  Clientshift_Date = ?
                ORDER BY dateTime_in ASC
            ");
            $stmt->bind_param('ss', $runName, $shiftDate);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $shiftRows[] = $row;
        }
        $stmt->close();
        $conn->close();
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtDate(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('l, j F Y') : htmlspecialchars($raw);
}
function fmtTime(?string $t): string {
    if (!$t) return '—';
    return htmlspecialchars(substr(trim($t), 0, 5));
}

// Aggregate earliest/latest across all rows
$earliestIn = null;
$latestOut  = null;
$areaLabel  = '';
foreach ($shiftRows as $r) {
    $in  = $r['dateTime_in']  ? substr($r['dateTime_in'],  0, 5) : null;
    $out = $r['dateTime_out'] ? substr($r['dateTime_out'], 0, 5) : null;
    if ($in  && ($earliestIn  === null || $in  < $earliestIn))  $earliestIn  = $in;
    if ($out && ($latestOut   === null || $out > $latestOut))   $latestOut   = $out;
    if (!$areaLabel && !empty($r['client_area'])) $areaLabel = $r['client_area'];
}

$isAlreadyAccepted = !empty($shiftRows) && !empty($shiftRows[0]['first_carer_Id']);
$totalCalls        = count($shiftRows);

// Build self-referencing POST URL (keep all GET params)
$postUrl = 'accept-shift.php'
         . '?run='      . urlencode($runName)
         . '&date='     . urlencode($shiftDate)
         . '&carer_id=' . urlencode($carerId)
         . ($companyId !== '' ? '&col_company_Id=' . urlencode($companyId) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Accept Shift – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
        /* ── Page shell ─────────────────────────────────────────────── */
        .accept-container {
            max-width: 780px;
            margin: 0 auto;
            padding: 1.5rem 1rem 5rem;
        }

        /* ── Shift hero card ─────────────────────────────────────────── */
        .shift-hero {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent2, #e05c6e) 100%);
            border-radius: var(--radius, 18px);
            color: #fff;
            padding: 2rem 1.75rem 1.75rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .shift-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='28'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            pointer-events: none;
        }
        .shift-hero .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 999px;
            padding: .25rem .75rem;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .04em;
            margin-bottom: 1rem;
        }
        .shift-hero h2 {
            font-size: 1.65rem;
            font-weight: 800;
            margin-bottom: .25rem;
        }
        .shift-hero .hero-sub {
            opacity: .8;
            font-size: .9rem;
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-top: 1.25rem;
        }
        .hero-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 999px;
            padding: .3rem .85rem;
            font-size: .82rem;
            font-weight: 500;
        }

        /* ── Call breakdown list ─────────────────────────────────────── */
        .calls-card {
            background: var(--card-bg, #fff);
            border-radius: var(--radius, 18px);
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .calls-card-header {
            padding: 1rem 1.25rem .75rem;
            border-bottom: 1px solid var(--border-color, #f0f0f0);
        }
        .call-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .85rem 1.25rem;
            border-bottom: 1px solid var(--border-color, #f0f0f0);
            transition: background .15s;
        }
        .call-row:last-child { border-bottom: none; }
        .call-row:hover { background: var(--hover-bg, #fafafa); }
        .call-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(201,74,87,.1);
            color: var(--accent, #c94a57);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .call-client { font-weight: 600; font-size: .92rem; }
        .call-meta   { font-size: .78rem; color: var(--text-muted, #888); margin-top: .1rem; }
        .call-time   {
            margin-left: auto;
            font-size: .82rem;
            font-weight: 600;
            color: var(--accent, #c94a57);
            white-space: nowrap;
        }

        /* ── Confirm panel ───────────────────────────────────────────── */
        .confirm-panel {
            background: var(--card-bg, #fff);
            border-radius: var(--radius, 18px);
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            padding: 1.5rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        .confirm-checklist {
            list-style: none;
            padding: 0;
            margin: 1rem 0 0;
        }
        .confirm-checklist li {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            font-size: .88rem;
            padding: .4rem 0;
            color: var(--text-muted, #555);
        }
        .confirm-checklist li i {
            color: #198754;
            margin-top: .15rem;
            flex-shrink: 0;
        }

        /* ── Action buttons ──────────────────────────────────────────── */
        .btn-accept {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent2, #e05c6e) 100%);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: .75rem 2.25rem;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: .02em;
            transition: opacity .2s, transform .15s;
        }
        .btn-accept:hover { opacity: .88; transform: translateY(-1px); color: #fff; }
        .btn-accept:active { transform: translateY(0); }

        /* ── Success / Error states ──────────────────────────────────── */
        .result-card {
            border-radius: var(--radius, 18px);
            padding: 2.5rem 1.75rem;
            text-align: center;
        }
        .result-card.success { background: #f0fdf4; border: 1.5px solid #bbf7d0; }
        .result-card.error   { background: #fff1f2; border: 1.5px solid #fecdd3; }
        .result-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.25rem;
        }
        .result-icon.success { background: #dcfce7; color: #16a34a; }
        .result-icon.error   { background: #ffe4e6; color: #dc2626; }

        /* ── Already-taken banner ────────────────────────────────────── */
        .taken-banner {
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-radius: 12px;
            padding: .85rem 1rem;
            font-size: .88rem;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.25rem;
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
                    <button class="chip border-0 position-relative" type="button" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="alert-dot">4</span>
                    </button>
                    <button class="chip border-0" id="darkModeBtn" type="button" aria-label="Toggle dark mode">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <img class="profile-avatar" id="topbarAvatar"
                        src="https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg" alt="Profile">
                </div>
            </div>
            <div class="mt-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="javascript:history.back()"
                       class="chip border-0 text-white text-decoration-none d-flex align-items-center gap-1"
                       style="font-size:.85rem">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <div>
                        <h2 class="mb-0 fw-bold">Accept Shift</h2>
                        <div class="text-white-50" style="font-size:.85rem">Review and confirm your shift</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="accept-container fade-in-up">

        <?php if ($errorMsg !== '' && empty($shiftRows)): ?>
            <!-- Hard error: bad params or DB failure -->
            <div class="result-card error">
                <div class="result-icon error"><i class="bi bi-x-circle-fill"></i></div>
                <h4 class="fw-bold mb-2">Something went wrong</h4>
                <p class="mb-4" style="color:#666"><?= htmlspecialchars($errorMsg) ?></p>
                <a href="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId !== '' ? '&col_company_Id=' . urlencode($companyId) : '' ?>"
                   class="btn btn-accept">Back to Dashboard</a>
            </div>

        <?php elseif ($successMsg !== ''): ?>
            <!-- ✅ Accepted successfully -->
            <div class="result-card success mb-4">
                <div class="result-icon success"><i class="bi bi-check-circle-fill"></i></div>
                <h4 class="fw-bold mb-2">Shift Accepted!</h4>
                <p class="mb-0" style="color:#166534"><?= htmlspecialchars($successMsg) ?></p>
            </div>

            <!-- Summary of what was accepted -->
            <div class="shift-hero">
                <div class="hero-badge">
                    <i class="bi bi-check2-all"></i> Confirmed
                </div>
                <h2><?= htmlspecialchars($runName) ?></h2>
                <?php if ($areaLabel): ?>
                    <div class="hero-sub"><?= htmlspecialchars($areaLabel) ?></div>
                <?php endif; ?>
                <div class="hero-meta">
                    <span class="hero-meta-pill">
                        <i class="bi bi-clock"></i>
                        <?= htmlspecialchars($earliestIn ?? '—') ?> – <?= htmlspecialchars($latestOut ?? '—') ?>
                    </span>
                    <span class="hero-meta-pill">
                        <i class="bi bi-calendar"></i>
                        <?= fmtDate($shiftDate) ?>
                    </span>
                    <span class="hero-meta-pill">
                        <i class="bi bi-journal-text"></i>
                        <?= $totalCalls ?> call<?= $totalCalls !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <div class="d-flex gap-3 flex-wrap">
                <a href="rota.php?carer_id=<?= urlencode($carerId) ?><?= $companyId !== '' ? '&col_company_Id=' . urlencode($companyId) : '' ?>"
                   class="btn btn-accept">
                    <i class="bi bi-calendar2-week me-2"></i>View My Rota
                </a>
                <a href="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId !== '' ? '&col_company_Id=' . urlencode($companyId) : '' ?>"
                   class="btn btn-sm btn-outline-secondary" style="border-radius:999px;padding:.65rem 1.5rem">
                    Back to Dashboard
                </a>
            </div>

        <?php elseif ($errorMsg !== ''): ?>
            <!-- POST error (shift already taken etc.) -->
            <div class="result-card error mb-4">
                <div class="result-icon error"><i class="bi bi-exclamation-circle-fill"></i></div>
                <h4 class="fw-bold mb-2">Could Not Accept Shift</h4>
                <p class="mb-4" style="color:#991b1b"><?= htmlspecialchars($errorMsg) ?></p>
                <a href="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId !== '' ? '&col_company_Id=' . urlencode($companyId) : '' ?>"
                   class="btn btn-accept">Back to Dashboard</a>
            </div>

        <?php else: ?>
            <!-- ── Confirmation screen ──────────────────────────────── -->

            <?php if ($isAlreadyAccepted): ?>
                <div class="taken-banner">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    This shift has already been accepted by another carer. You are viewing it in read-only mode.
                </div>
            <?php endif; ?>

            <!-- Hero -->
            <div class="shift-hero">
                <div class="hero-badge">
                    <i class="bi bi-briefcase-fill"></i> Open Shift
                </div>
                <h2><?= htmlspecialchars($runName) ?></h2>
                <?php if ($areaLabel): ?>
                    <div class="hero-sub"><?= htmlspecialchars($areaLabel) ?></div>
                <?php endif; ?>
                <div class="hero-meta">
                    <?php if ($earliestIn && $latestOut): ?>
                        <span class="hero-meta-pill">
                            <i class="bi bi-clock"></i>
                            <?= htmlspecialchars($earliestIn) ?> – <?= htmlspecialchars($latestOut) ?>
                        </span>
                    <?php endif; ?>
                    <span class="hero-meta-pill">
                        <i class="bi bi-calendar"></i>
                        <?= fmtDate($shiftDate) ?>
                    </span>
                    <span class="hero-meta-pill">
                        <i class="bi bi-journal-text"></i>
                        <?= $totalCalls ?> call<?= $totalCalls !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>

            <!-- Call breakdown -->
            <?php if (!empty($shiftRows)): ?>
                <div class="calls-card mb-4">
                    <div class="calls-card-header">
                        <h5 class="fw-bold mb-0">Calls in this shift</h5>
                        <div class="small-muted">All visits you will be responsible for</div>
                    </div>
                    <?php foreach ($shiftRows as $i => $row):
                        $careCall  = htmlspecialchars(ucfirst($row['care_calls'] ?? 'Visit'));
                        $clientNm  = htmlspecialchars($row['client_name']  ?? 'Unknown');
                        $clientAr  = htmlspecialchars($row['client_area']  ?? '');
                        $timeIn    = fmtTime($row['dateTime_in']);
                        $timeOut   = fmtTime($row['dateTime_out']);
                        $payRate   = htmlspecialchars($row['pay_rate'] ?? '');

                        // Icon per care call type
                        $callIcons = [
                            'morning'       => 'bi-sunrise-fill',
                            'lunch'         => 'bi-cup-hot-fill',
                            'tea'           => 'bi-cup-fill',
                            'bed'           => 'bi-moon-stars-fill',
                            'extra morning' => 'bi-sunrise',
                            'extra lunch'   => 'bi-cup-hot',
                            'extra tea'     => 'bi-cup',
                            'extra bed'     => 'bi-moon-stars',
                        ];
                        $iconClass = $callIcons[strtolower(trim($row['care_calls'] ?? ''))] ?? 'bi-heart-pulse-fill';
                    ?>
                        <div class="call-row">
                            <div class="call-icon">
                                <i class="bi <?= $iconClass ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="call-client"><?= $clientNm ?></div>
                                <div class="call-meta">
                                    <?= $careCall ?>
                                    <?php if ($clientAr): ?> · <?= $clientAr ?><?php endif; ?>
                                    <?php if ($payRate): ?> · <span style="color:var(--accent)"><?= $payRate ?></span><?php endif; ?>
                                </div>
                            </div>
                            <div class="call-time"><?= $timeIn ?> – <?= $timeOut ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Confirm panel -->
            <?php if (!$isAlreadyAccepted): ?>
                <div class="confirm-panel">
                    <h5 class="fw-bold mb-0">Before you confirm</h5>
                    <ul class="confirm-checklist">
                        <li><i class="bi bi-check-circle-fill"></i>
                            You are available for the full shift window shown above.</li>
                        <li><i class="bi bi-check-circle-fill"></i>
                            You have the necessary training and qualifications for these clients.</li>
                        <li><i class="bi bi-check-circle-fill"></i>
                            Accepting this shift will assign it to your rota immediately.</li>
                        <li><i class="bi bi-check-circle-fill"></i>
                            Contact your manager if you need to cancel after accepting.</li>
                    </ul>

                    <form method="POST" action="<?= htmlspecialchars($postUrl) ?>" class="mt-4 d-flex gap-3 flex-wrap align-items-center">
                        <button type="submit" name="confirm_accept" value="1" class="btn btn-accept">
                            <i class="bi bi-check2-circle me-2"></i>Confirm & Accept Shift
                        </button>
                        <a href="javascript:history.back()"
                           class="btn btn-sm btn-outline-secondary" style="border-radius:999px;padding:.65rem 1.5rem">
                            Cancel
                        </a>
                    </form>
                </div>
            <?php else: ?>
                <div class="d-flex gap-3 flex-wrap mt-2">
                    <a href="open-shifts.php?carer_id=<?= urlencode($carerId) ?><?= $companyId !== '' ? '&col_company_Id=' . urlencode($companyId) : '' ?>"
                       class="btn btn-accept">
                        <i class="bi bi-briefcase me-2"></i>Browse Other Shifts
                    </a>
                    <a href="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId !== '' ? '&col_company_Id=' . urlencode($companyId) : '' ?>"
                       class="btn btn-sm btn-outline-secondary" style="border-radius:999px;padding:.65rem 1.5rem">
                        Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <?php include 'new-footer.php'; ?>

    <script>
    (function () {
        const DEFAULT_AVATAR = 'https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg';
        const UPLOAD_BASE    = 'https://admin.stafflinks.co.uk/uploads/team_dp/';

        const raw = sessionStorage.getItem('loggedInUser');
        if (!raw) { window.location.href = './'; return; }

        let user;
        try { user = JSON.parse(raw); }
        catch (e) { sessionStorage.clear(); window.location.href = './'; return; }

        function avatarSrc(dp) {
            if (!dp || !dp.trim()) return DEFAULT_AVATAR;
            const v = dp.trim();
            if (v.startsWith('http://') || v.startsWith('https://')) return v;
            if (v.startsWith('uploads/') || v.startsWith('/uploads/'))
                return 'https://admin.stafflinks.co.uk/' + v.replace(/^\//, '');
            return UPLOAD_BASE + v;
        }
        function withFallback(el) {
            if (!el) return;
            el.onerror = function () { if (this.src !== DEFAULT_AVATAR) this.src = DEFAULT_AVATAR; };
        }
        function setSrc(id, src, alt) {
            const el = document.getElementById(id);
            if (!el) return;
            el.src = src;
            if (alt) el.alt = alt;
        }
        function setText(id, val, fb) {
            const el = document.getElementById(id);
            if (el) el.textContent = val || fb || '—';
        }

        const avatar = avatarSrc(user.team_dp);
        const name   = user.user_fullname || '';

        setSrc('topbarAvatar', avatar, name);
        withFallback(document.getElementById('topbarAvatar'));
        setSrc('navProfilePic', avatar, name);
        withFallback(document.getElementById('navProfilePic'));
        setText('navFullName', name);
        setText('navEmail',    user.user_email_address || '');
        setText('navPhone',    user.user_phone_number  || '');

        // Guard: redirect if carer_id in URL doesn't match session
        (function guardCarer() {
            const url       = new URL(window.location.href);
            const urlCarer  = url.searchParams.get('carer_id');
            const sessId    = String(user.user_special_Id || '');
            if (sessId && urlCarer && urlCarer !== sessId) {
                url.searchParams.set('carer_id', sessId);
                window.location.replace(url.toString());
            }
        })();

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function (e) {
                e.preventDefault();
                sessionStorage.removeItem('loggedInUser');
                sessionStorage.removeItem('loggedInUserId');
                window.location.href = './';
            });
        }
    })();
    </script>

</body>
</html>