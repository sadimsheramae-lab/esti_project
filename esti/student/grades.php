<?php
require_once 'config.php';
requireStudentLogin();
$page_title = 'My Grades';

$student = currentStudent($conn);
$sid     = $student['id_number'];

$stmt = $conn->prepare("
SELECT g.*, s.subject_desc, s.units, s.type,
CONCAT(f.last_name,', ',f.first_name) faculty_name
FROM grades g
JOIN subjects s ON s.subject_code = g.subject_code
LEFT JOIN faculty f ON f.faculty_id = g.faculty_id
WHERE g.student_id = ?
ORDER BY g.school_year DESC, g.semester, g.subject_code
");
$stmt->bind_param('s', $sid);
$stmt->execute();
$all_grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grouped = [];
foreach ($all_grades as $g) {
$key = $g['school_year'] . '|' . $g['semester'];
$grouped[$key][] = $g;
}

function computeGWA($grades) {
$total_units = 0; $weighted = 0;
foreach ($grades as $g) {
if ($g['final_grade'] !== null) {
$total_units += $g['units'];
$weighted    += $g['final_grade'] * $g['units'];
}
}
return $total_units > 0 ? round($weighted / $total_units, 2) : 0;
}

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>My Grades</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Grades</span></div>
</div>
<button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Grade Sheet</button>
</div>

<div class="card" style="margin-bottom:20px;padding:16px 20px;">
<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
<div><span style="font-size:11px;color:var(--gray-600);text-transform:uppercase;font-weight:700;">Student ID</span><br><strong><?= htmlspecialchars($student['id_number']) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);text-transform:uppercase;font-weight:700;">Name</span><br><strong><?= htmlspecialchars($student['last_name'].', '.$student['first_name'].' '.($student['middle_name']??'')) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);text-transform:uppercase;font-weight:700;">Course</span><br><strong><?= htmlspecialchars($student['course']) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);text-transform:uppercase;font-weight:700;">Year Level</span><br><strong><?= htmlspecialchars($student['year_level']) ?></strong></div>
<div><span style="font-size:11px;color:var(--gray-600);text-transform:uppercase;font-weight:700;">Status</span><br><span class="badge badge-<?= strtolower($student['status']) ?>"><?= $student['status'] ?></span></div>
</div>
</div>

<div class="sem-tabs">
<span class="sem-tab active" data-sem="all">All Semesters</span>
<?php foreach (array_keys($grouped) as $key):
[$yr, $sem] = explode('|', $key); ?>
<span class="sem-tab" data-sem="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($yr.' · '.$sem) ?></span>
<?php endforeach; ?>
</div>

<?php foreach ($grouped as $key => $grades):
[$yr, $sem] = explode('|', $key);
$gwa = computeGWA($grades);
[$gwa_ltr, $gwa_clr] = gradeLetter($gwa);
$total_units = array_sum(array_column($grades, 'units'));
?>
<div class="sem-section table-card" data-sem="<?= htmlspecialchars($key) ?>" style="margin-bottom:22px;">


<div style="padding:14px 18px;background:var(--green-dark);border-radius:var(--radius) var(--radius) 0 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
<div>
<div style="color:#fff;font-size:14px;font-weight:800;"><?= htmlspecialchars($sem) ?></div>
<div style="color:rgba(255,255,255,.65);font-size:12px;">School Year <?= htmlspecialchars($yr) ?></div>
</div>
<div style="display:flex;gap:24px;">
<div style="text-align:center;">
<div style="color:#fff;font-size:20px;font-weight:800;"><?= number_format($gwa,2) ?></div>
<div style="color:rgba(255,255,255,.65);font-size:10px;">GWA</div>
</div>
<div style="text-align:center;">
<div style="color:#fff;font-size:20px;font-weight:800;"><?= $total_units ?></div>
<div style="color:rgba(255,255,255,.65);font-size:10px;">Total Units</div>
</div>
<div style="text-align:center;">
<div style="font-size:13px;font-weight:800;color:<?= $gwa_clr === '#1d5c3a' ? '#90e8b4' : '#fff' ?>;background:rgba(255,255,255,.15);padding:4px 12px;border-radius:20px;"><?= $gwa_ltr ?></div>
<div style="color:rgba(255,255,255,.65);font-size:10px;margin-top:2px;">Rating</div>
</div>
</div>
</div>

