<?php
require_once '../../includes/config.php';
requireLogin();
$page_title = 'Class and Section Management';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$action = $_POST['action'] ?? '';
if ($action === 'add' || $action === 'edit') {
$cname  = trim($_POST['class_name']);
$section= trim($_POST['section']);
$course = $_POST['course'];
$year   = $_POST['year_level'];
$adv    = $_POST['adviser_id'];
$status = $_POST['status'];
if ($action === 'add') {
$stmt = $conn->prepare("INSERT INTO classes (class_name,section,course,year_level,adviser_id,status) VALUES (?,?,?,?,?,?)");
$stmt->bind_param('ssssss', $cname,$section,$course,$year,$adv,$status);
$stmt->execute();
$msg = 'success:Class added.';
} else {
$oid = (int)$_POST['old_id'];
$stmt = $conn->prepare("UPDATE classes SET class_name=?,section=?,course=?,year_level=?,adviser_id=?,status=? WHERE id=?");
$stmt->bind_param('ssssssi', $cname,$section,$course,$year,$adv,$status,$oid);
$stmt->execute();
$msg = 'success:Class updated.';
}
} elseif ($action === 'delete') {
$id = (int)$_POST['id'];
$conn->query("DELETE FROM classes WHERE id=$id");
$msg = 'success:Class deleted.';
}
}

