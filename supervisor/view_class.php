<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id'])) { header("Location: manage_classes.php"); exit(); }
$class_id = intval($_GET['id']);
$supervisor_id = $_SESSION['user_id'];

// Get class details
if ($_SESSION['role'] == 'admin') {
    $class_q = $conn->query("SELECT * FROM classes WHERE id='$class_id'");
} else {
    $class_q = $conn->query("SELECT * FROM classes WHERE id='$class_id' AND supervisor_id='$supervisor_id'");
}
if($class_q->num_rows == 0) {
    die("Error: Class not found or you do not have permission to view this class.");
}
$class = $class_q->fetch_assoc();
$class_sup_id = $class['supervisor_id'];

// Get department of the class's supervisor
$dept_q = $conn->query("SELECT department_id FROM users WHERE id='$class_sup_id'");
$dept_id = $dept_q->fetch_assoc()['department_id'];

// Handle add student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $university_id = $conn->real_escape_string($_POST['university_id']);
    
    // Default password 'student123'
    $password = password_hash('student123', PASSWORD_DEFAULT);
    
    $check_email = $conn->query("SELECT id FROM users WHERE email='$email' OR university_id='$university_id'");
    if($check_email->num_rows > 0) {
        $error_msg = "Error: Student with this Email or University ID already exists.";
    } else {
        $sql = "INSERT INTO users (full_name, email, university_id, password, role, department_id, class_id) 
                VALUES ('$full_name', '$email', '$university_id', '$password', 'student', '$dept_id', '$class_id')";
        if($conn->query($sql) === TRUE) {
            $success_msg = "Student successfully added to the class! Default Password: student123";
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

// Handle assign existing student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_existing'])) {
    $student_id = intval($_POST['existing_student_id']);
    if ($student_id > 0) {
        $conn->query("UPDATE users SET class_id='$class_id' WHERE id='$student_id'");
        $success_msg = "Existing student successfully assigned to this class!";
    }
}

// Get remove student logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_student'])) {
    $student_id = intval($_POST['student_id']);
    $conn->query("UPDATE users SET class_id=NULL WHERE id='$student_id'");
    $success_msg = "Student removed from the class.";
}

// Handle Update Student details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    $student_id = intval($_POST['student_id']);
    $new_name = $conn->real_escape_string($_POST['full_name']);
    $new_email = $conn->real_escape_string($_POST['email']);
    $new_uid = $conn->real_escape_string($_POST['university_id']);
    
    // Ensure the student actually belongs to this class/supervisor before updating
    $check_q = $conn->query("SELECT id FROM users WHERE id='$student_id' AND class_id='$class_id'");
    if ($check_q->num_rows > 0 || $_SESSION['role'] == 'admin') {
        $conn->query("UPDATE users SET full_name='$new_name', email='$new_email', university_id='$new_uid' WHERE id='$student_id'");
        $success_msg = "Student details updated successfully.";
    } else {
        $error_msg = "Error: Unauthorized action.";
    }
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Fetch class students
$stu_sql = "SELECT u.id, u.full_name, u.university_id, u.email, t.title, t.status 
            FROM users u 
            LEFT JOIN theses t ON u.id = t.student_id 
            WHERE u.class_id='$class_id' AND u.role='student'";

if($search) {
    $stu_sql .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.university_id LIKE '%$search%')";
}

$stu_sql .= " ORDER BY u.full_name ASC";
$students_q = $conn->query($stu_sql);

