<?php
require_once 'config.php';
requireStudentLogin();
$page_title = 'Enrolled Subjects';

$student = currentStudent($conn);
$sid     = $student['id_number'];

$stmt = $conn->prepare("
SELECT e.*, s.subject_desc, s.units, s.type,
g.final_grade, g.remarks, g.prelim, g.midterm, g.finals,
CONCAT(f.last_name,', ',f.first_name) faculty_name,
c.class_name, c.section, c.year_level
FROM enrollments e
JOIN subjects s  ON s.subject_code = e.subject_code
JOIN classes c   ON c.id = e.class_id
LEFT JOIN grades g ON g.student_id = e.student_id AND g.subject_code = e.subject_code
LEFT JOIN faculty f ON f.faculty_id = c.adviser_id
WHERE e.student_id = ?
ORDER BY e.school_year DESC, e.semester, s.subject_code
");
$stmt->bind_param('s', $sid);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_units = array_sum(array_column($subjects, 'units'));

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>Enrolled Subjects</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Subjects</span></div>
</div>
<div style="display:flex;gap:8px;align-items:center;">
<span style="font-size:13px;color:var(--gray-600);">Total Units: <strong style="color:var(--green-main)"><?= $total_units ?></strong></span>
</div>
</div>

<div class="student-stat-cards" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
<div class="stat-card">
<div class="stat-icon green"><i class="fas fa-book-open"></i></div>
<div><div class="stat-value"><?= count($subjects) ?></div><div class="stat-label">Total Subjects</div></div>
</div>
<div class="stat-card">
<div class="stat-icon blue"><i class="fas fa-layer-group"></i></div>
<div><div class="stat-value"><?= $total_units ?></div><div class="stat-label">Total Units</div></div>
</div>
<div class="stat-card">
<div class="stat-icon teal"><i class="fas fa-calendar-check"></i></div>
<div><div class="stat-value">2023–2024</div><div class="stat-label">School Year</div></div>
</div>
</div>

<?php if (empty($subjects)): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--gray-600);">
<i class="fas fa-book-open" style="font-size:40px;display:block;margin-bottom:12px;color:var(--gray-400)"></i>
<div style="font-size:15px;font-weight:600;">No enrolled subjects found.</div>
</div>
<?php else: ?>
<div class="subject-cards">
<?php foreach ($subjects as $s):
$fg = $s['final_grade'];
[$ltr, $clr] = $fg ? gradeLetter((float)$fg) : ['—', '#6c757d'];
$type_colors = ['Major'=>'#1d5c3a','GE'=>'#0c7490','PE'=>'#e07b00','Minor'=>'#6f42c1','Elective'=>'#6c757d'];
$type_clr = $type_colors[$s['type']] ?? '#6c757d';
?>
<div class="subject-card">
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
<span class="subject-card-code"><?= htmlspecialchars($s['subject_code']) ?></span>
<span style="font-size:10px;font-weight:700;color:<?= $type_clr ?>;background:<?= $type_clr ?>18;padding:2px 8px;border-radius:20px;"><?= htmlspecialchars($s['type']) ?></span>
</div>
<div class="subject-card-name"><?= htmlspecialchars($s['subject_desc']) ?></div>

<div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;font-size:11px;color:var(--gray-600);">
<div><i class="fas fa-layer-group" style="width:14px;"></i> <?= $s['units'] ?> Units</div>
<div><i class="fas fa-users" style="width:14px;"></i> <?= htmlspecialchars($s['class_name'].' '.$s['section']) ?> · <?= htmlspecialchars($s['year_level']) ?></div>
<div><i class="fas fa-user" style="width:14px;"></i> <?= htmlspecialchars($s['faculty_name'] ?? 'TBA') ?></div>
<div><i class="fas fa-calendar" style="width:14px;"></i> <?= htmlspecialchars($s['school_year']) ?> · <?= htmlspecialchars($s['semester']) ?></div>
</div>


<div style="border-top:1px solid var(--gray-200);padding-top:10px;">
<?php if ($fg !== null): ?>
<div style="display:flex;justify-content:space-between;align-items:center;">
<div>
<div style="font-size:10px;color:var(--gray-600);font-weight:700;text-transform:uppercase;">Final Grade</div>
<div style="font-size:22px;font-weight:900;color:<?= $clr ?>;line-height:1.1;"><?= number_format((float)$fg,1) ?></div>
<div style="font-size:10px;color:<?= $clr ?>;font-weight:700;"><?= $ltr ?></div>
</div>
<div style="text-align:right;font-size:11px;color:var(--gray-600);">
<div>Prelim: <strong><?= number_format((float)$s['prelim'],1) ?></strong></div>
<div>Midterm: <strong><?= number_format((float)$s['midterm'],1) ?></strong></div>
<div>Finals: <strong><?= number_format((float)$s['finals'],1) ?></strong></div>
</div>
</div>
<div class="grade-bar" style="margin-top:8px;">
<div class="grade-bar-fill" data-pct="<?= min(100,$fg) ?>" style="background:<?= $clr ?>;width:0;height:8px;"></div>
</div>
<?php else: ?>
<div style="text-align:center;color:var(--gray-600);font-size:12px;padding:6px 0;">
<i class="fas fa-hourglass-half"></i> Grade not yet posted
</div>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>