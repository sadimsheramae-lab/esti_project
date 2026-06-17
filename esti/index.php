<?php
require_once 'includes/config.php';
if (isset($_SESSION['admin_id'])) { header('Location: '.BASE_URL.'pages/admin/dashboard.php'); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_role'] = $user['role'];
        header('Location: '.BASE_URL.'pages/admin/dashboard.php'); exit();
    } else { $error = 'Invalid username or password.'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | ESTI CGMS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --ink:#13261c;
  --green-900:#0f2e22;
  --green-800:#163c2c;
  --green-700:#1f5238;
  --green-600:#2b6b48;
  --green-accent:#9fd8ab;
  --parchment:#f7f4ec;
  --parchment-dim:#ece6d6;
  --line:#dcd5c2;
  --gold:#c9a35a;
  --danger:#b3422f;
  --danger-bg:#fbe9e4;
}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{
  font-family:'Inter',sans-serif;
  color:var(--ink);
  min-height:100vh;
  display:flex;
  background:var(--parchment);
}

/* ===== Left panel — institutional identity ===== */
.brand-panel{
  position:relative;
  flex:0 0 44%;
  background:linear-gradient(165deg,var(--green-900) 0%,var(--green-800) 55%,var(--green-700) 100%);
  color:var(--parchment);
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  padding:64px 56px;
  overflow:hidden;
}
.brand-panel::before{
  content:"";
  position:absolute;
  inset:0;
  background-image:repeating-linear-gradient(
    to bottom,
    transparent 0,
    transparent 47px,
    rgba(247,244,236,0.05) 47px,
    rgba(247,244,236,0.05) 48px
  );
  pointer-events:none;
}
.brand-panel::after{
  content:"";
  position:absolute;
  top:-120px;
  right:-120px;
  width:340px;
  height:340px;
  border-radius:50%;
  background:radial-gradient(circle,rgba(159,216,171,0.16) 0%,transparent 70%);
}
.brand-top{position:relative;z-index:1;}
.brand-mark{
  display:flex;
  align-items:center;
  gap:14px;
  margin-bottom:54px;
}
.brand-mark img{
  width:46px;height:46px;border-radius:9px;object-fit:cover;
  border:1px solid rgba(247,244,236,0.25);
}
.brand-mark .mark-fallback{
  width:46px;height:46px;border-radius:9px;
  background:rgba(247,244,236,0.08);
  border:1px solid rgba(247,244,236,0.25);
  display:flex;align-items:center;justify-content:center;
  font-family:'Fraunces',serif;font-size:19px;font-weight:600;color:var(--green-accent);
}
.brand-mark .mark-text{
  font-family:'JetBrains Mono',monospace;
  font-size:11px;letter-spacing:0.14em;text-transform:uppercase;
  color:rgba(247,244,236,0.65);
  line-height:1.5;
}
.brand-mark .mark-text strong{display:block;color:var(--parchment);font-size:12.5px;letter-spacing:0.1em;}

.brand-heading{font-family:'Fraunces',serif;font-weight:500;font-size:38px;line-height:1.18;max-width:420px;}
.brand-heading em{font-style:italic;color:var(--green-accent);font-weight:400;}
.brand-sub{
  margin-top:18px;
  font-size:15px;
  line-height:1.7;
  color:rgba(247,244,236,0.72);
  max-width:380px;
}

.ledger-stats{
  position:relative;z-index:1;
  display:flex;
  gap:36px;
  margin-top:48px;
  padding-top:24px;
  border-top:1px solid rgba(247,244,236,0.18);
}
.ledger-stats .stat-label{
  font-family:'JetBrains Mono',monospace;
  font-size:10.5px;letter-spacing:0.1em;text-transform:uppercase;
  color:rgba(247,244,236,0.55);
  margin-bottom:6px;
}
.ledger-stats .stat-value{font-family:'Fraunces',serif;font-size:22px;color:var(--parchment);}

.brand-foot{
  position:relative;z-index:1;
  font-size:12.5px;
  color:rgba(247,244,236,0.5);
  display:flex;
  justify-content:space-between;
  align-items:baseline;
  border-top:1px solid rgba(247,244,236,0.15);
  padding-top:18px;
}
.brand-foot .tag{
  font-family:'JetBrains Mono',monospace;
  font-size:10.5px;letter-spacing:0.08em;
  color:var(--green-accent);
}

/* ===== Right panel — form ===== */
.form-panel{
  flex:1;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:48px;
  position:relative;
}
.form-wrap{width:100%;max-width:380px;}

.role-switch{
  display:flex;
  gap:4px;
  background:var(--parchment-dim);
  border:1px solid var(--line);
  border-radius:10px;
  padding:4px;
  margin-bottom:36px;
}
.role-switch a, .role-switch span{
  flex:1;
  text-align:center;
  padding:9px 0;
  font-size:13px;
  font-weight:600;
  border-radius:7px;
  text-decoration:none;
  color:#6b6354;
  transition:background .15s ease, color .15s ease;
}
.role-switch span.active{background:var(--green-700);color:var(--parchment);}
.role-switch a:hover{color:var(--ink);}

