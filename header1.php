<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>StaffLinks | Simplify. Organize. Thrive.</title>
    <meta name="description"
        content="StaffLinks is an all-in-one platform to manage staff, clients, schedules, and finances efficiently. Streamline operations and empower your team with a centralized web app." />
    <meta name="keywords"
        content="StaffLinks, staff management, client management, scheduling, finance portal, web app, team management, productivity, operations" />
    <meta name="author" content="StaffLinks Team" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="robots" content="index, follow" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta property="og:title" content="StaffLinks | Simplify. Organize. Thrive." />
    <meta property="og:description"
        content="Manage staff, clients, schedules, and finances in one unified platform. StaffLinks makes team and operations management simple and efficient." />
    <meta property="og:image" content="./logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://www.stafflinks.co.uk" />
    <meta name="twitter:title" content="StaffLinks | Simplify. Organize. Thrive." />
    <meta name="twitter:description"
        content="StaffLinks centralizes staff, client, schedule, and finance management in one platform for maximum efficiency." />
    <meta name="twitter:image" content="./logo.png" />
    <meta name="twitter:card" content="./logo.png" />
    <meta name="theme-color" content="#4CAF50" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="rating" content="General" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <link rel="icon" href="./images/favicon.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="./images/favicon.png">
    <link rel="stylesheet" href="./css/style1.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/footer.css">
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body>

    <!-- navbar.php - StaffLinks slide-out navigation -->
    <nav id="sideNav" aria-label="StaffLinks side navigation">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="m-0">StaffLinks</h5><button class="btn btn-sm btn-light" type="button" id="closeNav"
                aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="user-info"><img
                src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=180&q=80"
                alt="Sarah Johnson">
            <div class="name">Sarah Johnson</div>
            <div class="email">sarah.johnson@stafflinks.co.uk</div>
            <div class="phone">Carer • DBS Verified</div>
        </div>
        <ul>
            <li><a class="active" href="dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li><a href="app.php"><i class="bi bi-calendar2-week"></i> Rota</a></li>
            <li><a href="leave.php"><i class="bi bi-umbrella"></i> Leave</a></li>
            <li><a href="visit-logs.php"><i class="bi bi-clock-history"></i> Visits</a></li>
            <li><a href="timesheet.php"><i class="bi bi-clock"></i> Timesheet</a></li>
            <li><a href="calculator.php"><i class="bi bi-calculator"></i> Pay Estimator</a></li>
            <li><a href="service-users.php"><i class="bi bi-people"></i> Service Users</a></li>
            <li><a href="messages.php"><i class="bi bi-envelope"></i> Messages</a></li>
            <li><a href="documents.php"><i class="bi bi-file-earmark-text"></i> Documents</a></li>
            <li><a href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
        </ul><button class="btn btn-danger logout-btn"><i class="bi bi-box-arrow-left me-2"></i> Log out</button>
    </nav>
    <div id="overlay"></div>