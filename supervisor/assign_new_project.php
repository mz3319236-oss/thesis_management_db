<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

$success = ''; $error = '';
$current_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get supervisor's department if not admin
if ($current_role == 'supervisor') {
    $dept_res = $conn->query("SELECT department_id FROM users WHERE id='$user_id'");
    $supervisor_dept = $dept_res->fetch_assoc()['department_id'];
    
    // Fetch students of the same department who DON'T have an active project
    $students = $conn->query("SELECT id, full_name, university_id FROM users WHERE role='student' AND department_id='$supervisor_dept' AND id NOT IN (SELECT student_id FROM theses WHERE status != 'rejected')");
    $supervisors = $conn->query("SELECT id, full_name FROM users WHERE role='supervisor' AND department_id='$supervisor_dept'");
} else {
    // Admin fetches all students who don't have an active project
    $students = $conn->query("SELECT id, full_name, university_id FROM users WHERE role='student' AND id NOT IN (SELECT student_id FROM theses WHERE status != 'rejected')");
    $supervisors = $conn->query("SELECT id, full_name FROM users WHERE role='supervisor'");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_project'])) {
    $student_id = intval($_POST['student_id']);
    $domain = $conn->real_escape_string($_POST['domain']);
    $supervisor_id = ($current_role == 'supervisor') ? $user_id : intval($_POST['supervisor_id']);

    // Check if student already has a project
    $check = $conn->query("SELECT id FROM theses WHERE student_id='$student_id' AND status != 'rejected'");
    if ($check->num_rows > 0) {
        $error = "This student already has an active project or proposal.";
    } else {
        // Use placeholders since student will fill these later
        $placeholder_title = "Pending Student Input";
        $placeholder_desc = "Student will provide the project description.";
        
        $sql = "INSERT INTO theses (student_id, supervisor_id, title, abstract, domain, document_path, status) 
                VALUES ('$student_id', '$supervisor_id', '$placeholder_title', '$placeholder_desc', '$domain', '', 'assigned_to_student')";
        if ($conn->query($sql)) {
            $success = "Student assigned successfully! The student will now fill in their project title and description.";
            
            // Push Notification to Student
            $message = "Your supervisor has assigned you to a new project slot. Please fill in your title and description.";
            $link = "../student/assigned_projects.php";
            $conn->query("INSERT INTO notifications (user_id, message, link) VALUES ('$student_id', '$message', '$link')");
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Assign Student to Project Slot</h2>

        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="data-table-card" style="max-width: 800px;">
            <div class="card-header"><h3>Assignment Details</h3></div>
            <form action="" method="POST" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Select Student</label>
                        <select name="student_id" required>
                            <option value="">Select a student...</option>
                            <?php while($s = $students->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?> (<?php echo $s['university_id']; ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Research Domain</label>
                        <select name="domain" required>
                            <option value="Artificial Intelligence">Artificial Intelligence</option>
                            <option value="Web Development">Web Development</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Information Security">Information Security</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <?php if($current_role == 'admin'): ?>
                <div class="form-group">
                    <label>Assign to Supervisor</label>
                    <select name="supervisor_id" required>
                        <option value="">Select a supervisor...</option>
                        <?php while($sup = $supervisors->fetch_assoc()): ?>
                            <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['full_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="alert alert-info" style="margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-info"></i> <strong>Note:</strong> The student will be responsible for defining the Project Title and Description after this assignment.
                </div>

                <button type="submit" name="assign_project" class="btn-primary" style="width:100%;">Assign Student</button>
            </form>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
