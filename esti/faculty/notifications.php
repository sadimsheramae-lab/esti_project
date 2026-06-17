<?php
require_once 'config.php';
requireFacultyLogin();
$page_title = 'Notifications';

$faculty = currentFaculty($conn);
$fid     = $faculty['faculty_id'];

if (isset($_POST['mark_all'])) {
$stmt = $conn->prepare("UPDATE faculty_notifications SET is_read=1 WHERE faculty_id=?");
$stmt->bind_param('s', $fid);
$stmt->execute();
header('Location: notifications.php');
exit();
}

if (isset($_GET['ajax_id']) && is_numeric($_GET['ajax_id'])) {
$nid  = (int)$_GET['ajax_id'];
$stmt = $conn->prepare("UPDATE faculty_notifications SET is_read=1 WHERE id=? AND faculty_id=?");
$stmt->bind_param('is', $nid, $fid);
$stmt->execute();
echo 'ok'; exit();
}

$filter = $_GET['filter'] ?? 'all';
$where  = "faculty_id = ?";
if ($filter === 'unread')       $where .= " AND is_read = 0";
elseif ($filter === 'grade')    $where .= " AND type = 'grade'";
elseif ($filter === 'announce') $where .= " AND type = 'announcement'";

$stmt = $conn->prepare("SELECT * FROM faculty_notifications WHERE $where ORDER BY created_at DESC");
$stmt->bind_param('s', $fid);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_unread = facultyUnread($conn, $fid);

require_once 'header.php';
?>

<div class="page-header">
<div class="page-header-left">
<h1>Notifications
<?php if ($total_unread > 0): ?>
<span style="font-size:14px;background:#dc3545;color:#fff;padding:2px 10px;border-radius:20px;margin-left:6px;"><?= $total_unread ?> new</span>
<?php endif; ?>
</h1>
<div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <span>Notifications</span></div>
</div>
<?php if ($total_unread > 0): ?>
<form method="POST">
<button type="submit" name="mark_all" class="btn btn-secondary"><i class="fas fa-check-double"></i> Mark All as Read</button>
</form>
<?php endif; ?>
</div>

<div class="sem-tabs" style="margin-bottom:16px;">
<a href="?filter=all"      class="sem-tab <?= $filter==='all'      ?'active':'' ?>">All</a>
<a href="?filter=unread"   class="sem-tab <?= $filter==='unread'   ?'active':'' ?>">Unread<?= $total_unread>0?' ('.$total_unread.')':'' ?></a>
<a href="?filter=grade"    class="sem-tab <?= $filter==='grade'    ?'active':'' ?>">Grades</a>
<a href="?filter=announce" class="sem-tab <?= $filter==='announce' ?'active':'' ?>">Announcements</a>
</div>

<div class="table-card">
<?php if (empty($notifs)): ?>
<div style="text-align:center;padding:50px;color:var(--gray-600);">
<i class="fas fa-bell-slash" style="font-size:40px;display:block;margin-bottom:12px;color:var(--gray-400);"></i>
<div style="font-size:15px;font-weight:600;">No notifications found.</div>
</div>
<?php else:
$icon_map = ['grade'=>'fas fa-star','announcement'=>'fas fa-bullhorn','general'=>'fas fa-info-circle','system'=>'fas fa-cog'];
$cls_map  = ['grade'=>'grade','announcement'=>'announce','general'=>'general','system'=>'system'];
foreach ($notifs as $n):
$ico = $icon_map[$n['type']] ?? 'fas fa-bell';
$cls = $cls_map[$n['type']] ?? 'general';
?>
<div class="notif-item <?= !$n['is_read']?'unread':'' ?>" data-id="<?= $n['id'] ?>">
<div class="notif-icon <?= $cls ?>"><i class="<?= $ico ?>"></i></div>
<div class="notif-body">
<div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
<div class="notif-msg" style="font-size:13px;margin-top:4px;color:var(--gray-800);"><?= htmlspecialchars($n['message']) ?></div>
<div style="font-size:11px;color:var(--gray-600);margin-top:5px;">
<i class="fas fa-clock"></i> <?= date('M j, Y · h:i A', strtotime($n['created_at'])) ?>
&nbsp;·&nbsp;
<span style="text-transform:capitalize;"><?= htmlspecialchars($n['type']) ?></span>
</div>
</div>
<?php if (!$n['is_read']): ?><div class="unread-dot" style="margin-top:10px;"></div><?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>

<script>

document.querySelectorAll('.notif-item[data-id]').forEach(item => {
item.addEventListener('click', () => {
const id = item.dataset.id;
fetch('?ajax_id=' + id);
item.classList.remove('unread');
item.querySelector('.unread-dot')?.remove();
const badge = document.querySelector('.notif-badge');
if (badge) {
const n = parseInt(badge.textContent) - 1;
n <= 0 ? badge.remove() : (badge.textContent = n);
}
});
});
</script>

<?php require_once 'footer.php'; ?>