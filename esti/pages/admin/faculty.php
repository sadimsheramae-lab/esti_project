<?php
require_once '../../includes/config.php';
requireLogin();
$page_title = 'Faculty Management';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$action = $_POST['action'] ?? '';
if ($action === 'add' || $action === 'edit') {
$fid   = trim($_POST['faculty_id']);
$fname = trim($_POST['first_name']);
$lname = trim($_POST['last_name']);
$email = trim($_POST['email'] ?? '');
$dept  = $_POST['dept_code'];
$pos   = trim($_POST['position'] ?? '');
$status= $_POST['status'];
if ($action === 'add') {
$stmt = $conn->prepare("INSERT INTO faculty (faculty_id,first_name,last_name,email,dept_code,position,status) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param('sssssss', $fid,$fname,$lname,$email,$dept,$pos,$status);
$stmt->execute();
$msg = 'success:Faculty member added.';
} else {
$old = $_POST['old_id'];
$stmt = $conn->prepare("UPDATE faculty SET faculty_id=?,first_name=?,last_name=?,email=?,dept_code=?,position=?,status=? WHERE faculty_id=?");
$stmt->bind_param('ssssssss', $fid,$fname,$lname,$email,$dept,$pos,$status,$old);
$stmt->execute();
$msg = 'success:Faculty updated.';
}
} elseif ($action === 'delete') {
$conn->query("DELETE FROM faculty WHERE faculty_id='".$conn->real_escape_string($_POST['id'])."'");
$msg = 'success:Faculty deleted.';
}
}

$faculty = $conn->query("SELECT * FROM faculty ORDER BY faculty_id");
$depts   = $conn->query("SELECT dept_code FROM departments WHERE status='Active' ORDER BY dept_code");
$dept_list = [];
while ($d = $depts->fetch_assoc()) $dept_list[] = $d['dept_code'];

