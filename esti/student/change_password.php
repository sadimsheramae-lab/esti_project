<?php
require_once 'config.php';
requireStudentLogin();
$page_title = 'Change Password';

$student = currentStudent($conn);
$sid     = $student['id_number'];
$msg     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$current  = $_POST['current_password'] ?? '';
$new_pw   = $_POST['new_password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if (!password_verify($current, $student['password'])) {
$msg = 'error:Current password is incorrect.';
} elseif (strlen($new_pw) < 6) {
$msg = 'error:New password must be at least 6 characters.';
} elseif ($new_pw !== $confirm) {
$msg = 'error:New passwords do not match.';
} else {
$hashed = password_hash($new_pw, PASSWORD_DEFAULT);
$stmt   = $conn->prepare("UPDATE students SET password=? WHERE id_number=?");
$stmt->bind_param('ss', $hashed, $sid);
if ($stmt->execute()) {
$msg = 'success:Password changed successfully. Please use your new password next time you log in.';
} else {
$msg = 'error:Failed to update password. Please try again.';
}
}
}

[$msg_type, $msg_text] = $msg ? explode(':', $msg, 2) : ['',''];

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>Change Password</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Change Password</span></div>
</div>
</div>

<?php if ($msg_text): ?>
<div class="alert alert-<?= $msg_type ?>">
<i class="fas fa-<?= $msg_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
<?= htmlspecialchars($msg_text) ?>
</div>
<?php endif; ?>

<div style="max-width:480px;">
<div class="card pw-card">
<div style="text-align:center;padding:10px 0 18px;">
<div style="width:64px;height:64px;border-radius:50%;background:var(--green-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
<i class="fas fa-lock" style="font-size:26px;color:var(--green-main);"></i>
</div>
<div style="font-weight:700;font-size:16px;">Update Your Password</div>
<div style="font-size:12px;color:var(--gray-600);margin-top:4px;">Make sure your new password is strong and memorable.</div>
</div>

<form method="POST" action="" id="changePwForm">

<div class="form-group">
<label class="form-label">Current Password</label>
<div style="position:relative;">
<input type="password" name="current_password" id="cur_pw" class="form-control"
placeholder="Enter your current password" required
style="padding-right:38px;">
<i class="fas fa-eye" id="toggleCur"
style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#999;cursor:pointer;"></i>
</div>
</div>

<div class="form-group">
<label class="form-label">New Password</label>
<div style="position:relative;">
<input type="password" name="new_password" id="new_password" class="form-control"
placeholder="Enter new password (min. 6 characters)" required
style="padding-right:38px;">
<i class="fas fa-eye" id="toggleNew"
style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#999;cursor:pointer;"></i>
</div>

<div style="margin-top:6px;">
<div style="height:5px;background:var(--gray-200);border-radius:3px;overflow:hidden;">
<div id="strengthBar" style="height:100%;width:0;border-radius:3px;transition:width .3s,background .3s;"></div>
</div>
<div style="font-size:11px;color:var(--gray-600);margin-top:3px;">
Password strength: <span id="strengthLabel" style="font-weight:700;"></span>
</div>
</div>
</div>

<div class="form-group">
<label class="form-label">Confirm New Password</label>
<div style="position:relative;">
<input type="password" name="confirm_password" id="con_pw" class="form-control"
placeholder="Re-enter your new password" required
style="padding-right:38px;">
<i class="fas fa-eye" id="toggleCon"
style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#999;cursor:pointer;"></i>
</div>
<div id="matchMsg" style="font-size:11px;margin-top:3px;"></div>
</div>

<div style="background:var(--green-bg);border-radius:var(--radius);padding:12px 14px;font-size:12px;color:var(--green-text);margin-bottom:18px;">
<strong><i class="fas fa-shield-alt"></i> Password Tips:</strong>
<ul style="margin:6px 0 0 16px;line-height:1.8;">
<li>At least 6 characters long</li>
<li>Mix uppercase and lowercase letters</li>
<li>Include numbers and special characters</li>
<li>Avoid using your student ID as password</li>
</ul>
</div>

<div style="display:flex;gap:10px;">
<a href="profile.php" class="btn btn-secondary" style="flex:1;justify-content:center;"><i class="fas fa-arrow-left"></i> Back</a>
<button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;"><i class="fas fa-save"></i> Update Password</button>
</div>
</form>
</div>
</div>

<script>

function togglePw(inputId, iconId) {
const inp = document.getElementById(inputId);
const ico = document.getElementById(iconId);
const isText = inp.type === 'text';
inp.type = isText ? 'password' : 'text';
ico.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
ico.style.cssText = 'position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#999;cursor:pointer;';
}
document.getElementById('toggleCur').addEventListener('click', () => togglePw('cur_pw','toggleCur'));
document.getElementById('toggleNew').addEventListener('click', () => togglePw('new_password','toggleNew'));
document.getElementById('toggleCon').addEventListener('click', () => togglePw('con_pw','toggleCon'));

document.getElementById('con_pw').addEventListener('input', () => {
const np = document.getElementById('new_password').value;
const cp = document.getElementById('con_pw').value;
const mm = document.getElementById('matchMsg');
if (cp.length === 0) { mm.textContent = ''; return; }
if (np === cp) {
mm.innerHTML = '<span style="color:#239a57;"><i class="fas fa-check-circle"></i> Passwords match</span>';
} else {
mm.innerHTML = '<span style="color:#dc3545;"><i class="fas fa-times-circle"></i> Passwords do not match</span>';
}
});
</script>

<?php require_once 'footer.php'; ?>