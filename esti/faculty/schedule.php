<?php
require_once 'config.php';
requireFacultyLogin();
$page_title = 'My Schedule';

$faculty = currentFaculty($conn);
$fid     = $faculty['faculty_id'];

$stmt = $conn->prepare("
SELECT sc.*, s.subject_desc, s.units,
c.class_name, c.section, c.year_level, c.course
FROM schedules sc
JOIN subjects s ON s.subject_code = sc.subject_code
JOIN classes  c ON c.id = sc.class_id
WHERE sc.faculty_id = ?
ORDER BY sc.time_start
");
$stmt->bind_param('s', $fid);
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
$slot     = date('h:i A', strtotime($r['time_start'])) . ' – ' . date('h:i A', strtotime($r['time_end']));
$day_list = array_map('trim', explode(',', $r['day_of_week']));
foreach ($day_list as $day) {
$timetable[$slot][$day][] = $r; // array because a faculty could have 2 classes same time (edge case)
}
}

$today = date('l');

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>My Schedule</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Schedule</span></div>
</div>
<button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print Schedule</button>
</div>

<div class="card" style="margin-bottom:20px;padding:14px 20px;">
<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
<div><span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">Faculty</span><br><strong><?= htmlspecialchars($faculty['last_name'].', '.$faculty['first_name']) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">Department</span><br><strong><?= htmlspecialchars($faculty['dept_code']) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">Position</span><br><strong><?= htmlspecialchars($faculty['position'] ?? '—') ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">School Year</span><br><strong>2023–2024 · 1st Semester</strong></div>
<div style="margin-left:auto;">
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
<th style="<?= $day===$today ? 'background:var(--green-mid);' : '' ?>">
<?= $day ?><?= $day===$today ? ' <span style="font-size:9px;font-weight:400;">(Today)</span>' : '' ?>
</th>
<?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php if (empty($times)): ?>
<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--gray-600);">No schedule found.</td></tr>
<?php else:
foreach (array_keys($times) as $slot): ?>
<tr>
<td class="time-col"><?= $slot ?></td>
<?php foreach ($days as $day):
$cells   = $timetable[$slot][$day] ?? [];
$is_today = $day === $today;
?>
<td style="<?= $is_today ? 'background:#f0faf4;' : '' ?>">
<?php foreach ($cells as $cell): ?>
<div class="sched-cell" style="margin-bottom:3px;">
<div class="sc-subj"><?= htmlspecialchars($cell['subject_code']) ?></div>
<div style="font-size:10px;color:var(--green-text);font-weight:600;">
<?= htmlspecialchars($cell['class_name'].' '.$cell['section']) ?>
</div>
<div class="sc-room"><i class="fas fa-door-open" style="font-size:9px;"></i> <?= htmlspecialchars($cell['room'] ?? '—') ?></div>
<div class="sc-fac" style="font-size:9px;"><?= htmlspecialchars($cell['year_level']) ?></div>
</div>
<?php endforeach; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<div class="table-card">
<div style="padding:14px 16px;border-bottom:1px solid var(--gray-200);">
<div style="font-size:14px;font-weight:700;"><i class="fas fa-list" style="color:var(--green-main);"></i> Schedule List View</div>
</div>
<table>
<thead><tr>
<th>Subject</th><th>Class</th><th>Day(s)</th><th>Time</th><th>Room</th><th>Year Level</th><th>Units</th>
</tr></thead>
<tbody>
<?php if (empty($sched_rows)): ?>
<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--gray-600);">No schedule records found.</td></tr>
<?php else: foreach ($sched_rows as $r):
$day_list = array_map('trim', explode(',', $r['day_of_week']));
?>
<tr>
<td>
<strong><?= htmlspecialchars($r['subject_code']) ?></strong><br>
<span style="font-size:11px;color:var(--gray-600);"><?= htmlspecialchars($r['subject_desc']) ?></span>
</td>
<td><strong><?= htmlspecialchars($r['class_name'].' '.$r['section']) ?></strong></td>
<td>
<?php foreach ($day_list as $d):
$short = substr($d,0,3);
?>
<span class="day-badge day-<?= $short ?>"><?= $short ?></span>
<?php endforeach; ?>
</td>
<td><?= date('h:i A', strtotime($r['time_start'])) ?> – <?= date('h:i A', strtotime($r['time_end'])) ?></td>
<td><?= htmlspecialchars($r['room'] ?? '—') ?></td>
<td><?= htmlspecialchars($r['year_level']) ?></td>
<td style="text-align:center;"><?= $r['units'] ?></td>
</tr>
<?php endforeach; endif; ?>
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