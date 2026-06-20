<?php
session_start();
require_once '../config/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') header("Location: ../student/dashboard.php");
    elseif ($_SESSION['role'] === 'supervisor') header("Location: ../supervisor/dashboard.php");
    elseif ($_SESSION['role'] === 'admin') header("Location: ../admin/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    if(empty($email) || empty($password)) {
        $error = "Both fields are required!";
    } else {
        $sql = "SELECT id, full_name, password, role, is_approved FROM users WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check hashed password
            if (password_verify($password, $user['password'])) {
                // Check if account is approved (admin accounts are always approved)
                $isApproved = isset($user['is_approved']) ? $user['is_approved'] : 1;
                if ($isApproved == 0 && $user['role'] !== 'admin') {
                    $error = "Your account is pending admin approval.";
                } else {
                    // Password is correct, setup session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Log Login Action
                    logActivity($conn, $user['id'], "Login", "User logged into the system successfully.");
                    
                    header("Location: ../dashboard.php");
                    exit();
                }
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Thesis Management System</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-header">
        <h2>Welcome Back</h2>
        <p>Sign in to access your dashboard</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="e.g. admin@university.edu" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn-primary">Sign In</button>
    </form>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Register Here</a>
    </div>
</div>

</body>
</html>
