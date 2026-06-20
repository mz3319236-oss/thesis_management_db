<?php
session_start();
require_once '../config/db_connect.php';

$error = '';
$success = '';

// Fetch departments for dropdown
$departments = [];
$dept_query = $conn->query("SELECT id, name FROM departments");
if($dept_query) {
    while($row = $dept_query->fetch_assoc()) {
        $departments[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $university_id = $conn->real_escape_string($_POST['university_id']);
    $role = $conn->real_escape_string($_POST['role']);
    $department_id = $conn->real_escape_string($_POST['department_id']);

    if(empty($full_name) || empty($email) || empty($password) || empty($university_id) || empty($role) || empty($department_id)) {
        $error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if email or university ID already exists
        $check = $conn->query("SELECT id FROM users WHERE email='$email' OR university_id='$university_id'");
        if ($check->num_rows > 0) {
            $error = "Email or University ID already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $sql = "INSERT INTO users (full_name, email, password, university_id, role, department_id) 
                    VALUES ('$full_name', '$email', '$hashed_password', '$university_id', '$role', '$department_id')";
            
            if ($conn->query($sql) === TRUE) {
                $success = "Registration submitted! Your account is pending admin approval. You will be able to login once approved.";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Thesis Management System</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-container register-container">
    <div class="auth-header">
        <h2>Create Account</h2>
        <p>Enter your details to join the portal</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="john@university.edu">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>University ID (Roll/Staff No)</label>
                <input type="text" name="university_id" required placeholder="UNI12345">
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department_id" required>
                    <option value="">Select Department</option>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="">Select your role</option>
                <option value="student">Student</option>
                <option value="supervisor">Supervisor</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="••••••••">
            </div>
        </div>

        <button type="submit" class="btn-primary">Register Now</button>
    </form>

    <div class="auth-footer">
        Already have an account? <a href="login.php">Sign In</a>
    </div>
</div>

</body>
</html>
