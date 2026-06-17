<?php
require_once 'config.php';
requireFacultyLogin();
$page_title = 'My Classes';

$faculty = currentFaculty($conn);
$fid     = $faculty['faculty_id'];

$stmt = $conn->prepare("
SELECT fa.*, s.subject_desc, s.units, s.type,
c.class_name, c.section, c.year_level, c.course,
(SELECT COUNT(DISTINCT e.student_id) FROM enrollments e
WHERE e.class_id=fa.class_id AND e.subject_code=fa.subject_code) student_count,
(SELECT COUNT(*) FROM grades g
WHERE g.faculty_id=fa.faculty_id AND g.subject_code=fa.subject_code
AND g.class_id=fa.class_id AND g.final_grade IS NOT NULL) grades_posted
FROM faculty_assignments fa
JOIN subjects s ON s.subject_code = fa.subject_code
JOIN classes  c ON c.id = fa.class_id
WHERE fa.faculty_id = ?
ORDER BY fa.school_year DESC, fa.semester, c.class_name, c.section
");
$stmt->bind_param('s', $fid);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>My Classes</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>My Classes</span></div>
</div>
<span style="font-size:13px;color:var(--gray-600);">S.Y. 2023–2024 · 1st Semester</span>
</div>

<?php if (empty($assignments)): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--gray-600);">
<i class="fas fa-chalkboard" style="font-size:40px;display:block;margin-bottom:12px;color:var(--gray-400);"></i>
<div style="font-size:15px;font-weight:600;">No class assignments found.</div>
</div>
<?php else: ?>
<div class="class-cards">
<?php
$type_colors = ['Major'=>'#1d5c3a','GE'=>'#0c7490','PE'=>'#e07b00','Minor'=>'#6f42c1','Elective'=>'#6c757d'];
foreach ($assignments as $a):
$pct_graded = $a['student_count'] > 0 ? round($a['grades_posted'] / $a['student_count'] * 100) : 0;
$tc = $type_colors[$a['type']] ?? '#6c757d';
?>
<div class="class-card">
<div class="class-card-header">
<div style="display:flex;justify-content:space-between;align-items:flex-start;">
<div>
<div class="cc-name"><?= htmlspecialchars($a['class_name'].' '.$a['section']) ?></div>
<div class="cc-sub"><?= htmlspecialchars($a['year_level']) ?> · <?= htmlspecialchars($a['course']) ?></div>
</div>
<span style="font-size:10px;font-weight:700;background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;color:#fff;"><?= htmlspecialchars($a['type']) ?></span>
</div>
</div>
<div class="class-card-body">
<div style="font-size:15px;font-weight:800;margin-bottom:6px;color:var(--green-dark);"><?= htmlspecialchars($a['subject_code']) ?></div>
<div style="font-size:13px;font-weight:500;margin-bottom:12px;color:var(--gray-800);"><?= htmlspecialchars($a['subject_desc']) ?></div>
<div class="class-card-meta">
<div><i class="fas fa-layer-group"></i> <?= $a['units'] ?> Units</div>
<div><i class="fas fa-user-graduate"></i> <?= $a['student_count'] ?> Students Enrolled</div>
<div><i class="fas fa-calendar"></i> <?= htmlspecialchars($a['school_year'].' · '.$a['semester']) ?></div>
</div>


<div style="margin-top:4px;">
<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--gray-600);margin-bottom:4px;">
<span><i class="fas fa-star"></i> Grades Posted</span>
<span><strong><?= $a['grades_posted'] ?></strong> / <?= $a['student_count'] ?> (<?= $pct_graded ?>%)</span>
</div>
<div style="height:6px;background:var(--gray-200);border-radius:3px;overflow:hidden;">
<div style="height:100%;width:<?= $pct_graded ?>%;background:<?= $pct_graded==100 ? 'var(--green-btn)' : '#f5a623' ?>;border-radius:3px;transition:width .4s;"></div>
</div>
</div>
</div>
<div class="class-card-footer">
<a href="<?= FACULTY_URL ?>students.php?class=<?= $a['class_id'] ?>&subj=<?= urlencode($a['subject_code']) ?>"
class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
<i class="fas fa-user-graduate"></i> Students
</a>
<a href="<?= FACULTY_URL ?>grade_entry.php?class=<?= $a['class_id'] ?>&subj=<?= urlencode($a['subject_code']) ?>"
class="btn btn-primary btn-sm" style="flex:1;justify-content:center;">
<i class="fas fa-star-half-alt"></i> Grades
</a>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>