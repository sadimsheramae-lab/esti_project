<?php
require_once '../../includes/config.php';
requireLogin();
$page_title = 'Subject Management';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$action = $_POST['action'] ?? '';
if ($action === 'add' || $action === 'edit') {
$code = trim($_POST['subject_code']);
$desc = trim($_POST['subject_desc']);
$units = (int)$_POST['units'];
$type  = $_POST['type'];
$status= $_POST['status'];
if ($action === 'add') {
$stmt = $conn->prepare("INSERT INTO subjects (subject_code,subject_desc,units,type,status) VALUES (?,?,?,?,?)");
$stmt->bind_param('ssiss', $code,$desc,$units,$type,$status);
$stmt->execute();
$msg = 'success:Subject added.';
} else {
$old = $_POST['old_code'];
$stmt = $conn->prepare("UPDATE subjects SET subject_code=?,subject_desc=?,units=?,type=?,status=? WHERE subject_code=?");
$stmt->bind_param('ssisss', $code,$desc,$units,$type,$status,$old);
$stmt->execute();
$msg = 'success:Subject updated.';
}
} elseif ($action === 'delete') {
$conn->query("DELETE FROM subjects WHERE subject_code='".$conn->real_escape_string($_POST['id'])."'");
$msg = 'success:Subject deleted.';
}
}

$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_code");
require_once '../../includes/header.php';
$msg_type = $msg_text = '';
if ($msg) { [$msg_type, $msg_text] = explode(':', $msg, 2); }
?>
<div class="page-header">
<div class="page-header-left">
<h1>Subject Management</h1>
<div class="breadcrumb"><a href="#">Admin</a> / <a href="#">Subject</a> / <span>List</span></div>
</div>
<button class="btn btn-primary" onclick="openModal('addSubjModal')"><i class="fas fa-plus"></i> Add Subject</button>
</div>
<?php if ($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<div class="table-card">
<div class="table-toolbar">
<div class="search-wrap"><i class="fas fa-search"></i>
<input type="text" id="subjSearch" class="search-input" placeholder="Search by Subject Code or Description..."></div>
</div>
<table id="subjTable">
<thead><tr><th>Subject Code</th><th>Subject Description</th><th>Units</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while ($s = $subjects->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($s['subject_code']) ?></td>
<td><?= htmlspecialchars($s['subject_desc']) ?></td>
<td><?= $s['units'] ?></td>
<td><?= htmlspecialchars($s['type']) ?></td>
<td><span class="badge badge-<?= strtolower($s['status']) ?>"><?= $s['status'] ?></span></td>
<td>
<div class="action-btns">
<button class="action-btn edit" onclick="editSubj(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="fas fa-pencil-alt"></i></button>
<form method="POST" style="display:inline" onsubmit="confirmDelete(this);return false;">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $s['subject_code'] ?>">
<button type="submit" class="action-btn del"><i class="fas fa-trash"></i></button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="modal-backdrop" id="addSubjModal"><div class="modal">
<div class="modal-header"><span class="modal-title">Add Subject</span><button class="modal-close" onclick="closeModal('addSubjModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="add">
<div class="modal-body">
<div class="form-row">
<div class="form-group"><label class="form-label">Subject Code *</label><input type="text" name="subject_code" class="form-control" required></div>
<div class="form-group"><label class="form-label">Units *</label><input type="number" name="units" class="form-control" value="3" min="1" max="6" required></div>
</div>
<div class="form-group"><label class="form-label">Subject Description *</label><input type="text" name="subject_desc" class="form-control" required></div>
<div class="form-row">
<div class="form-group"><label class="form-label">Type</label>
<select name="type" class="form-control"><option>Major</option><option>Minor</option><option>GE</option><option>PE</option><option>Elective</option></select></div>
<div class="form-group"><label class="form-label">Status</label>
<select name="status" class="form-control"><option>Active</option><option>Inactive</option></select></div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('addSubjModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
</div>
</form>
</div></div>

<div class="modal-backdrop" id="editSubjModal"><div class="modal">
<div class="modal-header"><span class="modal-title">Edit Subject</span><button class="modal-close" onclick="closeModal('editSubjModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="old_code" id="e_old_code">
<div class="modal-body">
<div class="form-row">
<div class="form-group"><label class="form-label">Subject Code *</label><input type="text" name="subject_code" id="e_code" class="form-control" required></div>
<div class="form-group"><label class="form-label">Units *</label><input type="number" name="units" id="e_units" class="form-control" required></div>
</div>
<div class="form-group"><label class="form-label">Subject Description *</label><input type="text" name="subject_desc" id="e_desc" class="form-control" required></div>
<div class="form-row">
<div class="form-group"><label class="form-label">Type</label>
<select name="type" id="e_type" class="form-control"><option>Major</option><option>Minor</option><option>GE</option><option>PE</option><option>Elective</option></select></div>
<div class="form-group"><label class="form-label">Status</label>
<select name="status" id="e_status" class="form-control"><option>Active</option><option>Inactive</option></select></div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" onclick="closeModal('editSubjModal')">Cancel</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
</div>
</form>
</div></div>

<script>
initSearch('subjSearch','subjTable',[0,1]);
function editSubj(s) {
document.getElementById('e_old_code').value=s.subject_code;
document.getElementById('e_code').value=s.subject_code;
document.getElementById('e_desc').value=s.subject_desc;
document.getElementById('e_units').value=s.units;
document.getElementById('e_type').value=s.type;
document.getElementById('e_status').value=s.status;
openModal('editSubjModal');
}
</script>
<?php require_once '../../includes/footer.php'; ?>