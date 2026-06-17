<?php
require_once 'config.php';
requireStudentLogin();
$page_title = 'My Profile';

$student = currentStudent($conn);
$sid     = $student['id_number'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$pic_path = $student['profile_pic'];
if (!empty($_FILES['profile_pic']['name'])) {
$upload_dir = '../../uploads/students/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
$ext      = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
$allowed  = ['jpg','jpeg','png','gif','webp'];
if (in_array($ext, $allowed) && $_FILES['profile_pic']['size'] < 3 * 1024 * 1024) {
$filename = 'student_' . $sid . '_' . time() . '.' . $ext;
$dest     = $upload_dir . $filename;
if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
$pic_path = 'uploads/students/' . $filename;
}
} else {
$msg = 'error:Invalid file type or file too large (max 3MB).';
}
}

if (!str_starts_with($msg, 'error:')) {
$contact = trim($_POST['contact_no'] ?? '');
$address = trim($_POST['address'] ?? '');
$bday    = $_POST['birthday'] ?? null;
$gender  = $_POST['gender'] ?? null;
$email   = trim($_POST['email'] ?? '');

$stmt = $conn->prepare("
UPDATE students SET contact_no=?, address=?, birthday=?, gender=?, email=?, profile_pic=?
WHERE id_number=?
");
$stmt->bind_param('sssssss', $contact, $address, $bday, $gender, $email, $pic_path, $sid);
if ($stmt->execute()) {
$msg = 'success:Profile updated successfully.';
$student = currentStudent($conn); // refresh
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
<div class="alert alert-<?= $msg_type ?>"><i class="fas fa-<?= $msg_type==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($msg_text) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<div class="profile-card">


<div class="profile-left">
<div class="profile-avatar">
<?php if (!empty($student['profile_pic'])): ?>
<img src="<?= BASE_URL . htmlspecialchars($student['profile_pic']) ?>" alt="Profile" id="profilePreview">
<?php else: ?>
<i class="fas fa-user-graduate" id="profilePreview"></i>
<?php endif; ?>
<label class="profile-avatar-upload" title="Change photo">
<i class="fas fa-camera"></i>
<input type="file" name="profile_pic" id="profilePicInput" accept="image/*" style="display:none;">
</label>
</div>
<div class="profile-name"><?= htmlspecialchars($student['last_name'].', '.$student['first_name']) ?></div>
<div class="profile-id"><?= htmlspecialchars($student['id_number']) ?></div>
<div style="margin-top:4px;">
<span class="badge badge-active"><?= htmlspecialchars($student['course']) ?></span>
<span class="badge" style="background:#e8d5f5;color:#6f42c1;margin-left:4px;"><?= htmlspecialchars($student['year_level']) ?></span>
</div>
<div style="font-size:11px;color:var(--gray-600);margin-top:8px;">
<i class="fas fa-circle" style="color:var(--green-btn);font-size:8px;"></i>
Status: <strong><?= $student['status'] ?></strong>
</div>
<div style="width:100%;border-top:1px solid var(--gray-200);padding-top:14px;text-align:left;">
<div class="profile-field">
<span class="profile-field-label">Student ID</span>
<span class="profile-field-val"><?= htmlspecialchars($student['id_number']) ?></span>
</div>
<div class="profile-field">
<span class="profile-field-label">Course</span>
<span class="profile-field-val"><?= htmlspecialchars($student['course']) ?></span>
</div>
<div class="profile-field">
<span class="profile-field-label">Year Level</span>
<span class="profile-field-val"><?= htmlspecialchars($student['year_level']) ?></span>
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
<input type="text" class="form-control" value="<?= htmlspecialchars($student['last_name']) ?>" disabled style="background:var(--gray-100);">
</div>
<div class="form-group">
<label class="form-label">First Name</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($student['first_name']) ?>" disabled style="background:var(--gray-100);">
</div>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Middle Name</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>" disabled style="background:var(--gray-100);">
</div>
<div class="form-group">
<label class="form-label">Gender</label>
<select name="gender" class="form-control">
<option value="">— Select —</option>
<option value="Male"   <?= ($student['gender']??'')==='Male'   ? 'selected':'' ?>>Male</option>
<option value="Female" <?= ($student['gender']??'')==='Female' ? 'selected':'' ?>>Female</option>
<option value="Other"  <?= ($student['gender']??'')==='Other'  ? 'selected':'' ?>>Other</option>
</select>
</div>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Birthday</label>
<input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($student['birthday'] ?? '') ?>">
</div>
<div class="form-group">
<label class="form-label">Email Address</label>
<input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email'] ?? '') ?>" placeholder="your@email.com">
</div>
</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Contact Number</label>
<input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($student['contact_no'] ?? '') ?>" placeholder="09XXXXXXXXX">
</div>
</div>

<div class="profile-section-title" style="margin-top:8px;">Address</div>
<div class="form-group">
<label class="form-label">Home Address</label>
<textarea name="address" class="form-control" rows="3" placeholder="Street, Barangay, City, Province"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
</div>

<div class="profile-section-title" style="margin-top:8px;">Academic Information</div>
<div class="form-row">
<div class="form-group">
<label class="form-label">Course</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($student['course']) ?>" disabled style="background:var(--gray-100);">
</div>
<div class="form-group">
<label class="form-label">Year Level</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($student['year_level']) ?>" disabled style="background:var(--gray-100);">
</div>
</div>
<div style="font-size:11px;color:var(--gray-600);margin-top:-8px;margin-bottom:14px;">
<i class="fas fa-info-circle"></i> Name, course, and year level are managed by the Registrar and cannot be changed here.
</div>

<div style="display:flex;justify-content:flex-end;gap:10px;padding-top:10px;border-top:1px solid var(--gray-200);">
<a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
</div>
</div>
</div>
</form>

<?php require_once 'footer.php'; ?>