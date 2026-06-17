<?php
require_once 'config.php';
requireStudentLogin();
$page_title = 'Notifications';

$student = currentStudent($conn);
$sid     = $student['id_number'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
$nid = (int)$_GET['id'];
$conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND student_id=?")->execute() || true;
$stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND student_id=?");
$stmt->bind_param('is', $nid, $sid);
$stmt->execute();
}

if (isset($_POST['mark_all'])) {
$conn->prepare("UPDATE notifications SET is_read=1 WHERE student_id=?")->bind_param('s',$sid);
$stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE student_id=?");
$stmt->bind_param('s', $sid);
$stmt->execute();
header('Location: notifications.php');
exit();
}

if (isset($_GET['ajax_id']) && is_numeric($_GET['ajax_id'])) {
$nid  = (int)$_GET['ajax_id'];
$stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND student_id=?");
$stmt->bind_param('is', $nid, $sid);
$stmt->execute();
echo 'ok'; exit();
}

$filter = $_GET['filter'] ?? 'all';
$where  = "student_id = ?";
if ($filter === 'unread') $where .= " AND is_read = 0";
if ($filter === 'grade')  $where .= " AND type = 'grade'";

$stmt = $conn->prepare("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC");
$stmt->bind_param('s', $sid);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_unread = unreadCount($conn, $sid);

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>Notifications <?php if ($total_unread > 0): ?><span style="font-size:14px;background:#dc3545;color:#fff;padding:2px 10px;border-radius:20px;margin-left:6px;"><?= $total_unread ?> new</span><?php endif; ?></h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Notifications</span></div>
</div>
<?php if ($total_unread > 0): ?>
<form method="POST">
<button type="submit" name="mark_all" class="btn btn-secondary"><i class="fas fa-check-double"></i> Mark All as Read</button>
</form>
<?php endif; ?>
</div>

<div class="sem-tabs" style="margin-bottom:16px;">
<a href="?filter=all"    class="sem-tab <?= $filter==='all'    ? 'active':'' ?>">All</a>
<a href="?filter=unread" class="sem-tab <?= $filter==='unread' ? 'active':'' ?>">Unread <?php if ($total_unread>0): ?>(<?= $total_unread ?>)<?php endif; ?></a>
<a href="?filter=grade"  class="sem-tab <?= $filter==='grade'  ? 'active':'' ?>">Grades</a>
</div>

<div class="table-card">
<?php if (empty($notifs)): ?>
<div style="text-align:center;padding:50px;color:var(--gray-600);">
<i class="fas fa-bell-slash" style="font-size:40px;display:block;margin-bottom:12px;color:var(--gray-400);"></i>
<div style="font-size:15px;font-weight:600;">No notifications found.</div>
</div>
<?php else:
$icon_map = ['grade'=>'fas fa-star','announcement'=>'fas fa-bullhorn','general'=>'fas fa-info-circle'];
$cls_map  = ['grade'=>'grade','announcement'=>'announce','general'=>'general'];
foreach ($notifs as $n):
$ico = $icon_map[$n['type']] ?? 'fas fa-bell';
$cls = $cls_map[$n['type']] ?? 'general';
$time_str = date('M j, Y · h:i A', strtotime($n['created_at']));
?>
<div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>" data-id="<?= $n['id'] ?>">
<div class="notif-icon <?= $cls ?>"><i class="<?= $ico ?>"></i></div>
<div class="notif-body">
<div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
<div class="notif-msg" style="font-size:13px;margin-top:4px;color:var(--gray-800);"><?= htmlspecialchars($n['message']) ?></div>
<div class="notif-time" style="font-size:11px;color:var(--gray-600);margin-top:5px;"><i class="fas fa-clock"></i> <?= $time_str ?></div>
</div>
<?php if (!$n['is_read']): ?>
<div class="unread-dot" style="margin-top:10px;"></div>
<?php else: ?>
<div style="width:8px;flex-shrink:0;"></div>
<?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>

<?php require_once 'footer.php'; ?>