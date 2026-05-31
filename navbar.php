<nav id="sideNav" aria-label="StaffLinks side navigation">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="m-0">StaffLinks</h5>
        <button class="btn btn-sm btn-light" type="button" id="closeNav" aria-label="Close menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="user-info">
        <img id="navProfilePic" src="https://admin.stafflinks.co.uk/assets/images/default-avatar.jpg"
            alt="Display Picture">
        <div class="name" id="navFullName">Loading…</div>
        <div class="email" id="navEmail"></div>
        <div class="phone" id="navPhone"></div>
    </div>

    <ul>
        <li><a class="active" href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
        <li><a href="calendar.php"><i class="bi bi-calendar2-week"></i> Calendar</a></li>
        <li><a href="leave.php"><i class="bi bi-umbrella"></i> Leave</a></li>
        <li><a href="timesheet.php"><i class="bi bi-clock"></i> Timesheet</a></li>
        <li><a href="calculator.php"><i class="bi bi-calculator"></i> Pay Estimator</a></li>
        <li><a href="past-shifts.php"><i class="bi bi-people"></i> Past Shifts</a></li>
        <li><a href="community.php"><i class="bi bi-envelope"></i> Community</a></li>
        <li><a href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
    </ul>

    <a href="./" class="btn btn-danger logout-btn" id="logoutBtn">
        <i class="bi bi-box-arrow-left me-2"></i> Log out
    </a>
</nav>
<div id="overlay"></div>