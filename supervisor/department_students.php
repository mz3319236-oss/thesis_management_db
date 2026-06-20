<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$supervisor_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Get supervisor's department
$sup_q = $conn->query("SELECT d.name as dept_name, u.department_id FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id='$supervisor_id'");
$sup_info = $sup_q->fetch_assoc();
$dept_id = $sup_info['department_id'];
$dept_name = $sup_info['dept_name'] ?? 'Not Assigned';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    $student_id = intval($_POST['student_id']);
    $new_name = $conn->real_escape_string($_POST['full_name']);
    $new_email = $conn->real_escape_string($_POST['email']);
    $new_uid = $conn->real_escape_string($_POST['university_id']);
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $section = $conn->real_escape_string($_POST['section']);
    
    // Ensure the student actually belongs to this supervisor's department before updating!
    $check_q = $conn->query("SELECT id FROM users WHERE id='$student_id' AND department_id='$dept_id' AND role='student'");
    if ($check_q->num_rows > 0) {
        $conn->query("UPDATE users SET full_name='$new_name', email='$new_email', university_id='$new_uid', class_name='$class_name', section='$section' WHERE id='$student_id'");
        $success_msg = "Student details updated successfully.";
    } else {
        $error_msg = "Error: Invalid student or unauthorized action.";
    }
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
            <h2 style="color: var(--text-heading);">Students in <?php echo htmlspecialchars($dept_name); ?></h2>
            
            <!-- SEARCH BAR -->
            <form method="GET" action="" style="display: flex; gap: 10px; min-width: 350px;">
                <input type="text" name="search" placeholder="Search by name, ID or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); outline: none; font-size: 14px;">
                <button type="submit" class="btn-primary" style="padding: 0 20px;"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if($search): ?>
                    <a href="department_students.php" class="btn-primary" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; text-decoration: none; padding: 0 15px; border-radius: 8px;"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(isset($success_msg)): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if(isset($error_msg)): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

        <div class="data-table-card">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Thesis Topic (If Any)</th>
                            <th>Assigned Supervisor</th>
                            <th>Current Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($dept_id) {
                            $sql = "SELECT u.id, u.full_name, u.university_id, u.email, u.class_name, u.section, t.title, t.status, sup.full_name as supervisor_name 
                                    FROM users u 
                                    LEFT JOIN theses t ON u.id = t.student_id 
                                    LEFT JOIN users sup ON t.supervisor_id = sup.id
                                    WHERE u.role='student' AND u.department_id='$dept_id'";
                            
                            if($search) {
                                $sql .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.university_id LIKE '%$search%')";
                            }
                            
                            $sql .= " ORDER BY u.full_name ASC";
                            $result = $conn->query($sql);
                            if($result && $result->num_rows > 0):
                                while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                        <span style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($row['university_id']); ?> - <?php echo htmlspecialchars($row['email']); ?></span><br>
                                        <span style="font-size:12px; color:var(--text-heading); font-weight: 500;">
                                            Class: <span style="color:#38bdf8;"><?php echo htmlspecialchars($row['class_name'] ?? 'N/A'); ?></span> | 
                                            Sec: <span style="color:#38bdf8;"><?php echo htmlspecialchars($row['section'] ?? 'N/A'); ?></span>
                                        </span>
                                    </td>
                                    <td><?php echo $row['title'] ? htmlspecialchars(substr($row['title'], 0, 45)) . '...' : '<span style="color:#94a3b8; font-style:italic;">Not Submitted</span>'; ?></td>
                                    <td><?php echo $row['supervisor_name'] ? htmlspecialchars($row['supervisor_name']) : '<span style="color:#94a3b8; font-style:italic;">Not Assigned</span>'; ?></td>
                                    <td>
                                        <button type="button" class="btn-primary" style="padding: 5px 10px; font-size:11px; margin-bottom:5px;" onclick="document.getElementById('edit-modal-<?php echo $row['id']; ?>').style.display='block'">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit Details
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
                                                    <div class="form-group" style="margin-bottom:10px;">
                                                        <label>Class Name</label>
                                                        <input type="text" name="class_name" value="<?php echo htmlspecialchars($row['class_name'] ?? ''); ?>" placeholder="e.g. BSCS, MCS">
                                                    </div>
                                                    <div class="form-group" style="margin-bottom:10px;">
                                                        <label>Section</label>
                                                        <input type="text" name="section" value="<?php echo htmlspecialchars($row['section'] ?? ''); ?>" placeholder="e.g. A, B, Morning">
                                                    </div>
                                                    <div style="text-align:right; margin-top:20px;">
                                                        <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:10px;" onclick="document.getElementById('edit-modal-<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                                                        <button type="submit" name="update_student" class="btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <br>
                                        <?php if($row['status']): ?>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>" style="display:inline-block; margin-top:5px;">
                                                <?php echo str_replace('_', ' ', htmlspecialchars($row['status'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: rgba(148, 163, 184, 0.1); color: #94a3b8; display:inline-block; margin-top:5px;">Pending Submission</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center;">No students found in this department.</td></tr>
                            <?php endif; 
                        } else {
                            echo '<tr><td colspan="4" style="text-align: center;">You are not assigned to any department.</td></tr>';
                        }
                        ?>
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