$classes = $conn->query("
SELECT c.*, CONCAT(f.last_name,', ',f.first_name) adviser_name,
(SELECT COUNT(*) FROM students s WHERE s.course=c.course) students_count
FROM classes c
LEFT JOIN faculty f ON f.faculty_id=c.adviser_id
ORDER BY c.class_name, c.section
");

$depts   = $conn->query("SELECT dept_code FROM departments WHERE status='Active' ORDER BY dept_code");
$dept_list = [];
while ($d = $depts->fetch_assoc()) $dept_list[] = $d['dept_code'];

$faculty_list = $conn->query("SELECT faculty_id, CONCAT(last_name,', ',first_name) fn FROM faculty WHERE status='Active' ORDER BY last_name");

require_once '../../includes/header.php';
$msg_type = $msg_text = '';
if ($msg) { [$msg_type, $msg_text] = explode(':', $msg, 2); }
?>
<style>
/* ===== Green dashboard theme for Class & Section page ===== */
:root{
  --cs-green-50:#f0fdf6;
  --cs-green-100:#dcfce8;
  --cs-green-200:#bbf7d3;
  --cs-green-500:#22a35c;
  --cs-green-600:#16924c;
  --cs-green-700:#0f7a3f;
  --cs-green-900:#0a4a28;
  --cs-ink:#1b2b22;
  --cs-muted:#5d7468;
  --cs-border:#dbe9e0;
  --cs-card:#ffffff;
}

.cs-wrap{ color:var(--cs-ink); }

.cs-page-header{
  display:flex; align-items:flex-end; justify-content:space-between;
  gap:16px; margin-bottom:20px; flex-wrap:wrap;
}
.cs-page-header h1{
  margin:0 0 6px 0; font-size:1.6rem; font-weight:700; color:var(--cs-green-900);
}
.cs-breadcrumb{ font-size:.85rem; color:var(--cs-muted); }
.cs-breadcrumb a{ color:var(--cs-green-700); text-decoration:none; }
.cs-breadcrumb a:hover{ text-decoration:underline; }

.cs-btn-primary{
  display:inline-flex; align-items:center; gap:8px;
  background:linear-gradient(135deg,var(--cs-green-500),var(--cs-green-700));
  color:#fff; border:none; padding:11px 20px; border-radius:10px;
  font-weight:600; font-size:.92rem; cursor:pointer;
  box-shadow:0 4px 12px rgba(15,122,63,.25);
  transition:transform .15s ease, box-shadow .15s ease;
}
.cs-btn-primary:hover{ transform:translateY(-1px); box-shadow:0 6px 16px rgba(15,122,63,.32); }
.cs-btn-secondary{
  display:inline-flex; align-items:center; gap:8px;
  background:#f1f5f2; color:var(--cs-ink); border:1px solid var(--cs-border);
  padding:11px 20px; border-radius:10px; font-weight:600; font-size:.92rem; cursor:pointer;
}
.cs-btn-secondary:hover{ background:#e8efe9; }

.cs-alert{
  padding:13px 16px; border-radius:10px; margin-bottom:18px; font-size:.92rem;
  border:1px solid transparent;
}
.cs-alert-success{ background:var(--cs-green-50); border-color:var(--cs-green-200); color:var(--cs-green-900); }
.cs-alert-error,.cs-alert-danger{ background:#fef2f2; border-color:#fecaca; color:#991b1b; }

.cs-card{
  background:var(--cs-card); border:1px solid var(--cs-border); border-radius:16px;
  box-shadow:0 2px 10px rgba(15,122,63,.06); overflow:hidden;
}

.cs-toolbar{
  display:flex; align-items:center; justify-content:space-between;
  padding:16px 18px; border-bottom:1px solid var(--cs-border); gap:12px; flex-wrap:wrap;
}
.cs-search{
  position:relative; max-width:320px; width:100%;
}
.cs-search i{
  position:absolute; left:13px; top:50%; transform:translateY(-50%);
  color:var(--cs-green-600); font-size:.85rem;
}
.cs-search input{
  width:100%; padding:10px 14px 10px 34px; border:1px solid var(--cs-border);
  border-radius:10px; font-size:.88rem; background:var(--cs-green-50);
  color:var(--cs-ink); outline:none; transition:border-color .15s ease, background .15s ease;
}
.cs-search input:focus{ border-color:var(--cs-green-500); background:#fff; }

.cs-count-pill{
  font-size:.78rem; font-weight:600; color:var(--cs-green-700);
  background:var(--cs-green-100); padding:5px 12px; border-radius:999px;
}

table.cs-table{ width:100%; border-collapse:collapse; }
table.cs-table thead th{
  text-align:left; font-size:.74rem; letter-spacing:.04em; text-transform:uppercase;
  color:var(--cs-green-700); background:var(--cs-green-50);
  padding:13px 16px; border-bottom:1px solid var(--cs-border); font-weight:700;
}
table.cs-table tbody td{
  padding:14px 16px; border-bottom:1px solid var(--cs-border); font-size:.9rem; vertical-align:middle;
}
table.cs-table tbody tr:last-child td{ border-bottom:none; }
table.cs-table tbody tr{ transition:background .12s ease; }
table.cs-table tbody tr:hover{ background:var(--cs-green-50); }

.cs-class-chip{
  display:inline-flex; align-items:center; gap:8px; font-weight:600; color:var(--cs-green-900);
}
.cs-class-dot{
  width:8px; height:8px; border-radius:50%; background:var(--cs-green-500); flex-shrink:0;
}
.cs-section-badge{
  display:inline-block; padding:3px 10px; border-radius:999px; background:var(--cs-green-100);
  color:var(--cs-green-700); font-size:.8rem; font-weight:600;
}
.cs-students-count{
  display:inline-flex; align-items:center; gap:6px; font-weight:600; color:var(--cs-ink);
}
.cs-students-count i{ color:var(--cs-green-500); font-size:.8rem; }

.cs-action-btns{ display:flex; gap:8px; }
.cs-action-btn{
  width:34px; height:34px; border-radius:9px; border:1px solid var(--cs-border);
  background:#fff; display:inline-flex; align-items:center; justify-content:center;
  cursor:pointer; color:var(--cs-muted); transition:all .15s ease; font-size:.85rem;
}
.cs-action-btn.cs-edit:hover{ background:var(--cs-green-50); color:var(--cs-green-700); border-color:var(--cs-green-300); }
.cs-action-btn.cs-del:hover{ background:#fef2f2; color:#dc2626; border-color:#fecaca; }

.cs-empty{ text-align:center; padding:46px 16px; color:var(--cs-muted); }
.cs-empty i{ font-size:1.8rem; color:var(--cs-green-200); margin-bottom:10px; display:block; }

/* Modal restyle */
.cs-modal-backdrop{
  display:none; position:fixed; inset:0; background:rgba(10,40,25,.45);
  align-items:center; justify-content:center; z-index:1000; padding:20px;
}
.cs-modal-backdrop.cs-open{ display:flex; }
.cs-modal{
  background:#fff; border-radius:16px; width:100%; max-width:560px;
  box-shadow:0 20px 50px rgba(10,40,25,.25); overflow:hidden;
}
.cs-modal-header{
  display:flex; align-items:center; justify-content:space-between;
  padding:18px 22px; background:linear-gradient(135deg,var(--cs-green-600),var(--cs-green-800,#0c5c33));
  color:#fff;
}
.cs-modal-title{ font-weight:700; font-size:1.05rem; }
.cs-modal-close{
  background:rgba(255,255,255,.15); border:none; color:#fff; width:28px; height:28px;
  border-radius:8px; cursor:pointer; font-size:1.1rem; line-height:1;
}
.cs-modal-close:hover{ background:rgba(255,255,255,.28); }
.cs-modal-body{ padding:22px; }
.cs-form-row{ display:flex; gap:14px; margin-bottom:14px; }
.cs-form-row .cs-form-group{ flex:1; }
.cs-form-label{ display:block; font-size:.82rem; font-weight:600; color:var(--cs-ink); margin-bottom:6px; }
.cs-form-control{
  width:100%; padding:10px 13px; border:1px solid var(--cs-border); border-radius:9px;
  font-size:.88rem; color:var(--cs-ink); background:#fafdfb; outline:none;
  transition:border-color .15s ease, background .15s ease;
}
.cs-form-control:focus{ border-color:var(--cs-green-500); background:#fff; }
.cs-modal-footer{
  display:flex; justify-content:flex-end; gap:10px; padding:16px 22px;
  border-top:1px solid var(--cs-border); background:#fafdfb;
}

@media (max-width:640px){
  .cs-form-row{ flex-direction:column; gap:0; }
  table.cs-table thead{ display:none; }
  table.cs-table, table.cs-table tbody, table.cs-table tr, table.cs-table td{ display:block; width:100%; }
  table.cs-table tr{ border-bottom:1px solid var(--cs-border); padding:10px 0; }
  table.cs-table td{ border-bottom:none; padding:4px 16px; }
}
</style>

<div class="cs-wrap">

<div class="cs-page-header">
  <div>
    <h1>Class and Section</h1>
    <div class="cs-breadcrumb"><a href="#">Admin</a> / <a href="#">Class &amp; Section</a> / <span>List</span></div>
  </div>
  <button class="cs-btn-primary" onclick="openModal('addClassModal')"><i class="fas fa-plus"></i> Add Class/Section</button>
</div>

<?php if ($msg_text): ?>
  <div class="cs-alert cs-alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg_text) ?></div>
<?php endif; ?>

<div class="cs-card">
  <div class="cs-toolbar">
    <div class="cs-search">
      <i class="fas fa-search"></i>
      <input type="text" id="cSearch" placeholder="Search by Class or Section...">
    </div>
    <span class="cs-count-pill"><?= count($rows ?? []) ?: '' ?></span>
  </div>

  <table id="cTable" class="cs-table">
    <thead>
      <tr>
        <th>Class</th><th>Section</th><th>Course</th><th>Year Level</th><th>Adviser</th><th>Students</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
<?php
$rows = [];
while ($c = $classes->fetch_assoc()) $rows[] = $c;
foreach ($rows as $c):
?>
      <tr>
        <td><span class="cs-class-chip"><span class="cs-class-dot"></span><?= htmlspecialchars($c['class_name']) ?></span></td>
        <td><span class="cs-section-badge"><?= htmlspecialchars($c['section']) ?></span></td>
        <td><?= htmlspecialchars($c['course']) ?></td>
        <td><?= htmlspecialchars($c['year_level']) ?></td>
        <td><?= htmlspecialchars('Prof. '.($c['adviser_name'] ?? '—')) ?></td>
        <td><span class="cs-students-count"><i class="fas fa-user-graduate"></i><?= $c['students_count'] ?></span></td>
        <td>
          <div class="cs-action-btns">
            <button class="cs-action-btn cs-edit" onclick="editClass(<?= htmlspecialchars(json_encode($c)) ?>)"><i class="fas fa-pencil-alt"></i></button>
            <form method="POST" style="display:inline" onsubmit="confirmDelete(this);return false;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button type="submit" class="cs-action-btn cs-del"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (empty($rows)): ?>
      <tr><td colspan="7"><div class="cs-empty"><i class="fas fa-folder-open"></i>No classes or sections found.</div></td></tr>
<?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$faculty_opts = '';
$faculty_list->data_seek(0);
while ($f = $faculty_list->fetch_assoc()) {
$faculty_opts .= "<option value=\"{$f['faculty_id']}\">{$f['fn']}</option>";
}
$dept_opts = '';
foreach ($dept_list as $d) $dept_opts .= "<option>$d</option>";
?>

<div class="cs-modal-backdrop" id="addClassModal"><div class="cs-modal">
  <div class="cs-modal-header"><span class="cs-modal-title">Add Class/Section</span><button class="cs-modal-close" onclick="closeModal('addClassModal')">&times;</button></div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="cs-modal-body">
      <div class="cs-form-row">
        <div class="cs-form-group"><label class="cs-form-label">Class Name *</label><input type="text" name="class_name" class="cs-form-control" placeholder="e.g. BSIT" required></div>
        <div class="cs-form-group"><label class="cs-form-label">Section *</label><input type="text" name="section" class="cs-form-control" placeholder="e.g. 2A" required></div>
      </div>
      <div class="cs-form-row">
        <div class="cs-form-group"><label class="cs-form-label">Course *</label>
          <select name="course" class="cs-form-control"><?= $dept_opts ?></select></div>
        <div class="cs-form-group"><label class="cs-form-label">Year Level *</label>
          <select name="year_level" class="cs-form-control"><option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option></select></div>
      </div>
      <div class="cs-form-row">
        <div class="cs-form-group"><label class="cs-form-label">Adviser</label>
          <select name="adviser_id" class="cs-form-control"><?= $faculty_opts ?></select></div>
        <div class="cs-form-group"><label class="cs-form-label">Status</label>
          <select name="status" class="cs-form-control"><option>Active</option><option>Inactive</option></select></div>
      </div>
    </div>
    <div class="cs-modal-footer">
      <button type="button" class="cs-btn-secondary" onclick="closeModal('addClassModal')">Cancel</button>
      <button type="submit" class="cs-btn-primary"><i class="fas fa-save"></i> Save</button>
    </div>
  </form>
</div></div>

<div class="cs-modal-backdrop" id="editClassModal"><div class="cs-modal">
  <div class="cs-modal-header"><span class="cs-modal-title">Edit Class/Section</span><button class="cs-modal-close" onclick="closeModal('editClassModal')">&times;</button></div>
  <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="old_id" id="ec_oid">
    <div class="cs-modal-body">
      <div class="cs-form-row">
        <div class="cs-form-group"><label class="cs-form-label">Class Name *</label><input type="text" name="class_name" id="ec_cname" class="cs-form-control" required></div>
        <div class="cs-form-group"><label class="cs-form-label">Section *</label><input type="text" name="section" id="ec_section" class="cs-form-control" required></div>
      </div>
      <div class="cs-form-row">
        <div class="cs-form-group"><label class="cs-form-label">Course *</label>
          <select name="course" id="ec_course" class="cs-form-control"><?= $dept_opts ?></select></div>
        <div class="cs-form-group"><label class="cs-form-label">Year Level *</label>
          <select name="year_level" id="ec_year" class="cs-form-control"><option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option></select></div>
      </div>
      <div class="cs-form-row">
        <div class="cs-form-group"><label class="cs-form-label">Adviser</label>
          <select name="adviser_id" id="ec_adv" class="cs-form-control"><?= $faculty_opts ?></select></div>
        <div class="cs-form-group"><label class="cs-form-label">Status</label>
          <select name="status" id="ec_status" class="cs-form-control"><option>Active</option><option>Inactive</option></select></div>
      </div>
    </div>
    <div class="cs-modal-footer">
      <button type="button" class="cs-btn-secondary" onclick="closeModal('editClassModal')">Cancel</button>
      <button type="submit" class="cs-btn-primary"><i class="fas fa-save"></i> Update</button>
    </div>
  </form>
</div></div>

</div>

<script>
initSearch('cSearch','cTable',[0,1,2,4]);
function editClass(c){
document.getElementById('ec_oid').value=c.id;
document.getElementById('ec_cname').value=c.class_name;
document.getElementById('ec_section').value=c.section;
document.getElementById('ec_course').value=c.course;
document.getElementById('ec_year').value=c.year_level;
document.getElementById('ec_adv').value=c.adviser_id||'';
document.getElementById('ec_status').value=c.status;
openModal('editClassModal');
}
</script>
<?php require_once '../../includes/footer.php'; ?>