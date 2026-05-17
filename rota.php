<?php $pageTitle = "StaffLinks Rota"; ?>
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
    .rota-page {
        padding-bottom: 100px;
    }

    .month-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        max-width: 1050px;
        margin: 0 auto;
        background: #fff;
        border-radius: 0 0 16px 16px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .back-today {
        border-right: 1px solid rgba(0, 0, 0, .08);
        padding: 20px 14px;
        font-weight: 700;
        color: var(--muted);
        line-height: 1.15;
        background: #fff;
    }

    .back-today a {
        color: #0d6efd;
        text-decoration: none;
    }

    .month-title {
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.35rem;
        color: #10142d;
        background: #fff;
    }

    .rota-board {
        display: grid;
        grid-template-columns: 150px 1fr;
        max-width: 1050px;
        margin: 18px auto 0;
        background: rgba(255, 255, 255, .45);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .date-col {
        border-right: 1px solid rgba(0, 0, 0, .08);
        background: rgba(255, 255, 255, .75);
    }

    .date-cell {
        min-height: 152px;
        padding: 26px 12px 12px;
        text-align: center;
        color: #828694;
        font-weight: 500;
        font-size: 1.15rem;
        transition: background .2s ease, color .2s ease;
    }

    .date-cell.has-shift {
        color: var(--accent);
        font-weight: 800;
        cursor: pointer;
    }

    .date-cell.has-shift:hover {
        background: rgba(201, 74, 87, .08);
    }

    .date-cell .num {
        display: block;
        font-size: 2.25rem;
        line-height: 1;
        font-weight: 500;
    }

    .date-cell.has-shift .num {
        font-weight: 800;
    }

    .shift-col {
        background: linear-gradient(180deg, #f7fbff, #f2f8ff);
        padding: 18px;
    }

    .shift-slot {
        min-height: 152px;
        display: flex;
        align-items: center;
    }

    .no-shift-card {
        width: 100%;
        min-height: 96px;
        border-radius: 16px;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .05);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .no-shift-pill {
        background: #f1f1f4;
        color: #b2b2bb;
        border-radius: 999px;
        padding: 9px 28px;
        font-weight: 700;
    }

    .shift-card {
        width: 100%;
        background: #fff;
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        border: 1px solid rgba(0, 0, 0, .04);
        padding: 20px 18px 18px;
        position: relative;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .shift-card.clickable-shift {
        cursor: pointer;
    }

    .shift-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .shift-card::before {
        content: "";
        position: absolute;
        left: 16px;
        top: 18px;
        bottom: 18px;
        width: 6px;
        border-radius: 999px;
        background: linear-gradient(180deg, var(--accent2), var(--warning));
    }

    .shift-content {
        padding-left: 25px;
        padding-right: 44px;
    }

    .shift-time {
        font-size: 1.15rem;
        font-weight: 900;
        color: #10142d;
        margin-bottom: 12px;
    }

    .shift-info {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #55586a;
        font-size: 1.05rem;
        margin-top: 8px;
    }

    .shift-info i {
        color: #8b8d99;
        font-size: 1.25rem;
    }

    .shift-check {
        position: absolute;
        right: 20px;
        top: 28px;
        color: var(--success);
        font-size: 1.8rem;
        font-weight: 900;
    }

    .add-shift-btn {
        position: fixed;
        right: 28px;
        bottom: 96px;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        border: none;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        color: #fff;
        font-size: 2.3rem;
        display: grid;
        place-items: center;
        box-shadow: 0 14px 28px rgba(201, 74, 87, .35);
        z-index: 60;
    }

    body.dark-mode .month-row,
    body.dark-mode .back-today,
    body.dark-mode .month-title,
    body.dark-mode .date-col,
    body.dark-mode .shift-card,
    body.dark-mode .no-shift-card {
        background: #2a2a3d;
        color: #e9ecef;
        border-color: rgba(255, 255, 255, .08);
    }

    body.dark-mode .month-title,
    body.dark-mode .shift-time {
        color: #fff;
    }

    body.dark-mode .shift-col {
        background: #1e1e2f;
    }

    body.dark-mode .shift-info,
    body.dark-mode .date-cell {
        color: #adb5bd;
    }

    body.dark-mode .date-cell.has-shift {
        color: #fff;
        background: rgba(201, 74, 87, .16);
    }

    body.dark-mode .date-cell.has-shift:hover {
        background: rgba(232, 138, 61, .2);
    }

    body.dark-mode .no-shift-pill {
        background: rgba(255, 255, 255, .08);
        color: #adb5bd;
    }

    @media (max-width: 576px) {

        .month-row,
        .rota-board {
            grid-template-columns: 112px 1fr;
        }

        .back-today {
            padding: 18px 10px;
            font-size: .9rem;
        }

        .month-title {
            font-size: 1.2rem;
        }

        .date-cell {
            min-height: 152px;
            font-size: 1rem;
            padding-top: 28px;
        }

        .date-cell .num {
            font-size: 2rem;
        }

        .shift-col {
            padding: 14px;
        }

        .shift-card {
            padding: 18px 14px;
        }

        .shift-time {
            font-size: 1.05rem;
        }

        .shift-info {
            font-size: .96rem;
        }

        .add-shift-btn {
            width: 66px;
            height: 66px;
            right: 22px;
            bottom: 92px;
        }
    }
    </style>
</head>

<body>
    <?php include 'new-navbar.php'; ?>

    <header class="topbar">
        <div class="container-fluid px-3">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="menu-btn" id="menuBtn" type="button" aria-label="Open menu">
                        <i class="bi bi-list"></i>
                    </button>

                    <div class="app-mark">
                        <i class="bi bi-calendar2-week-fill"></i>
                    </div>

                    <div>
                        <h5 class="mb-0 fw-bold">StaffLinks</h5>
                        <div class="text-white-50 small">Rota</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button class="chip border-0" type="button" aria-label="Calendar">
                        <i class="bi bi-calendar3"></i>
                    </button>

                    <button class="chip border-0" type="button" aria-label="Filter">
                        <i class="bi bi-funnel"></i>
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
                        <h2 class="mb-1 fw-bold">Sarah’s Rota</h2>
                        <div class="text-white-50">View your upcoming shifts and availability.</div>
                    </div>

                    <span class="chip">
                        <i class="bi bi-calendar-check me-1"></i> May 2026
                    </span>
                </div>
            </div>
        </div>
    </header>

    <div class="rota-page">
        <div class="month-row">
            <div class="back-today">
                Back to<br>
                <a href="#">Today</a>
            </div>
            <div class="month-title">May 2026</div>
        </div>

        <main class="rota-board">
            <aside class="date-col">
                <div class="date-cell">Sat <span class="num">23</span></div>
                <div class="date-cell">Sun <span class="num">24</span></div>
                <div class="date-cell">Mon <span class="num">25</span></div>
                <div class="date-cell">Tue <span class="num">26</span></div>

                <div class="date-cell has-shift" onclick="window.location.href='visits.php'">
                    Wed <span class="num">27</span>
                </div>

                <div class="date-cell has-shift" onclick="window.location.href='visits.php'">
                    Thu <span class="num">28</span>
                </div>

                <div class="date-cell has-shift" onclick="window.location.href='visits.php'">
                    Fri <span class="num">29</span>
                </div>
            </aside>

            <section class="shift-col">
                <div class="shift-slot">
                    <div class="no-shift-card">
                        <span class="no-shift-pill">No shifts</span>
                    </div>
                </div>

                <div class="shift-slot">
                    <div class="no-shift-card">
                        <span class="no-shift-pill">No shifts</span>
                    </div>
                </div>

                <div class="shift-slot">
                    <div class="no-shift-card">
                        <span class="no-shift-pill">No shifts</span>
                    </div>
                </div>

                <div class="shift-slot">
                    <div class="no-shift-card">
                        <span class="no-shift-pill">No shifts</span>
                    </div>
                </div>

                <div class="shift-slot">
                    <article class="shift-card clickable-shift" onclick="window.location.href='visits.php'">
                        <i class="bi bi-check2-all shift-check"></i>
                        <div class="shift-content">
                            <div class="shift-time">20:00 - 08:00</div>
                            <div class="shift-info">
                                <i class="bi bi-briefcase"></i>
                                <span>Support Worker</span>
                            </div>
                            <div class="shift-info">
                                <i class="bi bi-geo-alt"></i>
                                <span>Bold St - PF</span>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="shift-slot">
                    <article class="shift-card clickable-shift" onclick="window.location.href='visits.php'">
                        <i class="bi bi-check2-all shift-check"></i>
                        <div class="shift-content">
                            <div class="shift-time">20:00 - 08:00</div>
                            <div class="shift-info">
                                <i class="bi bi-briefcase"></i>
                                <span>Support Worker</span>
                            </div>
                            <div class="shift-info">
                                <i class="bi bi-geo-alt"></i>
                                <span>Bold St - PF</span>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="shift-slot">
                    <article class="shift-card clickable-shift" onclick="window.location.href='visits.php'">
                        <i class="bi bi-check2-all shift-check"></i>
                        <div class="shift-content">
                            <div class="shift-time">20:00 - 08:00</div>
                            <div class="shift-info">
                                <i class="bi bi-briefcase"></i>
                                <span>Support Worker</span>
                            </div>
                            <div class="shift-info">
                                <i class="bi bi-geo-alt"></i>
                                <span>Bold St - PF</span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <button class="add-shift-btn" type="button" aria-label="Add shift">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>

    <?php include 'new-footer.php'; ?>

    <script>
    const darkModeBtn = document.getElementById('darkModeBtn');

    if (darkModeBtn) {
        darkModeBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
        });
    }
    </script>
</body>

</html>