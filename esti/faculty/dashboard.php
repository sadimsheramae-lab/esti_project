<?php
require_once 'config.php';
requireFacultyLogin();
$page_title = 'Dashboard';

$faculty = currentFaculty($conn);
$fid     = $faculty['faculty_id'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT class_id) c FROM faculty_assignments WHERE faculty_id=?");
$stmt->bind_param('s', $fid); $stmt->execute();
$total_classes = (int)$stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT subject_code) c FROM faculty_assignments WHERE faculty_id=?");
$stmt->bind_param('s', $fid); $stmt->execute();
$total_subjects = (int)$stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("
SELECT COUNT(DISTINCT e.student_id) c
FROM enrollments e
JOIN faculty_assignments fa ON fa.class_id = e.class_id AND fa.subject_code = e.subject_code
WHERE fa.faculty_id = ?
");
$stmt->bind_param('s', $fid); $stmt->execute();
$total_students = (int)$stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) c FROM grades WHERE faculty_id=? AND final_grade IS NOT NULL");
$stmt->bind_param('s', $fid); $stmt->execute();
$grades_posted = (int)$stmt->get_result()->fetch_assoc()['c'];

$stmt = $conn->prepare("
SELECT fa.*, s.subject_desc, s.units, c.class_name, c.section, c.year_level,
(SELECT COUNT(DISTINCT e.student_id) FROM enrollments e WHERE e.class_id=fa.class_id AND e.subject_code=fa.subject_code) student_count
FROM faculty_assignments fa
JOIN subjects s ON s.subject_code = fa.subject_code
JOIN classes  c ON c.id = fa.class_id
WHERE fa.faculty_id = ?
ORDER BY c.class_name, c.section
");
$stmt->bind_param('s', $fid); $stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$today = date('l');
$stmt  = $conn->prepare("
SELECT sc.*, s.subject_desc, c.class_name, c.section
FROM schedules sc
JOIN subjects s ON s.subject_code = sc.subject_code
JOIN classes  c ON c.id = sc.class_id
WHERE sc.faculty_id = ? AND FIND_IN_SET(?, sc.day_of_week)
ORDER BY sc.time_start
");
$stmt->bind_param('ss', $fid, $today); $stmt->execute();
$today_sched = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM faculty_notifications WHERE faculty_id=? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param('s', $fid); $stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'header.php';
?>

<div class="faculty-hero">
<div class="faculty-hero-avatar">
<?php if (!empty($faculty['profile_pic'])): ?>
<img src="<?= BASE_URL . htmlspecialchars($faculty['profile_pic']) ?>" alt="">
<?php else: ?>
<i class="fas fa-chalkboard-teacher"></i>
<?php endif; ?>
</div>
<div class="faculty-hero-info">
<h2>Welcome, <?= htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']) ?></h2>
<p><i class="fas fa-building"></i> <?= htmlspecialchars($faculty['dept_name'] ?? $faculty['dept_code']) ?>
&nbsp;·&nbsp;<i class="fas fa-user-tie"></i> <?= htmlspecialchars($faculty['position'] ?? 'Instructor') ?>
&nbsp;·&nbsp;<i class="fas fa-calendar"></i> S.Y. 2023–2024 · 1st Semester
</p>
</div>
<div class="faculty-hero-stats">
<div class="fh-stat"><div class="fh-stat-val"><?= $total_classes ?></div><div class="fh-stat-lbl">Classes</div></div>
<div class="fh-stat"><div class="fh-stat-val"><?= $total_subjects ?></div><div class="fh-stat-lbl">Subjects</div></div>
<div class="fh-stat"><div class="fh-stat-val"><?= $total_students ?></div><div class="fh-stat-lbl">Students</div></div>
<div class="fh-stat"><div class="fh-stat-val"><?= $grades_posted ?></div><div class="fh-stat-lbl">Grades Posted</div></div>
</div>
</div>

<div class="faculty-stat-cards">
<div class="stat-card">
<div class="stat-icon purple"><i class="fas fa-users"></i></div>
<div><div class="stat-value"><?= $total_classes ?></div><div class="stat-label">My Classes</div></div>
</div>
<div class="stat-card">
<div class="stat-icon blue"><i class="fas fa-book-open"></i></div>
<div><div class="stat-value"><?= $total_subjects ?></div><div class="stat-label">Subjects Handled</div></div>
</div>
<div class="stat-card">
<div class="stat-icon green"><i class="fas fa-user-graduate"></i></div>
<div><div class="stat-value"><?= $total_students ?></div><div class="stat-label">Total Students</div></div>
</div>
<div class="stat-card">
<div class="stat-icon orange"><i class="fas fa-star"></i></div>
<div><div class="stat-value"><?= $grades_posted ?></div><div class="stat-label">Grades Posted</div></div>
</div>
</div>

<div class="charts-row" style="grid-template-columns: 1.4fr 1fr;">


<div class="card">
<div class="card-title"><i class="fas fa-chalkboard"></i> My Classes & Subjects
<a href="<?= FACULTY_URL ?>my_classes.php" style="margin-left:auto;font-size:12px;color:var(--green-main);text-decoration:none;font-weight:600;">View All →</a>
</div>
<table style="font-size:12px;width:100%;border-collapse:collapse;">
<thead><tr style="background:var(--green-bg);">
<th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--green-text);">Subject</th>
<th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--green-text);">Class</th>
<th style="padding:8px 10px;text-align:center;font-size:11px;color:var(--green-text);">Students</th>
<th style="padding:8px 10px;text-align:center;font-size:11px;color:var(--green-text);">Action</th>
</tr></thead>
<tbody>
<?php foreach ($assignments as $a): ?>
<tr style="border-bottom:1px solid var(--gray-200);">
<td style="padding:9px 10px;">
<div style="font-weight:700;"><?= htmlspecialchars($a['subject_code']) ?></div>
<div style="font-size:10px;color:var(--gray-600);"><?= htmlspecialchars($a['subject_desc']) ?></div>
</td>
<td style="padding:9px 10px;">
<strong><?= htmlspecialchars($a['class_name'].' '.$a['section']) ?></strong>
<div style="font-size:10px;color:var(--gray-600);"><?= htmlspecialchars($a['year_level']) ?></div>
</td>
<td style="padding:9px 10px;text-align:center;"><strong><?= $a['student_count'] ?></strong></td>
<td style="padding:9px 10px;text-align:center;">
<a href="<?= FACULTY_URL ?>grade_entry.php?class=<?= $a['class_id'] ?>&subj=<?= urlencode($a['subject_code']) ?>"
class="btn btn-primary btn-sm"><i class="fas fa-star"></i> Grades</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>


