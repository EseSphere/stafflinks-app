<?php $pageTitle="StaffLinks Dashboard"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $pageTitle; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">

    <style>
    .feature-card {
        border: 0;
        border-radius: var(--radius);
        box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .shift-market-card,
    .training-card,
    .task-card,
    .wellbeing-card {
        border: 1px solid rgba(201, 74, 87, .12);
        border-radius: 18px;
        padding: 16px;
        background: #fff;
        transition: .25s ease;
    }

    .shift-market-card:hover,
    .training-card:hover,
    .task-card:hover,
    .wellbeing-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 35px rgba(15, 23, 42, .09);
    }

    .shift-rate {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--accent);
    }

    .mini-calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }

    .calendar-day {
        border-radius: 14px;
        padding: 10px 6px;
        text-align: center;
        background: rgba(15, 23, 42, .04);
        font-size: .85rem;
    }

    .calendar-day.active {
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        color: #fff;
        font-weight: 700;
    }

    .calendar-day.alert-day {
        background: rgba(255, 193, 7, .2);
        color: #7a4b00;
        font-weight: 700;
    }

    .emergency-alert {
        border-left: 5px solid #dc3545;
        background: rgba(220, 53, 69, .08);
        border-radius: 16px;
        padding: 16px;
    }

    .travel-pill,
    .skill-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 12px;
        background: rgba(74, 144, 226, .12);
        color: #1d5f9f;
        font-size: .85rem;
        font-weight: 600;
    }

    .swap-box {
        border-radius: 16px;
        background: rgba(15, 23, 42, .035);
        padding: 14px;
    }

    .priority-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        background: var(--accent);
    }

    .pay-box {
        border-radius: 18px;
        padding: 18px;
        background: linear-gradient(135deg, rgba(201, 74, 87, .12), rgba(244, 164, 96, .16));
    }

    .compliance-ring {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        background: conic-gradient(var(--accent) 86%, rgba(15, 23, 42, .08) 0);
        display: grid;
        place-items: center;
        font-weight: 800;
        color: var(--accent);
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

                    <img class="profile-avatar"
                        src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80"
                        alt="Sarah Johnson">
                </div>
            </div>

            <div class="mt-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h2 class="mb-1 fw-bold">Good morning, Sarah 👋</h2>
                        <div class="text-white-50">Here’s your care work overview for today.</div>
                    </div>

                    <span class="chip">
                        <i class="bi bi-shield-check me-1"></i> NHS DBS Verified
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="stats-grid mb-4 fade-in-up">
            <article class="stat-card">
                <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
                <div class="stat-value">28h 15m</div>
                <div class="stat-label">Hours this week</div>
                <div class="progress mt-3" style="height:8px;background:rgba(201,74,87,.12)">
                    <div class="progress-bar"
                        style="width:75%;background:linear-gradient(90deg,var(--accent),var(--accent2))"></div>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-calendar-event-fill"></i></div>
                <div class="stat-value">3</div>
                <div class="stat-label">Upcoming shifts</div>
                <a href="rota.php" class="small text-decoration-none mt-2 d-inline-block" style="color:var(--accent)">
                    View rota <i class="bi bi-arrow-right"></i>
                </a>
            </article>

            <article class="stat-card">
                <div class="stat-icon success"><i class="bi bi-wallet2"></i></div>
                <div class="stat-value">£1,245.60</div>
                <div class="stat-label">Estimated monthly pay</div>
                <a href="calculator.php" class="small text-decoration-none mt-2 d-inline-block"
                    style="color:var(--accent)">
                    Open calculator <i class="bi bi-arrow-right"></i>
                </a>
            </article>

            <article class="stat-card">
                <div class="stat-icon info"><i class="bi bi-umbrella-fill"></i></div>
                <div class="stat-value">12.5</div>
                <div class="stat-label">Leave days remaining</div>
                <a href="leave.php" class="small text-decoration-none mt-2 d-inline-block" style="color:var(--accent)">
                    Request leave <i class="bi bi-arrow-right"></i>
                </a>
            </article>
        </section>


        <section class="row g-4">
            <div class="col-12 col-xl-7">

                <!-- Shift Days Calendar -->
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h4 class="fw-bold mb-0">Shift Days Calendar</h4>
                                <div class="small-muted">Your working days and urgent opportunities.</div>
                            </div>

                            <a href="calendar.php" class="text-decoration-none" style="color:var(--accent)">
                                Open
                            </a>
                        </div>

                        <div class="mini-calendar">
                            <div class="calendar-day">Mon<br><strong>17</strong></div>
                            <div class="calendar-day active">Tue<br><strong>18</strong></div>
                            <div class="calendar-day active">Wed<br><strong>19</strong></div>
                            <div class="calendar-day alert-day">Thu<br><strong>20</strong></div>
                            <div class="calendar-day">Fri<br><strong>21</strong></div>
                            <div class="calendar-day active">Sat<br><strong>22</strong></div>
                            <div class="calendar-day">Sun<br><strong>23</strong></div>
                        </div>
                    </div>
                </div>

                <!-- Open Shifts Marketplace -->
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

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="shift-market-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="fw-bold mb-1">Care Home Assistant</h5>
                                            <div class="small-muted">Rosewood Care Home</div>
                                        </div>
                                        <span class="badge bg-success">Matched</span>
                                    </div>

                                    <div class="visit-meta mb-3">
                                        <span class="meta-pill"><i class="bi bi-clock me-1"></i>08:00 – 14:00</span>
                                        <span class="meta-pill"><i class="bi bi-calendar me-1"></i>Thu, 20 May</span>
                                        <span class="meta-pill"><i class="bi bi-geo-alt me-1"></i>2.1 miles</span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="shift-rate">£15.50/hr</div>
                                        <a href="accept-shift.php?id=101" class="btn btn-sm text-white"
                                            style="background:var(--accent);border-radius:999px">
                                            Accept Shift
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="shift-market-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="fw-bold mb-1">Live-in Support Cover</h5>
                                            <div class="small-muted">Private Client Placement</div>
                                        </div>
                                        <span class="badge bg-warning text-dark">Urgent</span>
                                    </div>

                                    <div class="visit-meta mb-3">
                                        <span class="meta-pill"><i class="bi bi-clock me-1"></i>24hr cover</span>
                                        <span class="meta-pill"><i class="bi bi-calendar me-1"></i>Fri, 21 May</span>
                                        <span class="meta-pill"><i class="bi bi-car-front me-1"></i>32 mins</span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="shift-rate">£145/day</div>
                                        <a href="accept-shift.php?id=102" class="btn btn-sm text-white"
                                            style="background:var(--accent2);border-radius:999px">
                                            One-tap Accept
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Shift Alert -->
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

                <!-- Added: Training and Compliance -->
                <div class="card feature-card mb-4 fade-in-up">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h4 class="fw-bold mb-1">Training & Compliance</h4>
                                <div class="small-muted">Keep your documents and training up to date.</div>
                            </div>

                            <div class="compliance-ring">86%</div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <div class="training-card">
                                    <i class="bi bi-file-earmark-check-fill text-success fs-3"></i>
                                    <h6 class="fw-bold mt-2 mb-1">DBS</h6>
                                    <div class="small-muted">Valid until Dec 2026</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="training-card">
                                    <i class="bi bi-capsule-pill text-primary fs-3"></i>
                                    <h6 class="fw-bold mt-2 mb-1">Medication</h6>
                                    <div class="small-muted">Renewal in 18 days</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="training-card">
                                    <i class="bi bi-fire text-danger fs-3"></i>
                                    <h6 class="fw-bold mt-2 mb-1">Fire Safety</h6>
                                    <div class="small-muted">Course incomplete</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-12 col-xl-5">

                <!-- Existing Quick Actions -->
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
                                <a href="rota.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:var(--accent2);border-radius:14px">
                                    <i class="bi bi-calendar2-week-fill d-block fs-3 mb-2"></i>
                                    <strong>Rota</strong>
                                    <small class="d-block opacity-75">View shifts</small>
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
                                <a href="shift-swap.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:#6f42c1;border-radius:14px">
                                    <i class="bi bi-arrow-left-right d-block fs-3 mb-2"></i>
                                    <strong>Swap Shift</strong>
                                    <small class="d-block opacity-75">Request cover</small>
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

                            <!-- Added Quick Actions -->
                            <div class="col-6">
                                <a href="care-notes.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:#20c997;border-radius:14px">
                                    <i class="bi bi-journal-medical d-block fs-3 mb-2"></i>
                                    <strong>Care Notes</strong>
                                    <small class="d-block opacity-75">Update notes</small>
                                </a>
                            </div>

                            <div class="col-6">
                                <a href="documents.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:#fd7e14;border-radius:14px">
                                    <i class="bi bi-folder-check d-block fs-3 mb-2"></i>
                                    <strong>Documents</strong>
                                    <small class="d-block opacity-75">Upload files</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Added: Pay Snapshot -->
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">Pay Snapshot</h4>

                        <div class="pay-box">
                            <div class="d-flex justify-content-between">
                                <span class="small-muted">Next payday</span>
                                <strong>Fri, 31 May</strong>
                            </div>

                            <div class="d-flex justify-content-between mt-2">
                                <span class="small-muted">Approved hours</span>
                                <strong>24.5h</strong>
                            </div>

                            <div class="d-flex justify-content-between mt-2">
                                <span class="small-muted">Pending timesheets</span>
                                <strong>2</strong>
                            </div>

                            <a href="payroll.php" class="btn btn-sm text-white mt-3"
                                style="background:var(--accent);border-radius:999px">
                                View payroll
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Added: Skills Match -->
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">Skills Match</h4>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="skill-pill"><i class="bi bi-check-circle"></i> Dementia Care</span>
                            <span class="skill-pill"><i class="bi bi-check-circle"></i> Medication</span>
                            <span class="skill-pill"><i class="bi bi-check-circle"></i> Moving & Handling</span>
                            <span class="skill-pill"><i class="bi bi-plus-circle"></i> Add Skill</span>
                        </div>
                    </div>
                </div>

                <!-- Existing Shift Swap Requests -->
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="fw-bold mb-0">Shift Swap Requests</h4>
                            <a href="shift-swap.php" class="text-decoration-none" style="color:var(--accent)">Manage</a>
                        </div>

                        <div class="swap-box mb-3">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <strong>Evening Care Visit</strong>
                                    <div class="small-muted">Linda Williams • Fri, 21 May • 19:00 – 21:00</div>
                                </div>
                                <span class="badge bg-warning text-dark">Pending</span>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-sm btn-success" style="border-radius:999px">Approve</button>
                                <button class="btn btn-sm btn-outline-secondary"
                                    style="border-radius:999px">Decline</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Added: Staff Wellbeing -->
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">Staff Wellbeing</h4>

                        <div class="wellbeing-card">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-heart-fill text-danger fs-3"></i>
                                <div>
                                    <strong>How are you feeling today?</strong>
                                    <div class="small-muted">Check in after long or difficult shifts.</div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-sm btn-outline-success" style="border-radius:999px">Good</button>
                                <button class="btn btn-sm btn-outline-warning" style="border-radius:999px">Okay</button>
                                <button class="btn btn-sm btn-outline-danger" style="border-radius:999px">Need
                                    support</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Existing Past Shift Records -->
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="fw-bold mb-0">Past Shift Records</h4>
                            <a href="past-shifts.php" class="text-decoration-none" style="color:var(--accent)">
                                View all
                            </a>
                        </div>

                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <div class="flex-grow-1">
                                <strong>Mr. James Wilson</strong>
                                <div class="small-muted">Sun, 19 May • 09:00 – 11:00</div>
                            </div>
                            <span class="small-muted">2h</span>
                        </div>

                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <div class="flex-grow-1">
                                <strong>Mrs. Margaret Smith</strong>
                                <div class="small-muted">Sat, 18 May • 14:00 – 16:00</div>
                            </div>
                            <span class="small-muted">2h</span>
                        </div>

                        <div class="d-flex align-items-center gap-3 py-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <div class="flex-grow-1">
                                <strong>Ms. Linda Williams</strong>
                                <div class="small-muted">Fri, 17 May • 19:00 – 21:30</div>
                            </div>
                            <span class="small-muted">2.5h</span>
                        </div>
                    </div>
                </div>

                <div class="alerts-container fade-in-up">
                    <div class="alert-item">
                        <strong>
                            <i class="bi bi-megaphone-fill me-1" style="color:var(--accent2)"></i>
                            Announcement
                        </strong>
                        <div class="small-muted">
                            Don’t forget to log your timesheet at the end of each shift.
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <?php include 'new-footer.php'; ?>

</body>

</html>