.form-eyebrow{
  font-family:'JetBrains Mono',monospace;
  font-size:11px;letter-spacing:0.12em;text-transform:uppercase;
  color:var(--green-700);
  margin-bottom:8px;
}
.form-title{font-family:'Fraunces',serif;font-size:30px;font-weight:500;color:var(--ink);margin-bottom:8px;}
.form-desc{font-size:14px;color:#736b5a;margin-bottom:30px;line-height:1.55;}

.alert{
  display:flex;align-items:flex-start;gap:9px;
  background:var(--danger-bg);
  border:1px solid #e3b3a6;
  color:var(--danger);
  padding:12px 14px;
  border-radius:9px;
  font-size:13.5px;
  margin-bottom:22px;
}
.alert i{margin-top:1px;}

.field{margin-bottom:20px;}
.field label{
  display:block;
  font-size:12.5px;
  font-weight:600;
  color:var(--ink);
  margin-bottom:7px;
  letter-spacing:0.01em;
}
.field-input{position:relative;}
.field-input i.icon-left{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:#9c9482;font-size:14px;
}
.field-input input{
  width:100%;
  padding:13px 14px 13px 40px;
  border:1.5px solid var(--line);
  border-radius:10px;
  font-size:14.5px;
  font-family:'Inter',sans-serif;
  background:#fff;
  color:var(--ink);
  transition:border-color .15s ease, box-shadow .15s ease;
}
.field-input input::placeholder{color:#b6ae9c;}
.field-input input:focus{
  outline:none;
  border-color:var(--green-600);
  box-shadow:0 0 0 3px rgba(43,107,72,0.14);
}
.field-input input#pwdInput{padding-right:42px;}
.toggle-pwd{
  position:absolute;right:14px;top:50%;transform:translateY(-50%);
  color:#9c9482;cursor:pointer;font-size:14px;
}

.row-between{
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:26px;font-size:13px;
}
.row-between label{display:flex;align-items:center;gap:7px;color:#6b6354;cursor:pointer;}
.row-between input[type="checkbox"]{width:14px;height:14px;accent-color:var(--green-700);}
.row-between a{color:var(--green-700);text-decoration:none;font-weight:600;}
.row-between a:hover{text-decoration:underline;}

.btn-login{
  width:100%;
  padding:14px 0;
  background:var(--green-700);
  color:var(--parchment);
  border:none;
  border-radius:10px;
  font-size:14.5px;
  font-weight:700;
  letter-spacing:0.03em;
  cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:9px;
  transition:background .15s ease, transform .1s ease;
}
.btn-login:hover{background:var(--green-800);}
.btn-login:active{transform:scale(0.99);}

.form-foot-note{
  margin-top:22px;
  text-align:center;
  font-size:12px;
  color:#9c9482;
}
.form-foot-note strong{color:var(--green-700);}

@media (max-width:860px){
  body{flex-direction:column;}
  .brand-panel{flex:none;padding:40px 32px;min-height:280px;}
  .ledger-stats{display:none;}
  .form-panel{padding:36px 24px;}
  .brand-foot{flex-direction:column;align-items:flex-start;gap:6px;}
}
</style>
</head>
<body class="login-page">

<div class="brand-panel">
    <div class="brand-top">
        <div class="brand-mark">
            <?php /* logo with graceful fallback initials if image missing */ ?>
            <img src="assets/images/logo.jpeg" alt="ESTI Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="mark-fallback" style="display:none;">E</div>
            <div class="mark-text"><strong>ESTI</strong>College Grading<br>Management System</div>
        </div>
        <h1 class="brand-heading">Every grade,<br><em>accounted for.</em></h1>
        <p class="brand-sub">Sign in to manage enrollment records, faculty access, and the institute's academic ledger.</p>
    </div>

    <div>
        <div class="ledger-stats">
            <div>
                <div class="stat-label">System</div>
                <div class="stat-value">CGMS</div>
            </div>
            <div>
                <div class="stat-label">Access level</div>
                <div class="stat-value">Admin</div>
            </div>
            <div>
                <div class="stat-label">Term</div>
                <div class="stat-value">A.Y. 2025–26</div>
            </div>
        </div>
        <div class="brand-foot">
            <span>&copy; 2024 Educational Systems Technological Institute</span>
            <span class="tag">SECURE PORTAL</span>
        </div>
    </div>
</div>

<div class="form-panel">
    <div class="form-wrap">
        <div class="role-switch">
            <span class="active">Admin</span>
            <a href="student/login.php">Student</a>
            <a href="faculty/login.php">Faculty</a>
        </div>

        <div class="form-eyebrow">Administrator access</div>
        <h2 class="form-title">Welcome back</h2>
        <p class="form-desc">Enter your credentials to open the admin dashboard.</p>

        <?php if ($error): ?>
        <div class="alert"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label for="username">Username</label>
                <div class="field-input">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" name="username" id="username" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="field">
                <label for="pwdInput">Password</label>
                <div class="field-input">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="password" id="pwdInput" placeholder="Enter your password" required>
                    <i class="fas fa-eye toggle-pwd" id="togglePwd"></i>
                </div>
            </div>
            <div class="row-between">
                <label><input type="checkbox" name="remember"> Remember me</label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Log in</button>
        </form>

        <p class="form-foot-note">Default password: <strong>password</strong></p>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click',function(){
    const p=document.getElementById('pwdInput');
    const isText=p.type==='text';
    p.type=isText?'password':'text';
    this.className=isText?'fas fa-eye toggle-pwd':'fas fa-eye-slash toggle-pwd';
});
</script>
</body>
</html>