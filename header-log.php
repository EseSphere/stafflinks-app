<?php require_once('dbconnection.php'); ?>
<!DOCTYPE html>
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
    <link rel="stylesheet" href="./css/style.css?v=<?php echo time(); ?>">
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="manifest" href="./manifest.json">
    <style>
        body,
        html {
            font-size: 18px;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-8">
                    <a href="./index" class="text-decoration-none fw-bold text-white flex justify-start items-start text-start">StaffLinks</a>
                </div>
                <div class="col-4 flex justify-end items-end text-end">
                    <a href="./signup.php" class="text-decoration-none fw-semibold text-white flex justify-end items-end text-end">New Pin?</a>
                </div>
            </div>
            <div class="d-flex justify-content-center text-white align-items-center h-100">
                <h3 class="mt-5">Authentication</h3>
            </div>
        </div>
    </div>