<?php
require_once '../../includes/config.php';
requireLogin();
$page_title = 'Department Management';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$action = $_POST['action'] ?? '';
if ($action === 'add' || $action === 'edit') {
$code  = trim($_POST['dept_code']);
$name  = trim($_POST['dept_name']);
$chair = trim($_POST['chairperson'] ?? '');
$status= $_POST['status'];
if ($action === 'add') {
$stmt = $conn->prepare("INSERT INTO departments (dept_code,dept_name,chairperson,status) VALUES (?,?,?,?)");
$stmt->bind_param('ssss', $code,$name,$chair,$status);
$stmt->execute();
$msg = 'success:Department added.';
} else {
$old = $_POST['old_code'];
$stmt = $conn->prepare("UPDATE departments SET dept_code=?,dept_name=?,chairperson=?,status=? WHERE dept_code=?");
$stmt->bind_param('sssss', $code,$name,$chair,$status,$old);
$stmt->execute();
$msg = 'success:Department updated.';
}
} elseif ($action === 'delete') {
$conn->query("DELETE FROM departments WHERE dept_code='".$conn->real_escape_string($_POST['id'])."'");
$msg = 'success:Department deleted.';
}
}

$departments = $conn->query("SELECT * FROM departments ORDER BY dept_code");
require_once '../../includes/header.php';
$msg_type = $msg_text = '';
if ($msg) { [$msg_type, $msg_text] = explode(':', $msg, 2); }
?>
<div class="page-header">
<div class="page-header-left">
<h1>Department Management</h1>
<div class="breadcrumb"><a href="#">Admin</a> / <a href="#">Department</a> / <span>List</span></div>
</div>
<button class="btn btn-primary" onclick="openModal('addDeptModal')"><i class="fas fa-plus"></i> Add Department</button>
</div>
<?php if ($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<div class="table-card">
<table id="deptTable">
<thead><tr><th>Department Code</th><th>Department Name</th><th>Chairperson</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while ($d = $departments->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($d['dept_code']) ?></td>
<td><?= htmlspecialchars($d['dept_name']) ?></td>
<td><?= htmlspecialchars($d['chairperson'] ?? '—') ?></td>
<td><span class="badge badge-<?= strtolower($d['status']) ?>"><?= $d['status'] ?></span></td>
<td>
<div class="action-btns">
<button class="action-btn edit" onclick="editDept(<?= htmlspecialchars(json_encode($d)) ?>)"><i class="fas fa-pencil-alt"></i></button>
<form method="POST" style="display:inline" onsubmit="confirmDelete(this);return false;">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $d['dept_code'] ?>">
<button type="submit" class="action-btn del"><i class="fas fa-trash"></i></button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="modal-backdrop" id="addDeptModal"><div class="modal">
<div class="modal-header"><span class="modal-title">Add Department</span><button class="modal-close" onclick="closeModal('addDeptModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="add">
<div class="modal-body">
<div class="form-row">
<div class="form-group"><label class="form-label">Department Code *</label><input type="text" name="dept_code" class="form-control" required></div>
<div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option>Active</option><option>Inactive</option></select></div>
</div>
<div class="form-group"><label class="form-label">Department Name *</label><input type="text" name="dept_name" class="form-control" required></div>
<div class="form-group"><label class="form-label">Chairperson</label><input type="text" name="chairperson" class="form-control" placeholder="e.g. Prof. Juan Dela Cruz"></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('addDeptModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
</div></form>
</div></div>

<div class="modal-backdrop" id="editDeptModal"><div class="modal">
<div class="modal-header"><span class="modal-title">Edit Department</span><button class="modal-close" onclick="closeModal('editDeptModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="old_code" id="ed_old">
<div class="modal-body">
<div class="form-row">
<div class="form-group"><label class="form-label">Department Code *</label><input type="text" name="dept_code" id="ed_code" class="form-control" required></div>
<div class="form-group"><label class="form-label">Status</label><select name="status" id="ed_status" class="form-control"><option>Active</option><option>Inactive</option></select></div>
</div>
<div class="form-group"><label class="form-label">Department Name *</label><input type="text" name="dept_name" id="ed_name" class="form-control" required></div>
<div class="form-group"><label class="form-label">Chairperson</label><input type="text" name="chairperson" id="ed_chair" class="form-control"></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('editDeptModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
</div></form>
</div></div>

<script>
function editDept(d) {
document.getElementById('ed_old').value=d.dept_code;
document.getElementById('ed_code').value=d.dept_code;
document.getElementById('ed_name').value=d.dept_name;
document.getElementById('ed_chair').value=d.chairperson||'';
document.getElementById('ed_status').value=d.status;
openModal('editDeptModal');
}
</script>
<?php require_once '../../includes/footer.php'; ?>