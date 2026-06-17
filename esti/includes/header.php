<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
function isActive($pages) {
    global $current_page;
    return in_array($current_page, (array)$pages) ? 'active' : '';
}
$admin_notif = 0; // admin has no notification system yet
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'Admin Portal' ?> | ESTI CGMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
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
    <div class="sidebar-label">ADMIN PORTAL</div>
    <!-- Admin profile block -->
    <div class="sidebar-profile">
        <div class="sp-avatar"><i class="fas fa-user-shield"></i></div>
        <div class="sp-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></div>
        <div class="sp-id"><?= htmlspecialchars($_SESSION['admin_role'] ?? 'Admin') ?></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>pages/admin/dashboard.php" class="nav-item <?= isActive('dashboard') ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>pages/admin/students.php" class="nav-item <?= isActive('students') ?>">
            <i class="fas fa-user-graduate"></i><span>Student</span>
        </a>
        <a href="<?= BASE_URL ?>pages/admin/subjects.php" class="nav-item <?= isActive('subjects') ?>">
            <i class="fas fa-book"></i><span>Subject</span>
        </a>
        <a href="<?= BASE_URL ?>pages/admin/classes.php" class="nav-item <?= isActive('classes') ?>">
            <i class="fas fa-users"></i><span>Class and Section</span>
        </a>
        <a href="<?= BASE_URL ?>pages/admin/departments.php" class="nav-item <?= isActive('departments') ?>">
            <i class="fas fa-building"></i><span>Department</span>
        </a>
        <a href="<?= BASE_URL ?>pages/admin/faculty.php" class="nav-item <?= isActive('faculty') ?>">
            <i class="fas fa-chalkboard-teacher"></i><span>Faculty</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>logout.php" class="nav-item logout-btn">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>
<div class="main-content">
    <div class="topbar">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <div class="topbar-title"><?= $page_title ?? '' ?></div>
        <div class="topbar-right">
            <div class="topbar-user">
                <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['admin_role'] ?? 'Admin') ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </div>
    <div class="page-content">