<table>
<thead>
<tr>
<th>Subject Code</th>
<th>Description</th>
<th style="text-align:center;">Units</th>
<th style="text-align:center;">Prelim</th>
<th style="text-align:center;">Midterm</th>
<th style="text-align:center;">Finals</th>
<th style="text-align:center;">Final Grade</th>
<th style="text-align:center;">Remarks</th>
</tr>
</thead>
<tbody>
<?php foreach ($grades as $g):
$fg = (float)($g['final_grade'] ?? 0);
[$ltr, $clr] = gradeLetter($fg);
$bar_pct = min(100, $fg);
?>
<tr>
<td><strong><?= htmlspecialchars($g['subject_code']) ?></strong></td>
<td>
<?= htmlspecialchars($g['subject_desc']) ?>
<?php if ($g['faculty_name']): ?>
<div style="font-size:10px;color:var(--gray-600);margin-top:2px;"><i class="fas fa-user"></i> <?= htmlspecialchars($g['faculty_name']) ?></div>
<?php endif; ?>
</td>
<td style="text-align:center;"><?= $g['units'] ?></td>
<td style="text-align:center;"><?= $g['prelim']  !== null ? number_format($g['prelim'],1)  : '<span style="color:#ccc">—</span>' ?></td>
<td style="text-align:center;"><?= $g['midterm'] !== null ? number_format($g['midterm'],1) : '<span style="color:#ccc">—</span>' ?></td>
<td style="text-align:center;"><?= $g['finals']  !== null ? number_format($g['finals'],1)  : '<span style="color:#ccc">—</span>' ?></td>
<td style="text-align:center;">
<div style="display:flex;align-items:center;justify-content:center;gap:8px;">
<div class="grade-bar-wrap">
<div class="grade-bar"><div class="grade-bar-fill" data-pct="<?= $bar_pct ?>" style="background:<?= $clr ?>;width:0"></div></div>
</div>
<span style="font-size:15px;font-weight:800;color:<?= $clr ?>"><?= $fg > 0 ? number_format($fg,1) : '—' ?></span>
</div>
</td>
<td style="text-align:center;">
<?php
$rem = $g['remarks'] ?? ($fg >= 75 ? 'Passed' : ($fg > 0 ? 'Failed' : '—'));
$rem_cls = strtolower($rem) === 'passed' ? 'badge-active' : ($rem === '—' ? '' : 'badge-inactive');
?>
<span class="badge <?= $rem_cls ?>"><?= htmlspecialchars($rem) ?></span>
</td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr style="background:var(--green-bg);">
<td colspan="2" style="padding:10px 14px;font-weight:700;color:var(--green-text);">SEMESTER TOTAL / GWA</td>
<td style="text-align:center;font-weight:700;padding:10px 14px;color:var(--green-text);"><?= $total_units ?></td>
<td colspan="3"></td>
<td style="text-align:center;font-weight:800;font-size:16px;color:<?= $gwa_clr ?>;padding:10px 14px;"><?= number_format($gwa,2) ?></td>
<td style="text-align:center;padding:10px 14px;"><span class="badge badge-active"><?= $gwa_ltr ?></span></td>
</tr>
</tfoot>
</table>
</div>
<?php endforeach; ?>

<?php if (empty($grouped)): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--gray-600);">
<i class="fas fa-star-half-alt" style="font-size:40px;display:block;margin-bottom:12px;color:var(--gray-400)"></i>
<div style="font-size:15px;font-weight:600;">No grades posted yet.</div>
<div style="font-size:13px;margin-top:4px;">Your grades will appear here once posted by your instructor.</div>
</div>
<?php endif; ?>

<style>
@media print {
.sidebar, .topbar, .page-header .btn, .sem-tabs, .page-footer { display: none !important; }
.main-content { margin-left: 0 !important; }
.table-card { box-shadow: none !important; border: 1px solid #ccc; }
body { background: #fff !important; }
}
</style>

<?php require_once 'footer.php'; ?>