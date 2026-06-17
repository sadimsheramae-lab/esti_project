<?php
require_once 'config.php';
requireFacultyLogin();
$page_title = 'Grade Entry';

$faculty = currentFaculty($conn);
$fid     = $faculty['faculty_id'];

$sel_class = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$sel_subj  = trim($_GET['subj'] ?? '');

$stmt = $conn->prepare("
SELECT fa.*, s.subject_desc, c.class_name, c.section, c.year_level
FROM faculty_assignments fa
JOIN subjects s ON s.subject_code = fa.subject_code
JOIN classes  c ON c.id = fa.class_id
WHERE fa.faculty_id = ?
ORDER BY c.class_name, c.section, s.subject_code
");
$stmt->bind_param('s', $fid);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
$class_id    = (int)$_POST['class_id'];
$subject_code = $_POST['subject_code'];
$school_year  = $_POST['school_year'] ?? '2023-2024';
$semester     = $_POST['semester']    ?? '1st Semester';

$chk = $conn->prepare("SELECT id FROM faculty_assignments WHERE faculty_id=? AND class_id=? AND subject_code=?");
$chk->bind_param('sis', $fid, $class_id, $subject_code);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) {
$msg = 'error:Unauthorized action.';
} else {
$students = $_POST['students'] ?? [];
$saved = 0;
foreach ($students as $sid => $scores) {
$prelim  = $scores['prelim']  !== '' ? (float)$scores['prelim']  : null;
$midterm = $scores['midterm'] !== '' ? (float)$scores['midterm'] : null;
$finals  = $scores['finals']  !== '' ? (float)$scores['finals']  : null;

$final_grade = null;
$remarks     = null;
if ($prelim !== null && $midterm !== null && $finals !== null) {
$final_grade = round(($prelim + $midterm + $finals) / 3, 2);
$remarks     = $final_grade >= 75 ? 'Passed' : 'Failed';
}

$exists = $conn->prepare("SELECT id FROM grades WHERE student_id=? AND subject_code=? AND class_id=?");
$exists->bind_param('ssi', $sid, $subject_code, $class_id);
$exists->execute();
$row = $exists->get_result()->fetch_assoc();

if ($row) {
$upd = $conn->prepare("UPDATE grades SET prelim=?,midterm=?,finals=?,final_grade=?,remarks=?,school_year=?,semester=?,faculty_id=? WHERE id=?");
$upd->bind_param('ddddssssi', $prelim,$midterm,$finals,$final_grade,$remarks,$school_year,$semester,$fid,$row['id']);
$upd->execute();
} else {
$ins = $conn->prepare("INSERT INTO grades (student_id,subject_code,class_id,prelim,midterm,finals,final_grade,remarks,school_year,semester,faculty_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$ins->bind_param('ssiddddsss', $sid,$subject_code,$class_id,$prelim,$midterm,$finals,$final_grade,$remarks,$school_year,$semester,$fid);
$ins->execute();
}
$saved++;
}
$msg = "success:Grades saved successfully for $saved student(s).";
}
}

