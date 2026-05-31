<?php
$carerId   = isset($_GET['carer_id'])       ? trim($_GET['carer_id'])       : '';
$companyId = isset($_GET['col_company_Id']) ? trim($_GET['col_company_Id']) : '';
$search    = isset($_GET['search'])         ? trim($_GET['search'])         : '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 15;

$posts       = [];
$totalPosts  = 0;
$totalPages  = 1;
$postSuccess = '';
$postError   = '';

$baseUrl = 'community.php?carer_id=' . urlencode($carerId)
         . ($companyId ? '&col_company_Id=' . urlencode($companyId) : '');

if ($companyId !== '') {
    include_once 'dbconnect.php';

    if (!$conn->connect_error) {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_message'])) {
            $senderName  = trim($_POST['sender_name']  ?? '');
            $email       = trim($_POST['sender_email'] ?? '');
            $uniqueId    = trim($_POST['unique_id']    ?? '');
            $message     = trim($_POST['message']      ?? '');
            $picturePath = '';

            if ($senderName === '' || $message === '') {
                $postError = 'Name and message are required.';
            } else {
                if (!empty($_FILES['picture']['name'])) {
                    $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
                    $mimeType = mime_content_type($_FILES['picture']['tmp_name']);
                    $maxSize  = 5 * 1024 * 1024;

                    if (!in_array($mimeType, $allowed)) {
                        $postError = 'Only JPG, PNG, GIF or WEBP images are allowed.';
                    } elseif ($_FILES['picture']['size'] > $maxSize) {
                        $postError = 'Image must be under 5 MB.';
                    } else {
                        $uploadDir = 'uploads/community/';
                        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        $ext         = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
                        $filename    = uniqid('comm_', true) . '.' . $ext;
                        $destination = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES['picture']['tmp_name'], $destination)) {
                            $picturePath = $destination;
                        } else {
                            $postError = 'Image upload failed. Please try again.';
                        }
                    }
                }

                if ($postError === '') {
                    $stmt = $conn->prepare("
                        INSERT INTO tbl_community
                               (sender_name, email, uryyTteamoeSS4, picture, message, col_company_Id, submitted_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param('ssssss', $senderName, $email, $uniqueId, $picturePath, $message, $companyId);
                    if ($stmt->execute()) {
                        $postSuccess = 'Your message has been posted!';
                    } else {
                        $postError = 'Could not save your post. Please try again.';
                    }
                    $stmt->close();
                }
            }
        }

        if ($search !== '') {
            $like    = '%' . $search . '%';
            $cntStmt = $conn->prepare("
                SELECT COUNT(*) FROM tbl_community
                WHERE col_company_Id = ? AND (sender_name LIKE ? OR message LIKE ?)
            ");
            $cntStmt->bind_param('sss', $companyId, $like, $like);
        } else {
            $cntStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_community WHERE col_company_Id = ?");
            $cntStmt->bind_param('s', $companyId);
        }
        $cntStmt->execute();
        $cntStmt->bind_result($totalPosts);
        $cntStmt->fetch();
        $cntStmt->close();

        $totalPages = max(1, (int)ceil($totalPosts / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        if ($search !== '') {
            $like    = '%' . $search . '%';
            $datStmt = $conn->prepare("
                SELECT id, sender_name, picture, message, submitted_at
                FROM   tbl_community
                WHERE  col_company_Id = ? AND (sender_name LIKE ? OR message LIKE ?)
                ORDER  BY submitted_at DESC
                LIMIT  ? OFFSET ?
            ");
            $datStmt->bind_param('sssii', $companyId, $like, $like, $perPage, $offset);
        } else {
            $datStmt = $conn->prepare("
                SELECT id, sender_name, picture, message, submitted_at
                FROM   tbl_community
                WHERE  col_company_Id = ?
                ORDER  BY submitted_at DESC
                LIMIT  ? OFFSET ?
            ");
            $datStmt->bind_param('sii', $companyId, $perPage, $offset);
        }
        $datStmt->execute();
        $res = $datStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $posts[] = $row;
        }
        $datStmt->close();
        $conn->close();
    }
}

function fmtTimestamp(string $ts): string {
    $dt   = new DateTime($ts);
    $now  = new DateTime();
    $diff = $now->diff($dt);
    if ($diff->days === 0) {
        if ($diff->h === 0) return $diff->i <= 1 ? 'just now' : $diff->i . 'm ago';
        return $diff->h . 'h ago';
    }
    if ($diff->days === 1) return 'Yesterday ' . $dt->format('H:i');
    if ($diff->days < 7)  return $dt->format('D') . ' ' . $dt->format('H:i');
    return $dt->format('j M Y · H:i');
}

function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $init  = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $init .= strtoupper(substr(end($parts), 0, 1));
    return $init ?: '?';
}

function avatarColour(string $name): string {
    $colours = ['#c94a57','#e05c6e','#198754','#0d6efd','#fd7e14','#6f42c1','#20c997','#0dcaf0'];
    return $colours[abs(crc32($name)) % count($colours)];
}

function pictureUrl(string $path): string {
    if (!$path) return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
    return htmlspecialchars($path);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Community – StaffLinks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lato:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link href="./css/style1.css" rel="stylesheet">
    <link href="./css/dashboard.css" rel="stylesheet">
    <style>
    body {
        font-family: 'Lato', sans-serif;
    }

    .community-container {
        max-width: 720px;
        margin: 0 auto;
        padding: 1.25rem 1rem 5rem;
    }

    .comm-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .85rem;
        margin-bottom: 1.5rem;
    }

    .comm-stat {
        background: var(--card-bg, #fff);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .65rem;
    }

    .cs-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }

    .cs-icon.red {
        background: rgba(201, 74, 87, .12);
        color: var(--accent, #c94a57);
    }

    .cs-icon.green {
        background: rgba(25, 135, 84, .12);
        color: #198754;
    }

    .cs-icon.blue {
        background: rgba(13, 110, 253, .12);
        color: #0d6efd;
    }

    .cs-val {
        font-weight: 800;
        font-size: 1.1rem;
        line-height: 1;
    }

    .cs-label {
        font-size: .7rem;
        color: var(--text-muted, #888);
        margin-top: .1rem;
    }

    .compose-card {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 16px rgba(0, 0, 0, .07);
        padding: 1.25rem 1.25rem 1rem;
        margin-bottom: 1.5rem;
    }

    .compose-header {
        display: flex;
        align-items: center;
        gap: .85rem;
        margin-bottom: 1rem;
    }

    .compose-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .9rem;
        color: #fff;
        flex-shrink: 0;
        font-family: 'Playfair Display', Georgia, serif;
    }

    .compose-trigger {
        flex: 1;
        background: var(--pill-bg, #f5f5f5);
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 999px;
        padding: .6rem 1.1rem;
        font-size: .88rem;
        color: var(--text-muted, #aaa);
        cursor: pointer;
        transition: border-color .18s, background .18s;
        text-align: left;
    }

    .compose-trigger:hover {
        border-color: var(--accent, #c94a57);
        background: var(--card-bg, #fff);
    }

    .compose-form {
        display: none;
    }

    .compose-form.open {
        display: block;
    }

    .compose-textarea {
        width: 100%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 12px;
        padding: .75rem 1rem;
        font-size: .92rem;
        font-family: 'Lato', sans-serif;
        color: var(--text-main, #1a1a1a);
        background: var(--input-bg, #fafafa);
        resize: vertical;
        min-height: 90px;
        outline: none;
        transition: border-color .18s;
    }

    .compose-textarea:focus {
        border-color: var(--accent, #c94a57);
    }

    .compose-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-top: .75rem;
        flex-wrap: wrap;
    }

    .compose-file-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .82rem;
        font-weight: 600;
        color: var(--text-muted, #888);
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 999px;
        padding: .38rem .9rem;
        cursor: pointer;
        transition: border-color .18s, color .18s;
        background: transparent;
    }

    .compose-file-btn:hover {
        border-color: var(--accent, #c94a57);
        color: var(--accent, #c94a57);
    }

    .compose-file-btn input {
        display: none;
    }

    .btn-post {
        background: linear-gradient(135deg, var(--accent, #c94a57), var(--accent2, #e05c6e));
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: .45rem 1.5rem;
        font-size: .88rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .18s, transform .15s;
    }

    .btn-post:hover {
        opacity: .88;
        transform: translateY(-1px);
    }

    .preview-wrap {
        margin-top: .65rem;
        position: relative;
        display: none;
    }

    .preview-wrap.show {
        display: block;
    }

    .preview-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 1.5px solid var(--border-color, #e8e8e8);
        cursor: pointer;
    }

    .preview-remove {
        position: absolute;
        top: 4px;
        left: 84px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(0, 0, 0, .6);
        color: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: .7rem;
    }

    .search-bar {
        display: flex;
        gap: .6rem;
        margin-bottom: 1.25rem;
    }

    .search-wrap {
        flex: 1;
        position: relative;
    }

    .search-wrap i {
        position: absolute;
        left: .9rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #aaa);
        pointer-events: none;
        font-size: .9rem;
    }

    .search-input {
        width: 100%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 999px;
        padding: .55rem 1rem .55rem 2.4rem;
        font-size: .87rem;
        background: var(--card-bg, #fff);
        color: var(--text-main, #1a1a1a);
        outline: none;
        transition: border-color .18s;
    }

    .search-input:focus {
        border-color: var(--accent, #c94a57);
    }

    .btn-search {
        background: var(--accent, #c94a57);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: .5rem 1.25rem;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .18s;
        white-space: nowrap;
    }

    .btn-search:hover {
        opacity: .88;
    }

    .btn-clear-search {
        background: transparent;
        border: 1.5px solid var(--border-color, #e8e8e8);
        border-radius: 999px;
        padding: .5rem 1rem;
        font-size: .85rem;
        color: var(--text-muted, #888);
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
    }

    .post-feed {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .post-card {
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        overflow: hidden;
        transition: box-shadow .2s;
        animation: postIn .3s ease both;
    }

    .post-card:hover {
        box-shadow: 0 6px 24px rgba(0, 0, 0, .1);
    }

    @keyframes postIn {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .post-card:nth-child(1) {
        animation-delay: .04s;
    }

    .post-card:nth-child(2) {
        animation-delay: .08s;
    }

    .post-card:nth-child(3) {
        animation-delay: .12s;
    }

    .post-card:nth-child(4) {
        animation-delay: .16s;
    }

    .post-card:nth-child(5) {
        animation-delay: .20s;
    }

    .post-card:nth-child(6) {
        animation-delay: .24s;
    }

    .post-body {
        padding: 1rem 1.15rem 1rem;
    }

    .post-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: .75rem;
    }

    .post-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .9rem;
        color: #fff;
        flex-shrink: 0;
        font-family: 'Playfair Display', Georgia, serif;
        letter-spacing: -.02em;
    }

    .post-sender-name {
        font-weight: 700;
        font-size: .95rem;
        color: var(--text-main, #1a1a1a);
        line-height: 1.2;
    }

    .post-meta {
        font-size: .73rem;
        color: var(--text-muted, #aaa);
        margin-top: .1rem;
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .post-meta i {
        font-size: .65rem;
    }

    .post-thumb-wrap {
        margin-bottom: .75rem;
        display: inline-block;
    }

    .post-thumb {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 1.5px solid var(--border-color, #e8e8e8);
        cursor: pointer;
        display: block;
        transition: opacity .18s, transform .18s;
    }

    .post-thumb:hover {
        opacity: .88;
        transform: scale(1.03);
    }

    .post-message {
        font-size: .92rem;
        line-height: 1.65;
        color: var(--text-main, #2a2a2a);
        white-space: pre-wrap;
        word-break: break-word;
    }

    .post-message.truncated {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .read-more-btn {
        background: none;
        border: none;
        padding: 0;
        color: var(--accent, #c94a57);
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: .35rem;
        display: inline-block;
    }

    .empty-feed {
        text-align: center;
        padding: 3.5rem 2rem;
        background: var(--card-bg, #fff);
        border-radius: var(--radius, 18px);
        box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: var(--pill-bg, #f5f5f5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--text-muted, #ccc);
        margin: 0 auto 1.1rem;
    }

    .pagination-wrap {
        display: flex;
        justify-content: center;
        gap: .4rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
    }

    .pg-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1.5px solid var(--border-color, #e8e8e8);
        background: var(--card-bg, #fff);
        color: var(--text-main, #444);
        font-size: .83rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background .18s, border-color .18s, color .18s;
    }

    .pg-btn:hover,
    .pg-btn.active {
        background: var(--accent, #c94a57);
        border-color: var(--accent, #c94a57);
        color: #fff;
    }

    .pg-btn.disabled {
        opacity: .35;
        pointer-events: none;
    }

    .lightbox-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .9);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .lightbox-overlay.open {
        display: flex;
    }

    .lightbox-img {
        max-width: 92vw;
        max-height: 88vh;
        border-radius: 12px;
        object-fit: contain;
        box-shadow: 0 16px 60px rgba(0, 0, 0, .5);
    }

    .lightbox-close {
        position: fixed;
        top: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .15);
        border: none;
        color: #fff;
        font-size: 1.1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .18s;
    }

    .lightbox-close:hover {
        background: rgba(255, 255, 255, .3);
    }

    .flash-success {
        background: #f0fdf4;
        border: 1.5px solid #bbf7d0;
        border-radius: 12px;
        padding: .75rem 1rem;
        font-size: .88rem;
        color: #166534;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1.1rem;
    }

    .flash-error {
        background: #fff1f2;
        border: 1.5px solid #fecdd3;
        border-radius: 12px;
        padding: .75rem 1rem;
        font-size: .88rem;
        color: #991b1b;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1.1rem;
    }

    .feed-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .85rem;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .feed-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main, #1a1a1a);
    }

    .feed-count {
        font-size: .75rem;
        color: var(--text-muted, #aaa);
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
                        <i class="bi bi-bell"></i>
                        <span class="alert-dot">4</span>
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
                            <h2 class="mb-0 fw-bold">Community</h2>
                            <div class="text-white-50" style="font-size:.85rem">Team messages and updates</div>
                        </div>
                    </div>
                    <span class="chip">
                        <i class="bi bi-people-fill me-1"></i>
                        <?= $totalPosts ?> post<?= $totalPosts !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="community-container">

        <?php
        $uniqueSenders = count(array_unique(array_column($posts, 'sender_name')));
        $withImages    = count(array_filter($posts, fn($p) => !empty($p['picture'])));
        ?>

        <div class="comm-stats fade-in-up">
            <div class="comm-stat">
                <div class="cs-icon red"><i class="bi bi-chat-fill"></i></div>
                <div>
                    <div class="cs-val"><?= $totalPosts ?></div>
                    <div class="cs-label">Total posts</div>
                </div>
            </div>
            <div class="comm-stat">
                <div class="cs-icon green"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="cs-val"><?= $uniqueSenders ?></div>
                    <div class="cs-label">Members</div>
                </div>
            </div>
            <div class="comm-stat">
                <div class="cs-icon blue"><i class="bi bi-image-fill"></i></div>
                <div>
                    <div class="cs-val"><?= $withImages ?></div>
                    <div class="cs-label">With photos</div>
                </div>
            </div>
        </div>

        <?php if ($postSuccess): ?>
        <div class="flash-success fade-in-up">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($postSuccess) ?>
        </div>
        <?php endif; ?>
        <?php if ($postError): ?>
        <div class="flash-error fade-in-up">
            <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($postError) ?>
        </div>
        <?php endif; ?>

        <?php if ($companyId !== ''): ?>
        <div class="compose-card fade-in-up">
            <div class="compose-header">
                <div class="compose-avatar" id="compose-avatar-el" style="background:#c94a57">?</div>
                <button class="compose-trigger" id="composeTrigger" onclick="openCompose()">
                    Share something with your team…
                </button>
            </div>
            <div class="compose-form" id="composeForm">
                <form method="POST" action="<?= $baseUrl ?>&page=<?= $page ?>" enctype="multipart/form-data"
                    onsubmit="return validatePost()">
                    <input type="hidden" name="post_message" value="1">
                    <input type="hidden" name="sender_name" id="f-sender-name">
                    <input type="hidden" name="sender_email" id="f-sender-email">
                    <input type="hidden" name="unique_id" id="f-unique-id">

                    <textarea class="compose-textarea" name="message" id="postMessage"
                        placeholder="What's on your mind? Share updates, tips, or news with your team…"
                        maxlength="2000"></textarea>

                    <div class="preview-wrap" id="previewWrap">
                        <img src="" alt="Preview" class="preview-img" id="previewImg" onclick="openLightbox(this.src)">
                        <button type="button" class="preview-remove" onclick="removeImage()" aria-label="Remove image">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <div class="compose-actions">
                        <label class="compose-file-btn">
                            <i class="bi bi-image"></i> Add Photo
                            <input type="file" name="picture" id="pictureInput" accept="image/*"
                                onchange="previewImage(this)">
                        </label>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn-clear-search" onclick="closeCompose()">Cancel</button>
                            <button type="submit" class="btn-post">
                                <i class="bi bi-send me-1"></i> Post
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <form method="GET" action="community.php" class="search-bar fade-in-up">
            <input type="hidden" name="carer_id" value="<?= htmlspecialchars($carerId) ?>">
            <?php if ($companyId): ?>
            <input type="hidden" name="col_company_Id" value="<?= htmlspecialchars($companyId) ?>">
            <?php endif; ?>
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" name="search" placeholder="Search messages or members…"
                    value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-search">Search</button>
            <?php if ($search): ?>
            <a href="<?= $baseUrl ?>" class="btn-clear-search">Clear</a>
            <?php endif; ?>
        </form>

        <div class="feed-header">
            <div class="feed-title">
                <?= $search ? 'Results for "' . htmlspecialchars($search) . '"' : 'Latest Posts' ?>
            </div>
            <?php if ($totalPosts > 0): ?>
            <div class="feed-count">
                Showing <?= count($posts) ?> of <?= $totalPosts ?>
                &nbsp;·&nbsp; Page <?= $page ?> of <?= $totalPages ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($posts)): ?>
        <div class="empty-feed fade-in-up">
            <div class="empty-icon"><i class="bi bi-chat-square-dots"></i></div>
            <h5 class="fw-bold mb-2">
                <?= $search ? 'No posts match your search' : 'No posts yet' ?>
            </h5>
            <p class="small-muted mb-3">
                <?= $search ? 'Try a different search term.' : 'Be the first to share something with your team!' ?>
            </p>
            <?php if ($search): ?>
            <a href="<?= $baseUrl ?>" class="btn btn-sm text-white"
                style="background:var(--accent);border-radius:999px;padding:.5rem 1.25rem">
                Clear search
            </a>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="post-feed">
            <?php foreach ($posts as $i => $post):
                    $initStr   = initials($post['sender_name']);
                    $bgColour  = avatarColour($post['sender_name']);
                    $timeStr   = fmtTimestamp($post['submitted_at']);
                    $picUrl    = pictureUrl($post['picture'] ?? '');
                    $msgId     = 'msg-' . $post['id'];
                    $wordCount = str_word_count($post['message']);
                    $longMsg   = $wordCount > 60;
                ?>
            <article class="post-card" style="animation-delay:<?= min($i,5)*0.04 ?>s">
                <div class="post-body">

                    <div class="post-header">
                        <div class="post-avatar" style="background:<?= $bgColour ?>">
                            <?= htmlspecialchars($initStr) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="post-sender-name">
                                <?= htmlspecialchars($post['sender_name']) ?>
                            </div>
                            <div class="post-meta">
                                <i class="bi bi-clock"></i>
                                <?= htmlspecialchars($timeStr) ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($picUrl): ?>
                    <div class="post-thumb-wrap">
                        <img src="<?= $picUrl ?>" alt="Attachment" class="post-thumb" loading="lazy"
                            onclick="openLightbox('<?= htmlspecialchars($picUrl, ENT_QUOTES) ?>')"
                            onerror="this.closest('.post-thumb-wrap').style.display='none'">
                    </div>
                    <?php endif; ?>

                    <div class="post-message <?= $longMsg ? 'truncated' : '' ?>" id="<?= $msgId ?>">
                        <?= nl2br(htmlspecialchars($post['message'])) ?>
                    </div>
                    <?php if ($longMsg): ?>
                    <button class="read-more-btn" onclick="toggleMessage('<?= $msgId ?>', this)">
                        Read more
                    </button>
                    <?php endif; ?>

                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1):
                function pgUrl(int $p, string $base, string $search): string {
                    $u = $base . '&page=' . $p;
                    if ($search) $u .= '&search=' . urlencode($search);
                    return $u;
                }
                $start = max(1, min($page - 2, $totalPages - 4));
                $end   = min($totalPages, $start + 4);
            ?>
        <nav class="pagination-wrap fade-in-up">
            <a href="<?= $page > 1 ? pgUrl($page-1,$baseUrl,$search) : '#' ?>"
                class="pg-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
            <?php for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?= pgUrl($p,$baseUrl,$search) ?>" class="pg-btn <?= $p === $page ? 'active' : '' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>
            <a href="<?= $page < $totalPages ? pgUrl($page+1,$baseUrl,$search) : '#' ?>"
                class="pg-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </nav>
        <?php endif; ?>

        <?php endif; ?>

    </main>

    <div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
        <img src="" alt="Full image" class="lightbox-img" id="lightboxImg" onclick="event.stopPropagation()">
    </div>

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

        const setVal = (id, v) => {
            const el = document.getElementById(id);
            if (el) el.value = v || '';
        };
        setVal('f-sender-name', user.user_fullname || '');
        setVal('f-sender-email', user.user_email_address || '');
        setVal('f-unique-id', user.user_special_Id || '');

        const avEl = document.getElementById('compose-avatar-el');
        if (avEl && name) {
            const parts = name.trim().split(/\s+/);
            const init = (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
            avEl.textContent = init;
            const colours = ['#c94a57', '#e05c6e', '#198754', '#0d6efd', '#fd7e14', '#6f42c1', '#20c997'];
            avEl.style.background = colours[name.charCodeAt(0) % colours.length];
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', e => {
            e.preventDefault();
            sessionStorage.removeItem('loggedInUser');
            sessionStorage.removeItem('loggedInUserId');
            window.location.href = './';
        });
    })();

    function openCompose() {
        document.getElementById('composeForm').classList.add('open');
        document.getElementById('composeTrigger').style.display = 'none';
        document.getElementById('postMessage').focus();
    }

    function closeCompose() {
        document.getElementById('composeForm').classList.remove('open');
        document.getElementById('composeTrigger').style.display = '';
        document.getElementById('postMessage').value = '';
        removeImage();
    }

    function validatePost() {
        const msg = document.getElementById('postMessage').value.trim();
        if (!msg) {
            document.getElementById('postMessage').focus();
            return false;
        }
        return true;
    }

    function previewImage(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewWrap').classList.add('show');
        };
        reader.readAsDataURL(input.files[0]);
    }

    function removeImage() {
        document.getElementById('previewImg').src = '';
        document.getElementById('previewWrap').classList.remove('show');
        const inp = document.getElementById('pictureInput');
        if (inp) inp.value = '';
    }

    function toggleMessage(id, btn) {
        const el = document.getElementById(id);
        if (el.classList.contains('truncated')) {
            el.classList.remove('truncated');
            btn.textContent = 'Show less';
        } else {
            el.classList.add('truncated');
            btn.textContent = 'Read more';
        }
    }

    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightbox').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeLightbox();
    });
    </script>
</body>

</html>