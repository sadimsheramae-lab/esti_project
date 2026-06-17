<?php
require_once '../../includes/config.php';
requireLogin();
$page_title = 'Student Management';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
$id_number  = trim($_POST['id_number']);
$last_name  = trim($_POST['last_name']);
$first_name = trim($_POST['first_name']);
$middle_name= trim($_POST['middle_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$course     = $_POST['course'];
$year_level = $_POST['year_level'];
$status     = $_POST['status'];

if ($action === 'add') {
$stmt = $conn->prepare("INSERT INTO students (id_number,last_name,first_name,middle_name,email,course,year_level,status) VALUES (?,?,?,?,?,?,?,?)");
$stmt->bind_param('ssssssss', $id_number,$last_name,$first_name,$middle_name,$email,$course,$year_level,$status);
$stmt->execute();
$msg = 'success:Student added successfully.';
} else {
$oid = $_POST['old_id'];
$stmt = $conn->prepare("UPDATE students SET id_number=?,last_name=?,first_name=?,middle_name=?,email=?,course=?,year_level=?,status=? WHERE id_number=?");
$stmt->bind_param('sssssssss', $id_number,$last_name,$first_name,$middle_name,$email,$course,$year_level,$status,$oid);
$stmt->execute();
$msg = 'success:Student updated successfully.';
}
} elseif ($action === 'delete') {
$id = $_POST['id'];
$conn->query("DELETE FROM students WHERE id_number='".($conn->real_escape_string($id))."'");
$msg = 'success:Student deleted.';
}
}

$departments = $conn->query("SELECT dept_code FROM departments WHERE status='Active' ORDER BY dept_code");
$dept_list = [];
while ($d = $departments->fetch_assoc()) $dept_list[] = $d['dept_code'];

$students = $conn->query("SELECT * FROM students ORDER BY created_at DESC");
$total = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];

require_once '../../includes/header.php';

$msg_type = $msg_text = '';
if ($msg) { [$msg_type, $msg_text] = explode(':', $msg, 2); }
?>

<style>
/* =====================================================
   Student Management — Roster / ID-card theme
   Same green family as other admin pages, distinct
   identity: each student is a record with a stub-ID,
   the detail view reads like an actual ID card.
===================================================== */
:root{
  --sr-green-deep:#0f3d28;
  --sr-green:#1d6b46;
  --sr-green-soft:#e7f0e9;
  --sr-ink:#212722;
  --sr-slate:#5b6b62;
  --sr-surface:#f4f7f5;
  --sr-line:#dfe7e2;
  --sr-card:#ffffff;
  --sr-mono:'JetBrains Mono', 'Courier New', monospace;
  --sr-sans:inherit;
}

.sr-wrap{ color:var(--sr-ink); }

.sr-header{
  display:flex; align-items:flex-end; justify-content:space-between;
  gap:14px; flex-wrap:wrap; margin-bottom:18px;
}
.sr-header h1{ margin:0 0 6px 0; font-size:1.55rem; font-weight:700; color:var(--sr-green-deep); }
.sr-breadcrumb{ font-size:.85rem; color:var(--sr-slate); }
.sr-breadcrumb a{ color:var(--sr-green); text-decoration:none; }
.sr-breadcrumb a:hover{ text-decoration:underline; }

