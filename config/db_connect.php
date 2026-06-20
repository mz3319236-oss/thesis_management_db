<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$user = "root";
$pass = "";
$db_name = "thesis_management_db";

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Auto-sync permissions (Disabled to keep exactly 12 pages)
require_once __DIR__ . '/../includes/permission_handler.php';
// syncPermissions($conn);

// Dynamic path helper for cross-directory navigation
$current_dirname = basename(dirname($_SERVER['PHP_SELF']));
// Check if we are in the root directory by looking for a root-only file like dashboard.php
$baseUrl = (file_exists('config/db_connect.php')) ? './' : '../';


// Global Activity Logger
function logActivity($conn, $user_id, $action, $details = "") {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $action = $conn->real_escape_string($action);
    $details = $conn->real_escape_string($details);
    $conn->query("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES ('$user_id', '$action', '$details', '$ip')");
}
