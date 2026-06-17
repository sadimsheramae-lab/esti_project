<?php
require_once 'config.php';
requireStudentLogin();
$page_title = 'Dashboard';

$student = currentStudent($conn);
$sid     = $student['id_number'];

$enrolled_subjects = $conn->prepare("SELECT COUNT(*) c FROM enrollments WHERE student_id=?");
$enrolled_subjects->bind_param('s', $sid);
$enrolled_subjects->execute();
$subj_count = (int)$enrolled_subjects->get_result()->fetch_assoc()['c'];

$grades_res = $conn->prepare("SELECT final_grade FROM grades WHERE student_id=? AND final_grade IS NOT NULL");
$grades_res->bind_param('s', $sid);
$grades_res->execute();
$grades_data = $grades_res->get_result();
$grade_vals  = [];
while ($g = $grades_data->fetch_assoc()) $grade_vals[] = (float)$g['final_grade'];
$gpa    = count($grade_vals) ? round(array_sum($grade_vals) / count($grade_vals), 2) : 0;
$passed = count(array_filter($grade_vals, fn($g) => $g >= 75));
$failed = count(array_filter($grade_vals, fn($g) => $g < 75));

$recent_grades = $conn->prepare("
SELECT g.*, s.subject_desc, s.units
FROM grades g
JOIN subjects s ON s.subject_code = g.subject_code
WHERE g.student_id = ?
ORDER BY g.created_at DESC LIMIT 5
");
$recent_grades->bind_param('s', $sid);
$recent_grades->execute();
$recent_grades = $recent_grades->get_result();

$today = date('l'); // e.g. "Monday"
$today_sched = $conn->prepare("
SELECT sc.*, s.subject_desc, s.subject_code,
CONCAT(f.last_name,', ',f.first_name) faculty_name
FROM schedules sc
JOIN subjects s ON s.subject_code = sc.subject_code
LEFT JOIN faculty f ON f.faculty_id = sc.faculty_id
JOIN classes c ON c.id = sc.class_id
JOIN enrollments e ON e.class_id = c.id AND e.student_id = ?
WHERE FIND_IN_SET(?, sc.day_of_week)
ORDER BY sc.time_start
");
$today_sched->bind_param('ss', $sid, $today);
$today_sched->execute();
$today_sched = $today_sched->get_result();

$notifs = $conn->prepare("SELECT * FROM notifications WHERE student_id=? ORDER BY created_at DESC LIMIT 3");
$notifs->bind_param('s', $sid);
$notifs->execute();
$notifs = $notifs->get_result();

[$gpa_letter, $gpa_color] = gradeLetter($gpa);

require_once 'header.php';
?>

<div class="gpa-hero">
<div class="gpa-hero-left">
<h2>General Weighted Average</h2>
<div class="gpa-val"><?= number_format($gpa, 2) ?></div>
<div class="gpa-label" style="margin-top:6px;">
<span style="background:rgba(255,255,255,.2);padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">
<?= $gpa_letter ?>
</span>
&nbsp; S.Y. 2023–2024 · 1st Semester
</div>
</div>
<div class="gpa-hero-right">
<div class="gpa-stat">
<div class="gpa-stat-val"><?= $subj_count ?></div>
<div class="gpa-stat-lbl">Enrolled<br>Subjects</div>
</div>
<div class="gpa-stat">
<div class="gpa-stat-val"><?= $passed ?></div>
<div class="gpa-stat-lbl">Subjects<br>Passed</div>
</div>
<div class="gpa-stat">
<div class="gpa-stat-val" style="color:<?= $failed > 0 ? '#ff8585' : 'inherit' ?>"><?= $failed ?></div>
<div class="gpa-stat-lbl">Subjects<br>Failed</div>
</div>
<div class="gpa-stat">
<div class="gpa-stat-val"><?= $student['year_level'][0] ?></div>
<div class="gpa-stat-lbl">Year<br>Level</div>
</div>
</div>
</div>

<div class="student-stat-cards">
<div class="stat-card">
<div class="stat-icon green"><i class="fas fa-book-open"></i></div>
<div><div class="stat-value"><?= $subj_count ?></div><div class="stat-label">Enrolled Subjects</div></div>
</div>
<div class="stat-card">
<div class="stat-icon blue"><i class="fas fa-star"></i></div>
<div><div class="stat-value"><?= number_format($gpa, 1) ?></div><div class="stat-label">Current GWA</div></div>
</div>
<div class="stat-card">
<div class="stat-icon teal"><i class="fas fa-check-circle"></i></div>
<div><div class="stat-value"><?= $passed ?></div><div class="stat-label">Passed Subjects</div></div>
</div>
<div class="stat-card">
<div class="stat-icon <?= $failed > 0 ? 'orange' : 'purple' ?>"><i class="fas fa-times-circle"></i></div>
<div><div class="stat-value"><?= $failed ?></div><div class="stat-label">Failed Subjects</div></div>
</div>
</div>

<div class="charts-row" style="grid-template-columns: 1.3fr 1fr;">


<div class="card">
<div class="card-title"><i class="fas fa-star-half-alt"></i> Recent Grades
<a href="<?= STUDENT_URL ?>grades.php" style="margin-left:auto;font-size:12px;color:var(--green-main);text-decoration:none;font-weight:600;">View All →</a>
</div>
<table style="font-size:12px;width:100%;border-collapse:collapse;">
<thead><tr style="background:var(--green-bg);">
<th style="padding:8px 10px;text-align:left;font-size:11px;color:var(--green-text);">Subject</th>
<th style="padding:8px 10px;text-align:center;font-size:11px;color:var(--green-text);">Prelim</th>
<th style="padding:8px 10px;text-align:center;font-size:11px;color:var(--green-text);">Midterm</th>
<th style="padding:8px 10px;text-align:center;font-size:11px;color:var(--green-text);">Finals</th>
<th style="padding:8px 10px;text-align:center;font-size:11px;color:var(--green-text);">Final</th>
</tr></thead>
<tbody>
<?php while ($g = $recent_grades->fetch_assoc()):
[$ltr, $clr] = gradeLetter((float)$g['final_grade']);
?>
<tr style="border-bottom:1px solid var(--gray-200);">
<td style="padding:9px 10px;">
<div style="font-weight:600;"><?= htmlspecialchars($g['subject_code']) ?></div>
<div style="font-size:10px;color:var(--gray-600);"><?= htmlspecialchars($g['subject_desc']) ?></div>
</td>
<td style="padding:9px 10px;text-align:center;"><?= $g['prelim'] ?? '—' ?></td>
<td style="padding:9px 10px;text-align:center;"><?= $g['midterm'] ?? '—' ?></td>
<td style="padding:9px 10px;text-align:center;"><?= $g['finals'] ?? '—' ?></td>
<td style="padding:9px 10px;text-align:center;">
<span style="font-weight:800;color:<?= $clr ?>"><?= number_format($g['final_grade'],1) ?></span>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>


<div style="display:flex;flex-direction:column;gap:16px;">


<div class="card">
<div class="card-title"><i class="fas fa-calendar-day"></i> Today's Classes
<span style="margin-left:auto;font-size:11px;color:var(--gray-600);"><?= date('l, F j') ?></span>
</div>
<?php
$today_rows = [];
while ($ts = $today_sched->fetch_assoc()) $today_rows[] = $ts;
if (empty($today_rows)): ?>
<div style="text-align:center;padding:20px;color:var(--gray-600);font-size:13px;">
<i class="fas fa-coffee" style="font-size:24px;display:block;margin-bottom:6px;"></i>
No classes today
</div>
<?php else:
foreach ($today_rows as $ts): ?>
<div style="display:flex;gap:12px;align-items:flex-start;padding:8px 0;border-bottom:1px solid var(--gray-200);">
<div style="text-align:center;min-width:52px;">
<div style="font-size:11px;font-weight:700;color:var(--green-main);"><?= date('h:i A', strtotime($ts['time_start'])) ?></div>
<div style="font-size:10px;color:var(--gray-600);"><?= date('h:i A', strtotime($ts['time_end'])) ?></div>
</div>
<div style="flex:1;">
<div style="font-weight:700;font-size:12px;"><?= htmlspecialchars($ts['subject_code']) ?></div>
<div style="font-size:11px;color:var(--gray-600);"><?= htmlspecialchars($ts['subject_desc']) ?></div>
<div style="font-size:10px;color:var(--gray-600);margin-top:2px;">
<i class="fas fa-door-open"></i> <?= htmlspecialchars($ts['room'] ?? '—') ?>
&nbsp;·&nbsp;<i class="fas fa-user"></i> <?= htmlspecialchars($ts['faculty_name'] ?? '—') ?>
</div>
</div>
</div>
<?php endforeach; endif; ?>
</div>


<div class="card">
<div class="card-title"><i class="fas fa-bell"></i> Notifications
<a href="<?= STUDENT_URL ?>notifications.php" style="margin-left:auto;font-size:12px;color:var(--green-main);text-decoration:none;font-weight:600;">View All →</a>
</div>
<?php while ($n = $notifs->fetch_assoc()):
$icon_map = ['grade'=>'fas fa-star','announcement'=>'fas fa-bullhorn','general'=>'fas fa-info-circle'];
$cls_map  = ['grade'=>'grade','announcement'=>'announce','general'=>'general'];
$ico = $icon_map[$n['type']] ?? 'fas fa-bell';
$cls = $cls_map[$n['type']] ?? 'general';
?>
<div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" data-id="<?= $n['id'] ?>">
<div class="notif-icon <?= $cls ?>"><i class="<?= $ico ?>"></i></div>
<div class="notif-body">
<div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
<div class="notif-msg"><?= htmlspecialchars(substr($n['message'],0,60)).'...' ?></div>
</div>
<?php if (!$n['is_read']): ?><div class="unread-dot"></div><?php endif; ?>
</div>
<?php endwhile; ?>
</div>

</div>
</div>

<?php require_once 'footer.php'; ?>