// Fetch unassigned students from supervisor's department who DON'T have an active project
$unassigned_q = $conn->query("SELECT id, full_name, university_id FROM users 
                              WHERE role='student' AND department_id='$dept_id' 
                              AND (class_id IS NULL OR class_id=0) 
                              AND id NOT IN (SELECT student_id FROM theses WHERE status != 'rejected')");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
            <h2 style="color: var(--text-heading);">Class: <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['session_name'] . ' - ' . $class['section_name'] . ')'); ?></h2>
            <div style="display:flex; gap:10px; align-items:center;">
                <!-- Search Bar -->
                <form method="GET" action="" style="display: flex; gap: 8px; margin-right: 15px;">
                    <input type="hidden" name="id" value="<?php echo $class_id; ?>">
                    <input type="text" name="search" placeholder="Search in class..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); outline: none; font-size: 13px; width: 200px;">
                    <button type="submit" class="btn-primary" style="padding: 0 15px; font-size: 13px;"><i class="fa-solid fa-magnifying-glass"></i></button>
                    <?php if($search): ?>
                        <a href="view_class.php?id=<?php echo $class_id; ?>" class="btn-primary" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; text-decoration: none; padding: 0 10px; border-radius: 6px;"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </form>

                <a href="<?php echo ($_SESSION['role'] == 'admin') ? '../admin/manage_users.php' : 'manage_classes.php'; ?>" class="btn-primary" style="background:transparent; border:1px solid #38bdf8; color:#38bdf8; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <button onclick="document.getElementById('add-student-modal').style.display='block'" class="btn-primary"><i class="fa-solid fa-user-plus"></i> Add Student</button>
            </div>
        </div>

        <?php if(isset($success_msg)): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if(isset($error_msg)): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

        <!-- Add Student Modal -->
        <div id="add-student-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; overflow-y:auto;">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:25px; width:90%; max-width:450px; margin: 50px auto; box-shadow:0 4px 15px rgba(0,0,0,0.3);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <h3 style="color:var(--text-heading); margin:0;">Add Student to Class</h3>
                    <button onclick="document.getElementById('add-student-modal').style.display='none'" style="background:none; border:none; color:var(--text-main); font-size:18px; cursor:pointer;">&times;</button>
                </div>
                
                <!-- Option 1: Assign Existing -->
                <h4 style="color:#38bdf8; margin-bottom:10px; font-size:14px;"><i class="fa-solid fa-link"></i> Option 1: Assign Existing Department Student</h4>
                <form action="" method="POST" style="margin-bottom: 25px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color);">
                    <div class="form-group" style="margin-bottom:10px;">
                        <select name="existing_student_id" required>
                            <option value="">Select an unassigned student...</option>
                            <?php if($unassigned_q && $unassigned_q->num_rows > 0): ?>
                                <?php while($u = $unassigned_q->fetch_assoc()): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo htmlspecialchars($u['university_id']); ?>)</option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No unassigned students found in your department.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_existing" class="btn-primary" style="width:100%; font-size:13px; padding:8px;" <?php echo ($unassigned_q && $unassigned_q->num_rows > 0) ? '' : 'disabled'; ?>>Assign Student</button>
                </form>

                <div style="text-align:center; margin: 15px 0; color:#64748b; font-size:12px; font-weight:bold;">OR</div>

                <!-- Option 2: Register New -->
                <h4 style="color:#22c55e; margin-bottom:10px; font-size:14px;"><i class="fa-solid fa-user-plus"></i> Option 2: Register & Add New Student</h4>
                <form action="" method="POST" style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color);">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="font-size:12px;">Full Name</label>
                        <input type="text" name="full_name" required placeholder="e.g. John Doe" style="padding:8px;">
                    </div>
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="font-size:12px;">University Roll/ID</label>
                        <input type="text" name="university_id" required placeholder="e.g. FA20-BCS-001" style="padding:8px;">
                    </div>
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="font-size:12px;">Email Address</label>
                        <input type="email" name="email" required placeholder="e.g. john@student.edu" style="padding:8px;">
                    </div>
                    <p style="font-size:11px; color:#94a3b8; margin-bottom:15px;"><i class="fa-solid fa-info-circle"></i> Default password: <b>student123</b></p>
                    <button type="submit" name="add_student" class="btn-primary" style="width:100%; font-size:13px; padding:8px;">Register & Add</button>
                </form>
            </div>
        </div>

        <div class="data-table-card">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Student Name & ID</th>
                            <th>Email Address</th>
                            <th>Thesis Title (If Any)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students_q->num_rows > 0): ?>
                            <?php while($row = $students_q->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                    <span style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($row['university_id']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['title'] ? htmlspecialchars(substr($row['title'], 0, 45)) . '...' : '<span style="color:#94a3b8; font-style:italic;">No Thesis Submitted</span>'; ?></td>
                                <td>
                                    <?php if($row['status']): ?>
                                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo str_replace('_', ' ', htmlspecialchars($row['status'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: rgba(148, 163, 184, 0.1); color: #94a3b8;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn-primary" style="padding: 5px 10px; font-size:11px; margin-right:5px;" onclick="document.getElementById('edit-modal-<?php echo $row['id']; ?>').style.display='block'">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>

                                    <!-- Edit Modal Inline -->
                                    <div id="edit-modal-<?php echo $row['id']; ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
                                        <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:25px; width:90%; max-width:400px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 4px 15px rgba(0,0,0,0.3); text-align:left;">
                                            <h3 style="color:var(--text-heading); margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Edit Student Details</h3>
                                            <form action="" method="POST">
                                                <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                                                <div class="form-group" style="margin-bottom:10px;">
                                                    <label>Full Name</label>
                                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                                                </div>
                                                <div class="form-group" style="margin-bottom:10px;">
                                                    <label>University ID</label>
                                                    <input type="text" name="university_id" value="<?php echo htmlspecialchars($row['university_id']); ?>" required>
                                                </div>
                                                <div class="form-group" style="margin-bottom:10px;">
                                                    <label>Email Address</label>
                                                    <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                                                </div>
                                                <div style="text-align:right; margin-top:20px;">
                                                    <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:10px;" onclick="document.getElementById('edit-modal-<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                                                    <button type="submit" name="update_student" class="btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this student from the class?');">
                                        <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="remove_student" class="btn-primary" style="background:rgba(239, 68, 68, 0.1); color:#ef4444; padding:5px 10px; font-size:11px; border:none;"><i class="fa-solid fa-trash"></i> Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding:20px; color:#64748b;">No students in this class yet. Click 'Add New Student' to begin.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
