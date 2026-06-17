<?php
require_once 'config.php';
requireStudentLogin();
$page_title = 'Class Schedule';

$student = currentStudent($conn);
$sid     = $student['id_number'];

$stmt = $conn->prepare("
SELECT sc.*, s.subject_desc, s.units, s.type,
CONCAT(f.last_name,', ',f.first_name) faculty_name,
c.class_name, c.section
FROM schedules sc
JOIN subjects s  ON s.subject_code = sc.subject_code
LEFT JOIN faculty f ON f.faculty_id = sc.faculty_id
JOIN classes c   ON c.id = sc.class_id
JOIN enrollments e ON e.class_id = c.id AND e.student_id = ?
ORDER BY sc.time_start
");
$stmt->bind_param('s', $sid);
$stmt->execute();
$sched_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$days  = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$times = [];
foreach ($sched_rows as $r) {
$slot = date('h:i A', strtotime($r['time_start'])) . ' – ' . date('h:i A', strtotime($r['time_end']));
$times[$slot] = true;
}
ksort($times);

$timetable = [];
foreach ($sched_rows as $r) {
$slot = date('h:i A', strtotime($r['time_start'])) . ' – ' . date('h:i A', strtotime($r['time_end']));
$day_list = array_map('trim', explode(',', $r['day_of_week']));
foreach ($day_list as $day) {
$timetable[$slot][$day] = $r;
}
}

$today = date('l');

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>Class Schedule</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Schedule</span></div>
</div>
<button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Schedule</button>
</div>

<div class="card" style="margin-bottom:20px;padding:14px 20px;">
<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
<div><span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">Student</span><br><strong><?= htmlspecialchars($student['last_name'].', '.$student['first_name']) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">Course & Year</span><br><strong><?= htmlspecialchars($student['course'].' – '.$student['year_level']) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">School Year</span><br><strong>2023–2024 · 1st Semester</strong></div>
<div style="margin-left:auto;text-align:right;">
<span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">Today</span><br>
<strong style="color:var(--green-main);"><?= date('l, F j, Y') ?></strong>
</div>
</div>
</div>

<div class="table-card" style="overflow-x:auto;margin-bottom:22px;">
<table class="schedule-grid">
<thead>
<tr>
<th style="width:90px;">Time</th>
<?php foreach ($days as $day): ?>
<th style="<?= $day === $today ? 'background:var(--green-mid);' : '' ?>">
<?= $day ?><?= $day === $today ? ' <span style="font-size:9px;font-weight:400;">(Today)</span>' : '' ?>
</th>
<?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php if (empty($times)): ?>
<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray-600);">No schedule available.</td></tr>
<?php else:
foreach (array_keys($times) as $slot): ?>
<tr>
<td class="time-col"><?= $slot ?></td>
<?php foreach ($days as $day):
$cell = $timetable[$slot][$day] ?? null;
$is_today = $day === $today;
?>
<td style="<?= $is_today ? 'background:#f0faf4;' : '' ?>">
<?php if ($cell): ?>
<div class="sched-cell">
<div class="sc-subj"><?= htmlspecialchars($cell['subject_code']) ?></div>
<div style="font-size:10px;color:var(--green-text);font-weight:600;margin-top:1px;"><?= htmlspecialchars(substr($cell['subject_desc'],0,22)).( strlen($cell['subject_desc'])>22 ? '…':'') ?></div>
<div class="sc-room"><i class="fas fa-door-open" style="font-size:9px;"></i> <?= htmlspecialchars($cell['room'] ?? '—') ?></div>
<div class="sc-fac"><i class="fas fa-user" style="font-size:9px;"></i> <?= htmlspecialchars($cell['faculty_name'] ?? '—') ?></div>
</div>
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<div class="card">
<div class="card-title"><i class="fas fa-list"></i> Schedule List View</div>
<table>
<thead><tr>
<th>Subject</th><th>Description</th><th>Day(s)</th><th>Time</th><th>Room</th><th>Instructor</th><th>Units</th>
</tr></thead>
<tbody>
<?php foreach ($sched_rows as $r): ?>
<tr>
<td><strong><?= htmlspecialchars($r['subject_code']) ?></strong></td>
<td><?= htmlspecialchars($r['subject_desc']) ?></td>
<td><?= htmlspecialchars($r['day_of_week']) ?></td>
<td><?= date('h:i A', strtotime($r['time_start'])) ?> – <?= date('h:i A', strtotime($r['time_end'])) ?></td>
<td><?= htmlspecialchars($r['room'] ?? '—') ?></td>
<td><?= htmlspecialchars($r['faculty_name'] ?? '—') ?></td>
<td style="text-align:center;"><?= $r['units'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<style>
@media print {
.sidebar,.topbar,.page-header .btn,.page-footer { display:none !important; }
.main-content { margin-left:0 !important; }
body { background:#fff !important; }
}
</style>

<?php require_once 'footer.php'; ?>