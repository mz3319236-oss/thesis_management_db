<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

$error = ''; $success = '';
$user_id = $_SESSION['user_id'];

// Fetch current user data
$query = $conn->query("SELECT * FROM users WHERE id='$user_id'");
$user = $query->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $password = $_POST['new_password'];
    
    // Update Name
    $sql = "UPDATE users SET full_name='$full_name' WHERE id='$user_id'";
    if ($conn->query($sql)) {
        $_SESSION['full_name'] = $full_name;
        $success = "Profile updated successfully!";
    }

    // Update Password if provided
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $error = "New password must be at least 8 characters.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $conn->query("UPDATE users SET password='$hashed' WHERE id='$user_id'");
            $success = "Profile and Password updated successfully!";
        }
    }
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Account Settings</h2>

        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="data-table-card" style="max-width: 600px;">
            <div class="card-header"><h3>Update Profile</h3></div>
            <form action="" method="POST" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email (Static)</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity:0.6; cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary" style="width:100%;">Save Changes</button>
            </form>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
