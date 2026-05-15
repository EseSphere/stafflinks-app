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
</head>

<body><?php include 'new-navbar.php'; ?>
    <header class="topbar">
        <div class="container-fluid px-3">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2"><button class="menu-btn" id="menuBtn" type="button"
                        aria-label="Open menu"><i class="bi bi-list"></i></button>
                    <div class="app-mark"><i class="bi bi-heart-pulse-fill"></i></div>
                    <div>
                        <h5 class="mb-0 fw-bold">StaffLinks</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2"><button class="chip border-0 position-relative"
                        type="button" aria-label="Notifications"><i class="bi bi-bell"></i><span
                            class="alert-dot">2</span></button><button class="chip border-0" id="darkModeBtn"
                        type="button" aria-label="Toggle dark mode"><i class="bi bi-moon-stars"></i></button><img
                        class="profile-avatar"
                        src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80"
                        alt="Sarah Johnson"></div>
            </div>
            <div class="mt-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h2 class="mb-1 fw-bold">Good morning, Sarah 👋</h2>
                        <div class="text-white-50">Here’s your care work overview for today.</div>
                    </div><span class="chip"><i class="bi bi-shield-check me-1"></i> NHS DBS Verified</span>
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
                <div class="stat-label">Upcoming shifts</div><a href="rota.php"
                    class="small text-decoration-none mt-2 d-inline-block" style="color:var(--accent)">View rota <i
                        class="bi bi-arrow-right"></i></a>
            </article>
            <article class="stat-card">
                <div class="stat-icon success"><i class="bi bi-wallet2"></i></div>
                <div class="stat-value">£1,245.60</div>
                <div class="stat-label">Estimated monthly pay</div><a href="calculator.php"
                    class="small text-decoration-none mt-2 d-inline-block" style="color:var(--accent)">Open calculator
                    <i class="bi bi-arrow-right"></i></a>
            </article>
            <article class="stat-card">
                <div class="stat-icon info"><i class="bi bi-umbrella-fill"></i></div>
                <div class="stat-value">12.5</div>
                <div class="stat-label">Leave days remaining</div><a href="leave.php"
                    class="small text-decoration-none mt-2 d-inline-block" style="color:var(--accent)">Request leave <i
                        class="bi bi-arrow-right"></i></a>
            </article>
        </section>
        <section class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="visits-list p-3 fade-in-up">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h4 class="fw-bold mb-0">Today’s Schedule</h4>
                            <div class="small-muted">Wednesday, 19 May 2024</div>
                        </div><a href="rota.php" class="text-decoration-none" style="color:var(--accent)">Full rota <i
                                class="bi bi-arrow-right"></i></a>
                    </div>
                    <article class="card mb-3 visit-upcoming">
                        <div class="card-body">
                            <div class="visit-row">
                                <div class="avatar"><img
                                        src="https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=140&q=80"
                                        alt="Margaret Smith"></div>
                                <div class="visit-details">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <div>
                                            <h5 class="fw-bold mb-1">Mrs. Margaret Smith</h5>
                                            <div class="small-muted">Personal Care</div>
                                        </div><span class="badge bg-warning text-dark badge-status">Upcoming</span>
                                    </div>
                                    <div class="visit-meta"><span class="meta-pill"><i
                                                class="bi bi-clock me-1"></i>14:00 – 16:00</span><span
                                            class="meta-pill"><i class="bi bi-hourglass-split me-1"></i>2h
                                            00m</span><span class="meta-pill"><i class="bi bi-geo-alt me-1"></i>12 Oak
                                            Avenue</span></div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="card mb-3 ">
                        <div class="card-body">
                            <div class="visit-row">
                                <div class="avatar"><img
                                        src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=140&q=80"
                                        alt="David Brown"></div>
                                <div class="visit-details">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <div>
                                            <h5 class="fw-bold mb-1">Mr. David Brown</h5>
                                            <div class="small-muted">Medication Support</div>
                                        </div><span class="badge bg-light text-dark badge-status">Scheduled</span>
                                    </div>
                                    <div class="visit-meta"><span class="meta-pill"><i
                                                class="bi bi-clock me-1"></i>16:30 – 18:30</span><span
                                            class="meta-pill"><i class="bi bi-hourglass-split me-1"></i>2h
                                            00m</span><span class="meta-pill"><i class="bi bi-geo-alt me-1"></i>22 Maple
                                            Drive</span></div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="card mb-3 ">
                        <div class="card-body">
                            <div class="visit-row">
                                <div class="avatar"><img
                                        src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=140&q=80"
                                        alt="Linda Williams"></div>
                                <div class="visit-details">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <div>
                                            <h5 class="fw-bold mb-1">Ms. Linda Williams</h5>
                                            <div class="small-muted">Companionship</div>
                                        </div><span class="badge bg-light text-dark badge-status">Scheduled</span>
                                    </div>
                                    <div class="visit-meta"><span class="meta-pill"><i
                                                class="bi bi-clock me-1"></i>19:00 – 21:00</span><span
                                            class="meta-pill"><i class="bi bi-hourglass-split me-1"></i>2h
                                            00m</span><span class="meta-pill"><i class="bi bi-geo-alt me-1"></i>8 Pine
                                            Road</span></div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">Quick Actions</h4>
                        <div class="row g-3">
                            <div class="col-6">
                                <a type="button" href="timesheet.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:var(--accent);border-radius:14px"><i
                                        class="bi bi-clock-fill d-block fs-3 mb-2"></i><strong>Timesheet</strong><small
                                        class="d-block opacity-75">Log hours</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a type="button" href="rota.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:var(--accent2);border-radius:14px"><i
                                        class="bi bi-calendar2-week-fill d-block fs-3 mb-2"></i><strong>Rota</strong><small
                                        class="d-block opacity-75">View shifts</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a type="button" href="leave.php" class="btn w-100 py-3 text-start text-dark"
                                    style="background:var(--warning);border-radius:14px"><i
                                        class="bi bi-umbrella-fill d-block fs-3 mb-2"></i><strong>Leave</strong><small
                                        class="d-block opacity-75">Request time off</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a type="button" href="calculator.php" class="btn w-100 py-3 text-start text-white"
                                    style="background:#4A90E2;border-radius:14px"><i
                                        class="bi bi-calculator-fill d-block fs-3 mb-2"></i><strong>Calculator</strong><small
                                        class="d-block opacity-75">Tax estimate</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mb-4 fade-in-up" style="border-radius:var(--radius)">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="fw-bold mb-0">Past Shift Records</h4><a href="past-shifts.php"
                                class="text-decoration-none" style="color:var(--accent)">View all</a>
                        </div>
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom"><i
                                class="bi bi-check-circle-fill text-success"></i>
                            <div class="flex-grow-1"><strong>Mr. James Wilson</strong>
                                <div class="small-muted">Sun, 19 May • 09:00 – 11:00</div>
                            </div><span class="small-muted">2h</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom"><i
                                class="bi bi-check-circle-fill text-success"></i>
                            <div class="flex-grow-1"><strong>Mrs. Margaret Smith</strong>
                                <div class="small-muted">Sat, 18 May • 14:00 – 16:00</div>
                            </div><span class="small-muted">2h</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 py-2"><i
                                class="bi bi-check-circle-fill text-success"></i>
                            <div class="flex-grow-1"><strong>Ms. Linda Williams</strong>
                                <div class="small-muted">Fri, 17 May • 19:00 – 21:30</div>
                            </div><span class="small-muted">2.5h</span>
                        </div>
                    </div>
                </div>
                <div class="alerts-container fade-in-up">
                    <div class="alert-item"><strong><i class="bi bi-megaphone-fill me-1"
                                style="color:var(--accent2)"></i> Announcement</strong>
                        <div class="small-muted">Don’t forget to log your timesheet at the end of each shift.</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include 'new-footer.php'; ?>