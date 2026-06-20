<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check permissions for the current page
require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "auth/login.php");
    exit();
}

checkPagePermission($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Thesis Management</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main Style -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