require_once '../../includes/header.php';
$msg_type = $msg_text = '';
if ($msg) { [$msg_type, $msg_text] = explode(':', $msg, 2); }
?>
<div class="page-header">
<div class="page-header-left">
<h1>Faculty Management</h1>
<div class="breadcrumb"><a href="#">Admin</a> / <a href="#">Faculty</a> / <span>List</span></div>
</div>
<button class="btn btn-primary" onclick="openModal('addFacultyModal')"><i class="fas fa-plus"></i> Add Faculty</button>
</div>
<?php if ($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<div class="table-card">
<div class="table-toolbar">
<div class="search-wrap"><i class="fas fa-search"></i>
<input type="text" id="fSearch" class="search-input" placeholder="Search by Name or Department..."></div>
</div>
<table id="fTable">
<thead><tr><th>ID</th><th>Name</th><th>Department</th><th>Position</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while ($f = $faculty->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($f['faculty_id']) ?></td>
<td><?= htmlspecialchars($f['last_name'].', '.$f['first_name']) ?></td>
<td><?= htmlspecialchars($f['dept_code']) ?></td>
<td><?= htmlspecialchars($f['position'] ?? '—') ?></td>
<td><span class="badge badge-<?= strtolower($f['status']) ?>"><?= $f['status'] ?></span></td>
<td>
<div class="action-btns">
<button class="action-btn view" onclick="viewFaculty(<?= htmlspecialchars(json_encode($f)) ?>)"><i class="fas fa-eye"></i></button>
<button class="action-btn edit" onclick="editFaculty(<?= htmlspecialchars(json_encode($f)) ?>)"><i class="fas fa-pencil-alt"></i></button>
<form method="POST" style="display:inline" onsubmit="confirmDelete(this);return false;">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $f['faculty_id'] ?>">
<button type="submit" class="action-btn del"><i class="fas fa-trash"></i></button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="modal-backdrop" id="addFacultyModal"><div class="modal">
<div class="modal-header"><span class="modal-title">Add Faculty</span><button class="modal-close" onclick="closeModal('addFacultyModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="add">
<div class="modal-body">
<div class="form-row">
<div class="form-group"><label class="form-label">Faculty ID *</label><input type="text" name="faculty_id" class="form-control" placeholder="F0001" required></div>
<div class="form-group"><label class="form-label">Department *</label>
<select name="dept_code" class="form-control" required>
<?php foreach ($dept_list as $d): ?><option><?= $d ?></option><?php endforeach; ?>
</select></div>
</div>
<div class="form-row">
<div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required></div>
<div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
</div>
<div class="form-row">
<div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
<div class="form-group"><label class="form-label">Position</label>
<select name="position" class="form-control">
<option>Instructor</option><option>Assistant Professor</option><option>Associate Professor</option><option>Professor</option>
</select></div>
</div>
<div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option>Active</option><option>Inactive</option></select></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('addFacultyModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
</div></form>
</div></div>

<div class="modal-backdrop" id="editFacultyModal"><div class="modal">
<div class="modal-header"><span class="modal-title">Edit Faculty</span><button class="modal-close" onclick="closeModal('editFacultyModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="old_id" id="ef_old">
<div class="modal-body">
<div class="form-row">
<div class="form-group"><label class="form-label">Faculty ID *</label><input type="text" name="faculty_id" id="ef_fid" class="form-control" required></div>
<div class="form-group"><label class="form-label">Department *</label>
<select name="dept_code" id="ef_dept" class="form-control">
<?php foreach ($dept_list as $d): ?><option><?= $d ?></option><?php endforeach; ?>
</select></div>
</div>
<div class="form-row">
<div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" id="ef_lname" class="form-control" required></div>
<div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" id="ef_fname" class="form-control" required></div>
</div>
<div class="form-row">
<div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="ef_email" class="form-control"></div>
<div class="form-group"><label class="form-label">Position</label>
<select name="position" id="ef_pos" class="form-control">
<option>Instructor</option><option>Assistant Professor</option><option>Associate Professor</option><option>Professor</option>
</select></div>
</div>
<div class="form-group"><label class="form-label">Status</label><select name="status" id="ef_status" class="form-control"><option>Active</option><option>Inactive</option></select></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('editFacultyModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
</div></form>
</div></div>

<div class="modal-backdrop" id="viewFacultyModal"><div class="modal">
<div class="modal-header"><span class="modal-title">Faculty Details</span><button class="modal-close" onclick="closeModal('viewFacultyModal')">&times;</button></div>
<div class="modal-body" id="viewFacultyBody"></div>
<div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('viewFacultyModal')">Close</button></div>
</div></div>

<script>
initSearch('fSearch','fTable',[1,2]);
function editFaculty(f){
document.getElementById('ef_old').value=f.faculty_id;
document.getElementById('ef_fid').value=f.faculty_id;
document.getElementById('ef_lname').value=f.last_name;
document.getElementById('ef_fname').value=f.first_name;
document.getElementById('ef_email').value=f.email||'';
document.getElementById('ef_dept').value=f.dept_code;
document.getElementById('ef_pos').value=f.position||'Instructor';
document.getElementById('ef_status').value=f.status;
openModal('editFacultyModal');
}
function viewFaculty(f){
document.getElementById('viewFacultyBody').innerHTML=`
<table style="width:100%;font-size:13px;">
<tr><td style="padding:8px 0;color:var(--gray-600);width:40%">Faculty ID</td><td><strong>${f.faculty_id}</strong></td></tr>
<tr><td style="padding:8px 0;color:var(--gray-600)">Name</td><td>${f.last_name}, ${f.first_name}</td></tr>
<tr><td style="padding:8px 0;color:var(--gray-600)">Email</td><td>${f.email||'—'}</td></tr>
<tr><td style="padding:8px 0;color:var(--gray-600)">Department</td><td>${f.dept_code}</td></tr>
<tr><td style="padding:8px 0;color:var(--gray-600)">Position</td><td>${f.position||'—'}</td></tr>
<tr><td style="padding:8px 0;color:var(--gray-600)">Status</td><td><span class="badge badge-${f.status.toLowerCase()}">${f.status}</span></td></tr>
</table>`;
openModal('viewFacultyModal');
}
</script>
<?php require_once '../../includes/footer.php'; ?>