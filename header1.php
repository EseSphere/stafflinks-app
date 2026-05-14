<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>StaffLinks | Simplify. Organize. Thrive.</title>
    <meta name="description" content="StaffLinks is an all-in-one platform to manage staff, clients, schedules, and finances efficiently. Streamline operations and empower your team with a centralized web app." />
    <meta name="keywords" content="StaffLinks, staff management, client management, scheduling, finance portal, web app, team management, productivity, operations" />
    <meta name="author" content="StaffLinks Team" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="robots" content="index, follow" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta property="og:title" content="StaffLinks | Simplify. Organize. Thrive." />
    <meta property="og:description" content="Manage staff, clients, schedules, and finances in one unified platform. StaffLinks makes team and operations management simple and efficient." />
    <meta property="og:image" content="./logo.png" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://www.stafflinks.co.uk" />
    <meta name="twitter:title" content="StaffLinks | Simplify. Organize. Thrive." />
    <meta name="twitter:description" content="StaffLinks centralizes staff, client, schedule, and finance management in one platform for maximum efficiency." />
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

    <div id="sideNav">
        <div class="user-info">
            <img src="./images/usericon.png" alt="Profile">
            <p class="name fs-6 fw-bold text-dark">Loading...</p>
            <p class="email text-dark"></p>
            <p class="phone text-dark"></p>
        </div>

        <hr>
        <ul class="list-unstyled">
            <li><a href="./app.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'home.php') {
                                                        echo 'active';
                                                    } ?>"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
            <li><a href="./visit-logs.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'visit-logs.php') {
                                                                echo 'active';
                                                            } ?>"><i class="bi bi-calendar-event me-2"></i> Visits</a></li>
            <li><a href="./timesheet.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'timesheet.php') {
                                                                echo 'active';
                                                            } ?>"><i class="bi bi-book me-2"></i> Timesheet</a></li>
            <li><a href="./calculator.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'timesheet.php') {
                                                                echo 'active';
                                                            } ?>"><i class="bi bi-calculator me-2"></i> Calculator</a></li>
        </ul>
        <hr>

        <div class="navbar-content bg-light p-2" style="font-family: Arial, sans-serif;">
            <span class="app-title" style="font-weight:bold; font-size:18px;">StaffLinks</span>
            <span class="app-version" style="font-size:14px; margin-left:15px;">v3.0.6</span><br>
            <span class="app-status" style="font-size:15px; color:#555;">Mobile App | Beta Release</span><br>
            <span class="app-environment" style="font-size:15px; color:#777;">Environment: Development</span>
        </div>

        <a href="./logout" class="btn btn-danger logout-btn mt-5"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>