$students_grades = [];
if ($sel_class && $sel_subj) {
$stmt = $conn->prepare("
SELECT s.id_number, s.last_name, s.first_name, s.middle_name,
g.prelim, g.midterm, g.finals, g.final_grade, g.remarks, g.id grade_id
FROM enrollments e
JOIN students s ON s.id_number = e.student_id
LEFT JOIN grades g ON g.student_id = e.student_id AND g.subject_code = ? AND g.class_id = ?
WHERE e.class_id = ? AND e.subject_code = ?
ORDER BY s.last_name, s.first_name
");
$stmt->bind_param('ssis', $sel_subj, $sel_class, $sel_class, $sel_subj);
$stmt->execute();
$students_grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$sel_info = null;
foreach ($assignments as $a) {
if ($a['class_id'] == $sel_class && $a['subject_code'] == $sel_subj) {
$sel_info = $a;
break;
}
}

[$msg_type, $msg_text] = $msg ? explode(':', $msg, 2) : ['',''];

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>Grade Entry</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Grade Entry</span></div>
</div>
<?php if ($sel_info && !empty($students_grades)): ?>
<button class="btn btn-secondary btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
<?php endif; ?>
</div>

<?php if ($msg_text): ?>
<div class="alert alert-<?= $msg_type ?>"><i class="fas fa-<?= $msg_type==='success'?'check-circle':'exclamation-circle'?>"></i> <?= htmlspecialchars($msg_text) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
<div class="card-title"><i class="fas fa-filter"></i> Select Class & Subject</div>
<form method="GET" action="" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
<div class="form-group" style="margin:0;flex:1;min-width:200px;">
<label class="form-label">Class & Subject</label>
<select name="class" id="classSelect" class="form-control" onchange="syncSubject(this)">
<option value="">— Select —</option>
<?php foreach ($assignments as $a): ?>
<option value="<?= $a['class_id'] ?>"
data-subj="<?= htmlspecialchars($a['subject_code']) ?>"
<?= ($a['class_id']==$sel_class && $a['subject_code']==$sel_subj) ? 'selected' : '' ?>>
<?= htmlspecialchars($a['class_name'].' '.$a['section'].' — '.$a['subject_code'].' ('.$a['subject_desc'].')') ?>
</option>
<?php endforeach; ?>
</select>
</div>
<input type="hidden" name="subj" id="subjHidden" value="<?= htmlspecialchars($sel_subj) ?>">
<button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load Students</button>
</form>
</div>

<?php if ($sel_info): ?>

<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px;">
<?php
$info_items = [
['fas fa-chalkboard','Class', $sel_info['class_name'].' '.$sel_info['section']],
['fas fa-book','Subject', $sel_info['subject_code'].' — '.$sel_info['subject_desc']],
['fas fa-layer-group','Year Level', $sel_info['year_level']],
['fas fa-user-graduate','Students', count($students_grades)],
];
foreach ($info_items as [$ic,$lbl,$val]): ?>
<div class="stat-card" style="flex:1;min-width:150px;padding:14px 16px;">
<div class="stat-icon green"><i class="<?= $ic ?>"></i></div>
<div><div class="stat-value" style="font-size:16px;"><?= htmlspecialchars((string)$val) ?></div><div class="stat-label"><?= $lbl ?></div></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($sel_class && $sel_subj): ?>
<?php if (empty($students_grades)): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--gray-600);">
<i class="fas fa-user-slash" style="font-size:36px;display:block;margin-bottom:10px;color:var(--gray-400);"></i>
<div style="font-size:15px;font-weight:600;">No students enrolled in this class/subject.</div>
</div>
<?php else: ?>

<form method="POST" action="" id="gradeForm">
<input type="hidden" name="save_grades"  value="1">
<input type="hidden" name="class_id"     value="<?= $sel_class ?>">
<input type="hidden" name="subject_code" value="<?= htmlspecialchars($sel_subj) ?>">
<input type="hidden" name="school_year"  value="2023-2024">
<input type="hidden" name="semester"     value="1st Semester">

<div class="table-card">
<div style="padding:14px 16px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
<div style="font-size:14px;font-weight:700;color:var(--gray-800);">
<i class="fas fa-star-half-alt" style="color:var(--green-main);"></i>
Grade Sheet — <?= htmlspecialchars($sel_info['class_name'].' '.$sel_info['section']) ?> · <?= htmlspecialchars($sel_subj) ?>
</div>
<div style="display:flex;gap:8px;">
<button type="button" class="btn btn-secondary btn-sm" onclick="fillAll()"><i class="fas fa-magic"></i> Auto-Compute All</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save All Grades</button>
</div>
</div>

<div style="overflow-x:auto;">
<table class="grade-entry-table">
<thead>
<tr>
<th style="width:40px;">#</th>
<th>Student ID</th>
<th>Name</th>
<th style="text-align:center;background:#fff8e1;">
Prelim<br><small style="font-weight:400;color:var(--gray-600);">(0–100)</small>
</th>
<th style="text-align:center;background:#e8f4fd;">
Midterm<br><small style="font-weight:400;color:var(--gray-600);">(0–100)</small>
</th>
<th style="text-align:center;background:#e8f5ee;">
Finals<br><small style="font-weight:400;color:var(--gray-600);">(0–100)</small>
</th>
<th style="text-align:center;">Final Grade</th>
<th style="text-align:center;">Remarks</th>
</tr>
</thead>
<tbody>
<?php foreach ($students_grades as $i => $sg):
$fg  = $sg['final_grade'];
$has = $fg !== null;
[$ltr,$clr] = $has ? gradeLetter((float)$fg) : ['—','#6c757d'];
$sid_val = $sg['id_number'];
?>
<tr id="row_<?= $i ?>" class="<?= $has ? 'grade-row-saved' : '' ?>">
<td style="text-align:center;color:var(--gray-600);"><?= $i+1 ?></td>
<td><strong><?= htmlspecialchars($sg['id_number']) ?></strong></td>
<td><?= htmlspecialchars($sg['last_name'].', '.$sg['first_name'].' '.($sg['middle_name']??'')) ?></td>
<td style="text-align:center;background:#fffdf0;">
<input type="number" name="students[<?= htmlspecialchars($sid_val) ?>][prelim]"
id="prelim_<?= $i ?>" class="grade-input"
data-row="<?= $i ?>" data-term="prelim"
value="<?= $sg['prelim'] !== null ? htmlspecialchars($sg['prelim']) : '' ?>"
min="0" max="100" step="0.01" placeholder="—">
</td>
<td style="text-align:center;background:#f0f7ff;">
<input type="number" name="students[<?= htmlspecialchars($sid_val) ?>][midterm]"
id="midterm_<?= $i ?>" class="grade-input"
data-row="<?= $i ?>" data-term="midterm"
value="<?= $sg['midterm'] !== null ? htmlspecialchars($sg['midterm']) : '' ?>"
min="0" max="100" step="0.01" placeholder="—">
</td>
<td style="text-align:center;background:#f0faf4;">
<input type="number" name="students[<?= htmlspecialchars($sid_val) ?>][finals]"
id="finals_<?= $i ?>" class="grade-input"
data-row="<?= $i ?>" data-term="finals"
value="<?= $sg['finals'] !== null ? htmlspecialchars($sg['finals']) : '' ?>"
min="0" max="100" step="0.01" placeholder="—">
</td>
<td style="text-align:center;">
<span class="computed-grade" id="fg_<?= $i ?>"
style="color:<?= $clr ?>">
<?= $has ? number_format((float)$fg,2) : '—' ?>
</span>
</td>
<td style="text-align:center;">
<span id="rem_<?= $i ?>" class="badge <?= $has ? ($fg>=75?'badge-active':'badge-inactive') : '' ?>">
<?= $has ? ($fg>=75?'Passed':'Failed') : '—' ?>
</span>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div style="padding:14px 16px;border-top:1px solid var(--gray-200);display:flex;justify-content:flex-end;gap:10px;">
<button type="button" class="btn btn-secondary" onclick="history.back()"><i class="fas fa-times"></i> Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save All Grades</button>
</div>
</div>
</form>

<div style="margin-top:14px;font-size:12px;color:var(--gray-600);">
<i class="fas fa-info-circle"></i>
Final Grade = Average of Prelim + Midterm + Finals. Passing grade is <strong>75</strong>.
Green rows indicate previously saved grades.
</div>

<?php endif; ?>
<?php elseif (empty($sel_class)): ?>
<div class="card" style="text-align:center;padding:40px;color:var(--gray-600);">
<i class="fas fa-hand-pointer" style="font-size:36px;display:block;margin-bottom:10px;color:var(--gray-400);"></i>
<div style="font-size:15px;font-weight:600;">Select a class and subject above to begin entering grades.</div>
</div>
<?php endif; ?>

<style>
@media print {
.sidebar,.topbar,.page-header .btn,.page-footer,.card:first-of-type,
form > div:last-child, .btn { display:none !important; }
.main-content { margin-left:0 !important; }
body { background:#fff !important; }
input[type=number] { border:none !important; }
}
</style>

<script>
function syncSubject(sel) {
const opt = sel.options[sel.selectedIndex];
document.getElementById('subjHidden').value = opt.dataset.subj || '';
}

document.querySelectorAll('.grade-input').forEach(inp => {
inp.addEventListener('input', () => computeRow(inp.dataset.row));
});

function computeRow(i) {
const p = parseFloat(document.getElementById('prelim_'  + i)?.value);
const m = parseFloat(document.getElementById('midterm_' + i)?.value);
const f = parseFloat(document.getElementById('finals_'  + i)?.value);
const fgEl  = document.getElementById('fg_'  + i);
const remEl = document.getElementById('rem_' + i);
if (!isNaN(p) && !isNaN(m) && !isNaN(f)) {
const fg = ((p + m + f) / 3).toFixed(2);
const passed = fg >= 75;
fgEl.textContent  = fg;
fgEl.style.color  = passed ? '#1d5c3a' : '#dc3545';
remEl.textContent = passed ? 'Passed' : 'Failed';
remEl.className   = 'badge ' + (passed ? 'badge-active' : 'badge-inactive');
} else {
fgEl.textContent  = '—';
fgEl.style.color  = '#6c757d';
remEl.textContent = '—';
remEl.className   = 'badge';
}
}

function fillAll() {
document.querySelectorAll('.grade-input').forEach(inp => {
const row = inp.dataset.row;
computeRow(row);
});
}

document.querySelectorAll('tr[id^="row_"]').forEach(tr => {
const i = tr.id.replace('row_','');
computeRow(i);
});
</script>

<?php require_once 'footer.php'; ?>