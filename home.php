<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StaffLinks Dashboard</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --care-red: #dc3545;
      --care-orange: #fd7e14;
      --care-yellow: #ffc107;
      --dark: #101229;
      --muted: #69708a;
      --border: #edf0f6;
      --bg: #fbfcff;
      --sidebar: #fff7f5;
      --sidebar-width: 270px;
      --radius-lg: 18px;
      --radius-md: 14px;
    }

    * {
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      margin: 0;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: var(--bg);
      color: var(--dark);
      overflow-x: hidden;
    }

    img {
      max-width: 100%;
      height: auto;
    }

    .app-shell {
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
    }

    .sidebar {
      width: var(--sidebar-width);
      min-height: 100vh;
      background: linear-gradient(180deg, #fff7f3 0%, #ffffff 100%);
      border-right: 1px solid var(--border);
      padding: 28px 16px;
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      z-index: 1040;
      overflow-y: auto;
      transition: transform 0.25s ease;
    }

    .brand {
      display: flex;
      gap: 12px;
      align-items: center;
      padding: 0 12px 34px;
    }

    .brand-icon {
      width: 42px;
      min-width: 42px;
      height: 42px;
      border-radius: 16px;
      background: linear-gradient(135deg, var(--care-red), var(--care-orange));
      color: white;
      display: grid;
      place-items: center;
      font-size: 24px;
      box-shadow: 0 12px 22px rgba(220, 53, 69, 0.22);
    }

    .brand h4 {
      margin: 0;
      font-weight: 800;
      font-size: clamp(18px, 2vw, 21px);
    }

    .brand span {
      display: block;
      color: var(--muted);
      font-size: 13px;
      margin-top: 1px;
    }

    .mobile-header {
      display: none;
      position: sticky;
      top: 0;
      z-index: 1030;
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border);
      padding: 12px 14px;
    }

    .menu-toggle {
      width: 42px;
      height: 42px;
      border: 1px solid var(--border);
      border-radius: 13px;
      background: white;
      color: var(--care-red);
      display: grid;
      place-items: center;
      font-size: 22px;
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(16, 18, 41, 0.42);
      z-index: 1035;
    }

    .nav-menu {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .nav-item-link {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 13px 14px;
      border-radius: 12px;
      color: #3f455f;
      text-decoration: none;
      font-weight: 600;
      transition: 0.25s ease;
      min-height: 48px;
    }

    .nav-item-link i {
      font-size: 19px;
      min-width: 22px;
    }

    .nav-item-link.active,
    .nav-item-link:hover {
      background: linear-gradient(90deg, rgba(220, 53, 69, 0.12), rgba(253, 126, 20, 0.12));
      color: var(--care-red);
    }

    .nav-divider {
      height: 1px;
      background: var(--border);
      margin: 16px 0;
    }

    .sidebar-card {
      margin-top: 28px;
      background: white;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 16px 32px rgba(16, 18, 41, 0.05);
    }

    .sidebar-card .shield {
      width: 42px;
      min-width: 42px;
      height: 42px;
      border-radius: 14px;
      background: rgba(255, 193, 7, 0.2);
      color: var(--care-orange);
      display: grid;
      place-items: center;
      font-size: 22px;
    }

    .logout {
      display: inline-flex;
      align-items: center;
      margin: 24px 12px 0;
      color: #3f455f;
      text-decoration: none;
      font-weight: 600;
    }

    .main-content {
      margin-left: var(--sidebar-width);
      width: calc(100% - var(--sidebar-width));
      padding: clamp(18px, 3vw, 34px) clamp(14px, 3vw, 100px);
      padding-bottom: 110px;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      gap: 24px;
      align-items: center;
      margin-bottom: clamp(20px, 3vw, 30px);
    }

    .page-title h1 {
      margin: 0;
      font-weight: 850;
      font-size: clamp(26px, 4vw, 40px);
      line-height: 1.1;
      letter-spacing: -0.04em;
    }

    .page-title p {
      margin: 8px 0 0;
      color: var(--muted);
      font-size: clamp(14px, 2vw, 17px);
    }

    .user-area {
      display: flex;
      align-items: center;
      gap: 18px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .notification {
      position: relative;
      width: 42px;
      height: 42px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: white;
      display: grid;
      place-items: center;
      font-size: 20px;
      color: #3f455f;
      flex: 0 0 auto;
    }

    .notification span {
      position: absolute;
      top: -6px;
      right: -5px;
      width: 21px;
      height: 21px;
      border-radius: 50%;
      background: var(--care-red);
      color: white;
      font-size: 12px;
      display: grid;
      place-items: center;
      border: 2px solid white;
    }

    .profile {
      display: flex;
      align-items: center;
      gap: 12px;
      padding-left: 18px;
      border-left: 1px solid var(--border);
      min-width: 0;
    }

    .profile img {
      width: 48px;
      min-width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
    }

    .profile strong {
      display: block;
      font-size: 15px;
      white-space: nowrap;
    }

    .profile span {
      color: var(--muted);
      font-size: 13px;
    }

    .summary-card,
    .dashboard-panel,
    .announcement-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: 0 15px 35px rgba(16, 18, 41, 0.04);
    }

    .summary-card {
      padding: clamp(18px, 2vw, 24px);
      min-height: 178px;
      position: relative;
      overflow: hidden;
      height: 100%;
    }

    .summary-card:after {
      content: "";
      position: absolute;
      right: -32px;
      top: -40px;
      width: 110px;
      height: 110px;
      border-radius: 50%;
      background: rgba(255, 193, 7, 0.12);
    }

    .metric-block {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      position: relative;
      z-index: 1;
    }

    .metric-icon {
      width: clamp(52px, 6vw, 62px);
      min-width: clamp(52px, 6vw, 62px);
      height: clamp(52px, 6vw, 62px);
      border-radius: 16px;
      display: grid;
      place-items: center;
      font-size: clamp(23px, 3vw, 28px);
    }

    .icon-red { background: rgba(220, 53, 69, 0.12); color: var(--care-red); }
    .icon-orange { background: rgba(253, 126, 20, 0.13); color: var(--care-orange); }
    .icon-yellow { background: rgba(255, 193, 7, 0.18); color: #b88600; }

    .metric-label {
      color: var(--dark);
      font-size: 14px;
      font-weight: 750;
      margin-bottom: 8px;
    }

    .metric-value {
      font-size: clamp(23px, 3vw, 28px);
      font-weight: 850;
      margin: 0;
      letter-spacing: -0.03em;
      word-break: break-word;
    }

    .metric-subtext {
      margin: 5px 0 0;
      color: var(--muted);
      font-size: 14px;
    }

    .card-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 25px;
      color: var(--care-red);
      text-decoration: none;
      font-weight: 750;
      position: relative;
      z-index: 1;
    }

    .progress {
      height: 10px;
      border-radius: 999px;
      background: rgba(255, 193, 7, 0.18);
    }

    .progress-bar {
      background: linear-gradient(90deg, var(--care-red), var(--care-orange));
      border-radius: 999px;
    }

    .dashboard-panel {
      padding: clamp(16px, 2vw, 22px);
      height: 100%;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 15px;
      margin-bottom: 18px;
      flex-wrap: wrap;
    }

    .panel-header h3 {
      margin: 0;
      font-size: clamp(18px, 2.4vw, 21px);
      font-weight: 850;
      letter-spacing: -0.02em;
    }

    .panel-header a {
      color: var(--care-red);
      text-decoration: none;
      font-weight: 750;
      white-space: nowrap;
    }

    .date-label {
      color: var(--muted);
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .schedule-list {
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
    }

    .schedule-item {
      display: grid;
      grid-template-columns: minmax(74px, 88px) minmax(0, 1fr) 24px;
      gap: clamp(12px, 2vw, 18px);
      align-items: center;
      padding: clamp(16px, 2vw, 22px) clamp(14px, 2vw, 18px);
      border-bottom: 1px solid var(--border);
      background: white;
    }

    .schedule-item:last-child {
      border-bottom: 0;
    }

    .schedule-time {
      align-self: stretch;
      min-height: 88px;
      display: grid;
      place-items: center;
      text-align: center;
      font-weight: 850;
      color: var(--care-red);
      font-size: clamp(15px, 2vw, 18px);
      background: linear-gradient(180deg, rgba(220, 53, 69, 0.07), rgba(255, 193, 7, 0.08));
      border-radius: 14px;
      line-height: 1.45;
    }

    .client {
      display: flex;
      gap: 15px;
      align-items: center;
      min-width: 0;
    }

    .client img {
      width: 54px;
      min-width: 54px;
      height: 54px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #fff3cd;
    }

    .client h5 {
      margin: 0;
      font-size: clamp(15px, 2vw, 17px);
      font-weight: 850;
    }

    .client p {
      margin: 3px 0;
      color: var(--muted);
    }

    .client small {
      color: var(--muted);
      overflow-wrap: anywhere;
    }

    .badge-soft {
      background: rgba(220, 53, 69, 0.10);
      color: var(--care-red);
      border-radius: 9px;
      padding: 7px 10px;
      font-weight: 750;
      font-size: 12px;
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(135px, 1fr));
      gap: 14px;
    }

    .quick-action {
      border: 0;
      border-radius: 15px;
      padding: clamp(16px, 2vw, 22px) 12px;
      text-align: center;
      background: #fff7f3;
      transition: 0.25s ease;
      min-height: 145px;
      width: 100%;
    }

    .quick-action:hover {
      transform: translateY(-4px);
      box-shadow: 0 14px 28px rgba(220, 53, 69, 0.12);
    }

    .quick-action .metric-icon {
      width: 58px;
      min-width: 58px;
      height: 58px;
      margin: 0 auto 12px;
    }

    .quick-action strong {
      display: block;
      font-size: 15px;
      margin-bottom: 6px;
    }

    .quick-action span {
      color: var(--muted);
      font-size: 13px;
    }

    .past-shifts-list {
      width: 100%;
    }

    .past-shift-row {
      display: grid;
      grid-template-columns: 30px minmax(95px, 1fr) minmax(130px, 1.2fr) minmax(90px, 1fr) minmax(64px, 0.7fr) 22px;
      align-items: center;
      gap: 12px;
      padding: 14px 4px;
      border-bottom: 1px solid var(--border);
      font-size: 14px;
    }

    .past-shift-row:last-child {
      border-bottom: 0;
    }

    .check {
      color: #198754;
      font-size: 19px;
    }

    .announcement-card {
      padding: clamp(16px, 2vw, 18px);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
    }

    .announcement-left {
      display: flex;
      align-items: center;
      gap: 16px;
      min-width: 0;
    }

    .announcement-icon {
      width: 58px;
      min-width: 58px;
      height: 58px;
      border-radius: 15px;
      background: rgba(255, 193, 7, 0.2);
      color: var(--care-orange);
      display: grid;
      place-items: center;
      font-size: 28px;
    }

    .announcement-card h5 {
      margin: 0 0 4px;
      font-weight: 850;
    }

    .announcement-card p {
      margin: 0;
      color: var(--muted);
    }

    .announcement-card a {
      color: var(--care-red);
      text-decoration: none;
      font-weight: 750;
      white-space: nowrap;
    }

    .mobile-footer-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 1050;
      background: rgba(255,255,255,0.96);
      backdrop-filter: blur(14px);
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-around;
      align-items: center;
      padding: 10px 8px calc(10px + env(safe-area-inset-bottom));
      box-shadow: 0 -8px 30px rgba(16, 18, 41, 0.06);
    }

    .footer-nav-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 5px;
      text-decoration: none;
      color: var(--muted);
      font-size: 12px;
      font-weight: 700;
      min-height: 58px;
      border-radius: 14px;
      transition: all 0.2s ease;
    }

    .footer-nav-item i {
      font-size: 20px;
    }

    .footer-nav-item.active,
    .footer-nav-item:hover {
      color: var(--care-red);
      background: rgba(220, 53, 69, 0.08);
    }

    @media (max-width: 1199.98px) {
      .main-content {
        padding-left: 22px;
        padding-right: 22px;
      }
    }

    @media (max-width: 991.98px) {
      .mobile-header {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 14px;
      }

      .sidebar {
        transform: translateX(-105%);
        width: min(86vw, 310px);
        box-shadow: 18px 0 45px rgba(16, 18, 41, 0.18);
      }

      body.sidebar-open .sidebar {
        transform: translateX(0);
      }

      body.sidebar-open .sidebar-overlay {
        display: block;
      }

      .app-shell {
        display: block;
      }

      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px 16px 120px;
      }

      .topbar {
        align-items: flex-start;
      }

      .profile {
        border-left: 0;
        padding-left: 0;
      }
    }

    @media (max-width: 767.98px) {
      .topbar {
        flex-direction: column;
      }

      .user-area {
        width: 100%;
        justify-content: space-between;
        gap: 12px;
      }

      .profile {
        flex: 1;
        justify-content: flex-end;
      }

      .summary-card {
        min-height: auto;
      }

      .schedule-item {
        grid-template-columns: 1fr 24px;
        align-items: start;
      }

      .schedule-time {
        grid-column: 1 / -1;
        min-height: auto;
        display: block;
        padding: 10px 12px;
        text-align: left;
      }

      .schedule-time br {
        display: none;
      }

      .client {
        align-items: flex-start;
      }

      .past-shift-row {
        grid-template-columns: 28px 1fr 22px;
        gap: 8px 12px;
        padding: 16px 2px;
      }

      .past-shift-row span:nth-child(2),
      .past-shift-row strong,
      .past-shift-row span:nth-child(4),
      .past-shift-row span:nth-child(5) {
        grid-column: 2 / 3;
      }

      .past-shift-row i:last-child {
        grid-column: 3 / 4;
        grid-row: 1 / 3;
      }

      .past-shift-row strong {
        font-size: 15px;
      }

      .past-shift-row span:nth-child(4),
      .past-shift-row span:nth-child(5) {
        color: var(--muted);
      }

      .announcement-card {
        flex-direction: column;
        align-items: flex-start;
      }
    }

    @media (max-width: 575.98px) {
      .main-content {
        padding: 16px 12px 120px;
      }

      .mobile-header .brand {
        padding: 0;
        gap: 10px;
      }

      .mobile-header .brand-icon {
        width: 38px;
        min-width: 38px;
        height: 38px;
        border-radius: 14px;
        font-size: 21px;
      }

      .mobile-header .brand h4 {
        font-size: 17px;
      }

      .mobile-header .brand span {
        font-size: 11px;
      }

      .page-title h1 {
        font-size: 25px;
      }

      .profile img {
        width: 42px;
        min-width: 42px;
        height: 42px;
      }

      .profile strong {
        font-size: 13px;
      }

      .profile span,
      .metric-subtext,
      .client p,
      .client small,
      .announcement-card p {
        font-size: 12.5px;
      }

      .metric-block {
        gap: 12px;
      }

      .metric-icon {
        width: 50px;
        min-width: 50px;
        height: 50px;
        font-size: 22px;
      }

      .quick-actions {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }

      .quick-action {
        min-height: 132px;
        padding: 15px 8px;
      }

      .quick-action .metric-icon {
        width: 48px;
        min-width: 48px;
        height: 48px;
        font-size: 21px;
      }

      .quick-action strong {
        font-size: 13px;
      }

      .quick-action span {
        font-size: 12px;
      }

      .client img {
        width: 46px;
        min-width: 46px;
        height: 46px;
      }

      .badge-soft {
        padding: 5px 8px;
        font-size: 11px;
      }

      .announcement-left {
        align-items: flex-start;
      }
    }

    @media (max-width: 390px) {
      .quick-actions {
        grid-template-columns: 1fr;
      }

      .profile .bi-chevron-down,
      .profile span {
        display: none;
      }

      .panel-header a,
      .announcement-card a,
      .card-link {
        font-size: 13px;
      }
    }
  </style>
