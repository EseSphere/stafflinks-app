<?php
$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Pay Calculator – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap"
        rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
    /* ── Layout ──────────────────────────────────────────────────── */
    .calc-container {
        max-width: 960px;
        margin: 0 auto;
        padding: 1.5rem 1rem 5rem;
    }

    /* ── Mode tabs ───────────────────────────────────────────────── */
    .mode-tabs {
        display: flex;
        gap: .5rem;
        background: var(--card-bg, #fff);
        border-radius: 999px;
        padding: .35rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .07);
        margin-bottom: 1.75rem;
        width: fit-content;
    }

    .mode-tab {
        border: none;
        border-radius: 999px;
        padding: .55rem 1.35rem;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        background: transparent;
        color: var(--text-muted, #888);
        transition: background .2s, color .2s;
        white-space: nowrap;
    }

    .mode-tab.active {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
    }

    /* ── Card shell (shared) ─────────────────────────────────────── */
    .calc-card {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 16px rgba(0, 0, 0, .07);
        padding: 1.75rem 1.5rem;
        margin-bottom: 1.25rem;
    }

    .calc-card-title {
        font-family: 'DM Serif Display', Georgia, serif;
        font-size: 1.15rem;
        font-weight: 400;
        margin-bottom: 1.1rem;
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .calc-card-title i {
        color: var(--accent, #c94a57);
    }

    /* ── Form fields ─────────────────────────────────────────────── */
    .field-group {
        margin-bottom: 1.1rem;
    }

    .field-label {
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--text-muted, #888);
        margin-bottom: .4rem;
        display: block;
    }

    .field-row {
        display: flex;
        align-items: stretch;
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 12px;
        overflow: hidden;
        transition: border-color .2s;
        background: var(--input-bg, #fafafa);
    }

    .field-row:focus-within {
        border-color: var(--accent, #c94a57);
        box-shadow: 0 0 0 3px rgba(201, 74, 87, .1);
    }

    .field-prefix,
    .field-suffix {
        padding: .65rem .9rem;
        background: var(--pill-bg, #f0f0f0);
        color: var(--text-muted, #888);
        font-size: .85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        user-select: none;
    }

    .field-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        padding: .65rem .85rem;
        font-size: .95rem;
        font-weight: 500;
        color: var(--text-main, #1a1a1a);
        min-width: 0;
    }

    .field-input::placeholder {
        color: var(--text-muted, #bbb);
        font-weight: 400;
    }

    select.field-select {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        padding: .65rem .85rem;
        font-size: .92rem;
        font-weight: 500;
        color: var(--text-main, #1a1a1a);
        cursor: pointer;
    }

    /* ── Two-col grid for form ───────────────────────────────────── */
    .fields-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    @media (max-width: 560px) {
        .fields-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ── Shift entry (quick mode) ────────────────────────────────── */
    .shift-entry-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: .6rem;
        align-items: end;
        margin-bottom: .6rem;
        padding-bottom: .6rem;
        border-bottom: 1px solid var(--border-color, #f0f0f0);
    }

    .shift-entry-row:last-of-type {
        border-bottom: none;
        margin-bottom: 0;
    }

    @media (max-width: 600px) {
        .shift-entry-row {
            grid-template-columns: 1fr 1fr;
        }

        .shift-entry-row .remove-btn {
            grid-column: span 2;
            justify-self: end;
        }
    }

    .remove-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        background: transparent;
        color: var(--text-muted, #aaa);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .2s, color .2s, border-color .2s;
        flex-shrink: 0;
    }

    .remove-btn:hover {
        background: #ffe4e6;
        color: #dc2626;
        border-color: #fecdd3;
    }

    .btn-add-shift {
        border: 1.5px dashed var(--border-color, #d0d0d0);
        background: transparent;
        border-radius: 10px;
        padding: .55rem 1rem;
        font-size: .83rem;
        font-weight: 600;
        color: var(--text-muted, #888);
        cursor: pointer;
        width: 100%;
        transition: border-color .2s, color .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
    }

    .btn-add-shift:hover {
        border-color: var(--accent, #c94a57);
        color: var(--accent, #c94a57);
    }

    /* ── Calculate button ────────────────────────────────────────── */
    .btn-calculate {
        background: linear-gradient(135deg, var(--accent, #c94a57) 0%, var(--accent2, #e05c6e) 100%);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: .8rem 2.5rem;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: .02em;
        cursor: pointer;
        transition: opacity .2s, transform .15s, box-shadow .2s;
        box-shadow: 0 4px 16px rgba(201, 74, 87, .3);
    }

    .btn-calculate:hover {
        opacity: .88;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(201, 74, 87, .35);
    }

    .btn-calculate:active {
        transform: translateY(0);
    }

    /* ── Results panel ───────────────────────────────────────────── */
    .results-panel {
        display: none;
        animation: slideUp .35s ease;
    }

    .results-panel.visible {
        display: block;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ── Hero result ─────────────────────────────────────────────── */
    .result-hero {
        background: linear-gradient(135deg, var(--accent, #c94a57) 0%, var(--accent2, #e05c6e) 100%);
        border-radius: var(--radius, 18px);
        color: #fff;
        padding: 2rem 1.75rem;
        margin-bottom: 1.25rem;
        position: relative;
        overflow: hidden;
    }

    .result-hero::after {
        content: '£';
        position: absolute;
        right: -10px;
        top: -20px;
        font-family: 'DM Serif Display', Georgia, serif;
        font-size: 10rem;
        opacity: .06;
        line-height: 1;
        pointer-events: none;
    }

    .result-hero-label {
        font-size: .82rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        opacity: .75;
        margin-bottom: .35rem;
    }

    .result-hero-amount {
        font-family: 'DM Serif Display', Georgia, serif;
        font-size: 3.2rem;
        font-weight: 400;
        line-height: 1;
        margin-bottom: .25rem;
    }

    .result-hero-sub {
        opacity: .75;
        font-size: .88rem;
    }

    /* ── Breakdown grid ──────────────────────────────────────────── */
    .breakdown-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .breakdown-item {
        background: var(--card-bg, #fff);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        padding: 1rem 1.1rem;
    }

    .breakdown-item .bi-label {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-muted, #888);
        margin-bottom: .35rem;
    }

    .breakdown-item .bi-value {
        font-family: 'DM Serif Display', Georgia, serif;
        font-size: 1.55rem;
        color: var(--text-main, #1a1a1a);
    }

    .breakdown-item .bi-value.green {
        color: #15803d;
    }

    .breakdown-item .bi-value.red {
        color: #dc2626;
    }

    .breakdown-item .bi-value.blue {
        color: #1d4ed8;
    }

    .breakdown-item .bi-note {
        font-size: .73rem;
        color: var(--text-muted, #aaa);
        margin-top: .2rem;
    }

    /* ── Tax breakdown table ─────────────────────────────────────── */
    .tax-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .88rem;
    }

    .tax-table th {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-muted, #888);
        padding: .5rem .75rem;
        border-bottom: 1.5px solid var(--border-color, #ebebeb);
        text-align: left;
    }

    .tax-table td {
        padding: .65rem .75rem;
        border-bottom: 1px solid var(--border-color, #f5f5f5);
        color: var(--text-main, #1a1a1a);
    }

    .tax-table tr:last-child td {
        border-bottom: none;
    }

    .tax-table .td-amount {
        text-align: right;
        font-weight: 700;
    }

    .tax-table .td-deduct {
        text-align: right;
        font-weight: 700;
        color: #dc2626;
    }

    .tax-table .td-net {
        text-align: right;
        font-weight: 700;
        color: #15803d;
    }

    .tax-table .td-muted {
        color: var(--text-muted, #aaa);
        font-size: .78rem;
    }

    .tax-table .row-total td {
        background: var(--pill-bg, #f7f7f7);
        font-weight: 700;
        font-size: .92rem;
    }

    /* ── Disclaimer ──────────────────────────────────────────────── */
    .disclaimer {
        font-size: .75rem;
        color: var(--text-muted, #aaa);
        line-height: 1.6;
        padding: .85rem 1rem;
        background: var(--pill-bg, #f8f8f8);
        border-radius: 10px;
        border-left: 3px solid var(--border-color, #ddd);
    }

    /* ── Info chips ──────────────────────────────────────────────── */
    .info-row {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1rem;
    }

    .info-chip {
        background: var(--pill-bg, #f5f5f5);
        border-radius: 999px;
        padding: .3rem .85rem;
        font-size: .77rem;
        font-weight: 600;
        color: var(--text-muted, #666);
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }

    .info-chip i {
        color: var(--accent, #c94a57);
    }

    /* ── Mileage specific ─────────────────────────────────────────── */
    .mileage-note {
        background: #fffbeb;
        border: 1.5px solid #fde68a;
        border-radius: 10px;
        padding: .7rem 1rem;
        font-size: .82rem;
        color: #92400e;
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        margin-top: .75rem;
    }

    /* ── Section fade ─────────────────────────────────────────────── */
    .section-fade {
        opacity: 0;
        animation: fadeUp .4s ease forwards;
    }

    .section-fade:nth-child(1) {
        animation-delay: .05s;
    }

    .section-fade:nth-child(2) {
        animation-delay: .12s;
    }

    .section-fade:nth-child(3) {
        animation-delay: .19s;
    }

    .section-fade:nth-child(4) {
        animation-delay: .26s;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- ── Top bar ─────────────────────────────────────────────────────── -->
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
                        <i class="bi bi-bell"></i>
                        <span class="alert-dot">4</span>
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
                            <h2 class="mb-0 fw-bold">Pay Calculator</h2>
                            <div class="text-white-50" style="font-size:.85rem">Estimate your take-home pay</div>
                        </div>
                    </div>
                    <span class="chip">
                        <i class="bi bi-calculator-fill me-1"></i> 2024/25 Tax Year
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="calc-container">

        <!-- ── Mode tabs ─────────────────────────────────────────────── -->
        <div class="mode-tabs section-fade">
            <button class="mode-tab active" onclick="switchMode('shift')" id="tab-shift">
                <i class="bi bi-clock me-1"></i> Single Shift
            </button>
            <button class="mode-tab" onclick="switchMode('multi')" id="tab-multi">
                <i class="bi bi-list-check me-1"></i> Multiple Shifts
            </button>
            <button class="mode-tab" onclick="switchMode('annual')" id="tab-annual">
                <i class="bi bi-graph-up me-1"></i> Annual Estimate
            </button>
            <button class="mode-tab" onclick="switchMode('mileage')" id="tab-mileage">
                <i class="bi bi-car-front me-1"></i> Mileage
            </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             MODE 1 — Single Shift
        ════════════════════════════════════════════════════════════════ -->
        <div id="mode-shift" class="section-fade">
            <div class="calc-card">
                <div class="calc-card-title">
                    <i class="bi bi-clock-fill"></i> Shift Details
                </div>

                <div class="info-row">
                    <span class="info-chip"><i class="bi bi-info-circle"></i> UK 2024/25 rates</span>
                    <span class="info-chip"><i class="bi bi-shield-check"></i> PAYE & NI included</span>
                </div>

                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">Start Time</label>
                        <div class="field-row">
                            <input type="time" class="field-input" id="s-timeIn" value="08:00">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">End Time</label>
                        <div class="field-row">
                            <input type="time" class="field-input" id="s-timeOut" value="14:00">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Hourly Pay Rate</label>
                        <div class="field-row">
                            <span class="field-prefix">£</span>
                            <input type="number" class="field-input" id="s-rate" value="12.50" min="0" step="0.01"
                                placeholder="12.50">
                            <span class="field-suffix">/ hr</span>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Employment Type</label>
                        <div class="field-row">
                            <select class="field-select" id="s-empType">
                                <option value="paye">PAYE (Employed)</option>
                                <option value="self">Self-Employed</option>
                            </select>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Unpaid Break</label>
                        <div class="field-row">
                            <input type="number" class="field-input" id="s-break" value="0" min="0" step="5"
                                placeholder="0">
                            <span class="field-suffix">mins</span>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Tax Code</label>
                        <div class="field-row">
                            <input type="text" class="field-input" id="s-taxCode" value="1257L" placeholder="1257L"
                                maxlength="8">
                        </div>
                    </div>
                </div>

                <button class="btn-calculate mt-2" onclick="calcShift()">
                    <i class="bi bi-calculator me-2"></i>Calculate Pay
                </button>
            </div>

            <!-- Results -->
            <div class="results-panel" id="shift-results">
                <div class="result-hero">
                    <div class="result-hero-label">Estimated Take-Home</div>
                    <div class="result-hero-amount" id="sr-takehome">£0.00</div>
                    <div class="result-hero-sub" id="sr-hours">for 0h 00m</div>
                </div>

                <div class="breakdown-grid">
                    <div class="breakdown-item">
                        <div class="bi-label">Gross Pay</div>
                        <div class="bi-value" id="sr-gross">£0.00</div>
                        <div class="bi-note" id="sr-rate-note">at £0.00/hr</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Income Tax</div>
                        <div class="bi-value red" id="sr-tax">−£0.00</div>
                        <div class="bi-note">PAYE deduction</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">National Insurance</div>
                        <div class="bi-value red" id="sr-ni">−£0.00</div>
                        <div class="bi-note">Employee NI</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Effective Rate</div>
                        <div class="bi-value blue" id="sr-eff">0%</div>
                        <div class="bi-note">of gross</div>
                    </div>
                </div>

                <div class="calc-card">
                    <div class="calc-card-title">
                        <i class="bi bi-table"></i> Full Breakdown
                    </div>
                    <table class="tax-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="text-align:right">Amount</th>
                                <th style="text-align:right">Notes</th>
                            </tr>
                        </thead>
                        <tbody id="sr-table-body">
                        </tbody>
                    </table>
                    <div class="disclaimer mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        This is an <strong>estimate only</strong> based on 2024/25 UK tax rates and a standard
                        personal allowance (1257L). Actual deductions depend on your full-year income,
                        other employments, and HMRC coding notices. Always check your payslip.
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             MODE 2 — Multiple Shifts
        ════════════════════════════════════════════════════════════════ -->
        <div id="mode-multi" class="section-fade" style="display:none">
            <div class="calc-card">
                <div class="calc-card-title">
                    <i class="bi bi-list-check"></i> Multiple Shift Entry
                </div>

                <div class="info-row">
                    <span class="info-chip"><i class="bi bi-info-circle"></i> Add all shifts then calculate</span>
                    <span class="info-chip"><i class="bi bi-shield-check"></i> Cumulative tax applied</span>
                </div>

                <div id="multi-shifts-list"></div>

                <button class="btn-add-shift mt-2" onclick="addShiftRow()">
                    <i class="bi bi-plus-circle"></i> Add Another Shift
                </button>

                <div class="fields-grid mt-3">
                    <div class="field-group">
                        <label class="field-label">Employment Type</label>
                        <div class="field-row">
                            <select class="field-select" id="m-empType">
                                <option value="paye">PAYE (Employed)</option>
                                <option value="self">Self-Employed</option>
                            </select>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Tax Code</label>
                        <div class="field-row">
                            <input type="text" class="field-input" id="m-taxCode" value="1257L" maxlength="8">
                        </div>
                    </div>
                </div>

                <button class="btn-calculate mt-2" onclick="calcMulti()">
                    <i class="bi bi-calculator me-2"></i>Calculate Total Pay
                </button>
            </div>

            <div class="results-panel" id="multi-results">
                <div class="result-hero">
                    <div class="result-hero-label">Total Estimated Take-Home</div>
                    <div class="result-hero-amount" id="mr-takehome">£0.00</div>
                    <div class="result-hero-sub" id="mr-hours">for 0 shifts · 0h total</div>
                </div>
                <div class="breakdown-grid">
                    <div class="breakdown-item">
                        <div class="bi-label">Gross Pay</div>
                        <div class="bi-value" id="mr-gross">£0.00</div>
                        <div class="bi-note">before deductions</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Income Tax</div>
                        <div class="bi-value red" id="mr-tax">−£0.00</div>
                        <div class="bi-note">PAYE deduction</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">National Insurance</div>
                        <div class="bi-value red" id="mr-ni">−£0.00</div>
                        <div class="bi-note">Employee NI</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Shifts Entered</div>
                        <div class="bi-value blue" id="mr-count">0</div>
                        <div class="bi-note">total shifts</div>
                    </div>
                </div>
                <div class="calc-card">
                    <div class="calc-card-title"><i class="bi bi-table"></i> Per-Shift Breakdown</div>
                    <table class="tax-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Time</th>
                                <th>Hours</th>
                                <th style="text-align:right">Gross</th>
                                <th style="text-align:right">Tax</th>
                                <th style="text-align:right">NI</th>
                                <th style="text-align:right">Take-Home</th>
                            </tr>
                        </thead>
                        <tbody id="mr-table-body"></tbody>
                    </table>
                    <div class="disclaimer mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Tax is apportioned pro-rata across shifts. Actual deductions depend on your
                        cumulative income and HMRC tax code. This is an estimate only.
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             MODE 3 — Annual Estimate
        ════════════════════════════════════════════════════════════════ -->
        <div id="mode-annual" class="section-fade" style="display:none">
            <div class="calc-card">
                <div class="calc-card-title">
                    <i class="bi bi-graph-up"></i> Annual Pay Estimate
                </div>
                <div class="info-row">
                    <span class="info-chip"><i class="bi bi-calendar"></i> Full tax year 2024/25</span>
                    <span class="info-chip"><i class="bi bi-shield-check"></i> PAYE, NI & Student Loan</span>
                </div>
                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">Gross Annual Salary / Income</label>
                        <div class="field-row">
                            <span class="field-prefix">£</span>
                            <input type="number" class="field-input" id="a-annual" value="24000" min="0" step="100"
                                placeholder="24000">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Employment Type</label>
                        <div class="field-row">
                            <select class="field-select" id="a-empType">
                                <option value="paye">PAYE (Employed)</option>
                                <option value="self">Self-Employed</option>
                            </select>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Tax Code</label>
                        <div class="field-row">
                            <input type="text" class="field-input" id="a-taxCode" value="1257L" maxlength="8">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Student Loan Plan</label>
                        <div class="field-row">
                            <select class="field-select" id="a-loan">
                                <option value="none">None</option>
                                <option value="plan1">Plan 1 (pre-2012)</option>
                                <option value="plan2">Plan 2 (post-2012)</option>
                                <option value="plan4">Plan 4 (Scotland)</option>
                                <option value="plan5">Plan 5 (from Aug 2023)</option>
                            </select>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Pension Contribution</label>
                        <div class="field-row">
                            <input type="number" class="field-input" id="a-pension" value="0" min="0" max="100"
                                step="0.5" placeholder="0">
                            <span class="field-suffix">%</span>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Pay Frequency</label>
                        <div class="field-row">
                            <select class="field-select" id="a-freq">
                                <option value="52">Weekly</option>
                                <option value="26">Fortnightly</option>
                                <option value="12" selected>Monthly</option>
                                <option value="4">Quarterly</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button class="btn-calculate mt-2" onclick="calcAnnual()">
                    <i class="bi bi-calculator me-2"></i>Calculate Annual Pay
                </button>
            </div>

            <div class="results-panel" id="annual-results">
                <div class="result-hero">
                    <div class="result-hero-label">Annual Take-Home Pay</div>
                    <div class="result-hero-amount" id="ar-takehome">£0.00</div>
                    <div class="result-hero-sub" id="ar-period">£0.00 per month</div>
                </div>

                <div class="breakdown-grid">
                    <div class="breakdown-item">
                        <div class="bi-label">Gross Income</div>
                        <div class="bi-value" id="ar-gross">£0.00</div>
                        <div class="bi-note">before tax</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Income Tax</div>
                        <div class="bi-value red" id="ar-tax">−£0.00</div>
                        <div class="bi-note">PAYE/IT</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">National Insurance</div>
                        <div class="bi-value red" id="ar-ni">−£0.00</div>
                        <div class="bi-note" id="ar-ni-note">Employee NI</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Student Loan</div>
                        <div class="bi-value red" id="ar-loan">−£0.00</div>
                        <div class="bi-note" id="ar-loan-note">not applicable</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Pension</div>
                        <div class="bi-value red" id="ar-pension">−£0.00</div>
                        <div class="bi-note" id="ar-pension-note">0% contribution</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Effective Tax Rate</div>
                        <div class="bi-value blue" id="ar-eff">0%</div>
                        <div class="bi-note">of gross income</div>
                    </div>
                </div>

                <div class="calc-card">
                    <div class="calc-card-title"><i class="bi bi-table"></i> Annual Tax Breakdown</div>
                    <table class="tax-table">
                        <thead>
                            <tr>
                                <th>Band / Item</th>
                                <th style="text-align:right">Taxable</th>
                                <th style="text-align:right">Rate</th>
                                <th style="text-align:right">Tax</th>
                            </tr>
                        </thead>
                        <tbody id="ar-table-body"></tbody>
                    </table>
                    <div class="disclaimer mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Based on 2024/25 UK tax bands: Personal Allowance £12,570 · Basic rate 20% (£12,571–£50,270)
                        · Higher rate 40% (£50,271–£125,140) · Additional rate 45% above £125,140.
                        NI: Class 1 employee 8% on £12,570–£50,270, 2% above. This is an estimate only.
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             MODE 4 — Mileage
        ════════════════════════════════════════════════════════════════ -->
        <div id="mode-mileage" class="section-fade" style="display:none">
            <div class="calc-card">
                <div class="calc-card-title">
                    <i class="bi bi-car-front-fill"></i> Mileage Allowance Calculator
                </div>
                <div class="info-row">
                    <span class="info-chip"><i class="bi bi-info-circle"></i> HMRC approved rates 2024/25</span>
                    <span class="info-chip"><i class="bi bi-fuel-pump"></i> Tax-free allowance</span>
                </div>
                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">Total Miles Driven</label>
                        <div class="field-row">
                            <input type="number" class="field-input" id="mi-miles" value="100" min="0" step="1"
                                placeholder="100">
                            <span class="field-suffix">miles</span>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Vehicle Type</label>
                        <div class="field-row">
                            <select class="field-select" id="mi-vehicle">
                                <option value="car">Car / Van</option>
                                <option value="motor">Motorcycle</option>
                                <option value="cycle">Bicycle</option>
                            </select>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Employer Pays (per mile)</label>
                        <div class="field-row">
                            <span class="field-prefix">£</span>
                            <input type="number" class="field-input" id="mi-employer" value="0.45" min="0" step="0.01"
                                placeholder="0.45">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Period</label>
                        <div class="field-row">
                            <select class="field-select" id="mi-period">
                                <option value="one">One-off journey</option>
                                <option value="week">Per week</option>
                                <option value="month">Per month</option>
                                <option value="year">Per year</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mileage-note">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        HMRC approved mileage rates (2024/25): <strong>Cars & vans</strong> 45p/mile (first 10,000),
                        25p thereafter · <strong>Motorcycles</strong> 24p/mile · <strong>Bicycles</strong> 20p/mile.
                        Any excess over HMRC rate paid by employer is taxable.
                    </div>
                </div>
                <button class="btn-calculate mt-3" onclick="calcMileage()">
                    <i class="bi bi-calculator me-2"></i>Calculate Mileage Pay
                </button>
            </div>

            <div class="results-panel" id="mileage-results">
                <div class="result-hero">
                    <div class="result-hero-label">Total Mileage Allowance</div>
                    <div class="result-hero-amount" id="mi-total">£0.00</div>
                    <div class="result-hero-sub" id="mi-sub">0 miles · £0.00/mile</div>
                </div>
                <div class="breakdown-grid">
                    <div class="breakdown-item">
                        <div class="bi-label">HMRC Approved</div>
                        <div class="bi-value green" id="mi-approved">£0.00</div>
                        <div class="bi-note">tax-free amount</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Employer Pays</div>
                        <div class="bi-value" id="mi-empTotal">£0.00</div>
                        <div class="bi-note">at your rate</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Taxable Excess</div>
                        <div class="bi-value red" id="mi-excess">£0.00</div>
                        <div class="bi-note">above HMRC rate</div>
                    </div>
                    <div class="breakdown-item">
                        <div class="bi-label">Relief Claimable</div>
                        <div class="bi-value blue" id="mi-relief">£0.00</div>
                        <div class="bi-note">if underpaid by employer</div>
                    </div>
                </div>
                <div class="calc-card">
                    <div class="calc-card-title"><i class="bi bi-table"></i> Mileage Breakdown</div>
                    <table class="tax-table">
                        <thead>
                            <tr>
                                <th>Band</th>
                                <th style="text-align:right">Miles</th>
                                <th style="text-align:right">Rate</th>
                                <th style="text-align:right">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="mi-table-body"></tbody>
                    </table>
                    <div class="disclaimer mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        If your employer pays less than the HMRC approved amount you can claim Mileage Allowance
                        Relief (MAR) via your Self Assessment or by contacting HMRC. If they pay more, the excess
                        is treated as taxable income.
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include 'new-footer.php'; ?>

    <script>
    // ═══════════════════════════════════════════════════════════════════════════
    // UK Tax Engine — 2024/25
    // ═══════════════════════════════════════════════════════════════════════════
    const TAX = {
        personalAllowance: 12570,
        bands: [{
                name: 'Personal Allowance',
                min: 0,
                max: 12570,
                rate: 0
            },
            {
                name: 'Basic Rate',
                min: 12570,
                max: 50270,
                rate: 0.20
            },
            {
                name: 'Higher Rate',
                min: 50270,
                max: 125140,
                rate: 0.40
            },
            {
                name: 'Additional Rate',
                min: 125140,
                max: Infinity,
                rate: 0.45
            },
        ],
        // NI Class 1 Employee 2024/25
        ni: {
            paye: {
                lower: 12570,
                upper: 50270,
                mainRate: 0.08,
                upperRate: 0.02
            },
            // Class 4 Self-Employed
            self: {
                lower: 12570,
                upper: 50270,
                mainRate: 0.06,
                upperRate: 0.02
            },
        },
        studentLoan: {
            plan1: {
                threshold: 24990,
                rate: 0.09
            },
            plan2: {
                threshold: 27295,
                rate: 0.09
            },
            plan4: {
                threshold: 31395,
                rate: 0.09
            },
            plan5: {
                threshold: 25000,
                rate: 0.09
            },
        }
    };

    // Parse tax code to get personal allowance (simplified — handles L codes)
    function parseTaxCode(code) {
        if (!code) return TAX.personalAllowance;
        const m = code.trim().toUpperCase().match(/^(\d+)L$/);
        if (m) return parseInt(m[1]) * 10;
        if (code.toUpperCase() === 'BR') return 0;
        if (code.toUpperCase() === 'NT') return 9999999;
        if (code.toUpperCase() === '0T') return 0;
        return TAX.personalAllowance;
    }

    // Annual income tax for given gross & personal allowance
    function calcIncomeTax(gross, personalAllowance) {
        // Taper allowance above £100k
        let pa = personalAllowance;
        if (gross > 100000) pa = Math.max(0, pa - Math.floor((gross - 100000) / 2));
        const bands = [{
                min: 0,
                max: pa,
                rate: 0
            },
            {
                min: pa,
                max: 50270,
                rate: 0.20
            },
            {
                min: 50270,
                max: 125140,
                rate: 0.40
            },
            {
                min: 125140,
                max: Infinity,
                rate: 0.45
            },
        ];
        let tax = 0;
        const detail = [];
        for (const b of bands) {
            const taxable = Math.max(0, Math.min(gross, b.max) - b.min);
            const t = taxable * b.rate;
            tax += t;
            if (b.rate > 0 || taxable > 0)
                detail.push({
                    name: b.rate === 0 ? 'Personal Allowance (0%)' : `${(b.rate*100).toFixed(0)}% Band`,
                    taxable,
                    rate: b.rate,
                    tax: t
                });
        }
        return {
            tax: Math.max(0, tax),
            detail
        };
    }

    // Annual NI
    function calcNI(gross, type) {
        const ni = TAX.ni[type] || TAX.ni.paye;
        const lower = Math.max(0, Math.min(gross, ni.upper) - ni.lower);
        const upper = Math.max(0, gross - ni.upper);
        return (lower * ni.mainRate) + (upper * ni.upperRate);
    }

    // Student loan repayment
    function calcStudentLoan(gross, plan) {
        if (!plan || plan === 'none') return 0;
        const p = TAX.studentLoan[plan];
        if (!p) return 0;
        return Math.max(0, (gross - p.threshold) * p.rate);
    }

    // Hours between two HH:MM strings (handles overnight)
    function hoursFromTimes(inStr, outStr, breakMins) {
        const [ih, im] = inStr.split(':').map(Number);
        const [oh, om] = outStr.split(':').map(Number);
        let mins = (oh * 60 + om) - (ih * 60 + im);
        if (mins <= 0) mins += 24 * 60; // overnight
        mins -= (breakMins || 0);
        return Math.max(0, mins / 60);
    }

    function fmt(n) {
        return '£' + Math.abs(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function fmtH(h) {
        const hh = Math.floor(h),
            mm = Math.round((h - hh) * 60);
        return hh + 'h ' + String(mm).padStart(2, '0') + 'm';
    }

    // Pro-rate annual deductions to a single shift gross
    function proRateDeductions(shiftGross, annualGross, empType, taxCode) {
        if (annualGross <= 0) return {
            tax: 0,
            ni: 0
        };
        const pa = parseTaxCode(taxCode);
        const {
            tax: annTax
        } = calcIncomeTax(annualGross, pa);
        const annNI = calcNI(annualGross, empType);
        const ratio = shiftGross / annualGross;
        return {
            tax: annTax * ratio,
            ni: annNI * ratio
        };
    }

    // ── Mode switcher ──────────────────────────────────────────────────────
    function switchMode(m) {
        ['shift', 'multi', 'annual', 'mileage'].forEach(id => {
            document.getElementById('mode-' + id).style.display = id === m ? '' : 'none';
            document.getElementById('tab-' + id).classList.toggle('active', id === m);
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CALC 1 — Single Shift
    // ═══════════════════════════════════════════════════════════════════════
    function calcShift() {
        const inT = document.getElementById('s-timeIn').value;
        const outT = document.getElementById('s-timeOut').value;
        const rate = parseFloat(document.getElementById('s-rate').value) || 0;
        const brk = parseFloat(document.getElementById('s-break').value) || 0;
        const emp = document.getElementById('s-empType').value;
        const code = document.getElementById('s-taxCode').value || '1257L';

        const hours = hoursFromTimes(inT, outT, brk);
        const gross = hours * rate;

        // Estimate annual gross assuming this is representative of 52 weeks
        // Use 48 weeks to allow for holidays — gives a reasonable approximation
        const annualGross = gross * 48;
        const {
            tax,
            ni
        } = proRateDeductions(gross, annualGross, emp, code);
        const pa = parseTaxCode(code);
        const {
            detail
        } = calcIncomeTax(annualGross, pa);
        const takehome = gross - tax - ni;
        const effRate = gross > 0 ? ((tax + ni) / gross * 100) : 0;

        // Update hero
        document.getElementById('sr-takehome').textContent = fmt(takehome);
        document.getElementById('sr-hours').textContent = `for ${fmtH(hours)} · ${inT} – ${outT}`;
        document.getElementById('sr-gross').textContent = fmt(gross);
        document.getElementById('sr-rate-note').textContent = `at £${rate.toFixed(2)}/hr`;
        document.getElementById('sr-tax').textContent = '−' + fmt(tax);
        document.getElementById('sr-ni').textContent = '−' + fmt(ni);
        document.getElementById('sr-eff').textContent = effRate.toFixed(1) + '%';

        // Table
        const rows = [
            ['Gross Shift Pay', '', fmt(gross), ''],
            ['Hours Worked', fmtH(hours), '', ''],
            ['Hourly Rate', `£${rate.toFixed(2)}/hr`, '', ''],
            ['Income Tax (est.)', '', '', '−' + fmt(tax)],
            ['Nat. Insurance', emp === 'self' ? 'Class 4' : 'Class 1 Emp.', '', '−' + fmt(ni)],
            ['Take-Home Pay', '', '', fmt(takehome)],
        ];
        const tbody = document.getElementById('sr-table-body');
        tbody.innerHTML = rows.map((r, i) => {
            const cls = i === rows.length - 1 ? 'row-total' : '';
            return `<tr class="${cls}">
                <td>${r[0]}</td>
                <td class="td-amount td-muted">${r[1]}</td>
                <td class="td-amount">${r[2]}</td>
                <td class="${r[3].startsWith('−') ? 'td-deduct' : 'td-net'}">${r[3]}</td>
            </tr>`;
        }).join('');

        document.getElementById('shift-results').classList.add('visible');
        document.getElementById('shift-results').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CALC 2 — Multiple Shifts
    // ═══════════════════════════════════════════════════════════════════════
    let shiftRows = [];

    function addShiftRow(defaultIn = '09:00', defaultOut = '17:00', defaultRate = 12.50) {
        const id = Date.now();
        shiftRows.push(id);

        const container = document.getElementById('multi-shifts-list');
        const div = document.createElement('div');
        div.className = 'shift-entry-row';
        div.id = 'sr-' + id;
        div.innerHTML = `
            <div class="field-group mb-0">
                <label class="field-label">Start</label>
                <div class="field-row">
                    <input type="time" class="field-input" id="mi-in-${id}" value="${defaultIn}">
                </div>
            </div>
            <div class="field-group mb-0">
                <label class="field-label">End</label>
                <div class="field-row">
                    <input type="time" class="field-input" id="mi-out-${id}" value="${defaultOut}">
                </div>
            </div>
            <div class="field-group mb-0">
                <label class="field-label">Rate (£/hr)</label>
                <div class="field-row">
                    <span class="field-prefix">£</span>
                    <input type="number" class="field-input" id="mi-rate-${id}"
                           value="${defaultRate}" min="0" step="0.01">
                </div>
            </div>
            <button class="remove-btn" onclick="removeShiftRow(${id})" title="Remove">
                <i class="bi bi-trash3"></i>
            </button>`;
        container.appendChild(div);
    }

    function removeShiftRow(id) {
        const el = document.getElementById('sr-' + id);
        if (el) el.remove();
        shiftRows = shiftRows.filter(r => r !== id);
    }

    function calcMulti() {
        const emp = document.getElementById('m-empType').value;
        const code = document.getElementById('m-taxCode').value || '1257L';

        const shifts = shiftRows.map(id => {
            const inT = document.getElementById(`mi-in-${id}`)?.value || '09:00';
            const outT = document.getElementById(`mi-out-${id}`)?.value || '17:00';
            const rate = parseFloat(document.getElementById(`mi-rate-${id}`)?.value) || 0;
            const hrs = hoursFromTimes(inT, outT, 0);
            return {
                inT,
                outT,
                rate,
                hrs,
                gross: hrs * rate
            };
        }).filter(s => s.hrs > 0 && s.gross > 0);

        if (shifts.length === 0) {
            alert('Please add at least one valid shift.');
            return;
        }

        const totalGross = shifts.reduce((a, s) => a + s.gross, 0);
        const totalHours = shifts.reduce((a, s) => a + s.hrs, 0);
        const annualGross = totalGross * 48;

        const pa = parseTaxCode(code);
        const {
            tax: annTax
        } = calcIncomeTax(annualGross, pa);
        const annNI = calcNI(annualGross, emp);
        const totalTax = (annTax / annualGross) * totalGross;
        const totalNI = (annNI / annualGross) * totalGross;
        const totalNet = totalGross - totalTax - totalNI;

        document.getElementById('mr-takehome').textContent = fmt(totalNet);
        document.getElementById('mr-hours').textContent =
            `${shifts.length} shift${shifts.length !== 1 ? 's' : ''} · ${fmtH(totalHours)} total`;
        document.getElementById('mr-gross').textContent = fmt(totalGross);
        document.getElementById('mr-tax').textContent = '−' + fmt(totalTax);
        document.getElementById('mr-ni').textContent = '−' + fmt(totalNI);
        document.getElementById('mr-count').textContent = shifts.length;

        const tbody = document.getElementById('mr-table-body');
        const ratio = totalGross > 0 ? (totalTax + totalNI) / totalGross : 0;
        tbody.innerHTML = shifts.map((s, i) => {
            const ded = s.gross * ratio;
            const net = s.gross - ded;
            const tax = s.gross * (totalTax / totalGross);
            const ni = s.gross * (totalNI / totalGross);
            return `<tr>
                <td>${i + 1}</td>
                <td class="td-muted">${s.inT} – ${s.outT}</td>
                <td>${fmtH(s.hrs)}</td>
                <td class="td-amount">${fmt(s.gross)}</td>
                <td class="td-deduct">−${fmt(tax)}</td>
                <td class="td-deduct">−${fmt(ni)}</td>
                <td class="td-net">${fmt(net)}</td>
            </tr>`;
        }).join('') + `<tr class="row-total">
            <td colspan="3">Total</td>
            <td class="td-amount">${fmt(totalGross)}</td>
            <td class="td-deduct">−${fmt(totalTax)}</td>
            <td class="td-deduct">−${fmt(totalNI)}</td>
            <td class="td-net">${fmt(totalNet)}</td>
        </tr>`;

        document.getElementById('multi-results').classList.add('visible');
        document.getElementById('multi-results').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CALC 3 — Annual
    // ═══════════════════════════════════════════════════════════════════════
    function calcAnnual() {
        const gross = parseFloat(document.getElementById('a-annual').value) || 0;
        const emp = document.getElementById('a-empType').value;
        const code = document.getElementById('a-taxCode').value || '1257L';
        const loan = document.getElementById('a-loan').value;
        const pension = parseFloat(document.getElementById('a-pension').value) || 0;
        const freq = parseInt(document.getElementById('a-freq').value) || 12;

        const pensionAmt = gross * (pension / 100);
        const taxableGross = gross - pensionAmt;

        const pa = parseTaxCode(code);
        const {
            tax: itax,
            detail
        } = calcIncomeTax(taxableGross, pa);
        const ni = calcNI(taxableGross, emp);
        const sl = calcStudentLoan(gross, loan);
        const totalDed = itax + ni + sl + pensionAmt;
        const net = gross - totalDed;
        const eff = gross > 0 ? (totalDed / gross * 100) : 0;

        document.getElementById('ar-takehome').textContent = fmt(net);
        document.getElementById('ar-period').textContent =
            `${fmt(net / freq)} per ${freq === 52 ? 'week' : freq === 26 ? 'fortnight' : freq === 12 ? 'month' : 'quarter'}`;
        document.getElementById('ar-gross').textContent = fmt(gross);
        document.getElementById('ar-tax').textContent = '−' + fmt(itax);
        document.getElementById('ar-ni').textContent = '−' + fmt(ni);
        document.getElementById('ar-ni-note').textContent = emp === 'self' ? 'Class 4 NI' : 'Class 1 Employee NI';
        document.getElementById('ar-loan').textContent = loan === 'none' ? '£0.00' : '−' + fmt(sl);
        document.getElementById('ar-loan-note').textContent = loan === 'none' ? 'no student loan' :
            `Plan ${loan.slice(-1)} repayment`;
        document.getElementById('ar-pension').textContent = pension > 0 ? '−' + fmt(pensionAmt) : '£0.00';
        document.getElementById('ar-pension-note').textContent = pension > 0 ? `${pension}% contribution` :
            'no pension deduction';
        document.getElementById('ar-eff').textContent = eff.toFixed(1) + '%';

        const tbody = document.getElementById('ar-table-body');
        const bandRows = detail.map(b => `<tr>
            <td>${b.name}</td>
            <td class="td-amount">${b.taxable > 0 ? fmt(b.taxable) : '—'}</td>
            <td class="td-amount">${(b.rate * 100).toFixed(0)}%</td>
            <td class="${b.tax > 0 ? 'td-deduct' : 'td-muted'}">${b.tax > 0 ? '−' + fmt(b.tax) : '—'}</td>
        </tr>`).join('');
        const extraRows = [
            pension > 0 ?
            `<tr><td>Pension (${pension}%)</td><td class="td-amount">${fmt(gross)}</td><td class="td-amount">${pension}%</td><td class="td-deduct">−${fmt(pensionAmt)}</td></tr>` :
            '',
            `<tr><td>National Insurance (${emp === 'self' ? 'Class 4' : 'Class 1'})</td><td class="td-amount">${fmt(taxableGross)}</td><td class="td-amount td-muted">8% / 2%</td><td class="td-deduct">−${fmt(ni)}</td></tr>`,
            loan !== 'none' ?
            `<tr><td>Student Loan (Plan ${loan.slice(-1)})</td><td class="td-amount">${fmt(gross)}</td><td class="td-amount">9%</td><td class="td-deduct">−${fmt(sl)}</td></tr>` :
            '',
            `<tr class="row-total"><td colspan="3">Annual Take-Home</td><td class="td-net">${fmt(net)}</td></tr>`,
        ].join('');
        tbody.innerHTML = bandRows + extraRows;

        document.getElementById('annual-results').classList.add('visible');
        document.getElementById('annual-results').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CALC 4 — Mileage
    // ═══════════════════════════════════════════════════════════════════════
    const MILEAGE_RATES = {
        car: {
            first: 0.45,
            after: 0.25,
            threshold: 10000,
            label: 'Car / Van'
        },
        motor: {
            first: 0.24,
            after: 0.24,
            threshold: Infinity,
            label: 'Motorcycle'
        },
        cycle: {
            first: 0.20,
            after: 0.20,
            threshold: Infinity,
            label: 'Bicycle'
        },
    };

    function calcMileage() {
        const miles = parseFloat(document.getElementById('mi-miles').value) || 0;
        const vehicle = document.getElementById('mi-vehicle').value;
        const empRate = parseFloat(document.getElementById('mi-employer').value) || 0;
        const period = document.getElementById('mi-period').value;

        const vr = MILEAGE_RATES[vehicle];
        const firstBand = Math.min(miles, vr.threshold);
        const afterBand = Math.max(0, miles - vr.threshold);
        const approved = (firstBand * vr.first) + (afterBand * vr.after);
        const empTotal = miles * empRate;
        const excess = Math.max(0, empTotal - approved);
        const relief = Math.max(0, approved - empTotal);

        document.getElementById('mi-total').textContent = fmt(approved);
        document.getElementById('mi-sub').textContent =
            `${miles} miles · £${vr.first.toFixed(2)}/mile${vr.after !== vr.first ? ` then £${vr.after.toFixed(2)}` : ''}`;
        document.getElementById('mi-approved').textContent = fmt(approved);
        document.getElementById('mi-empTotal').textContent = fmt(empTotal);
        document.getElementById('mi-excess').textContent = fmt(excess);
        document.getElementById('mi-relief').textContent = fmt(relief);

        const periodLabel = {
            one: 'journey',
            week: 'week',
            month: 'month',
            year: 'year'
        } [period];

        const rows = [];
        if (firstBand > 0) rows.push([`First ${firstBand.toLocaleString()} miles`, firstBand.toLocaleString(),
            `£${vr.first.toFixed(2)}`, fmt(firstBand * vr.first)
        ]);
        if (afterBand > 0) rows.push([`Next ${afterBand.toLocaleString()} miles`, afterBand.toLocaleString(),
            `£${vr.after.toFixed(2)}`, fmt(afterBand * vr.after)
        ]);
        rows.push(['Employer pays', miles.toLocaleString(), `£${empRate.toFixed(2)}`, fmt(empTotal)]);
        rows.push(['HMRC approved', miles.toLocaleString(), '—', fmt(approved)]);
        if (excess > 0) rows.push(['Taxable excess', '—', '—', fmt(excess)]);
        if (relief > 0) rows.push(['MAR claimable', '—', '—', fmt(relief)]);

        const tbody = document.getElementById('mi-table-body');
        tbody.innerHTML = rows.map((r, i) => {
            const isLast = i === rows.length - 1;
            return `<tr class="${isLast && (excess > 0 || relief > 0) ? 'row-total' : ''}">
                <td>${r[0]}</td>
                <td class="td-amount td-muted">${r[1]}</td>
                <td class="td-amount td-muted">${r[2]}</td>
                <td class="td-net">${r[3]}</td>
            </tr>`;
        }).join('');

        document.getElementById('mileage-results').classList.add('visible');
        document.getElementById('mileage-results').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Init
    // ═══════════════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        // Seed multi-shift with two default rows
        addShiftRow('08:00', '14:00', 12.50);
        addShiftRow('14:00', '20:00', 12.50);

        // Session guard & avatar
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
            if (user.user_special_Id && url.searchParams.get('carer_id') !== String(user.user_special_Id)) {
                url.searchParams.set('carer_id', user.user_special_Id);
                changed = true;
            }
            if (user.col_company_Id && url.searchParams.get('col_company_Id') !== String(user
                    .col_company_Id)) {
                url.searchParams.set('col_company_Id', user.col_company_Id);
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
        const avatar = avatarSrc(user.team_dp);
        const name = user.user_fullname || '';
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
        const wFb = el => {
            if (el) el.onerror = function() {
                if (this.src !== DEFAULT_AVATAR) this.src = DEFAULT_AVATAR;
            };
        };
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

        // Allow Enter key to trigger calculate in single shift mode
        document.querySelectorAll('#mode-shift input').forEach(inp => {
            inp.addEventListener('keydown', e => {
                if (e.key === 'Enter') calcShift();
            });
        });
    });
    </script>

</body>

</html>