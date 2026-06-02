<?php
$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';
$view      = in_array($_GET['view'] ?? '', ['month','week','day'])
             ? $_GET['view'] : 'month';

// Current navigation date
$navYear  = isset($_GET['y']) ? (int)$_GET['y']  : (int)date('Y');
$navMonth = isset($_GET['m']) ? (int)$_GET['m']  : (int)date('n');
$navDay   = isset($_GET['d']) ? (int)$_GET['d']  : (int)date('j');

// Clamp
$navMonth = max(1, min(12, $navMonth));
$navDay   = max(1, min(31, $navDay));

// Date range to fetch from DB (always fetch ±1 month so week view on boundaries works)
$fetchStart = date('Y-m-d', mktime(0,0,0, $navMonth - 1, 1, $navYear));
$fetchEnd   = date('Y-m-d', mktime(0,0,0, $navMonth + 2, 0, $navYear));

// $shifts  [ 'YYYY-MM-DD' => [ run_name => grouped_run ] ]
// $rawRows [ 'YYYY-MM-DD' => [ run_name => [ ...individual call rows ] ] ]  (for drawer)
$shifts  = [];
$rawRows = [];

if ($carerId !== '') {
    include_once 'dbconnect.php';
    if (!$conn->connect_error) {

        // ── 1. Grouped query: one row per run per day ─────────────────────────
        $stmt = $conn->prepare("
            SELECT
                col_run_name,
                Clientshift_Date,
                MIN(client_area)          AS area_label,
                MIN(dateTime_in)          AS earliest_in,
                MAX(dateTime_out)         AS latest_out,
                COUNT(*)                  AS total_calls,
                MIN(pay_rate)             AS sample_pay,
                CASE
                    WHEN SUM(call_status = 'Scheduled')  > 0 THEN 'Scheduled'
                    WHEN SUM(call_status = 'Completed')  = COUNT(*) THEN 'Completed'
                    ELSE 'Not completed'
                END                       AS run_status
            FROM   tbl_schedule_calls
            WHERE  first_carer_Id = ?
              AND  call_status IN ('Scheduled','Completed','Not completed')
              AND  STR_TO_DATE(Clientshift_Date,'%Y-%m-%d') BETWEEN ? AND ?
            GROUP  BY col_run_name, Clientshift_Date
            ORDER  BY STR_TO_DATE(Clientshift_Date,'%Y-%m-%d') ASC,
                      MIN(dateTime_in) ASC
        ");
        $stmt->bind_param('sss', $carerId, $fetchStart, $fetchEnd);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $d = $row['Clientshift_Date'];
            $r = $row['col_run_name'] ?? '';
            $shifts[$d][$r] = $row;
        }
        $stmt->close();

        // ── 2. Raw calls for the drawer (all individual rows) ─────────────────
        $stmt2 = $conn->prepare("
            SELECT id, client_name, client_area, care_calls,
                   dateTime_in, dateTime_out, col_run_name,
                   Clientshift_Date, call_status, pay_rate,
                   col_required_carers
            FROM   tbl_schedule_calls
            WHERE  first_carer_Id = ?
              AND  call_status IN ('Scheduled','Completed','Not completed')
              AND  STR_TO_DATE(Clientshift_Date,'%Y-%m-%d') BETWEEN ? AND ?
            ORDER  BY Clientshift_Date ASC, col_run_name ASC, dateTime_in ASC
        ");
        $stmt2->bind_param('sss', $carerId, $fetchStart, $fetchEnd);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) {
            $d = $row['Clientshift_Date'];
            $r = $row['col_run_name'] ?? '';
            $rawRows[$d][$r][] = $row;
        }
        $stmt2->close();
        $conn->close();
    }
}

// ── Helper: flat array of grouped runs for a date ────────────────────────────
function runsForDate(array $shifts, string $date): array {
    return array_values($shifts[$date] ?? []);
}

// ── Navigation URL helper ─────────────────────────────────────────────────────
function navUrl(string $view, int $y, int $m, int $d,
                string $carerId, string $companyId): string {
    return 'calendar.php?view=' . $view
         . '&y=' . $y . '&m=' . $m . '&d=' . $d
         . '&carer_id=' . urlencode($carerId)
         . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');
}

// ── Status colour mapping ─────────────────────────────────────────────────────
function statusClass(string $s): string {
    return match(strtolower(trim($s))) {
        'completed'     => 'status-done',
        'not completed' => 'status-missed',
        default         => 'status-sched',
    };
}
function statusLabel(string $s): string {
    return match(strtolower(trim($s))) {
        'completed'     => 'Completed',
        'not completed' => 'Not Completed',
        default         => 'Scheduled',
    };
}

$todayStr = date('Y-m-d');

// Month boundary helpers
$firstOfMonth     = mktime(0,0,0,$navMonth,1,$navYear);
$daysInMonth      = (int)date('t', $firstOfMonth);
$firstDayOfWeek   = (int)date('N', $firstOfMonth); // 1=Mon … 7=Sun

// Week helpers (Mon–Sun)
$navDateStr       = date('Y-m-d', mktime(0,0,0,$navMonth,$navDay,$navYear));
$weekMonday       = date('Y-m-d', strtotime('monday this week', strtotime($navDateStr)));
if (date('N', strtotime($navDateStr)) == 1) $weekMonday = $navDateStr;
$weekMonday       = date('Y-m-d', strtotime('monday this week', strtotime($navDateStr)));

