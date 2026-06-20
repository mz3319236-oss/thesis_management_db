<?php
session_start();
require_once '../config/db_connect.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id='$user_id'");
}

// Redirect back to the page the user came from
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../auth/login.php';
header("Location: $referer");
exit();