.sr-btn-primary{
  display:inline-flex; align-items:center; gap:8px;
  background:var(--sr-green-deep); color:#fff; border:none;
  padding:11px 20px; border-radius:9px; font-weight:600; font-size:.92rem; cursor:pointer;
  box-shadow:0 3px 10px rgba(15,61,40,.2); transition:transform .15s ease, background .15s ease;
}
.sr-btn-primary:hover{ background:var(--sr-green); transform:translateY(-1px); }
.sr-btn-secondary{
  display:inline-flex; align-items:center; gap:8px;
  background:var(--sr-surface); color:var(--sr-ink); border:1px solid var(--sr-line);
  padding:11px 20px; border-radius:9px; font-weight:600; font-size:.92rem; cursor:pointer;
}
.sr-btn-secondary:hover{ background:#eaf0ec; }

.sr-alert{ padding:13px 16px; border-radius:9px; margin-bottom:16px; font-size:.92rem; border:1px solid transparent; }
.sr-alert-success{ background:var(--sr-green-soft); border-color:#bfe0cc; color:var(--sr-green-deep); }
.sr-alert-error,.sr-alert-danger{ background:#fef2f2; border-color:#fecaca; color:#991b1b; }

.sr-card{ background:var(--sr-card); border:1px solid var(--sr-line); border-radius:14px; overflow:hidden; }

.sr-toolbar{
  display:flex; align-items:center; gap:10px; padding:15px 18px;
  border-bottom:1px solid var(--sr-line); flex-wrap:wrap;
}
.sr-search{ position:relative; flex:1; min-width:220px; max-width:320px; }
.sr-search i{ position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--sr-green); font-size:.85rem; }
.sr-search input{
  width:100%; padding:10px 14px 10px 34px; border:1px solid var(--sr-line); border-radius:9px;
  font-size:.88rem; background:var(--sr-surface); outline:none; transition:border-color .15s ease, background .15s ease;
}
.sr-search input:focus{ border-color:var(--sr-green); background:#fff; }
.sr-filter{
  padding:9px 12px; border:1px solid var(--sr-line); border-radius:9px; font-size:.85rem;
  background:var(--sr-surface); color:var(--sr-ink); cursor:pointer; outline:none;
}
.sr-filter:focus{ border-color:var(--sr-green); }
.sr-toolbar-count{
  margin-left:auto; font-size:.78rem; font-weight:600; color:var(--sr-green-deep);
  background:var(--sr-green-soft); padding:5px 12px; border-radius:999px;
}

table.sr-table{ width:100%; border-collapse:collapse; }
table.sr-table thead th{
  text-align:left; font-size:.72rem; letter-spacing:.05em; text-transform:uppercase;
  color:var(--sr-slate); background:var(--sr-surface); padding:12px 16px;
  border-bottom:1px solid var(--sr-line); font-weight:700;
}
table.sr-table tbody td{ padding:13px 16px; border-bottom:1px solid var(--sr-line); font-size:.9rem; vertical-align:middle; }
table.sr-table tbody tr:last-child td{ border-bottom:none; }
table.sr-table tbody tr:hover{ background:var(--sr-surface); }

/* ID stub: small colored tick + monospace ID, like a card stub */
.sr-idstub{ display:flex; align-items:center; gap:9px; }
.sr-idstub-tick{ width:4px; height:22px; border-radius:2px; background:var(--sr-green); flex-shrink:0; }
.sr-idstub.is-inactive .sr-idstub-tick{ background:#c2c9c4; }
.sr-idnum{ font-family:var(--sr-mono); font-weight:600; font-size:.86rem; letter-spacing:.01em; }

.sr-name{ font-weight:600; }
.sr-name-sub{ font-size:.76rem; color:var(--sr-slate); margin-top:1px; }

.sr-badge{
  display:inline-flex; align-items:center; gap:5px; padding:3px 11px; border-radius:999px;
  font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em;
}
.sr-badge::before{ content:''; width:6px; height:6px; border-radius:50%; }
.sr-badge.active{ background:var(--sr-green-soft); color:var(--sr-green-deep); }
.sr-badge.active::before{ background:var(--sr-green); }
.sr-badge.inactive{ background:#f1f3f1; color:var(--sr-slate); }
.sr-badge.inactive::before{ background:#9aa39c; }

.sr-action-btns{ display:flex; gap:7px; }
.sr-action-btn{
  width:32px; height:32px; border-radius:8px; border:1px solid var(--sr-line);
  background:#fff; display:inline-flex; align-items:center; justify-content:center;
  cursor:pointer; color:var(--sr-slate); transition:all .15s ease; font-size:.8rem;
}
.sr-action-btn.sr-view:hover{ background:#eef5fb; color:#2563a8; border-color:#bcd6ee; }
.sr-action-btn.sr-edit:hover{ background:var(--sr-green-soft); color:var(--sr-green-deep); border-color:#bfe0cc; }
.sr-action-btn.sr-del:hover{ background:#fef2f2; color:#dc2626; border-color:#fecaca; }

.sr-pagination-wrap{
  display:flex; align-items:center; justify-content:space-between; padding:14px 18px;
  border-top:1px solid var(--sr-line); flex-wrap:wrap; gap:10px;
}
.sr-rowcount{ font-size:.82rem; color:var(--sr-slate); }
.sr-pagination{ display:flex; gap:6px; }
.sr-page-btn{
  width:32px; height:32px; border-radius:8px; border:1px solid var(--sr-line); background:#fff;
  color:var(--sr-ink); font-size:.82rem; cursor:pointer;
}
.sr-page-btn.active{ background:var(--sr-green-deep); color:#fff; border-color:var(--sr-green-deep); }
.sr-page-btn:hover:not(.active){ background:var(--sr-surface); }

/* Modal */
.sr-modal-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,40,30,.45); align-items:center; justify-content:center; z-index:1000; padding:20px; }
.sr-modal-backdrop.sr-open{ display:flex; }
.sr-modal{ background:#fff; border-radius:16px; width:100%; max-width:580px; box-shadow:0 20px 50px rgba(15,40,30,.25); overflow:hidden; }
.sr-modal-header{ display:flex; align-items:center; justify-content:space-between; padding:18px 22px; background:var(--sr-green-deep); color:#fff; }
.sr-modal-title{ display:flex; align-items:center; gap:9px; font-weight:700; font-size:1.02rem; }
.sr-modal-close{ background:rgba(255,255,255,.15); border:none; color:#fff; width:28px; height:28px; border-radius:8px; cursor:pointer; font-size:1.1rem; }
.sr-modal-close:hover{ background:rgba(255,255,255,.28); }
.sr-modal-body{ padding:22px; }
.sr-form-row{ display:flex; gap:14px; margin-bottom:14px; }
.sr-form-row .sr-form-group{ flex:1; }
.sr-form-label{ display:block; font-size:.8rem; font-weight:600; margin-bottom:6px; color:var(--sr-ink); }
.sr-form-control{
  width:100%; padding:10px 13px; border:1px solid var(--sr-line); border-radius:9px;
  font-size:.88rem; background:var(--sr-surface); outline:none; transition:border-color .15s ease, background .15s ease;
}
.sr-form-control:focus{ border-color:var(--sr-green); background:#fff; }
.sr-modal-footer{ display:flex; justify-content:flex-end; gap:10px; padding:16px 22px; border-top:1px solid var(--sr-line); background:var(--sr-surface); }

/* ── Student ID card (view modal signature element) ── */
.sr-idcard{
  border-radius:16px; overflow:hidden; border:1px solid var(--sr-line);
  box-shadow:0 6px 18px rgba(15,61,40,.10);
}
.sr-idcard-top{
  background:linear-gradient(135deg, var(--sr-green-deep) 0%, var(--sr-green) 100%);
  color:#fff; padding:18px 20px 34px; position:relative;
}
.sr-idcard-org{ font-size:.68rem; letter-spacing:.1em; text-transform:uppercase; opacity:.8; font-weight:700; }
.sr-idcard-label{ font-size:.95rem; font-weight:700; margin-top:2px; }
.sr-idcard-photo{
  position:absolute; right:20px; top:18px; width:46px; height:46px; border-radius:50%;
  background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.5);
  display:flex; align-items:center; justify-content:center; font-size:1.1rem;
}
.sr-idcard-mid{
  background:#fff; padding:18px 20px 14px; margin-top:-22px; border-radius:16px 16px 0 0; position:relative;
}
.sr-idcard-name{ font-size:1.15rem; font-weight:700; color:var(--sr-ink); }
.sr-idcard-idnum{ font-family:var(--sr-mono); font-size:.86rem; color:var(--sr-green); font-weight:700; margin-top:3px; letter-spacing:.02em; }
.sr-idcard-grid{
  display:grid; grid-template-columns:1fr 1fr; gap:12px 18px; margin-top:16px; padding-top:14px;
  border-top:1px dashed var(--sr-line);
}
.sr-idcard-field-label{ font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--sr-slate); margin-bottom:2px; }
.sr-idcard-field-value{ font-size:.92rem; font-weight:600; color:var(--sr-ink); }
.sr-idcard-bottom{ padding:14px 20px 18px; background:#fff; }

@media (max-width:640px){
  .sr-form-row{ flex-direction:column; gap:0; }
  .sr-idcard-grid{ grid-template-columns:1fr; }
  table.sr-table thead{ display:none; }
  table.sr-table, table.sr-table tbody, table.sr-table tr, table.sr-table td{ display:block; width:100%; }
  table.sr-table tr{ border-bottom:1px solid var(--sr-line); padding:10px 0; }
  table.sr-table td{ border-bottom:none; padding:5px 16px; }
}
</style>

<div class="sr-wrap">

<div class="sr-header">
  <div>
    <h1>Student Management</h1>
    <div class="sr-breadcrumb"><a href="#">Admin</a> / <a href="#">Student</a> / <span>List</span></div>
  </div>
  <button class="sr-btn-primary" onclick="openModal('addStudentModal')"><i class="fas fa-plus"></i> Add Student</button>
</div>

<?php if ($msg_text): ?>
<div class="sr-alert sr-alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg_text) ?></div>
<?php endif; ?>

<div class="sr-card">
  <div class="sr-toolbar">
    <div class="sr-search"><i class="fas fa-search"></i>
      <input type="text" id="studentSearch" placeholder="Search by ID, Name or Course...">
    </div>
    <select id="courseFilter" class="sr-filter">
      <option value="">All Courses</option>
      <?php foreach ($dept_list as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?>
    </select>
    <select id="yearFilter" class="sr-filter">
      <option value="">All Years</option>
      <option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option>
    </select>
    <span class="sr-toolbar-count"><?= $total ?> students</span>
  </div>

  <table id="studentTable" class="sr-table">
    <thead><tr>
      <th>ID Number</th><th>Name</th><th>Course</th><th>Year Level</th><th>Status</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php while ($s = $students->fetch_assoc()): ?>
    <?php $isActive = strtolower($s['status']) === 'active'; ?>
    <tr>
      <td>
        <div class="sr-idstub <?= $isActive ? '' : 'is-inactive' ?>">
          <span class="sr-idstub-tick"></span>
          <span class="sr-idnum"><?= htmlspecialchars($s['id_number']) ?></span>
        </div>
      </td>
      <td>
        <div class="sr-name"><?= htmlspecialchars($s['last_name'].', '.$s['first_name']) ?></div>
        <?php if (!empty($s['email'])): ?><div class="sr-name-sub"><?= htmlspecialchars($s['email']) ?></div><?php endif; ?>
      </td>
      <td><?= htmlspecialchars($s['course']) ?></td>
      <td><?= htmlspecialchars($s['year_level']) ?></td>
      <td><span class="sr-badge <?= $isActive ? 'active' : 'inactive' ?>"><?= htmlspecialchars($s['status']) ?></span></td>
      <td>
        <div class="sr-action-btns">
          <button class="sr-action-btn sr-view" title="View" onclick="viewStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-eye"></i></button>
          <button class="sr-action-btn sr-edit" title="Edit" onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-pencil-alt"></i></button>
          <form method="POST" style="display:inline" onsubmit="confirmDelete(this);return false;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= htmlspecialchars($s['id_number']) ?>">
            <button type="submit" class="sr-action-btn sr-del" title="Delete"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>

  <div class="sr-pagination-wrap">
    <span class="sr-rowcount" id="rowCount">Showing <?= $total ?> entries</span>
    <div class="sr-pagination">
      <button class="sr-page-btn active">1</button>
      <button class="sr-page-btn">2</button>
      <button class="sr-page-btn">3</button>
      <button class="sr-page-btn"><i class="fas fa-chevron-right"></i></button>
    </div>
  </div>
</div>

<div class="sr-modal-backdrop" id="addStudentModal">
<div class="sr-modal">
<div class="sr-modal-header">
<span class="sr-modal-title"><i class="fas fa-user-plus"></i> Add Student</span>
<button class="sr-modal-close" onclick="closeModal('addStudentModal')">&times;</button>
</div>
<form method="POST">
<input type="hidden" name="action" value="add">
<div class="sr-modal-body">
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">ID Number *</label>
<input type="text" name="id_number" class="sr-form-control" required></div>
<div class="sr-form-group"><label class="sr-form-label">Course *</label>
<select name="course" class="sr-form-control" required>
<?php foreach ($dept_list as $d): ?><option><?= $d ?></option><?php endforeach; ?>
</select></div>
</div>
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">Last Name *</label>
<input type="text" name="last_name" class="sr-form-control" required></div>
<div class="sr-form-group"><label class="sr-form-label">First Name *</label>
<input type="text" name="first_name" class="sr-form-control" required></div>
</div>
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">Middle Name</label>
<input type="text" name="middle_name" class="sr-form-control"></div>
<div class="sr-form-group"><label class="sr-form-label">Email</label>
<input type="email" name="email" class="sr-form-control"></div>
</div>
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">Year Level *</label>
<select name="year_level" class="sr-form-control" required>
<option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option>
</select></div>
<div class="sr-form-group"><label class="sr-form-label">Status</label>
<select name="status" class="sr-form-control">
<option>Active</option><option>Inactive</option>
</select></div>
</div>
</div>
<div class="sr-modal-footer">
<button type="button" class="sr-btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button>
<button type="submit" class="sr-btn-primary"><i class="fas fa-save"></i> Save Student</button>
</div>
</form>
</div>
</div>

<div class="sr-modal-backdrop" id="editStudentModal">
<div class="sr-modal">
<div class="sr-modal-header">
<span class="sr-modal-title"><i class="fas fa-pencil-alt"></i> Edit Student</span>
<button class="sr-modal-close" onclick="closeModal('editStudentModal')">&times;</button>
</div>
<form method="POST" id="editStudentForm">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="old_id" id="edit_old_id">
<div class="sr-modal-body">
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">ID Number *</label>
<input type="text" name="id_number" id="edit_id_number" class="sr-form-control" required></div>
<div class="sr-form-group"><label class="sr-form-label">Course *</label>
<select name="course" id="edit_course" class="sr-form-control" required>
<?php foreach ($dept_list as $d): ?><option><?= $d ?></option><?php endforeach; ?>
</select></div>
</div>
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">Last Name *</label>
<input type="text" name="last_name" id="edit_last_name" class="sr-form-control" required></div>
<div class="sr-form-group"><label class="sr-form-label">First Name *</label>
<input type="text" name="first_name" id="edit_first_name" class="sr-form-control" required></div>
</div>
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">Middle Name</label>
<input type="text" name="middle_name" id="edit_middle_name" class="sr-form-control"></div>
<div class="sr-form-group"><label class="sr-form-label">Email</label>
<input type="email" name="email" id="edit_email" class="sr-form-control"></div>
</div>
<div class="sr-form-row">
<div class="sr-form-group"><label class="sr-form-label">Year Level *</label>
<select name="year_level" id="edit_year_level" class="sr-form-control" required>
<option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option>
</select></div>
<div class="sr-form-group"><label class="sr-form-label">Status</label>
<select name="status" id="edit_status" class="sr-form-control">
<option>Active</option><option>Inactive</option>
</select></div>
</div>
</div>
<div class="sr-modal-footer">
<button type="button" class="sr-btn-secondary" onclick="closeModal('editStudentModal')">Cancel</button>
<button type="submit" class="sr-btn-primary"><i class="fas fa-save"></i> Update Student</button>
</div>
</form>
</div>
</div>

<div class="sr-modal-backdrop" id="viewStudentModal">
<div class="sr-modal">
<div class="sr-modal-header">
<span class="sr-modal-title"><i class="fas fa-user"></i> Student Details</span>
<button class="sr-modal-close" onclick="closeModal('viewStudentModal')">&times;</button>
</div>
<div class="sr-modal-body" id="viewStudentBody"></div>
<div class="sr-modal-footer">
<button type="button" class="sr-btn-secondary" onclick="closeModal('viewStudentModal')">Close</button>
</div>
</div>
</div>

</div>

<script>
initSearch('studentSearch', 'studentTable', [0,1,2]);
initFilter('courseFilter', 'studentTable', 2);
initFilter('yearFilter', 'studentTable', 3);

function editStudent(s) {
document.getElementById('edit_old_id').value = s.id_number;
document.getElementById('edit_id_number').value = s.id_number;
document.getElementById('edit_last_name').value = s.last_name;
document.getElementById('edit_first_name').value = s.first_name;
document.getElementById('edit_middle_name').value = s.middle_name || '';
document.getElementById('edit_email').value = s.email || '';
document.getElementById('edit_course').value = s.course;
document.getElementById('edit_year_level').value = s.year_level;
document.getElementById('edit_status').value = s.status;
openModal('editStudentModal');
}

function viewStudent(s) {
const initials = ((s.first_name||'')[0]||'').toUpperCase() + ((s.last_name||'')[0]||'').toUpperCase();
const statusClass = (s.status||'').toLowerCase();
document.getElementById('viewStudentBody').innerHTML = `
<div class="sr-idcard">
  <div class="sr-idcard-top">
    <div class="sr-idcard-org">Student Identification</div>
    <div class="sr-idcard-label">${s.course} · ${s.year_level}</div>
    <div class="sr-idcard-photo">${initials || '<i class="fas fa-user"></i>'}</div>
  </div>
  <div class="sr-idcard-mid">
    <div class="sr-idcard-name">${s.last_name}, ${s.first_name} ${s.middle_name||''}</div>
    <div class="sr-idcard-idnum">${s.id_number}</div>
    <div class="sr-idcard-grid">
      <div><div class="sr-idcard-field-label">Email</div><div class="sr-idcard-field-value">${s.email || '—'}</div></div>
      <div><div class="sr-idcard-field-label">Status</div><div class="sr-idcard-field-value"><span class="sr-badge ${statusClass}">${s.status}</span></div></div>
      <div><div class="sr-idcard-field-label">Course</div><div class="sr-idcard-field-value">${s.course}</div></div>
      <div><div class="sr-idcard-field-label">Year Level</div><div class="sr-idcard-field-value">${s.year_level}</div></div>
    </div>
  </div>
  <div class="sr-idcard-bottom"></div>
</div>`;
openModal('viewStudentModal');
}
</script>
<?php require_once '../../includes/footer.php'; ?>