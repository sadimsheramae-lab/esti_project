<?php
$current = basename($_SERVER['PHP_SELF'], '.php');
function sActive($pages) {
    global $current;
    return in_array($current, (array)$pages) ? 'active' : '';
}
$student   = currentStudent($conn);
$notif_cnt = unreadCount($conn, $student['id_number']);
$full_name = $student ? ($student['last_name'].', '.$student['first_name']) : 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'Student Portal' ?> | ESTI CGMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/student.css">
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
    <div class="sidebar-label">STUDENT PORTAL</div>
    <!-- Student profile block -->
    <div class="sidebar-profile">
        <div class="sp-avatar">
            <?php if (!empty($student['profile_pic'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($student['profile_pic']) ?>" alt="avatar">
            <?php else: ?>
                <i class="fas fa-user-graduate"></i>
            <?php endif; ?>
        </div>
        <div class="sp-name"><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></div>
        <div class="sp-id"><?= htmlspecialchars($student['id_number']) ?></div>
        <div><span class="badge badge-active"><?= htmlspecialchars($student['course'].' · '.$student['year_level']) ?></span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= STUDENT_URL ?>dashboard.php" class="nav-item <?= sActive('dashboard') ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="<?= STUDENT_URL ?>grades.php" class="nav-item <?= sActive('grades') ?>">
            <i class="fas fa-star-half-alt"></i><span>My Grades</span>
        </a>
        <a href="<?= STUDENT_URL ?>schedule.php" class="nav-item <?= sActive('schedule') ?>">
            <i class="fas fa-calendar-alt"></i><span>Class Schedule</span>
        </a>
        <a href="<?= STUDENT_URL ?>subjects.php" class="nav-item <?= sActive('subjects') ?>">
            <i class="fas fa-book-open"></i><span>Enrolled Subjects</span>
        </a>
        <a href="<?= STUDENT_URL ?>notifications.php" class="nav-item <?= sActive('notifications') ?>">
            <i class="fas fa-bell"></i><span>Notifications</span>
            <?php if ($notif_cnt > 0): ?><span class="notif-badge"><?= $notif_cnt ?></span><?php endif; ?>
        </a>
        <a href="<?= STUDENT_URL ?>profile.php" class="nav-item <?= sActive('profile') ?>">
            <i class="fas fa-user-circle"></i><span>My Profile</span>
        </a>
        <a href="<?= STUDENT_URL ?>change_password.php" class="nav-item <?= sActive('change_password') ?>">
            <i class="fas fa-lock"></i><span>Change Password</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= STUDENT_URL ?>logout.php" class="nav-item logout-btn">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>
<div class="main-content">
    <div class="topbar">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><?= $page_title ?? '' ?></div>
        <div class="topbar-right">
            <a href="<?= STUDENT_URL ?>notifications.php" class="notif-bell">
                <i class="fas fa-bell"></i>
                <?php if ($notif_cnt > 0): ?><span class="notif-dot"></span><?php endif; ?>
            </a>
            <div class="topbar-user">
                <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($full_name) ?></span>
                    <span class="user-role"><?= htmlspecialchars($student['course'].' · '.$student['year_level']) ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </div>
    <div class="page-content">
