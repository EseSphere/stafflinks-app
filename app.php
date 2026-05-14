<?php include_once 'header1.php'; ?>

<div id="overlay" aria-hidden="true"></div>

<!-- Side Navigation -->
<aside id="sideNav" aria-label="Side navigation">
    <div class="user-info">
        <img src="https://via.placeholder.com/100x100.png?text=SL" alt="User profile">
        <p class="name mb-0">StaffLinks User</p>
        <div class="email">stafflinks@example.com</div>
        <div class="phone">Care Team</div>
    </div>

    <h5>Menu</h5>
    <ul>
        <li><a href="./home"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li><a href="./visit-logs"><i class="bi bi-journal-text me-2"></i>Visit Logs</a></li>
        <li><a href="./settings"><i class="bi bi-gear me-2"></i>Settings</a></li>
        <li><a href="./profile"><i class="bi bi-person me-2"></i>Profile</a></li>
    </ul>

    <button type="button" class="btn btn-outline-danger logout-btn">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
    </button>
</aside>

<div class="topbar mb-3 p-2">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <button class="menu-btn fs-1" id="menuBtn" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>

        <h4 class="mb-0 fw-bold">StaffLinks</h4>

        <div class="d-flex align-items-center gap-2">
            <div class="chip"><span id="today-clock">--:--</span></div>
            <button class="btn btn-sm btn-light" id="refreshBtn" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button class="btn btn-sm btn-light" id="todayBtn" title="Today">
                <i class="bi bi-calendar-check"></i>
            </button>
            <button class="btn btn-sm btn-light" id="themeBtn" title="Toggle theme">
                <i class="bi bi-moon-stars"></i>
            </button>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light" id="prevDay" aria-label="Previous day">‹</button>
            <button class="btn btn-sm btn-light" id="nextDay" aria-label="Next day">›</button>
        </div>

        <div class="date-strip w-100" id="dateStrip" aria-label="Pick a date"></div>

        <div class="text-end">
            <div class="small-muted">Hours</div>
            <div class="hour-total" id="totalHours">0h 0m</div>
        </div>
    </div>

    <div class="mt-2 mb-2">
        <div class="d-flex justify-content-between align-items-center">
            <div class="small-light">Completion Progress</div>
            <div class="small-muted" id="progressText">0%</div>
        </div>
        <div class="progress" style="height:8px;">
            <div class="progress-bar" role="progressbar" id="progressBar" style="width:0%;"></div>
        </div>
    </div>
</div>

<div class="container py-3">
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="filter-bar">
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <input type="text" class="form-control" id="searchVisits" placeholder="Search visits by client name">
                    </div>
                    <div class="col-6 col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="all">All statuses</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="in-progress">In progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select class="form-select" id="sortVisits">
                            <option value="time-asc">Time ↑</option>
                            <option value="time-desc">Time ↓</option>
                            <option value="name-asc">Name A-Z</option>
                            <option value="name-desc">Name Z-A</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-outline-secondary" id="clearFilters">
                            <i class="bi bi-x-circle me-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card p-3 visits-list" id="visitsContainer"></div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="stats-grid fade-in-up mb-3">
                <div class="stat-card">
                    <div class="small-muted">Care Calls</div>
                    <div class="stat-value" id="countCalls">0</div>
                </div>
                <div class="stat-card">
                    <div class="small-muted">Completed</div>
                    <div class="stat-value" id="completedCalls">0</div>
                </div>
                <div class="stat-card">
                    <div class="small-muted">Pending</div>
                    <div class="stat-value" id="pendingCalls">0</div>
                </div>
                <div class="stat-card">
                    <div class="small-muted">Status</div>
                    <div class="mt-1">
                        <span id="connStatus" class="badge bg-success">Online</span>
                        <span id="offlineStatus" style="display:none;" class="badge bg-danger">Offline</span>
                    </div>
                </div>
            </div>

            <div class="card p-3 mb-3 next-visit-card">
                <h6 class="mb-2">Next Visit</h6>
                <div id="nextVisitCard" class="small-muted">No upcoming visit for this date.</div>
            </div>

            <div class="card p-3 mb-3">
                <h6>Run Details</h6>
                <ul class="list-unstyled small-muted mb-0">
                    <li>Run name: <strong id="runName">N/A</strong></li>
                    <li>Selected date: <strong id="selectedDateLabel">-</strong></li>
                    <li>Last refresh: <strong id="lastRefreshTime">-</strong></li>
                </ul>
            </div>

            <div class="card p-3">
                <h6>Alerts</h6>
                <div id="alertsContainer" class="alerts-container small-muted"></div>
            </div>
        </div>
    </div>
</div>

<template id="visitTpl">
    <div class="card mb-3 visit-item fade-in-up" tabindex="0">
        <div class="card-body p-3">
            <div class="visit-row">
                <div class="avatar"><img src="" alt="user"></div>

                <div class="visit-details">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="h6 mb-0 name text-truncate"></div>
                            <div class="small-muted service"></div>
                            <div class="visit-meta">
                                <span class="meta-pill visit-date"></span>
                                <span class="meta-pill visit-duration"></span>
                            </div>
                        </div>
                        <div class="text-end carers-icons"></div>
                    </div>

                    <div class="mt-2 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div class="small-muted times">09:00 - 10:00</div>
                        <div><span class="badge badge-status status">Scheduled</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<div class="footer">
    <button onclick="history.back()" title="Back" id="btn-back"><i class="bi bi-arrow-left"></i></button>
    <a href="./home" title="Home"><i class="bi bi-house"></i></a>
    <a href="./visit-logs" title="Log"><i class="bi bi-journal-text"></i></a>
    <a href="./settings" title="User"><i class="bi bi-person"></i></a>
</div>

<!-- Second Carer Modal -->
<div class="modal fade" id="secondCarerModal" tabindex="-1" aria-labelledby="secondCarerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-sm">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="secondCarerModalLabel">Second Carer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fw-bold mb-1">Run Name: <span id="modalRunName" class="text-primary"></span></p>
                <p class="mb-0">Second Carer: <span id="modalCarerName" class="fw-semibold"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="./js/jquery-3.7.0.min.js"></script>
<script src="./js/app.js?v=<?php echo time(); ?>"></script>
<script src="./js/sync_visits.js?v=<?php echo time(); ?>"></script>

<?php include_once 'footer.php'; ?>