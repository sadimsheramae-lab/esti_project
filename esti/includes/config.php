<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'esti_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
session_start();
}

function requireLogin() {
if (!isset($_SESSION['admin_id'])) {
header('Location: ' . BASE_URL . 'index.php');
exit();
}
}

define('BASE_URL', '/esti/');
define('SITE_NAME', 'ESTI College Grading Management System');
define('SHORT_NAME', 'ESTI');
?>