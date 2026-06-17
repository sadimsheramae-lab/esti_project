<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'esti_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL',    '/esti/');
define('STUDENT_URL', '/esti/student/');
define('SITE_NAME',   'ESTI College Grading Management System');

function requireStudentLogin() {
if (!isset($_SESSION['student_id'])) {
header('Location: ' . STUDENT_URL . 'login.php');
exit();
}
}

function currentStudent($conn) {
$id = $_SESSION['student_id'];
$st = $conn->prepare("SELECT * FROM students WHERE id_number = ?");
$st->bind_param('s', $id);
$st->execute();
return $st->get_result()->fetch_assoc();
}

function gradeLetter($g) {
if ($g >= 90) return ['Excellent','#1d5c3a'];
if ($g >= 80) return ['Very Good','#4a90d9'];
if ($g >= 70) return ['Good','#f5a623'];
if ($g >= 60) return ['Satisfactory','#e8c844'];
return ['Failed','#dc3545'];
}

function unreadCount($conn, $student_id) {
$r = $conn->prepare("SELECT COUNT(*) c FROM notifications WHERE student_id=? AND is_read=0");
$r->bind_param('s', $student_id);
$r->execute();
return (int)$r->get_result()->fetch_assoc()['c'];
}
?>