</head>
<body>
  <!-- =========================
       MOBILE NAVBAR
       ========================= -->
  <div class="mobile-header">
    <button class="menu-toggle" type="button" aria-label="Open menu">
      <i class="bi bi-list"></i>
    </button>

    <div class="brand">
      <div class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></div>
      <div>
        <h4>StaffLinks</h4>
        <span>Care Management</span>
      </div>
    </div>
  </div>

  <div class="sidebar-overlay"></div>

  <div class="app-shell">
    <!-- =========================
         SIDEBAR NAVBAR
         Move this <aside> block into navbar.html if you want a separate file.
         ========================= -->
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></div>
        <div>
          <h4>StaffLinks</h4>
          <span>Care Management</span>
        </div>
      </div>

      <nav class="nav-menu" aria-label="Main navigation">
        <a href="#" class="nav-item-link active"><i class="bi bi-grid-fill"></i> Dashboard</a>
        <a href="#" class="nav-item-link"><i class="bi bi-clock"></i> Timesheet</a>
        <a href="#" class="nav-item-link"><i class="bi bi-calendar2-week"></i> Rota</a>
        <a href="#" class="nav-item-link"><i class="bi bi-umbrella"></i> Leave</a>
        <a href="#" class="nav-item-link"><i class="bi bi-calculator"></i> Shift & Tax Calculator</a>
        <a href="#" class="nav-item-link"><i class="bi bi-clock-history"></i> Past Shifts</a>
      </nav>

      <div class="nav-divider"></div>

      <nav class="nav-menu" aria-label="Secondary navigation">
        <a href="#" class="nav-item-link"><i class="bi bi-people"></i> Service Users</a>
        <a href="#" class="nav-item-link"><i class="bi bi-envelope"></i> Messages <span class="badge rounded-pill ms-auto" style="background: rgba(220,53,69,.12); color: var(--care-red);">3</span></a>
        <a href="#" class="nav-item-link"><i class="bi bi-file-earmark-text"></i> Documents</a>
        <a href="#" class="nav-item-link"><i class="bi bi-gear"></i> Settings</a>
        <a href="#" class="nav-item-link"><i class="bi bi-question-circle"></i> Help & Support</a>
      </nav>

      <div class="sidebar-card">
        <div class="d-flex align-items-center gap-3">
          <div class="shield"><i class="bi bi-shield-check"></i></div>
          <div>
            <strong>You’re all set!</strong>
            <div class="text-muted small">NHS DBS Verified <i class="bi bi-check-circle-fill text-success"></i></div>
          </div>
        </div>
      </div>

      <a href="#" class="logout"><i class="bi bi-box-arrow-left me-2"></i> Log out</a>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <div class="page-title">
          <h1>Good morning, Sarah 👋</h1>
          <p>Here’s your overview for today.</p>
        </div>

        <div class="user-area">
          <button class="notification" type="button" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span>2</span>
          </button>
          <div class="profile">
            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80" alt="Sarah Johnson">
            <div>
              <strong>Sarah Johnson</strong>
              <span>Carer</span>
            </div>
            <i class="bi bi-chevron-down text-muted"></i>
          </div>
        </div>
      </header>

      <section class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
          <div class="summary-card">
            <div class="metric-block">
              <div class="metric-icon icon-red"><i class="bi bi-clock-fill"></i></div>
              <div>
                <div class="metric-label">Hours This Week</div>
                <p class="metric-value">28h 15m</p>
                <p class="metric-subtext">of 37h 30m scheduled</p>
              </div>
            </div>
            <div class="d-flex align-items-center gap-3 mt-4">
              <div class="progress flex-grow-1">
                <div class="progress-bar" style="width: 75%"></div>
              </div>
              <span class="fw-bold text-muted">75%</span>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="summary-card">
            <div class="metric-block">
              <div class="metric-icon icon-orange"><i class="bi bi-calendar-event-fill"></i></div>
              <div>
                <div class="metric-label">Upcoming Shifts</div>
                <p class="metric-value">3</p>
                <p class="metric-subtext">Next: Today, 14:00</p>
              </div>
            </div>
            <a class="card-link" href="#">View rota <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="summary-card">
            <div class="metric-block">
              <div class="metric-icon icon-yellow"><i class="bi bi-wallet2"></i></div>
              <div>
                <div class="metric-label">Est. Pay This Month</div>
                <p class="metric-value">£1,245.60</p>
                <p class="metric-subtext">After tax</p>
              </div>
            </div>
            <a class="card-link" href="#">View calculator <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="summary-card">
            <div class="metric-block">
              <div class="metric-icon icon-orange"><i class="bi bi-umbrella-fill"></i></div>
              <div>
                <div class="metric-label">Leave Balance</div>
                <p class="metric-value">12.5 days</p>
                <p class="metric-subtext">Annual leave remaining</p>
              </div>
            </div>
            <a class="card-link" href="#">View leave <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
      </section>

      <section class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
          <div class="dashboard-panel">
            <div class="panel-header">
              <div class="d-flex flex-wrap align-items-center gap-3">
                <h3>Today’s Schedule</h3>
                <span class="date-label"><i class="bi bi-calendar2"></i> Monday, 20 May 2024</span>
              </div>
              <a href="#">View full rota <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="schedule-list">
              <div class="schedule-item">
                <div class="schedule-time">14:00<br>–<br>16:00</div>
                <div class="client">
                  <img src="https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=120&q=80" alt="Margaret Smith">
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <h5>Mrs. Margaret Smith</h5>
                      <span class="badge-soft">Upcoming</span>
                    </div>
                    <p>Personal Care</p>
                    <small><i class="bi bi-geo-alt-fill" style="color: var(--care-red);"></i> 12 Oak Avenue, M15 6FG</small>
                  </div>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
              </div>

              <div class="schedule-item">
                <div class="schedule-time">16:30<br>–<br>18:30</div>
                <div class="client">
                  <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=120&q=80" alt="David Brown">
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <h5>Mr. David Brown</h5>
                      <span class="badge-soft">Upcoming</span>
                    </div>
                    <p>Medication Support</p>
                    <small><i class="bi bi-geo-alt-fill" style="color: var(--care-red);"></i> 22 Maple Drive, M15 4PL</small>
                  </div>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
              </div>

              <div class="schedule-item">
                <div class="schedule-time">19:00<br>–<br>21:00</div>
                <div class="client">
                  <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=120&q=80" alt="Linda Williams">
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <h5>Ms. Linda Williams</h5>
                      <span class="badge-soft">Upcoming</span>
                    </div>
                    <p>Companionship</p>
                    <small><i class="bi bi-geo-alt-fill" style="color: var(--care-red);"></i> 8 Pine Road, M15 5AA</small>
                  </div>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6">
          <div class="row g-4">
            <div class="col-12">
              <div class="dashboard-panel">
                <div class="panel-header">
                  <h3>Quick Actions</h3>
                </div>
                <div class="quick-actions">
                  <button class="quick-action" data-action="Timesheet">
                    <div class="metric-icon icon-red"><i class="bi bi-clock-fill"></i></div>
                    <strong>Timesheet</strong>
                    <span>Log your hours</span>
                  </button>
                  <button class="quick-action" data-action="Rota">
                    <div class="metric-icon icon-orange"><i class="bi bi-calendar-event-fill"></i></div>
                    <strong>View Rota</strong>
                    <span>Check your shifts</span>
                  </button>
                  <button class="quick-action" data-action="Leave">
                    <div class="metric-icon icon-yellow"><i class="bi bi-umbrella-fill"></i></div>
                    <strong>Request Leave</strong>
                    <span>Apply for time off</span>
                  </button>
                  <button class="quick-action" data-action="Calculator">
                    <div class="metric-icon icon-orange"><i class="bi bi-calculator-fill"></i></div>
                    <strong>Shift & Tax Calculator</strong>
                    <span>Estimate earnings</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="dashboard-panel">
                <div class="panel-header">
                  <h3>Past Shift Records</h3>
                  <a href="#">View all <i class="bi bi-arrow-right"></i></a>
                </div>

                <div class="past-shift-row">
                  <i class="bi bi-check-circle check"></i>
                  <span>Sun, 19 May</span>
                  <strong>Mr. James Wilson</strong>
                  <span>09:00 – 11:00</span>
                  <span>2h 00m</span>
                  <i class="bi bi-chevron-right text-muted"></i>
                </div>
                <div class="past-shift-row">
                  <i class="bi bi-check-circle check"></i>
                  <span>Sat, 18 May</span>
                  <strong>Mrs. Margaret Smith</strong>
                  <span>14:00 – 16:00</span>
                  <span>2h 00m</span>
                  <i class="bi bi-chevron-right text-muted"></i>
                </div>
                <div class="past-shift-row">
                  <i class="bi bi-check-circle check"></i>
                  <span>Fri, 17 May</span>
                  <strong>Ms. Linda Williams</strong>
                  <span>19:00 – 21:30</span>
                  <span>2h 30m</span>
                  <i class="bi bi-chevron-right text-muted"></i>
                </div>

                <a class="card-link" href="#">View all past shifts <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="announcement-card">
        <div class="announcement-left">
          <div class="announcement-icon"><i class="bi bi-megaphone-fill"></i></div>
          <div>
            <h5>Announcements</h5>
            <p>Don’t forget to log your timesheet at the end of each shift.</p>
          </div>
        </div>
        <a href="#">View all announcements <i class="bi bi-arrow-right"></i></a>
      </section>
          <nav class="mobile-footer-nav d-lg-none">
        <a href="#" class="footer-nav-item active">
          <i class="bi bi-house-fill"></i>
          <span>Home</span>
        </a>

        <a href="#" class="footer-nav-item">
          <i class="bi bi-calendar2-week-fill"></i>
          <span>Rota</span>
        </a>

        <a href="#" class="footer-nav-item">
          <i class="bi bi-clock-history"></i>
          <span>Past Shift</span>
        </a>

        <a href="#" class="footer-nav-item">
          <i class="bi bi-umbrella-fill"></i>
          <span>Leave</span>
        </a>
      </nav>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const actions = document.querySelectorAll('.quick-action');
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-item-link, .logout');

    actions.forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.dataset.action;
        alert(`${action} section selected`);
      });
    });

    function closeSidebar() {
      document.body.classList.remove('sidebar-open');
      menuToggle?.setAttribute('aria-label', 'Open menu');
      menuToggle?.querySelector('i')?.classList.remove('bi-x-lg');
      menuToggle?.querySelector('i')?.classList.add('bi-list');
    }

    function openSidebar() {
      document.body.classList.add('sidebar-open');
      menuToggle?.setAttribute('aria-label', 'Close menu');
      menuToggle?.querySelector('i')?.classList.remove('bi-list');
      menuToggle?.querySelector('i')?.classList.add('bi-x-lg');
    }

    menuToggle?.addEventListener('click', () => {
      document.body.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
    });

    sidebarOverlay?.addEventListener('click', closeSidebar);

    sidebarLinks.forEach((link) => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992) closeSidebar();
      });
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth >= 992) closeSidebar();
    });
  </script>
</body>
</html>
