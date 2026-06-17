<?php
require_once 'config.php';
requireStudentLogin();

$sid = $_SESSION['student_id'];
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
$stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND student_id=?");
$stmt->bind_param('is', $id, $sid);
$stmt->execute();
echo json_encode(['ok' => true]);
} else {
echo json_encode(['ok' => false]);
}