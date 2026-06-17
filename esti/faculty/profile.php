<?php
require_once 'config.php';
requireFacultyLogin();
$page_title = 'My Profile';

$faculty = currentFaculty($conn);
$fid     = $faculty['faculty_id'];
$msg     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$pic_path = $faculty['profile_pic'];
if (!empty($_FILES['profile_pic']['name'])) {
$upload_dir = '../../uploads/faculty/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
$ext     = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif','webp'];
if (in_array($ext, $allowed) && $_FILES['profile_pic']['size'] < 3 * 1024 * 1024) {
$filename = 'faculty_' . $fid . '_' . time() . '.' . $ext;
$dest     = $upload_dir . $filename;
if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
$pic_path = 'uploads/faculty/' . $filename;
}
} else {
$msg = 'error:Invalid file type or file too large (max 3MB).';
}
}

if (!str_starts_with($msg, 'error:')) {
$contact  = trim($_POST['contact_no']      ?? '');
$address  = trim($_POST['address']          ?? '');
$bday     = $_POST['birthday']             ?? null;
$gender   = $_POST['gender']               ?? null;
$email    = trim($_POST['email']            ?? '');
$specialize = trim($_POST['specialization'] ?? '');

$stmt = $conn->prepare("
UPDATE faculty
SET contact_no=?, address=?, birthday=?, gender=?, email=?, profile_pic=?, specialization=?
WHERE faculty_id=?
");
$stmt->bind_param('ssssssss', $contact, $address, $bday, $gender, $email, $pic_path, $specialize, $fid);
if ($stmt->execute()) {
$msg     = 'success:Profile updated successfully.';
$faculty = currentFaculty($conn);
} else {
$msg = 'error:Failed to update profile.';
}
}
}

[$msg_type, $msg_text] = $msg ? explode(':', $msg, 2) : ['',''];

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>My Profile</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Profile</span></div>
</div>
</div>

<?php if ($msg_text): ?>
<div class="alert alert-<?= $msg_type ?>">
<i class="fas fa-<?= $msg_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
<?= htmlspecialchars($msg_text) ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<div class="profile-card">


<div class="profile-left">
<div class="profile-avatar">
<?php if (!empty($faculty['profile_pic'])): ?>
<img src="<?= BASE_URL . htmlspecialchars($faculty['profile_pic']) ?>" alt="Profile" id="profilePreview">
<?php else: ?>
<i class="fas fa-chalkboard-teacher" id="profilePreview"></i>
<?php endif; ?>
<label class="profile-avatar-upload" title="Change photo">
<i class="fas fa-camera"></i>
<input type="file" name="profile_pic" id="profilePicInput" accept="image/*" style="display:none;">
</label>
</div>

<div class="profile-name"><?= htmlspecialchars($faculty['last_name'].', '.$faculty['first_name']) ?></div>
<div class="profile-id"><?= htmlspecialchars($fid) ?></div>

<div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;justify-content:center;">
<span class="badge badge-active"><?= htmlspecialchars($faculty['position'] ?? 'Instructor') ?></span>
<span class="badge" style="background:#cce5ff;color:#004085;"><?= htmlspecialchars($faculty['dept_code']) ?></span>
</div>

<div style="width:100%;border-top:1px solid var(--gray-200);padding-top:14px;text-align:left;margin-top:4px;">
<div style="margin-bottom:10px;">
<div style="font-size:10px;color:var(--gray-600);font-weight:700;text-transform:uppercase;margin-bottom:2px;">Faculty ID</div>
<div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($fid) ?></div>
</div>
<div style="margin-bottom:10px;">
<div style="font-size:10px;color:var(--gray-600);font-weight:700;text-transform:uppercase;margin-bottom:2px;">Department</div>
<div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($faculty['dept_name'] ?? $faculty['dept_code']) ?></div>
</div>
<div style="margin-bottom:10px;">
<div style="font-size:10px;color:var(--gray-600);font-weight:700;text-transform:uppercase;margin-bottom:2px;">Position</div>
<div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($faculty['position'] ?? '—') ?></div>
</div>
<div>
<div style="font-size:10px;color:var(--gray-600);font-weight:700;text-transform:uppercase;margin-bottom:2px;">Status</div>
<span class="badge badge-<?= strtolower($faculty['status']) ?>"><?= $faculty['status'] ?></span>
</div>
</div>

<button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
<i class="fas fa-save"></i> Save Changes
</button>
<a href="change_password.php" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:6px;">
<i class="fas fa-lock"></i> Change Password
</a>
</div>


<div class="profile-right">

<div class="profile-section-title">Personal Information</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Last Name</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($faculty['last_name']) ?>" disabled style="background:var(--gray-100);">
</div>
<div class="form-group">
<label class="form-label">First Name</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($faculty['first_name']) ?>" disabled style="background:var(--gray-100);">
</div>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Gender</label>
<select name="gender" class="form-control">
<option value="">— Select —</option>
<option value="Male"   <?= ($faculty['gender']??'')==='Male'   ?'selected':'' ?>>Male</option>
<option value="Female" <?= ($faculty['gender']??'')==='Female' ?'selected':'' ?>>Female</option>
<option value="Other"  <?= ($faculty['gender']??'')==='Other'  ?'selected':'' ?>>Other</option>
</select>
</div>
<div class="form-group">
<label class="form-label">Birthday</label>
<input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($faculty['birthday'] ?? '') ?>">
</div>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Email Address</label>
<input type="email" name="email" class="form-control" value="<?= htmlspecialchars($faculty['email'] ?? '') ?>" placeholder="your@email.com">
</div>
<div class="form-group">
<label class="form-label">Contact Number</label>
<input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($faculty['contact_no'] ?? '') ?>" placeholder="09XXXXXXXXX">
</div>
</div>
<div class="form-group">
<label class="form-label">Home Address</label>
<textarea name="address" class="form-control" rows="2" placeholder="Street, Barangay, City, Province"><?= htmlspecialchars($faculty['address'] ?? '') ?></textarea>
</div>

<div class="profile-section-title" style="margin-top:8px;">Academic Information</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Department</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($faculty['dept_name'] ?? $faculty['dept_code']) ?>" disabled style="background:var(--gray-100);">
</div>
<div class="form-group">
<label class="form-label">Position</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($faculty['position'] ?? '') ?>" disabled style="background:var(--gray-100);">
</div>
</div>
<div class="form-group">
<label class="form-label">Area of Specialization</label>
<input type="text" name="specialization" class="form-control"
value="<?= htmlspecialchars($faculty['specialization'] ?? '') ?>"
placeholder="e.g. Software Engineering, Data Structures, Networking">
</div>
<div style="font-size:11px;color:var(--gray-600);margin-bottom:14px;">
<i class="fas fa-info-circle"></i> Name, department, and position are managed by the Administrator.
</div>

<div style="display:flex;justify-content:flex-end;gap:10px;padding-top:12px;border-top:1px solid var(--gray-200);">
<a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
</div>
</div>

</div>
</form>

<script>

document.getElementById('profilePicInput').addEventListener('change', function () {
const file = this.files[0];
if (!file) return;
const reader = new FileReader();
reader.onload = e => {
const el = document.getElementById('profilePreview');
if (el.tagName === 'IMG') {
el.src = e.target.result;
} else {
const img = document.createElement('img');
img.id  = 'profilePreview';
img.src = e.target.result;
el.replaceWith(img);
}
};
reader.readAsDataURL(file);
});
</script>

<?php require_once 'footer.php'; ?>