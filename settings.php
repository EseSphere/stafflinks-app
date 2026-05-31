<?php
$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';

$profile      = [];
$successMsg   = '';
$errorMsg     = '';
$pwSuccess    = '';
$pwError      = '';
$dpSuccess    = '';
$dpError      = '';

$baseUrl = 'settings.php?carer_id=' . urlencode($carerId)
         . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');

if ($carerId !== '') {
    include_once 'dbconnect.php';

    if (!$conn->connect_error) {

        $sel = $conn->prepare("
            SELECT id, user_fullname, team_dp, user_email_address,
                   user_phone_number, user_special_Id, col_company_Id
            FROM   tbl_team_account
            WHERE  user_special_Id = ?
            LIMIT  1
        ");
        $sel->bind_param('s', $carerId);
        $sel->execute();
        $res = $sel->get_result();
        $profile = $res->fetch_assoc() ?: [];
        $sel->close();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (isset($_POST['update_profile'])) {
                $fullName = trim($_POST['user_fullname']      ?? '');
                $email    = trim($_POST['user_email_address'] ?? '');
                $phone    = trim($_POST['user_phone_number']  ?? '');

                if ($fullName === '' || $email === '') {
                    $errorMsg = 'Full name and email are required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errorMsg = 'Please enter a valid email address.';
                } else {
                    $upd = $conn->prepare("
                        UPDATE tbl_team_account
                        SET    user_fullname       = ?,
                               user_email_address  = ?,
                               user_phone_number   = ?
                        WHERE  user_special_Id = ?
                    ");
                    $upd->bind_param('ssss', $fullName, $email, $phone, $carerId);
                    if ($upd->execute()) {
                        $successMsg = 'Profile updated successfully.';
                        $profile['user_fullname']      = $fullName;
                        $profile['user_email_address'] = $email;
                        $profile['user_phone_number']  = $phone;
                    } else {
                        $errorMsg = 'Could not update profile. Please try again.';
                    }
                    $upd->close();
                }
            }

            if (isset($_POST['change_password'])) {
                $current  = $_POST['current_password']  ?? '';
                $newPw    = $_POST['new_password']       ?? '';
                $confirm  = $_POST['confirm_password']   ?? '';

                if ($current === '' || $newPw === '' || $confirm === '') {
                    $pwError = 'All password fields are required.';
                } elseif ($newPw !== $confirm) {
                    $pwError = 'New passwords do not match.';
                } elseif (strlen($newPw) < 8) {
                    $pwError = 'New password must be at least 8 characters.';
                } else {
                    $chk = $conn->prepare("
                        SELECT user_password FROM tbl_team_account
                        WHERE  user_special_Id = ? LIMIT 1
                    ");
                    $chk->bind_param('s', $carerId);
                    $chk->execute();
                    $chk->bind_result($storedHash);
                    $chk->fetch();
                    $chk->close();

                    $verified = password_verify($current, $storedHash)
                             || ($current === $storedHash);

                    if (!$verified) {
                        $pwError = 'Current password is incorrect.';
                    } else {
                        $hash   = password_hash($newPw, PASSWORD_DEFAULT);
                        $pwUpd  = $conn->prepare("
                            UPDATE tbl_team_account
                            SET    user_password = ?
                            WHERE  user_special_Id = ?
                        ");
                        $pwUpd->bind_param('ss', $hash, $carerId);
                        if ($pwUpd->execute()) {
                            $pwSuccess = 'Password changed successfully.';
                        } else {
                            $pwError = 'Could not update password. Please try again.';
                        }
                        $pwUpd->close();
                    }
                }
            }

            if (isset($_POST['update_photo'])) {
                if (empty($_FILES['team_dp']['name'])) {
                    $dpError = 'Please select an image file.';
                } else {
                    $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
                    $mimeType = mime_content_type($_FILES['team_dp']['tmp_name']);
                    $maxSize  = 5 * 1024 * 1024;

                    if (!in_array($mimeType, $allowed)) {
                        $dpError = 'Only JPG, PNG, GIF or WEBP images allowed.';
                    } elseif ($_FILES['team_dp']['size'] > $maxSize) {
                        $dpError = 'Image must be under 5 MB.';
                    } else {
                        $uploadDir = 'uploads/team_dp/';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        $ext  = pathinfo($_FILES['team_dp']['name'], PATHINFO_EXTENSION);
                        $fname= 'dp_' . $carerId . '_' . time() . '.' . $ext;
                        $dest = $uploadDir . $fname;
                        if (move_uploaded_file($_FILES['team_dp']['tmp_name'], $dest)) {
                            $dpUpd = $conn->prepare("
                                UPDATE tbl_team_account
                                SET    team_dp = ?
                                WHERE  user_special_Id = ?
                            ");
                            $dpUpd->bind_param('ss', $dest, $carerId);
                            if ($dpUpd->execute()) {
                                $dpSuccess = 'Profile photo updated.';
                                $profile['team_dp'] = $dest;
                            } else {
                                $dpError = 'Photo saved but database update failed.';
                            }
                            $dpUpd->close();
                        } else {
                            $dpError = 'File upload failed. Please try again.';
                        }
                    }
                }
            }
        }

        $conn->close();
    }
}

function avatarUrl(string $path, string $name = ''): string {
    $base = 'https://admin.stafflinks.co.uk/';
    if (!$path || trim($path) === '') return '';
    $p = trim($path);
    if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) return htmlspecialchars($p);
    if (str_starts_with($p, 'uploads/') || str_starts_with($p, '/uploads/'))
        return htmlspecialchars($base . ltrim($p, '/'));
    return htmlspecialchars($base . 'uploads/team_dp/' . $p);
}

function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(substr(end($parts), 0, 1));
    return $i ?: '?';
}

function avatarBg(string $name): string {
    $c = ['#c94a57','#198754','#0d6efd','#6f42c1','#fd7e14','#0dcaf0','#e05c6e'];
    return $c[abs(crc32($name)) % count($c)];
}

$dpUrl       = avatarUrl($profile['team_dp'] ?? '');
$displayName = $profile['user_fullname'] ?? '—';
$displayInit = initials($displayName);
$displayBg   = avatarBg($displayName);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Profile Settings – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap"
        rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
    body {
        font-family: 'DM Sans', sans-serif;
    }

    .settings-container {
        max-width: 820px;
        margin: 0 auto;
        padding: 1.25rem 1rem 5rem;
    }

    .profile-hero {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 16px rgba(0, 0, 0, .07);
        padding: 2rem 1.75rem 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .profile-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 90px;
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        border-radius: var(--radius, 18px) var(--radius, 18px) 0 0;
    }

    .avatar-ring {
        position: relative;
        z-index: 2;
        width: 88px;
        height: 88px;
        border-radius: 50%;
        border: 4px solid var(--card-bg, #fff);
        box-shadow: 0 4px 18px rgba(0, 0, 0, .15);
        margin-bottom: .9rem;
        cursor: pointer;
        overflow: hidden;
        flex-shrink: 0;
        background: var(--pill-bg, #f0f0f0);
    }

    .avatar-ring img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .avatar-ring .avatar-initials {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Fraunces', Georgia, serif;
        font-size: 2rem;
        font-weight: 700;
        color: #fff;
    }

    .avatar-edit-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .2s;
        border-radius: 50%;
        color: #fff;
        font-size: 1.1rem;
    }

    .avatar-ring:hover .avatar-edit-overlay {
        opacity: 1;
    }

    .profile-hero-body {
        position: relative;
        z-index: 2;
        padding-top: 52px;
        display: flex;
        align-items: flex-end;
        gap: 1.25rem;
        flex-wrap: wrap;
    }

    .profile-name {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--text-main, #1a1a1a);
    }

    .profile-id-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: var(--pill-bg, #f5f5f5);
        border-radius: 999px;
        padding: .22rem .75rem;
        font-size: .75rem;
        font-weight: 600;
        color: var(--text-muted, #666);
        margin-top: .3rem;
    }

    .profile-id-pill i {
        color: var(--accent, #c94a57);
        font-size: .7rem;
    }

    .profile-company-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: rgba(201, 74, 87, .1);
        border-radius: 999px;
        padding: .22rem .75rem;
        font-size: .75rem;
        font-weight: 600;
        color: var(--accent, #c94a57);
        margin-top: .3rem;
    }

    .section-card {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        overflow: hidden;
        margin-bottom: 1.25rem;
    }

    .section-header {
        padding: 1.1rem 1.5rem .9rem;
        border-bottom: 1px solid var(--border-color, #f0f0f0);
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .section-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        flex-shrink: 0;
    }

    .ic-red {
        background: rgba(201, 74, 87, .12);
        color: var(--accent, #c94a57);
    }

    .ic-blue {
        background: rgba(13, 110, 253, .12);
        color: #0d6efd;
    }

    .ic-green {
        background: rgba(25, 135, 84, .12);
        color: #198754;
    }

    .ic-purple {
        background: rgba(111, 66, 193, .12);
        color: #6f42c1;
    }

    .section-title {
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main, #1a1a1a);
        line-height: 1;
    }

    .section-sub {
        font-size: .73rem;
        color: var(--text-muted, #aaa);
        margin-top: .1rem;
    }

    .section-body {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    @media(max-width:560px) {
        .field-grid {
            grid-template-columns: 1fr;
        }
    }

    .field-full {
        grid-column: 1 / -1;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: .3rem;
    }

    .field-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-muted, #888);
    }

    .field-input-wrap {
        position: relative;
    }

    .field-input-wrap .field-icon {
        position: absolute;
        left: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #bbb);
        font-size: .88rem;
        pointer-events: none;
    }

    .field-input {
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 10px;
        padding: .6rem .9rem .6rem 2.4rem;
        font-size: .9rem;
        background: var(--input-bg, #fafafa);
        color: var(--text-main, #1a1a1a);
        outline: none;
        transition: border-color .18s, box-shadow .18s;
        font-family: 'DM Sans', sans-serif;
        width: 100%;
    }

    .field-input.no-icon {
        padding-left: .9rem;
    }

    .field-input:focus {
        border-color: var(--accent, #c94a57);
        box-shadow: 0 0 0 3px rgba(201, 74, 87, .08);
    }

    .field-input[readonly],
    .field-input[disabled] {
        background: var(--pill-bg, #f0f0f0);
        color: var(--text-muted, #888);
        cursor: not-allowed;
    }

    .pw-toggle {
        position: absolute;
        right: .85rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted, #aaa);
        font-size: .9rem;
        padding: 0;
        transition: color .15s;
    }

    .pw-toggle:hover {
        color: var(--accent, #c94a57);
    }

    .pw-strength {
        height: 4px;
        border-radius: 999px;
        margin-top: .4rem;
        overflow: hidden;
        background: var(--border-color, #e8e8e8);
    }

    .pw-strength-fill {
        height: 100%;
        border-radius: 999px;
        transition: width .3s, background .3s;
    }

    .pw-hint {
        font-size: .72rem;
        color: var(--text-muted, #aaa);
        margin-top: .3rem;
    }

    .btn-save {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: .65rem 2rem;
        font-size: .9rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .18s, transform .15s;
        box-shadow: 0 4px 14px rgba(201, 74, 87, .25);
    }

    .btn-save:hover {
        opacity: .88;
        transform: translateY(-1px);
    }

    .btn-save:active {
        transform: translateY(0);
    }

    .btn-secondary {
        background: transparent;
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 999px;
        padding: .6rem 1.5rem;
        font-size: .88rem;
        color: var(--text-muted, #666);
        cursor: pointer;
        transition: border-color .18s, color .18s;
        font-family: 'DM Sans', sans-serif;
    }

    .btn-secondary:hover {
        border-color: var(--accent, #c94a57);
        color: var(--accent, #c94a57);
    }

    .flash-success {
        background: #f0fdf4;
        border: 1.5px solid #bbf7d0;
        border-radius: 10px;
        padding: .7rem 1rem;
        font-size: .85rem;
        color: #166534;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1rem;
    }

    .flash-error {
        background: #fff1f2;
        border: 1.5px solid #fecdd3;
        border-radius: 10px;
        padding: .7rem 1rem;
        font-size: .85rem;
        color: #991b1b;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1rem;
    }

    .photo-upload-area {
        border: 2px dashed var(--border-color, #e8e8e8);
        border-radius: 12px;
        padding: 1.5rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: border-color .18s, background .18s;
        position: relative;
    }

    .photo-upload-area:hover {
        border-color: var(--accent, #c94a57);
        background: rgba(201, 74, 87, .03);
    }

    .photo-upload-area input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .photo-preview-wrap {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1rem;
    }

    .photo-preview {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--border-color, #e8e8e8);
        display: none;
    }

    .photo-preview.show {
        display: block;
    }

    .photo-initials-fallback {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Fraunces', Georgia, serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
    }

    .read-only-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .65rem 0;
        border-bottom: 1px solid var(--border-color, #f5f5f5);
        font-size: .87rem;
    }

    .read-only-row:last-child {
        border-bottom: none;
    }

    .read-only-label {
        color: var(--text-muted, #888);
        font-weight: 500;
    }

    .read-only-val {
        font-weight: 600;
        color: var(--text-main, #1a1a1a);
    }

    .read-only-copy {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted, #bbb);
        font-size: .85rem;
        padding: .15rem .35rem;
        border-radius: 6px;
        transition: color .15s, background .15s;
    }

    .read-only-copy:hover {
        color: var(--accent, #c94a57);
        background: rgba(201, 74, 87, .07);
    }

    .danger-zone {
        background: #fff1f2;
        border: 1.5px solid #fecdd3;
        border-radius: var(--radius, 18px);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
    }

    .danger-title {
        font-weight: 800;
        color: #dc2626;
        font-size: .9rem;
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .4rem;
    }

    .btn-danger-outline {
        background: transparent;
        border: 1.5px solid #dc2626;
        border-radius: 999px;
        padding: .5rem 1.25rem;
        color: #dc2626;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        transition: background .18s, color .18s;
    }

    .btn-danger-outline:hover {
        background: #dc2626;
        color: #fff;
    }

    .fade-in-up {
        opacity: 0;
        animation: fiu .35s ease forwards;
    }

    .fade-in-up:nth-child(1) {
        animation-delay: .04s;
    }

    .fade-in-up:nth-child(2) {
        animation-delay: .08s;
    }

    .fade-in-up:nth-child(3) {
        animation-delay: .12s;
    }

    .fade-in-up:nth-child(4) {
        animation-delay: .16s;
    }

    .fade-in-up:nth-child(5) {
        animation-delay: .20s;
    }

    .fade-in-up:nth-child(6) {
        animation-delay: .24s;
    }

    @keyframes fiu {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: none;
        }
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
                    <div class="app-mark"><i class="bi bi-heart-pulse-fill"></i></div>
                    <h5 class="mb-0 fw-bold">StaffLinks</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="chip border-0 position-relative" type="button">
                        <i class="bi bi-bell"></i><span class="alert-dot">4</span>
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
                            <h2 class="mb-0 fw-bold">Profile Settings</h2>
                            <div class="text-white-50" style="font-size:.85rem">Manage your account and preferences
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="settings-container">

        <div class="profile-hero fade-in-up">
            <div class="profile-hero-body">
                <div class="avatar-ring" id="avatarRing" onclick="document.getElementById('dpFileInput').click()"
                    title="Change photo">
                    <?php if ($dpUrl): ?>
                    <img src="<?= $dpUrl ?>" alt="Profile photo" id="heroAvatarImg"
                        onerror="this.style.display='none';document.getElementById('heroAvatarInit').style.display='flex'">
                    <div class="avatar-initials" id="heroAvatarInit" style="background:<?= $displayBg ?>;display:none">
                        <?= htmlspecialchars($displayInit) ?>
                    </div>
                    <?php else: ?>
                    <div class="avatar-initials" id="heroAvatarInit" style="background:<?= $displayBg ?>">
                        <?= htmlspecialchars($displayInit) ?>
                    </div>
                    <?php endif; ?>
                    <div class="avatar-edit-overlay">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                </div>
                <div>
                    <div class="profile-name"><?= htmlspecialchars($displayName) ?></div>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <?php if ($profile['user_special_Id'] ?? ''): ?>
                        <span class="profile-id-pill">
                            <i class="bi bi-person-badge"></i>
                            ID: <?= htmlspecialchars($profile['user_special_Id']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($successMsg): ?>
        <div class="flash-success fade-in-up">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($successMsg) ?>
        </div>
        <?php endif; ?>

        <div class="section-card fade-in-up">
            <div class="section-header">
                <div class="section-icon ic-red"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="section-title">Personal Information</div>
                    <div class="section-sub">Update your name, email and phone number</div>
                </div>
            </div>
            <div class="section-body">
                <?php if ($errorMsg): ?>
                <div class="flash-error"><i class="bi bi-exclamation-circle-fill"></i>
                    <?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
                <form method="POST" action="<?= $baseUrl ?>">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="field-grid">
                        <div class="field-group field-full">
                            <label class="field-label">Full Name <span style="color:var(--accent)">*</span></label>
                            <div class="field-input-wrap">
                                <i class="bi bi-person field-icon"></i>
                                <input type="text" class="field-input" name="user_fullname"
                                    value="<?= htmlspecialchars($profile['user_fullname'] ?? '') ?>"
                                    placeholder="Your full name" required>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Email Address <span style="color:var(--accent)">*</span></label>
                            <div class="field-input-wrap">
                                <i class="bi bi-envelope field-icon"></i>
                                <input type="email" class="field-input" name="user_email_address"
                                    value="<?= htmlspecialchars($profile['user_email_address'] ?? '') ?>"
                                    placeholder="you@example.com" required>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Phone Number</label>
                            <div class="field-input-wrap">
                                <i class="bi bi-telephone field-icon"></i>
                                <input type="tel" class="field-input" name="user_phone_number"
                                    value="<?= htmlspecialchars($profile['user_phone_number'] ?? '') ?>"
                                    placeholder="+44 7700 900000">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4 align-items-center flex-wrap">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-check2 me-2"></i>Save Changes
                        </button>
                        <a href="<?= $baseUrl ?>" class="btn-secondary text-decoration-none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="section-card fade-in-up">
            <div class="section-header">
                <div class="section-icon ic-purple"><i class="bi bi-camera-fill"></i></div>
                <div>
                    <div class="section-title">Profile Photo</div>
                    <div class="section-sub">JPG, PNG, GIF or WEBP · Max 5 MB</div>
                </div>
            </div>
            <div class="section-body">
                <?php if ($dpSuccess): ?>
                <div class="flash-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($dpSuccess) ?>
                </div>
                <?php endif; ?>
                <?php if ($dpError): ?>
                <div class="flash-error"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($dpError) ?>
                </div>
                <?php endif; ?>
                <form method="POST" action="<?= $baseUrl ?>" enctype="multipart/form-data" id="photoForm">
                    <input type="hidden" name="update_photo" value="1">
                    <div class="photo-preview-wrap">
                        <div style="position:relative;flex-shrink:0">
                            <img src="<?= $dpUrl ?: '' ?>" alt="Current photo"
                                class="photo-preview <?= $dpUrl ? 'show' : '' ?>" id="photoPreviewImg"
                                onerror="this.classList.remove('show');document.getElementById('photoInitFallback').style.display='flex'">
                            <div class="photo-initials-fallback" id="photoInitFallback"
                                style="background:<?= $displayBg ?>;<?= $dpUrl ? 'display:none' : '' ?>">
                                <?= htmlspecialchars($displayInit) ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:.9rem;margin-bottom:.25rem">
                                <?= htmlspecialchars($displayName) ?>
                            </div>
                            <div style="font-size:.78rem;color:var(--text-muted,#888)">
                                Click the area below or drag a photo to update your picture.
                            </div>
                        </div>
                    </div>
                    <div class="photo-upload-area" id="dropArea">
                        <input type="file" name="team_dp" id="dpFileInput" accept="image/*" onchange="previewDp(this)">
                        <i class="bi bi-cloud-arrow-up"
                            style="font-size:2rem;color:var(--accent,#c94a57);margin-bottom:.5rem;display:block"></i>
                        <div style="font-weight:600;font-size:.88rem;margin-bottom:.25rem">
                            Drop your photo here or <span style="color:var(--accent)">browse</span>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted,#aaa)">
                            JPG, PNG, GIF or WEBP · Max 5 MB
                        </div>
                        <div id="dpFileName"
                            style="margin-top:.5rem;font-size:.8rem;color:var(--accent);font-weight:600;display:none">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn-save" id="dpSubmitBtn" style="display:none">
                            <i class="bi bi-upload me-2"></i>Upload Photo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="section-card fade-in-up">
            <div class="section-header">
                <div class="section-icon ic-blue"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                    <div class="section-title">Change Password</div>
                    <div class="section-sub">Use a strong password with at least 8 characters</div>
                </div>
            </div>
            <div class="section-body">
                <?php if ($pwSuccess): ?>
                <div class="flash-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($pwSuccess) ?>
                </div>
                <?php endif; ?>
                <?php if ($pwError): ?>
                <div class="flash-error"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($pwError) ?>
                </div>
                <?php endif; ?>
                <form method="POST" action="<?= $baseUrl ?>" id="pwForm">
                    <input type="hidden" name="change_password" value="1">
                    <div class="field-grid">
                        <div class="field-group field-full">
                            <label class="field-label">Current Password</label>
                            <div class="field-input-wrap">
                                <i class="bi bi-lock field-icon"></i>
                                <input type="password" class="field-input" name="current_password" id="currentPw"
                                    placeholder="Enter current password" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('currentPw','toggleCurrent')">
                                    <i class="bi bi-eye" id="toggleCurrent"></i>
                                </button>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">New Password</label>
                            <div class="field-input-wrap">
                                <i class="bi bi-lock-fill field-icon"></i>
                                <input type="password" class="field-input" name="new_password" id="newPw"
                                    placeholder="Min. 8 characters" required oninput="checkStrength(this.value)">
                                <button type="button" class="pw-toggle" onclick="togglePw('newPw','toggleNew')">
                                    <i class="bi bi-eye" id="toggleNew"></i>
                                </button>
                            </div>
                            <div class="pw-strength">
                                <div class="pw-strength-fill" id="strengthBar" style="width:0"></div>
                            </div>
                            <div class="pw-hint" id="strengthHint">Enter a new password</div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Confirm New Password</label>
                            <div class="field-input-wrap">
                                <i class="bi bi-lock-fill field-icon"></i>
                                <input type="password" class="field-input" name="confirm_password" id="confirmPw"
                                    placeholder="Repeat new password" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('confirmPw','toggleConfirm')">
                                    <i class="bi bi-eye" id="toggleConfirm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4 flex-wrap">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-shield-check me-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="section-card fade-in-up">
            <div class="section-header">
                <div class="section-icon ic-green"><i class="bi bi-info-circle-fill"></i></div>
                <div>
                    <div class="section-title">Account Information</div>
                    <div class="section-sub">Read-only account identifiers</div>
                </div>
            </div>
            <div class="section-body">
                <div class="read-only-row">
                    <span class="read-only-label">Staff ID</span>
                    <span class="d-flex align-items-center gap-2">
                        <span class="read-only-val" id="roSpecialId">
                            <?= htmlspecialchars($profile['user_special_Id'] ?? '—') ?>
                        </span>
                        <?php if ($profile['user_special_Id'] ?? ''): ?>
                        <button class="read-only-copy" onclick="copyText('roSpecialId', this)" title="Copy">
                            <i class="bi bi-copy"></i>
                        </button>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="read-only-row">
                    <span class="read-only-label">Account ID</span>
                    <span class="read-only-val" id="roAccountId">
                        209<?= htmlspecialchars($profile['id'] ?? '—') ?>51
                    </span>
                    <?php if ($profile['id'] ?? ''): ?>
                    <button class="read-only-copy" onclick="copyText('roAccountId', this)" title="Copy">
                        <i class="bi bi-copy"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="danger-zone fade-in-up">
            <div class="danger-title">
                <i class="bi bi-exclamation-triangle-fill"></i> Sign Out
            </div>
            <p style="font-size:.82rem;color:#7f1d1d;margin-bottom:.9rem">
                This will end your current session and return you to the login screen.
            </p>
            <button class="btn-danger-outline" id="signOutBtn">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
            </button>
        </div>

    </main>

    <?php include 'new-footer.php'; ?>

    <script>
    (function() {
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
            sessionStorage.clear();
            window.location.href = './';
            return;
        }

        (function ensureParams() {
            const url = new URL(window.location.href);
            let changed = false;
            const sessId = String(user.user_special_Id || '');
            const sessC = String(user.col_company_Id || '');
            if (sessId && url.searchParams.get('carer_id') !== sessId) {
                url.searchParams.set('carer_id', sessId);
                changed = true;
            }
            if (sessC && url.searchParams.get('col_company_Id') !== sessC) {
                url.searchParams.set('col_company_Id', sessC);
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
        const wFb = el => {
            if (el) el.onerror = function() {
                if (this.src !== DEFAULT_AVATAR) this.src = DEFAULT_AVATAR;
            };
        };
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

        const avatar = avatarSrc(user.team_dp);
        const name = user.user_fullname || '';
        setSrc('topbarAvatar', avatar, name);
        wFb(document.getElementById('topbarAvatar'));
        setSrc('navProfilePic', avatar, name);
        wFb(document.getElementById('navProfilePic'));
        setText('navFullName', name);
        setText('navEmail', user.user_email_address || '');
        setText('navPhone', user.user_phone_number || '');

        const signOutBtn = document.getElementById('signOutBtn');
        if (signOutBtn) signOutBtn.addEventListener('click', () => {
            sessionStorage.removeItem('loggedInUser');
            sessionStorage.removeItem('loggedInUserId');
            window.location.href = './';
        });

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', e => {
            e.preventDefault();
            sessionStorage.removeItem('loggedInUser');
            sessionStorage.removeItem('loggedInUserId');
            window.location.href = './';
        });
    })();

    function togglePw(inputId, iconId) {
        const inp = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!inp || !icon) return;
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    function checkStrength(val) {
        const bar = document.getElementById('strengthBar');
        const hint = document.getElementById('strengthHint');
        if (!bar || !hint) return;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        const configs = [{
                pct: '0%',
                bg: 'transparent',
                text: 'Enter a new password'
            },
            {
                pct: '25%',
                bg: '#dc2626',
                text: 'Weak — add more characters'
            },
            {
                pct: '50%',
                bg: '#f59e0b',
                text: 'Fair — try adding numbers'
            },
            {
                pct: '75%',
                bg: '#0d6efd',
                text: 'Good — almost there'
            },
            {
                pct: '100%',
                bg: '#198754',
                text: 'Strong password'
            },
        ];
        const cfg = configs[Math.min(score, 4)];
        bar.style.width = cfg.pct;
        bar.style.background = cfg.bg;
        hint.textContent = cfg.text;
        hint.style.color = cfg.bg === 'transparent' ? 'var(--text-muted,#aaa)' : cfg.bg;
    }

    function previewDp(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('photoPreviewImg');
            const init = document.getElementById('photoInitFallback');
            const hero = document.getElementById('heroAvatarImg');
            const heroInit = document.getElementById('heroAvatarInit');
            const submitBtn = document.getElementById('dpSubmitBtn');
            const fileNameEl = document.getElementById('dpFileName');

            if (img) {
                img.src = e.target.result;
                img.classList.add('show');
            }
            if (init) init.style.display = 'none';
            if (hero) {
                hero.src = e.target.result;
                hero.style.display = 'block';
            }
            if (heroInit) heroInit.style.display = 'none';
            if (submitBtn) submitBtn.style.display = '';
            if (fileNameEl) {
                fileNameEl.textContent = input.files[0].name;
                fileNameEl.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }

    (function dragDrop() {
        const area = document.getElementById('dropArea');
        if (!area) return;
        ['dragenter', 'dragover'].forEach(e => area.addEventListener(e, ev => {
            ev.preventDefault();
            area.style.borderColor = 'var(--accent,#c94a57)';
            area.style.background = 'rgba(201,74,87,.05)';
        }));
        ['dragleave', 'drop'].forEach(e => area.addEventListener(e, ev => {
            ev.preventDefault();
            area.style.borderColor = '';
            area.style.background = '';
        }));
        area.addEventListener('drop', ev => {
            ev.preventDefault();
            const file = ev.dataTransfer.files[0];
            if (!file) return;
            const input = document.getElementById('dpFileInput');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            previewDp(input);
        });
    })();

    function copyText(elementId, btn) {
        const el = document.getElementById(elementId);
        if (!el) return;
        navigator.clipboard.writeText(el.textContent.trim()).then(() => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-check2';
                setTimeout(() => icon.className = 'bi bi-copy', 1500);
            }
        });
    }
    </script>
</body>

</html>