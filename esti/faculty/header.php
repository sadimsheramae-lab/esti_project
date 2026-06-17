<?php
$current = basename($_SERVER['PHP_SELF'], '.php');
function fActive($pages) {
    global $current;
    return in_array($current, (array)$pages) ? 'active' : '';
}
$faculty   = currentFaculty($conn);
$fid       = $faculty['faculty_id'];
$notif_cnt = facultyUnread($conn, $fid);
$full_name = ($faculty['last_name'] ?? '') . ', ' . ($faculty['first_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'Faculty Portal' ?> | ESTI CGMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/faculty.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo-circle"><i class="fas fa-graduation-cap"></i></div>
        <div class="brand-info">
            <span class="brand-name">ESTI</span>
            <span class="brand-sub">COLLEGE GRADING<br>MANAGEMENT SYSTEM</span>
        </div>
    </div>
    <div class="sidebar-label">FACULTY PORTAL</div>
    <!-- Faculty profile block -->
    <div class="sidebar-profile">
        <div class="sp-avatar">
            <?php if (!empty($faculty['profile_pic'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($faculty['profile_pic']) ?>" alt="avatar">
            <?php else: ?>
                <i class="fas fa-chalkboard-teacher"></i>
            <?php endif; ?>
        </div>
        <div class="sp-name"><?= htmlspecialchars($faculty['first_name'].' '.$faculty['last_name']) ?></div>
        <div class="sp-id"><?= htmlspecialchars($fid) ?></div>
        <div><span class="badge badge-active"><?= htmlspecialchars($faculty['position'] ?? 'Instructor') ?></span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= FACULTY_URL ?>dashboard.php" class="nav-item <?= fActive('dashboard') ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="<?= FACULTY_URL ?>my_classes.php" class="nav-item <?= fActive('my_classes') ?>">
            <i class="fas fa-users"></i><span>My Classes</span>
        </a>
        <a href="<?= FACULTY_URL ?>grade_entry.php" class="nav-item <?= fActive('grade_entry') ?>">
            <i class="fas fa-star-half-alt"></i><span>Grade Entry</span>
        </a>
        <a href="<?= FACULTY_URL ?>schedule.php" class="nav-item <?= fActive('schedule') ?>">
            <i class="fas fa-calendar-alt"></i><span>My Schedule</span>
        </a>
        <a href="<?= FACULTY_URL ?>students.php" class="nav-item <?= fActive('students') ?>">
            <i class="fas fa-user-graduate"></i><span>My Students</span>
        </a>
        <a href="<?= FACULTY_URL ?>notifications.php" class="nav-item <?= fActive('notifications') ?>">
            <i class="fas fa-bell"></i><span>Notifications</span>
            <?php if ($notif_cnt > 0): ?><span class="notif-badge"><?= $notif_cnt ?></span><?php endif; ?>
        </a>
        <a href="<?= FACULTY_URL ?>profile.php" class="nav-item <?= fActive('profile') ?>">
            <i class="fas fa-user-circle"></i><span>My Profile</span>
        </a>
        <a href="<?= FACULTY_URL ?>change_password.php" class="nav-item <?= fActive('change_password') ?>">
            <i class="fas fa-lock"></i><span>Change Password</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= FACULTY_URL ?>logout.php" class="nav-item logout-btn">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>
<div class="main-content">
    <div class="topbar">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><?= $page_title ?? '' ?></div>
        <div class="topbar-right">
            <a href="<?= FACULTY_URL ?>notifications.php" class="notif-bell">
                <i class="fas fa-bell"></i>
                <?php if ($notif_cnt > 0): ?><span class="notif-dot"></span><?php endif; ?>
            </a>
            <div class="topbar-user">
                <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($full_name) ?></span>
                    <span class="user-role"><?= htmlspecialchars(($faculty['position'] ?? 'Instructor').' · '.($faculty['dept_code'] ?? '')) ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </div>
    <div class="page-content">