<div style="display:flex;flex-direction:column;gap:16px;">


<div class="card">
<div class="card-title"><i class="fas fa-calendar-day"></i> Today's Classes
<span style="margin-left:auto;font-size:11px;color:var(--gray-600);"><?= date('l, F j') ?></span>
</div>
<?php if (empty($today_sched)): ?>
<div style="text-align:center;padding:18px;color:var(--gray-600);font-size:13px;">
<i class="fas fa-coffee" style="font-size:22px;display:block;margin-bottom:6px;"></i>No classes today
</div>
<?php else: foreach ($today_sched as $ts): ?>
<div style="display:flex;gap:10px;align-items:flex-start;padding:8px 0;border-bottom:1px solid var(--gray-200);">
<div style="text-align:center;min-width:52px;">
<div style="font-size:11px;font-weight:700;color:var(--green-main);"><?= date('h:i A', strtotime($ts['time_start'])) ?></div>
<div style="font-size:10px;color:var(--gray-600);"><?= date('h:i A', strtotime($ts['time_end'])) ?></div>
</div>
<div>
<div style="font-weight:700;font-size:12px;"><?= htmlspecialchars($ts['subject_code']) ?> — <?= htmlspecialchars($ts['class_name'].' '.$ts['section']) ?></div>
<div style="font-size:11px;color:var(--gray-600);"><?= htmlspecialchars($ts['subject_desc']) ?></div>
<div style="font-size:10px;color:var(--gray-600);"><i class="fas fa-door-open"></i> <?= htmlspecialchars($ts['room'] ?? '—') ?></div>
</div>
</div>
<?php endforeach; endif; ?>
</div>


<div class="card">
<div class="card-title"><i class="fas fa-bell"></i> Notifications
<a href="<?= FACULTY_URL ?>notifications.php" style="margin-left:auto;font-size:12px;color:var(--green-main);text-decoration:none;font-weight:600;">View All →</a>
</div>
<?php
$icon_map = ['grade'=>'fas fa-star','announcement'=>'fas fa-bullhorn','general'=>'fas fa-info-circle','system'=>'fas fa-cog'];
$cls_map  = ['grade'=>'grade','announcement'=>'announce','general'=>'general','system'=>'system'];
foreach ($notifs as $n):
$ico = $icon_map[$n['type']] ?? 'fas fa-bell';
$cls = $cls_map[$n['type']] ?? 'general';
?>
<div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>" data-id="<?= $n['id'] ?>">
<div class="notif-icon <?= $cls ?>"><i class="<?= $ico ?>"></i></div>
<div class="notif-body">
<div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
<div class="notif-msg"><?= htmlspecialchars(substr($n['message'], 0, 55)) ?>...</div>
</div>
<?php if (!$n['is_read']): ?><div class="unread-dot"></div><?php endif; ?>
</div>
<?php endforeach; ?>
</div>

</div>
</div>

<?php require_once 'footer.php'; ?>