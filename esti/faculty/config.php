<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'esti_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die('Database connection failed: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL',    '/esti/');
define('FACULTY_URL', '/esti/faculty/');
define('SITE_NAME',   'ESTI College Grading Management System');

function requireFacultyLogin() {
if (!isset($_SESSION['faculty_id'])) {
header('Location: ' . FACULTY_URL . 'login.php');
exit();
}
}

function currentFaculty($conn) {
$id   = $_SESSION['faculty_id'];
$stmt = $conn->prepare("SELECT f.*, d.dept_name FROM faculty f LEFT JOIN departments d ON d.dept_code = f.dept_code WHERE f.faculty_id = ?");
$stmt->bind_param('s', $id);
$stmt->execute();
return $stmt->get_result()->fetch_assoc();
}

function gradeLetter($g) {
if ($g >= 90) return ['Excellent', '#1d5c3a'];
if ($g >= 80) return ['Very Good', '#4a90d9'];
if ($g >= 70) return ['Good',      '#f5a623'];
if ($g >= 60) return ['Satisfactory','#e8c844'];
return ['Failed', '#dc3545'];
}

function facultyUnread($conn, $faculty_id) {
$stmt = $conn->prepare("SELECT COUNT(*) c FROM faculty_notifications WHERE faculty_id=? AND is_read=0");
$stmt->bind_param('s', $faculty_id);
$stmt->execute();
return (int)$stmt->get_result()->fetch_assoc()['c'];
}
?>