// Encode grouped runs + raw calls for JS drawer
$shiftsJson   = json_encode($shifts,  JSON_HEX_TAG | JSON_HEX_AMP);
$rawRowsJson  = json_encode($rawRows, JSON_HEX_TAG | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Shift Calendar – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap"
        rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
    /* ── Base overrides ──────────────────────────────────────────── */
    body {
        font-family: 'DM Sans', sans-serif;
    }

    .cal-container {
        max-width: 1080px;
        margin: 0 auto;
        padding: 1.25rem 1rem 5rem;
    }

    /* ── View toggle ─────────────────────────────────────────────── */
    .view-toggle {
        display: inline-flex;
        background: var(--card-bg, #fff);
        border-radius: 999px;
        padding: .28rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .07);
        gap: .2rem;
    }

    .view-btn {
        border: none;
        border-radius: 999px;
        padding: .4rem 1rem;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        background: transparent;
        color: var(--text-muted, #888);
        transition: background .18s, color .18s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }

    .view-btn.active,
    .view-btn:hover {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
    }

    /* ── Nav strip ───────────────────────────────────────────────── */
    .cal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .cal-nav-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--text-main, #1a1a1a);
        line-height: 1;
    }

    .cal-nav-sub {
        font-size: .78rem;
        color: var(--text-muted, #999);
        margin-top: .2rem;
    }

    .nav-arrow {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        background: var(--card-bg, #fff);
        color: var(--text-main, #444);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: .95rem;
        transition: background .18s, border-color .18s, color .18s;
        flex-shrink: 0;
    }

    .nav-arrow:hover {
        background: var(--accent, #c94a57);
        border-color: var(--accent, #c94a57);
        color: #fff;
    }

    .btn-today {
        border: 1.5px solid var(--border-color, #e8e8e8);
        background: var(--card-bg, #fff);
        border-radius: 999px;
        padding: .38rem 1rem;
        font-size: .8rem;
        font-weight: 600;
        color: var(--text-muted, #666);
        text-decoration: none;
        transition: border-color .18s, color .18s;
    }

    .btn-today:hover {
        border-color: var(--accent, #c94a57);
        color: var(--accent, #c94a57);
    }

    /* ── Stats strip ─────────────────────────────────────────────── */
    .cal-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: .85rem;
        margin-bottom: 1.25rem;
    }

    .cal-stat {
        background: var(--card-bg, #fff);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        padding: .85rem 1rem;
        display: flex;
        align-items: center;
        gap: .65rem;
    }

    .cal-stat-icon {
        width: 36px;
        height: 36px;
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

    .cal-stat-val {
        font-weight: 800;
        font-size: 1.15rem;
        line-height: 1;
    }

    .cal-stat-label {
        font-size: .7rem;
        color: var(--text-muted, #888);
        margin-top: .12rem;
    }

    /* ── Month view ──────────────────────────────────────────────── */
    .month-grid-wrap {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 16px rgba(0, 0, 0, .07);
        overflow: hidden;
    }

    .month-dow-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        border-bottom: 1.5px solid var(--border-color, #f0f0f0);
    }

    .month-dow-header div {
        text-align: center;
        padding: .65rem .25rem;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--text-muted, #aaa);
    }

    .month-cells {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }

    .month-cell {
        min-height: 100px;
        border-right: 1px solid var(--border-color, #f2f2f2);
        border-bottom: 1px solid var(--border-color, #f2f2f2);
        padding: .45rem .4rem .4rem;
        cursor: pointer;
        transition: background .15s;
        position: relative;
        overflow: hidden;
    }

    .month-cell:nth-child(7n) {
        border-right: none;
    }

    .month-cell:hover {
        background: var(--hover-bg, #fafafa);
    }

    .month-cell.other-month {
        background: var(--pill-bg, #fafafa);
    }

    .month-cell.other-month .cell-day {
        opacity: .35;
    }

    .month-cell.is-today {
        background: rgba(201, 74, 87, .04);
    }

    .month-cell.is-today::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
    }

    .cell-day {
        font-size: .82rem;
        font-weight: 700;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: .3rem;
        transition: background .15s, color .15s;
    }

    .is-today .cell-day {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
    }

    .cell-shift {
        display: block;
        font-size: .68rem;
        font-weight: 600;
        border-radius: 5px;
        padding: .18rem .4rem;
        margin-bottom: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        transition: opacity .15s;
    }

    .cell-shift:hover {
        opacity: .8;
    }

    .cell-more {
        font-size: .65rem;
        color: var(--text-muted, #aaa);
        font-weight: 600;
        padding: .1rem .35rem;
    }

    /* Status colours */
    .status-sched {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-done {
        background: #dcfce7;
        color: #15803d;
    }

    .status-missed {
        background: #fee2e2;
        color: #dc2626;
    }

    /* ── Week view ───────────────────────────────────────────────── */
    .week-grid {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 16px rgba(0, 0, 0, .07);
        overflow: hidden;
    }

    .week-header {
        display: grid;
        grid-template-columns: 52px repeat(7, 1fr);
        border-bottom: 1.5px solid var(--border-color, #f0f0f0);
    }

    .week-header-cell {
        text-align: center;
        padding: .75rem .25rem;
        cursor: pointer;
    }

    .week-header-cell:hover .wh-day {
        color: var(--accent, #c94a57);
    }

    .wh-dow {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--text-muted, #aaa);
    }

    .wh-day {
        font-size: 1.2rem;
        font-weight: 800;
        line-height: 1.1;
        transition: color .15s;
    }

    .wh-today .wh-day {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    .week-body {
        display: grid;
        grid-template-columns: 52px repeat(7, 1fr);
    }

    .week-time-col {
        border-right: 1px solid var(--border-color, #f0f0f0);
    }

    .week-hour-label {
        height: 56px;
        display: flex;
        align-items: flex-start;
        justify-content: flex-end;
        padding: .2rem .5rem 0 0;
        font-size: .65rem;
        color: var(--text-muted, #bbb);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color, #f5f5f5);
    }

    .week-day-col {
        border-right: 1px solid var(--border-color, #f2f2f2);
        position: relative;
        min-height: 1008px;
        /* 18 hours × 56px */
    }

    .week-day-col:last-child {
        border-right: none;
    }

    .week-hour-line {
        position: absolute;
        left: 0;
        right: 0;
        height: 1px;
        background: var(--border-color, #f5f5f5);
    }

    .week-shift-block {
        position: absolute;
        left: 2px;
        right: 2px;
        border-radius: 7px;
        padding: .25rem .4rem;
        font-size: .7rem;
        font-weight: 600;
        cursor: pointer;
        overflow: hidden;
        transition: opacity .15s, transform .15s;
        z-index: 2;
    }

    .week-shift-block:hover {
        opacity: .85;
        transform: scale(1.01);
    }

    .week-shift-block .wsb-time {
        opacity: .75;
        font-weight: 400;
        font-size: .65rem;
    }

    .now-line {
        position: absolute;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--accent, #c94a57);
        z-index: 3;
    }

    .now-dot {
        position: absolute;
        left: -4px;
        top: -4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--accent, #c94a57);
    }

    /* ── Day view ────────────────────────────────────────────────── */
    .day-header {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .day-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }

    .day-list {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .day-shift-card {
        background: var(--card-bg, #fff);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        padding: 1rem 1.1rem 1rem 1.35rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        transition: transform .18s, box-shadow .18s;
        position: relative;
        overflow: hidden;
    }

    .day-shift-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
    }

    .day-shift-card.status-sched::before {
        background: #3b82f6;
    }

    .day-shift-card.status-done::before {
        background: #22c55e;
    }

    .day-shift-card.status-missed::before {
        background: #ef4444;
    }

    .day-shift-card:hover {
        transform: translateX(3px);
        box-shadow: 0 4px 18px rgba(0, 0, 0, .1);
    }

    .dsc-time {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--accent, #c94a57);
        white-space: nowrap;
        min-width: 90px;
    }

    .dsc-title {
        font-weight: 700;
        font-size: .95rem;
    }

    .dsc-sub {
        font-size: .78rem;
        color: var(--text-muted, #888);
        margin-top: .12rem;
    }

    .dsc-badge {
        margin-left: auto;
        font-size: .7rem;
        font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 999px;
        flex-shrink: 0;
    }

    .day-empty {
        text-align: center;
        padding: 3rem 2rem;
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .day-empty-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: var(--pill-bg, #f5f5f5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: var(--text-muted, #ccc);
        margin: 0 auto 1rem;
    }

    /* ── Drawer ──────────────────────────────────────────────────── */
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
        width: min(420px, 100vw);
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
        padding: 1.1rem 1.25rem 2rem;
    }

    .drawer-date-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: var(--pill-bg, #f5f5f5);
        border-radius: 999px;
        padding: .3rem .85rem;
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-muted, #666);
        margin-bottom: 1rem;
    }

    .drawer-shift-item {
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: .75rem;
        border-left: 4px solid transparent;
    }

    .drawer-shift-item.status-sched {
        background: #eff6ff;
        border-color: #3b82f6;
    }

    .drawer-shift-item.status-done {
        background: #f0fdf4;
        border-color: #22c55e;
    }

    .drawer-shift-item.status-missed {
        background: #fff1f2;
        border-color: #ef4444;
    }

    .dsi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .5rem;
    }

    .dsi-title {
        font-weight: 700;
        font-size: .95rem;
    }

    .dsi-status {
        font-size: .68rem;
        font-weight: 700;
        padding: .22rem .6rem;
        border-radius: 999px;
    }

    .status-sched .dsi-status {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-done .dsi-status {
        background: #dcfce7;
        color: #15803d;
    }

    .status-missed .dsi-status {
        background: #fee2e2;
        color: #dc2626;
    }

    .dsi-row {
        display: flex;
        align-items: center;
        gap: .45rem;
        font-size: .82rem;
        color: var(--text-muted, #666);
        margin-bottom: .3rem;
    }

    .dsi-row i {
        color: var(--accent, #c94a57);
        width: 14px;
    }

    .dsi-row strong {
        color: var(--text-main, #333);
    }

    .dsi-pay {
        display: inline-block;
        margin-top: .4rem;
        font-size: .78rem;
        font-weight: 700;
        color: var(--accent, #c94a57);
    }

    .drawer-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--text-muted, #bbb);
    }

    /* ── Legend ──────────────────────────────────────────────────── */
    .legend {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: .35rem;
        font-size: .75rem;
        color: var(--text-muted, #777);
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .ld-sched {
        background: #3b82f6;
    }

    .ld-done {
        background: #22c55e;
    }

    .ld-missed {
        background: #ef4444;
    }

    /* ── Animations ──────────────────────────────────────────────── */
    .fade-in {
        animation: fi .35s ease both;
    }

    @keyframes fi {
        from {
            opacity: 0;
            transform: translateY(10px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    /* ── Responsive ──────────────────────────────────────────────── */
    @media(max-width:640px) {
        .month-cell {
            min-height: 62px;
            padding: .3rem .2rem;
        }

        .cell-shift {
            font-size: .6rem;
            padding: .12rem .25rem;
        }

        .week-body {
            overflow-x: auto;
        }

        .cal-nav-title {
            font-size: 1.2rem;
        }
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- ── Topbar ─────────────────────────────────────────────────────── -->
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
                        <a href="dashboard.php?carer_id=<?= urlencode($carerId) ?><?= $companyId?'&col_company_Id='.urlencode($companyId):'' ?>"
                            class="chip border-0 text-white text-decoration-none d-flex align-items-center gap-1"
                            style="font-size:.85rem">
                            <i class="bi bi-arrow-left"></i> Dashboard
                        </a>
                        <div>
                            <h2 class="mb-0 fw-bold">Shift Calendar</h2>
                            <div class="text-white-50" style="font-size:.85rem">Your scheduled and completed shifts
                            </div>
                        </div>
                    </div>
                    <!-- View toggle -->
                    <div class="view-toggle">
                        <a href="<?= navUrl('month',$navYear,$navMonth,$navDay,$carerId,$companyId) ?>"
                            class="view-btn <?= $view==='month'?'active':'' ?>">
                            <i class="bi bi-grid-3x3-gap"></i> Month
                        </a>
                        <a href="<?= navUrl('week',$navYear,$navMonth,$navDay,$carerId,$companyId) ?>"
                            class="view-btn <?= $view==='week'?'active':'' ?>">
                            <i class="bi bi-calendar-week"></i> Week
                        </a>
                        <a href="<?= navUrl('day',$navYear,$navMonth,$navDay,$carerId,$companyId) ?>"
                            class="view-btn <?= $view==='day'?'active':'' ?>">
                            <i class="bi bi-calendar-day"></i> Day
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="cal-container fade-in">
        <?php
        // ── Compute stats for the current month ──────────────────────────────
        $monthShifts    = 0;
        $completedCount = 0;
        $missedCount    = 0;
        $totalHours     = 0.0;
        for ($d2 = 1; $d2 <= $daysInMonth; $d2++) {
            $dk   = sprintf('%04d-%02d-%02d', $navYear, $navMonth, $d2);
            $runs = runsForDate($shifts, $dk);   // one entry per run, not per call
            foreach ($runs as $sh) {
                $monthShifts++;
                $st = strtolower(trim($sh['run_status']));
                if ($st === 'completed')     $completedCount++;
                if ($st === 'not completed') $missedCount++;
                // hours from earliest_in → latest_out of the whole run
                if ($sh['earliest_in'] && $sh['latest_out']) {
                    $ti = DateTime::createFromFormat('H:i', substr($sh['earliest_in'],0,5));
                    $to = DateTime::createFromFormat('H:i', substr($sh['latest_out'],0,5));
                    if ($ti && $to) {
                        if ($to < $ti) $to->modify('+1 day');
                        $diff = $to->diff($ti);
                        $totalHours += $diff->h + $diff->i/60;
                    }
                }
            }
        }
        $totalHoursDisplay = floor($totalHours).'h '.str_pad(round(fmod($totalHours,1)*60),2,'0',STR_PAD_LEFT).'m';
        ?>

        <!-- Stats strip -->
        <div class="cal-stats">
            <div class="cal-stat">
                <div class="cal-stat-icon ic-red"><i class="bi bi-calendar-event-fill"></i></div>
                <div>
                    <div class="cal-stat-val"><?= $monthShifts ?></div>
                    <div class="cal-stat-label">Shifts this month</div>
                </div>
            </div>
            <div class="cal-stat">
                <div class="cal-stat-icon ic-green"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="cal-stat-val"><?= $completedCount ?></div>
                    <div class="cal-stat-label">Completed</div>
                </div>
            </div>
            <div class="cal-stat">
                <div class="cal-stat-icon ic-orange"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <div class="cal-stat-val"><?= $totalHoursDisplay ?></div>
                    <div class="cal-stat-label">Hours logged</div>
                </div>
            </div>
            <div class="cal-stat">
                <div class="cal-stat-icon ic-blue"><i class="bi bi-calendar-check-fill"></i></div>
                <div>
                    <div class="cal-stat-val"><?= $monthShifts - $completedCount - $missedCount ?></div>
                    <div class="cal-stat-label">Upcoming</div>
                </div>
            </div>
        </div>

        <!-- Nav row -->
        <div class="cal-nav">
            <?php
            // Prev / next depend on view
            if ($view === 'month') {
                $prevM = $navMonth - 1; $prevY = $navYear;
                if ($prevM < 1) { $prevM = 12; $prevY--; }
                $nextM = $navMonth + 1; $nextY = $navYear;
                if ($nextM > 12) { $nextM = 1; $nextY++; }
                $prevUrl = navUrl('month',$prevY,$prevM,1,$carerId,$companyId);
                $nextUrl = navUrl('month',$nextY,$nextM,1,$carerId,$companyId);
                $title   = date('F Y', mktime(0,0,0,$navMonth,1,$navYear));
                $sub     = $monthShifts.' shift'.($monthShifts!==1?'s':'').' this month';
            } elseif ($view === 'week') {
                $wMon  = new DateTime($weekMonday);
                $wSun  = clone $wMon; $wSun->modify('+6 days');
                $pMon  = clone $wMon; $pMon->modify('-7 days');
                $nMon  = clone $wMon; $nMon->modify('+7 days');
                $prevUrl = navUrl('week',(int)$pMon->format('Y'),(int)$pMon->format('n'),(int)$pMon->format('j'),$carerId,$companyId);
                $nextUrl = navUrl('week',(int)$nMon->format('Y'),(int)$nMon->format('n'),(int)$nMon->format('j'),$carerId,$companyId);
                $title   = $wMon->format('j M').' – '.$wSun->format('j M Y');
                $sub     = 'Week '.date('W', strtotime($weekMonday));
            } else {
                // day
                $cur   = new DateTime(sprintf('%04d-%02d-%02d',$navYear,$navMonth,$navDay));
                $prev  = clone $cur; $prev->modify('-1 day');
                $next  = clone $cur; $next->modify('+1 day');
                $prevUrl = navUrl('day',(int)$prev->format('Y'),(int)$prev->format('n'),(int)$prev->format('j'),$carerId,$companyId);
                $nextUrl = navUrl('day',(int)$next->format('Y'),(int)$next->format('n'),(int)$next->format('j'),$carerId,$companyId);
                $title   = $cur->format('l, j F Y');
                $dayRuns = runsForDate($shifts, $cur->format('Y-m-d'));
                $sub     = count($dayRuns).' run'.(count($dayRuns)!==1?'s':'').' today';
            }
            ?>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $prevUrl ?>" class="nav-arrow" aria-label="Previous">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="<?= $nextUrl ?>" class="nav-arrow" aria-label="Next">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <div>
                    <div class="cal-nav-title"><?= htmlspecialchars($title) ?></div>
                    <div class="cal-nav-sub"><?= htmlspecialchars($sub) ?></div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Legend -->
                <div class="legend d-none d-sm-flex">
                    <span class="legend-item"><span class="legend-dot ld-sched"></span>Scheduled</span>
                    <span class="legend-item"><span class="legend-dot ld-done"></span>Completed</span>
                    <span class="legend-item"><span class="legend-dot ld-missed"></span>Not Completed</span>
                </div>
                <a href="<?= navUrl($view,(int)date('Y'),(int)date('n'),(int)date('j'),$carerId,$companyId) ?>"
                    class="btn-today">Today</a>
            </div>
        </div>

        <?php /* ══════════════════════════════════════════════════════════
               MONTH VIEW
               ══════════════════════════════════════════════════════════ */ ?>
        <?php if ($view === 'month'): ?>
        <div class="month-grid-wrap">
            <div class="month-dow-header">
                <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dw): ?>
                <div><?= $dw ?></div>
                <?php endforeach; ?>
            </div>
            <div class="month-cells">
                <?php
                // Leading blanks (Mon=1 means no blank; Tue=2 means 1 blank, etc.)
                $leadBlanks = $firstDayOfWeek - 1;
                // Previous month days to show
                $prevMonthDays = (int)date('t', mktime(0,0,0,$navMonth-1,1,$navYear));
                for ($b = $leadBlanks; $b > 0; $b--):
                    $ghostDay = $prevMonthDays - $b + 1;
                    $ghostM   = $navMonth - 1 < 1 ? 12 : $navMonth - 1;
                    $ghostY   = $navMonth - 1 < 1 ? $navYear - 1 : $navYear;
                    $ghostStr = sprintf('%04d-%02d-%02d', $ghostY, $ghostM, $ghostDay);
                ?>
                <div class="month-cell other-month" onclick="openDrawer('<?= $ghostStr ?>')">
                    <div class="cell-day"><?= $ghostDay ?></div>
                    <?php foreach(array_slice(runsForDate($shifts,$ghostStr),0,2) as $gs): ?>
                    <span class="cell-shift <?= statusClass($gs['run_status']) ?>">
                        <?= htmlspecialchars($gs['col_run_name'] ?: substr($gs['earliest_in'],0,5) ?: '—') ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endfor; ?>

                <?php for ($d2 = 1; $d2 <= $daysInMonth; $d2++):
                    $dk      = sprintf('%04d-%02d-%02d',$navYear,$navMonth,$d2);
                    $dayRuns = runsForDate($shifts, $dk);
                    $isToday = $dk === $todayStr;
                    $classes = 'month-cell' . ($isToday?' is-today':'');
                ?>
                <div class="<?= $classes ?>" onclick="openDrawer('<?= $dk ?>')">
                    <div class="cell-day"><?= $d2 ?></div>
                    <?php foreach(array_slice($dayRuns,0,3) as $sh): ?>
                    <span class="cell-shift <?= statusClass($sh['run_status']) ?>"
                        onclick="event.stopPropagation();openDrawerRun('<?= $dk ?>',<?= json_encode($sh['col_run_name']) ?>)">
                        <?= htmlspecialchars(substr($sh['earliest_in']??'',0,5).' '.($sh['col_run_name']?:'Run')) ?>
                    </span>
                    <?php endforeach; ?>
                    <?php if (count($dayRuns) > 3): ?>
                    <span class="cell-more">+<?= count($dayRuns)-3 ?> more</span>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>

                <?php
                // Trailing blanks
                $total = $leadBlanks + $daysInMonth;
                $trailBlanks = (7 - ($total % 7)) % 7;
                for ($b = 1; $b <= $trailBlanks; $b++):
                    $ghostDay = $b;
                    $ghostM   = $navMonth + 1 > 12 ? 1 : $navMonth + 1;
                    $ghostY   = $navMonth + 1 > 12 ? $navYear + 1 : $navYear;
                    $ghostStr = sprintf('%04d-%02d-%02d', $ghostY, $ghostM, $ghostDay);
                ?>
                <div class="month-cell other-month" onclick="openDrawer('<?= $ghostStr ?>')">
                    <div class="cell-day"><?= $ghostDay ?></div>
                    <?php foreach(array_slice(runsForDate($shifts,$ghostStr),0,2) as $gs): ?>
                    <span class="cell-shift <?= statusClass($gs['run_status']) ?>">
                        <?= htmlspecialchars($gs['col_run_name'] ?: substr($gs['earliest_in'],0,5) ?: '—') ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <?php /* ══════════════════════════════════════════════════════════
               WEEK VIEW
               ══════════════════════════════════════════════════════════ */ ?>
        <?php elseif ($view === 'week'):
            $wStart = new DateTime($weekMonday);
            $weekDays = [];
            for ($wi = 0; $wi < 7; $wi++) {
                $wd = clone $wStart; $wd->modify("+{$wi} days");
                $weekDays[] = $wd->format('Y-m-d');
            }
            $hourStart = 6; // show 06:00 – 23:00
            $hourEnd   = 23;
        ?>
        <div class="week-grid">
            <!-- Header row -->
            <div class="week-header">
                <div></div><!-- time col spacer -->
                <?php foreach($weekDays as $wd):
                    $wDate = new DateTime($wd);
                    $isT   = $wd === $todayStr;
                ?>
                <div class="week-header-cell <?= $isT?'wh-today':'' ?>"
                    onclick="window.location='<?= navUrl('day',(int)$wDate->format('Y'),(int)$wDate->format('n'),(int)$wDate->format('j'),$carerId,$companyId) ?>'">
                    <div class="wh-dow"><?= $wDate->format('D') ?></div>
                    <div class="wh-day <?= $isT?'':'mt-1' ?>"><?= $wDate->format('j') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Body -->
            <div class="week-body" id="week-body">
                <!-- Time column -->
                <div class="week-time-col">
                    <?php for($h=$hourStart;$h<=$hourEnd;$h++): ?>
                    <div class="week-hour-label"><?= sprintf('%02d:00',$h) ?></div>
                    <?php endfor; ?>
                </div>
                <!-- Day columns -->
                <?php foreach($weekDays as $wd):
                    $isT = $wd === $todayStr;
                ?>
                <div class="week-day-col <?= $isT?'is-today':'' ?>" id="wdc-<?= $wd ?>">
                    <!-- Hour lines -->
                    <?php for($h=$hourStart;$h<=$hourEnd;$h++): ?>
                    <div class="week-hour-line" style="top:<?= ($h-$hourStart)*56 ?>px"></div>
                    <?php endfor; ?>
                    <!-- Shift blocks — one block per run -->
                    <?php foreach(runsForDate($shifts,$wd) as $sh):
                            $inStr  = substr($sh['earliest_in']??'',0,5);
                            $outStr = substr($sh['latest_out']??'',0,5);
                            if(!$inStr||!$outStr) continue;
                            list($ih,$im) = array_map('intval',explode(':',$inStr));
                            list($oh,$om) = array_map('intval',explode(':',$outStr));
                            $startMins = max(0, ($ih*60+$im) - $hourStart*60);
                            $endMins   = ($oh*60+$om) - $hourStart*60;
                            if($endMins<=$startMins) $endMins += 24*60;
                            $top    = $startMins / 60 * 56;
                            $height = max(36, ($endMins - $startMins) / 60 * 56);
                            $sc     = statusClass($sh['run_status']);
                        ?>
                    <div class="week-shift-block <?= $sc ?>" style="top:<?= $top ?>px;height:<?= $height ?>px"
                        onclick="openDrawerRun('<?= $wd ?>',<?= json_encode($sh['col_run_name']) ?>)">
                        <div><?= htmlspecialchars($sh['col_run_name']?:'Run') ?></div>
                        <div class="wsb-time"><?= $inStr ?> – <?= $outStr ?>
                            <?php if((int)$sh['total_calls']>1): ?>
                            · <?= $sh['total_calls'] ?> calls
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <!-- Now line for today -->
                    <?php if($isT): ?>
                    <div class="now-line" id="now-line">
                        <div class="now-dot"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php /* ══════════════════════════════════════════════════════════
               DAY VIEW
               ══════════════════════════════════════════════════════════ */ ?>
        <?php else:
            $dayKey  = sprintf('%04d-%02d-%02d',$navYear,$navMonth,$navDay);
            $dayDate = new DateTime($dayKey);
            $dayRuns = runsForDate($shifts, $dayKey);
        ?>
        <div class="day-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="day-title"><?= $dayDate->format('l') ?></div>
                    <div style="font-size:1rem;color:var(--text-muted,#999);margin-top:.2rem">
                        <?= $dayDate->format('j F Y') ?>
                    </div>
                </div>
                <?php if($dayKey===$todayStr): ?>
                <span class="chip" style="font-size:.78rem">
                    <i class="bi bi-circle-fill text-danger me-1" style="font-size:.5rem"></i> Today
                </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if(empty($dayRuns)): ?>
        <div class="day-empty">
            <div class="day-empty-icon"><i class="bi bi-calendar-x"></i></div>
            <h5 class="fw-bold mb-1">No shifts on this day</h5>
            <p class="small-muted mb-3">You have no scheduled or completed shifts for <?= $dayDate->format('j F') ?>.
            </p>
            <a href="open-shifts.php?carer_id=<?= urlencode($carerId) ?><?= $companyId?'&col_company_Id='.urlencode($companyId):'' ?>"
                class="btn btn-sm text-white"
                style="background:var(--accent);border-radius:999px;padding:.5rem 1.25rem">
                Browse open shifts
            </a>
        </div>
        <?php else: ?>
        <div class="day-list">
            <?php foreach($dayRuns as $sh):
                    $sc      = statusClass($sh['run_status']);
                    $inT     = substr($sh['earliest_in']??'',0,5);
                    $outT    = substr($sh['latest_out']??'',0,5);
                    $timeStr = $inT&&$outT ? "$inT – $outT" : ($inT?:($outT?:'—'));
                    $calls   = (int)($sh['total_calls'] ?? 0);
                ?>
            <div class="day-shift-card <?= $sc ?>"
                onclick="openDrawerRun('<?= $dayKey ?>',<?= json_encode($sh['col_run_name']) ?>)">
                <div class="dsc-time"><?= htmlspecialchars($timeStr) ?></div>
                <div class="flex-grow-1">
                    <div class="dsc-title">
                        <?= htmlspecialchars($sh['col_run_name']?:'Unnamed Run') ?>
                    </div>
                    <div class="dsc-sub">
                        <?php if($sh['area_label']): ?>
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($sh['area_label']) ?>
                        <?php endif; ?>
                        <?php if($calls): ?>
                        · <?= $calls ?> call<?= $calls!==1?'s':'' ?>
                        <?php endif; ?>
                        <?php if($sh['sample_pay']): ?>
                        · <span style="color:var(--accent)"><?= htmlspecialchars($sh['sample_pay']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="dsc-badge <?= $sc ?>">
                    <?= statusLabel($sh['run_status']) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>

    <!-- ── Drawer overlay & panel ────────────────────────────────────── -->
    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="drawer" role="dialog" aria-label="Shift details">
        <div class="drawer-header">
            <div>
                <div class="fw-bold" id="drawer-title" style="font-size:1rem">Shift Details</div>
                <div class="drawer-date-badge mt-1" id="drawer-date-badge">
                    <i class="bi bi-calendar3"></i> <span id="drawer-date-label">—</span>
                </div>
            </div>
            <button class="drawer-close" onclick="closeDrawer()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <!--Add visits button here-->
        <div class="drawer-body" id="drawer-body">
            <div class="drawer-empty">
                <i class="bi bi-calendar-event" style="font-size:2rem;opacity:.3"></i>
                <p class="mt-2 mb-0 small">Select a shift to see details</p>
            </div>
        </div>
    </div>

    <?php include 'new-footer.php'; ?>

    <script>
    // ── Data: grouped runs (calendar) + raw calls (drawer) ──────────────────
    const SHIFTS = <?= $shiftsJson ?>; // { date: { run_name: grouped_row } }
    const RAW_ROWS = <?= $rawRowsJson ?>; // { date: { run_name: [call, ...] } }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function fmtDate(ymd) {
        const [y, m, d] = ymd.split('-').map(Number);
        const names = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const dt = new Date(y, m - 1, d);
        return days[dt.getDay()] + ', ' + d + ' ' + names[m - 1] + ' ' + y;
    }

    function statusInfo(s) {
        const l = (s || '').toLowerCase().trim();
        if (l === 'completed') return {
            cls: 'status-done',
            label: 'Completed'
        };
        if (l === 'not completed') return {
            cls: 'status-missed',
            label: 'Not Completed'
        };
        return {
            cls: 'status-sched',
            label: 'Scheduled'
        };
    }

    // Build a single call row for the drawer
    function buildCallItem(sh) {
        const si = statusInfo(sh.call_status);
        const inT = (sh.dateTime_in || '').slice(0, 5);
        const outT = (sh.dateTime_out || '').slice(0, 5);
        const time = (inT && outT) ? `${inT} – ${outT}` : (inT || outT || '—');
        const callIcons = {
            morning: 'bi-sunrise-fill',
            lunch: 'bi-cup-hot-fill',
            tea: 'bi-cup-fill',
            bed: 'bi-moon-stars-fill',
            'extra morning': 'bi-sunrise',
            'extra lunch': 'bi-cup-hot',
            'extra tea': 'bi-cup',
            'extra bed': 'bi-moon-stars'
        };
        const icon = callIcons[(sh.care_calls || '').toLowerCase().trim()] || 'bi-heart-pulse-fill';
        return `<div class="drawer-shift-item ${si.cls}">
            <div class="dsi-header">
                <div class="dsi-title">
                    <i class="bi ${icon} me-1"></i>
                    ${sh.client_name || 'Unknown Client'}
                </div>
                <span class="dsi-status">${si.label}</span>
            </div>
            ${sh.client_area ? `<div class="dsi-row"><i class="bi bi-map"></i><span>${sh.client_area}</span></div>` : ''}
            <div class="dsi-row"><i class="bi bi-clock"></i><strong>${time}</strong></div>
            ${sh.care_calls  ? `<div class="dsi-row"><i class="bi bi-journal-text"></i><span>${sh.care_calls.charAt(0).toUpperCase()+sh.care_calls.slice(1)}</span></div>` : ''}
            ${sh.pay_rate    ? `<span class="dsi-pay"><i class="bi bi-currency-pound me-1"></i>${sh.pay_rate}</span>` : ''}
        </div>`;
    }

    // Build a run section header + its individual calls
    function buildRunSection(runName, calls) {
        const si = statusInfo(calls[0]?.call_status || '');
        const earliest = calls.reduce((a, c) => (!a || c.dateTime_in < a) ? c.dateTime_in : a, null);
        const latest = calls.reduce((a, c) => (!a || c.dateTime_out > a) ? c.dateTime_out : a, null);
        const timeRange = (earliest && latest) ?
            earliest.slice(0, 5) + ' – ' + latest.slice(0, 5) : '—';
        return `
            <div style="margin-bottom:1rem">
                <div style="display:flex;align-items:center;justify-content:space-between;
                            margin-bottom:.6rem;padding:.5rem .75rem;
                            background:var(--pill-bg,#f5f5f5);border-radius:10px;">
                    <div>
                        <div style="font-weight:700;font-size:.92rem">
                            <i class="bi bi-geo-alt me-1" style="color:var(--accent)"></i>
                            ${runName || 'Unnamed Run'}
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted,#888);margin-top:.1rem">
                            <i class="bi bi-clock me-1"></i>${timeRange}
                            &nbsp;·&nbsp;${calls.length} call${calls.length!==1?'s':''}
                        </div>
                    </div>
                </div>
                ${calls.map(buildCallItem).join('')}
            </div>`;
    }

    // ── openDrawer: show all runs for a date ──────────────────────────────────
    function openDrawer(dateStr) {
        const runsObj = SHIFTS[dateStr] || {};
        const rawObj = RAW_ROWS[dateStr] || {};
        const label = fmtDate(dateStr);
        document.getElementById('drawer-date-label').textContent = label;

        const runKeys = Object.keys(runsObj);
        document.getElementById('drawer-title').textContent =
            runKeys.length ?
            `${runKeys.length} run${runKeys.length!==1?'s':''} on this day` :
            'No shifts';

        const body = document.getElementById('drawer-body');
        if (!runKeys.length) {
            body.innerHTML = `<div class="drawer-empty">
                <i class="bi bi-calendar-x" style="font-size:2rem;opacity:.3"></i>
                <p class="mt-2 mb-0 small">No shifts on ${label}</p>
            </div>`;
        } else {
            body.innerHTML = runKeys
                .map(r => buildRunSection(r, rawObj[r] || []))
                .join('');
        }
        _openDrawer();
    }

    // ── openDrawerRun: show one specific run ──────────────────────────────────
    function openDrawerRun(dateStr, runName) {
        const rawObj = RAW_ROWS[dateStr] || {};
        const calls = rawObj[runName] || [];
        document.getElementById('drawer-date-label').textContent = fmtDate(dateStr);
        document.getElementById('drawer-title').textContent =
            runName || 'Run Details';
        const body = document.getElementById('drawer-body');
        body.innerHTML = calls.length ?
            buildRunSection(runName, calls) :
            `<div class="drawer-empty"><p class="small">No calls found for this run.</p></div>`;
        _openDrawer();
    }

    function _openDrawer() {
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

    // ── Week view: current-time line ─────────────────────────────────────
    function updateNowLine() {
        const nl = document.getElementById('now-line');
        if (!nl) return;
        const now = new Date();
        const mins = now.getHours() * 60 + now.getMinutes();
        const top = Math.max(0, (mins - 6 * 60)) / 60 * 56;
        nl.style.top = top + 'px';
    }
    updateNowLine();
    setInterval(updateNowLine, 60000);

    // ── Week view: scroll to working hours on load ────────────────────────
    (function scrollWeek() {
        const wb = document.getElementById('week-body');
        if (!wb) return;
        wb.scrollTop = 2 * 56; // scroll to 08:00 (2 hours from 06:00)
    })();

    // ── Session guard & avatar ────────────────────────────────────────────
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
            window.location.href = './';
            return;
        }

        (function ensureParams() {
            const url = new URL(window.location.href);
            let changed = false;
            const sessId = String(user.user_special_Id || '');
            const sessC = String(user.col_company_Id || '');
            if (sessId && url.searchParams.get('carer_id') !== sessId) {
                url.searchParams.set('carer_id', sessId);
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