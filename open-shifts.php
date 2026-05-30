<?php
// ─────────────────────────────────────────────────────────────────────────────
// open-shifts.php  –  Full Open Shifts Marketplace
// URL params: ?carer_id=&col_company_Id=&area=&date_filter=&search=&page=
// Displays ALL unassigned Scheduled shifts grouped by col_run_name + Clientshift_Date,
// scoped to the carer's company. Supports search, area filter, date filter & pagination.
// ─────────────────────────────────────────────────────────────────────────────

$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';
$search    = isset($_GET['search'])         ? trim($_GET['search'])         : '';
$areaFilter= isset($_GET['area'])           ? trim($_GET['area'])           : '';
$dateFilter= isset($_GET['date_filter'])    ? trim($_GET['date_filter'])    : '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

$openShifts  = [];
$areaOptions = [];
$totalGroups = 0;
$totalPages  = 1;
$todayStr    = date('Y-m-d');

if ($carerId !== '') {
    include_once 'dbconnect.php';

    if (!$conn->connect_error) {

        // ── Build WHERE clauses shared between count + data queries ───────────
        // Always: unassigned + scheduled + future/today + company scope
        $whereParts = [
            "(first_carer              IS NULL OR first_carer              = '')",
            "(first_carer_Id           IS NULL OR first_carer_Id           = '')",
            "(assigned_carer_unique_id IS NULL OR assigned_carer_unique_id = '')",
            "call_status = 'Scheduled'",
            "STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') >= ?",
        ];
        $bindTypes  = 's';
        $bindValues = [$todayStr];

        if ($companyId !== '') {
            $whereParts[]  = 'col_company_Id = ?';
            $bindTypes    .= 's';
            $bindValues[]  = $companyId;
        }
        if ($areaFilter !== '') {
            $whereParts[]  = 'client_area = ?';
            $bindTypes    .= 's';
            $bindValues[]  = $areaFilter;
        }
        if ($search !== '') {
            $whereParts[]  = '(col_run_name LIKE ? OR client_area LIKE ?)';
            $bindTypes    .= 'ss';
            $like          = '%' . $search . '%';
            $bindValues[]  = $like;
            $bindValues[]  = $like;
        }
        if ($dateFilter !== '') {
            $whereParts[]  = 'Clientshift_Date = ?';
            $bindTypes    .= 's';
            $bindValues[]  = $dateFilter;
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $whereParts);

        // ── Fetch distinct area values for the filter dropdown ─────────────────
        $areaStmt = $conn->prepare("
            SELECT DISTINCT client_area
            FROM   tbl_schedule_calls
            $whereSQL
            ORDER BY client_area ASC
        ");
        $areaStmt->bind_param($bindTypes, ...$bindValues);
        $areaStmt->execute();
        $areaResult = $areaStmt->get_result();
        while ($r = $areaResult->fetch_assoc()) {
            if ($r['client_area']) $areaOptions[] = $r['client_area'];
        }
        $areaStmt->close();

        // ── Count total grouped rows (for pagination) ─────────────────────────
        $countStmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM (
                SELECT 1
                FROM   tbl_schedule_calls
                $whereSQL
                GROUP  BY col_run_name, Clientshift_Date
            ) AS grp
        ");
        $countStmt->bind_param($bindTypes, ...$bindValues);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        if ($cr = $countResult->fetch_assoc()) {
            $totalGroups = (int) $cr['total'];
        }
        $countStmt->close();

        $totalPages = max(1, (int) ceil($totalGroups / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        // ── Fetch grouped shift data ───────────────────────────────────────────
        $dataBindTypes  = $bindTypes  . 'ii';
        $dataBindValues = array_merge($bindValues, [$perPage, $offset]);

        $dataStmt = $conn->prepare("
            SELECT
                col_run_name,
                MIN(client_area)  AS area_label,
                Clientshift_Date,
                MIN(dateTime_in)  AS earliest_in,
                MAX(dateTime_out) AS latest_out,
                COUNT(*)          AS total_calls,
                MIN(pay_rate)     AS sample_pay
            FROM   tbl_schedule_calls
            $whereSQL
            GROUP  BY col_run_name, Clientshift_Date
            ORDER  BY STR_TO_DATE(Clientshift_Date, '%Y-%m-%d') ASC, col_run_name ASC
            LIMIT  ? OFFSET ?
        ");
        $dataStmt->bind_param($dataBindTypes, ...$dataBindValues);
        $dataStmt->execute();
        $dataResult = $dataStmt->get_result();
        while ($row = $dataResult->fetch_assoc()) {
            $openShifts[] = $row;
        }
        $dataStmt->close();
        $conn->close();
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtDate(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('D, j M Y') : htmlspecialchars($raw);
}
function fmtDateShort(string $raw): string {
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return $d ? $d->format('D, j M') : htmlspecialchars($raw);
}
function fmtTime(?string $t): string {
    if (!$t) return '?';
    return htmlspecialchars(substr(trim($t), 0, 5));
}
function isToday(string $raw): bool {
    return $raw === date('Y-m-d');
}
function isTomorrow(string $raw): bool {
    return $raw === date('Y-m-d', strtotime('+1 day'));
}

// Build a URL preserving all current filters
function pageUrl(int $p, string $carerId, string $companyId,
                 string $search, string $area, string $date): string {
    $q  = 'open-shifts.php?carer_id=' . urlencode($carerId);
    $q .= $companyId ? '&col_company_Id=' . urlencode($companyId) : '';
    $q .= $search    ? '&search='         . urlencode($search)    : '';
    $q .= $area      ? '&area='           . urlencode($area)      : '';
    $q .= $date      ? '&date_filter='    . urlencode($date)      : '';
    $q .= '&page=' . $p;
    return $q;
}

$baseUrl = 'open-shifts.php?carer_id=' . urlencode($carerId)
         . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Open Shifts – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
    /* ── Page shell ──────────────────────────────────────────────── */
    .marketplace-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 1.5rem 1rem 5rem;
    }

    /* ── Filter bar ──────────────────────────────────────────────── */
    .filter-bar {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        padding: 1.1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        align-items: center;
    }

    .filter-bar .search-wrap {
        position: relative;
        flex: 1 1 220px;
    }

    .filter-bar .search-wrap i {
        position: absolute;
        left: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #999);
        font-size: .95rem;
        pointer-events: none;
    }

    .filter-bar input[type="text"],
    .filter-bar input[type="date"],
    .filter-bar select {
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 999px;
        padding: .5rem 1rem .5rem 2.25rem;
        font-size: .87rem;
        background: var(--input-bg, #fafafa);
        color: var(--text-main, #1a1a1a);
        outline: none;
        transition: border-color .2s;
        width: 100%;
    }

    .filter-bar select,
    .filter-bar input[type="date"] {
        padding-left: 1rem;
    }

    .filter-bar input:focus,
    .filter-bar select:focus {
        border-color: var(--accent, #c94a57);
    }

    .filter-bar .btn-filter {
        background: var(--accent, #c94a57);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: .5rem 1.25rem;
        font-size: .87rem;
        font-weight: 600;
        cursor: pointer;
        transition: opacity .2s;
        white-space: nowrap;
    }

    .filter-bar .btn-filter:hover {
        opacity: .85;
    }

    .filter-bar .btn-clear {
        background: transparent;
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 999px;
        padding: .5rem 1rem;
        font-size: .87rem;
        color: var(--text-muted, #888);
        cursor: pointer;
        transition: border-color .2s, color .2s;
        white-space: nowrap;
        text-decoration: none;
    }

    .filter-bar .btn-clear:hover {
        border-color: var(--accent, #c94a57);
        color: var(--accent, #c94a57);
    }

    /* ── Results summary bar ─────────────────────────────────────── */
    .results-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1.25rem;
    }

    .results-count {
        font-weight: 700;
        font-size: 1rem;
    }

    .results-count span {
        color: var(--accent, #c94a57);
    }

    /* ── Shift cards grid ────────────────────────────────────────── */
    .shifts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.1rem;
        margin-bottom: 2rem;
    }

    /* ── Individual shift card ───────────────────────────────────── */
    .shift-card {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: .75rem;
        transition: transform .2s, box-shadow .2s;
        position: relative;
        overflow: hidden;
    }

    .shift-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 28px rgba(0, 0, 0, .1);
    }

    .shift-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        border-radius: 18px 18px 0 0;
    }

    .shift-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .5rem;
    }

    .shift-card-title {
        font-weight: 800;
        font-size: 1rem;
        line-height: 1.3;
        color: var(--text-main, #1a1a1a);
    }

    .shift-card-area {
        font-size: .8rem;
        color: var(--text-muted, #888);
        margin-top: .15rem;
    }

    .shift-badge {
        font-size: .7rem;
        font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 999px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .badge-open {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-today {
        background: #fef9c3;
        color: #a16207;
    }

    .badge-tomorrow {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .shift-meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }

    .shift-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        background: var(--pill-bg, #f5f5f5);
        border-radius: 999px;
        padding: .25rem .7rem;
        font-size: .78rem;
        color: var(--text-muted, #666);
        font-weight: 500;
    }

    .shift-meta-pill i {
        font-size: .75rem;
        color: var(--accent, #c94a57);
    }

    .shift-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-top: auto;
        padding-top: .5rem;
        border-top: 1px solid var(--border-color, #f0f0f0);
    }

    .shift-pay {
        font-weight: 700;
        font-size: .88rem;
        color: var(--accent, #c94a57);
    }

    .btn-accept-sm {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: .4rem 1.1rem;
        font-size: .82rem;
        font-weight: 700;
        text-decoration: none;
        transition: opacity .2s, transform .15s;
        display: inline-block;
    }

    .btn-accept-sm:hover {
        opacity: .85;
        transform: translateY(-1px);
        color: #fff;
    }

    /* ── Date group header ───────────────────────────────────────── */
    .date-group-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: .75rem;
        margin-top: .5rem;
    }

    .date-group-header .date-label {
        font-weight: 800;
        font-size: .95rem;
        color: var(--text-main, #1a1a1a);
    }

    .date-group-header .date-line {
        flex: 1;
        height: 1px;
        background: var(--border-color, #ebebeb);
    }

    .date-group-header .date-count {
        font-size: .75rem;
        color: var(--text-muted, #999);
    }

    /* ── Empty state ─────────────────────────────────────────────── */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--pill-bg, #f5f5f5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.25rem;
        margin: 0 auto 1.25rem;
        color: var(--text-muted, #bbb);
    }

    /* ── Pagination ──────────────────────────────────────────────── */
    .pagination-wrap {
        display: flex;
        justify-content: center;
        gap: .4rem;
        flex-wrap: wrap;
    }

    .pg-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        background: var(--card-bg, #fff);
        color: var(--text-main, #333);
        font-size: .85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: border-color .2s, background .2s, color .2s;
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

    /* ── Stats strip ─────────────────────────────────────────────── */
    .stats-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .strip-card {
        background: var(--card-bg, #fff);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .strip-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .strip-icon.red {
        background: rgba(201, 74, 87, .1);
        color: var(--accent, #c94a57);
    }

    .strip-icon.green {
        background: rgba(25, 135, 84, .1);
        color: #198754;
    }

    .strip-icon.blue {
        background: rgba(13, 110, 253, .1);
        color: #0d6efd;
    }

    .strip-icon.orange {
        background: rgba(253, 126, 20, .1);
        color: #fd7e14;
    }

    .strip-val {
        font-weight: 800;
        font-size: 1.2rem;
        line-height: 1;
    }

    .strip-label {
        font-size: .72rem;
        color: var(--text-muted, #888);
        margin-top: .15rem;
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- ── Top bar ── -->
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
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId ? '&col_company_Id='.urlencode($companyId) : '' ?>"
                            class="chip border-0 text-white text-decoration-none d-flex align-items-center gap-1"
                            style="font-size:.85rem">
                            <i class="bi bi-arrow-left"></i> Dashboard
                        </a>
                        <div>
                            <h2 class="mb-0 fw-bold">Open Shifts</h2>
                            <div class="text-white-50" style="font-size:.85rem">
                                Available shifts matched to your company
                            </div>
                        </div>
                    </div>
                    <span class="chip">
                        <i class="bi bi-briefcase-fill me-1"></i>
                        <?= $totalGroups ?> shift<?= $totalGroups !== 1 ? 's' : '' ?> available
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="marketplace-container">

        <?php
        // ── Compute some quick summary stats ──────────────────────────────────
        $todayCount    = 0;
        $tomorrowCount = 0;
        $uniqueAreas   = [];
        $uniqueDates   = [];
        foreach ($openShifts as $s) {
            $d = $s['Clientshift_Date'];
            if (isToday($d))    $todayCount++;
            if (isTomorrow($d)) $tomorrowCount++;
            if ($s['area_label']) $uniqueAreas[$s['area_label']] = true;
            $uniqueDates[$d] = true;
        }
        // Total on this page; accurate totals need separate count queries —
        // we use $totalGroups from the count query above for the real total.
        ?>

        <!-- Stats strip -->
        <div class="stats-strip fade-in-up">
            <div class="strip-card">
                <div class="strip-icon red"><i class="bi bi-briefcase-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $totalGroups ?></div>
                    <div class="strip-label">Total open shifts</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon orange"><i class="bi bi-lightning-charge-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $todayCount ?></div>
                    <div class="strip-label">Available today</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon blue"><i class="bi bi-calendar-event-fill"></i></div>
                <div>
                    <div class="strip-val"><?= $tomorrowCount ?></div>
                    <div class="strip-label">Tomorrow</div>
                </div>
            </div>
            <div class="strip-card">
                <div class="strip-icon green"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <div class="strip-val"><?= count($areaOptions) ?></div>
                    <div class="strip-label">Areas covered</div>
                </div>
            </div>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="open-shifts.php" class="filter-bar fade-in-up">
            <input type="hidden" name="carer_id" value="<?= htmlspecialchars($carerId) ?>">
            <?php if ($companyId): ?>
            <input type="hidden" name="col_company_Id" value="<?= htmlspecialchars($companyId) ?>">
            <?php endif; ?>

            <!-- Search -->
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search by run or area…"
                    value="<?= htmlspecialchars($search) ?>">
            </div>

            <!-- Area filter -->
            <select name="area" style="min-width:160px;border-radius:999px;padding:.5rem 1rem;
                border:1.5px solid var(--border-color,#e8e8e8);background:var(--input-bg,#fafafa);
                font-size:.87rem;color:var(--text-main,#1a1a1a);">
                <option value="">All areas</option>
                <?php foreach ($areaOptions as $ao): ?>
                <option value="<?= htmlspecialchars($ao) ?>" <?= $areaFilter === $ao ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ao) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Date filter -->
            <input type="date" name="date_filter" value="<?= htmlspecialchars($dateFilter) ?>" min="<?= $todayStr ?>"
                style="min-width:155px;border-radius:999px;padding:.5rem 1rem;
                   border:1.5px solid var(--border-color,#e8e8e8);background:var(--input-bg,#fafafa);
                   font-size:.87rem;color:var(--text-main,#1a1a1a);">

            <button type="submit" class="btn-filter">
                <i class="bi bi-funnel-fill me-1"></i> Filter
            </button>

            <?php if ($search || $areaFilter || $dateFilter): ?>
            <a href="<?= $baseUrl ?>" class="btn-clear">
                <i class="bi bi-x me-1"></i> Clear
            </a>
            <?php endif; ?>
        </form>

        <!-- Results bar -->
        <?php if (!empty($openShifts) || $search || $areaFilter || $dateFilter): ?>
        <div class="results-bar fade-in-up">
            <div class="results-count">
                Showing <span><?= count($openShifts) ?></span> of
                <span><?= $totalGroups ?></span>
                shift<?= $totalGroups !== 1 ? 's' : '' ?>
                <?php if ($search): ?>
                matching "<strong><?= htmlspecialchars($search) ?></strong>"
                <?php endif; ?>
                <?php if ($areaFilter): ?>
                in <strong><?= htmlspecialchars($areaFilter) ?></strong>
                <?php endif; ?>
                <?php if ($dateFilter): ?>
                on <strong><?= fmtDate($dateFilter) ?></strong>
                <?php endif; ?>
            </div>
            <div class="small-muted">Page <?= $page ?> of <?= $totalPages ?></div>
        </div>
        <?php endif; ?>

        <?php if (empty($openShifts)): ?>
        <!-- Empty state -->
        <div class="empty-state fade-in-up">
            <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
            <h4 class="fw-bold mb-2">No shifts found</h4>
            <p class="small-muted mb-4">
                <?php if ($search || $areaFilter || $dateFilter): ?>
                No open shifts match your filters. Try adjusting your search.
                <?php else: ?>
                There are no open shifts available right now. Check back soon.
                <?php endif; ?>
            </p>
            <?php if ($search || $areaFilter || $dateFilter): ?>
            <a href="<?= $baseUrl ?>" class="btn btn-sm text-white"
                style="background:var(--accent);border-radius:999px;padding:.6rem 1.5rem">
                Clear filters
            </a>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <?php
            // ── Group shifts by date for section headers ───────────────────────
            $groupedByDate = [];
            foreach ($openShifts as $shift) {
                $groupedByDate[$shift['Clientshift_Date']][] = $shift;
            }
            ?>

        <?php foreach ($groupedByDate as $date => $dateShifts): ?>

        <!-- Date section header -->
        <div class="date-group-header fade-in-up">
            <div class="date-label">
                <?php if (isToday($date)): ?>
                <span style="color:var(--accent)">Today</span> —
                <?= fmtDate($date) ?>
                <?php elseif (isTomorrow($date)): ?>
                <span style="color:#0d6efd">Tomorrow</span> —
                <?= fmtDate($date) ?>
                <?php else: ?>
                <?= fmtDate($date) ?>
                <?php endif; ?>
            </div>
            <div class="date-line"></div>
            <div class="date-count"><?= count($dateShifts) ?> run<?= count($dateShifts) !== 1 ? 's' : '' ?></div>
        </div>

        <!-- Cards grid for this date -->
        <div class="shifts-grid fade-in-up">
            <?php foreach ($dateShifts as $shift):
                        $runName      = htmlspecialchars($shift['col_run_name']    ?? 'Unnamed Run');
                        $areaLabel    = htmlspecialchars($shift['area_label']       ?? '');
                        $shiftDateFmt = fmtDateShort($shift['Clientshift_Date']    ?? '');
                        $earliestIn   = fmtTime($shift['earliest_in']);
                        $latestOut    = fmtTime($shift['latest_out']);
                        $totalCalls   = (int)($shift['total_calls'] ?? 1);
                        $payLabel     = !empty($shift['sample_pay'])
                                        ? htmlspecialchars($shift['sample_pay'])
                                        : '';
                        $d            = $shift['Clientshift_Date'] ?? '';

                        // Badge
                        if (isToday($d)) {
                            $badgeClass = 'badge-today';
                            $badgeText  = 'Today';
                        } elseif (isTomorrow($d)) {
                            $badgeClass = 'badge-tomorrow';
                            $badgeText  = 'Tomorrow';
                        } else {
                            $badgeClass = 'badge-open';
                            $badgeText  = 'Open';
                        }

                        $acceptUrl = 'accept-shift.php'
                                   . '?run='      . urlencode($shift['col_run_name']    ?? '')
                                   . '&date='     . urlencode($shift['Clientshift_Date'] ?? '')
                                   . '&carer_id=' . urlencode($carerId)
                                   . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');
                    ?>
            <div class="shift-card">
                <div class="shift-card-head">
                    <div>
                        <div class="shift-card-title"><?= $runName ?></div>
                        <?php if ($areaLabel): ?>
                        <div class="shift-card-area">
                            <i class="bi bi-geo-alt me-1"></i><?= $areaLabel ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="shift-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                </div>

                <div class="shift-meta-row">
                    <span class="shift-meta-pill">
                        <i class="bi bi-clock"></i>
                        <?= $earliestIn ?> – <?= $latestOut ?>
                    </span>
                    <span class="shift-meta-pill">
                        <i class="bi bi-calendar"></i>
                        <?= $shiftDateFmt ?>
                    </span>
                    <span class="shift-meta-pill">
                        <i class="bi bi-journal-text"></i>
                        <?= $totalCalls ?> call<?= $totalCalls !== 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="shift-card-footer">
                    <?php if ($payLabel): ?>
                    <div class="shift-pay">£<?= $payLabel ?></div>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    <a href="<?= $acceptUrl ?>" class="btn-accept-sm">
                        Accept Shift
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="pagination-wrap mt-4 fade-in-up" aria-label="Shift pages">

            <!-- Prev -->
            <a href="<?= $page > 1
                        ? pageUrl($page - 1, $carerId, $companyId, $search, $areaFilter, $dateFilter)
                        : '#' ?>" class="pg-btn <?= $page <= 1 ? 'disabled' : '' ?>" aria-label="Previous">
                <i class="bi bi-chevron-left"></i>
            </a>

            <?php
                    // Show up to 5 page numbers around the current page
                    $start = max(1, min($page - 2, $totalPages - 4));
                    $end   = min($totalPages, $start + 4);
                    for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?= pageUrl($p, $carerId, $companyId, $search, $areaFilter, $dateFilter) ?>"
                class="pg-btn <?= $p === $page ? 'active' : '' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>

            <!-- Next -->
            <a href="<?= $page < $totalPages
                        ? pageUrl($page + 1, $carerId, $companyId, $search, $areaFilter, $dateFilter)
                        : '#' ?>" class="pg-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" aria-label="Next">
                <i class="bi bi-chevron-right"></i>
            </a>

        </nav>
        <?php endif; ?>

        <?php endif; ?>

    </main>

    <?php include 'new-footer.php'; ?>

    <script>
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

        // ── Sync URL params with session (one-time redirect) ──────────────────
        (function ensureParams() {
            const url = new URL(window.location.href);
            const sessId = String(user.user_special_Id || '');
            const sessComp = String(user.col_company_Id || '');
            let changed = false;

            if (sessId && url.searchParams.get('carer_id') !== sessId) {
                url.searchParams.set('carer_id', sessId);
                changed = true;
            }
            if (sessComp && url.searchParams.get('col_company_Id') !== sessComp) {
                url.searchParams.set('col_company_Id', sessComp);
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

        function withFallback(el) {
            if (!el) return;
            el.onerror = function() {
                if (this.src !== DEFAULT_AVATAR) this.src = DEFAULT_AVATAR;
            };
        }

        function setSrc(id, src, alt) {
            const el = document.getElementById(id);
            if (!el) return;
            el.src = src;
            if (alt) el.alt = alt;
        }

        function setText(id, val) {
            const el = document.getElementById(id);
            if (el) el.textContent = val || '—';
        }

        const avatar = avatarSrc(user.team_dp);
        const name = user.user_fullname || '';

        setSrc('topbarAvatar', avatar, name);
        withFallback(document.getElementById('topbarAvatar'));
        setSrc('navProfilePic', avatar, name);
        withFallback(document.getElementById('navProfilePic'));
        setText('navFullName', name);
        setText('navEmail', user.user_email_address || '');
        setText('navPhone', user.user_phone_number || '');

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
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