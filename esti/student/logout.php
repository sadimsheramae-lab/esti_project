<?php
require_once 'config.php';
session_destroy();
header('Location: ' . STUDENT_URL . 'login.php');
exit();