<?php
require_once 'config.php';
session_destroy();
header('Location: ' . FACULTY_URL . 'login.php');
exit();