<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_departments.php");
    exit();
}

$dept_id = intval($_GET['id']);

// Get Department Details
$dept_query = $conn->query("SELECT * FROM departments WHERE id = '$dept_id'");
if ($dept_query->num_rows == 0) {
    echo "Department not found.";
    exit();
}
$dept = $dept_query->fetch_assoc();

// Handle Add Supervisor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_supervisor'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $university_id = $conn->real_escape_string($_POST['university_id']);
    
    // Default password 'supervisor123'
    $password = password_hash('supervisor123', PASSWORD_DEFAULT);
    
    $check_email = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($check_email->num_rows > 0) {
        $error_msg = "Error: A user with this Email already exists.";
    } else {
        $sql = "INSERT INTO users (full_name, email, university_id, password, role, department_id) 
                VALUES ('$full_name', '$email', '$university_id', '$password', 'supervisor', '$dept_id')";
        if($conn->query($sql) === TRUE) {
            $success_msg = "Supervisor successfully added! Default Password: supervisor123";
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

// Get Students in this department
$students = $conn->query("SELECT * FROM users WHERE department_id = '$dept_id' AND role = 'student'");

// Get Supervisors in this department
$supervisors = $conn->query("SELECT * FROM users WHERE department_id = '$dept_id' AND role = 'supervisor'");

// Get Classes in this department (Classes created by supervisors of this department)
$classes = $conn->query("
    SELECT c.*, u.full_name as supervisor_name 
    FROM classes c 
    JOIN users u ON c.supervisor_id = u.id 
    WHERE u.department_id = '$dept_id'
");

?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: var(--text-heading);">Department: <?php echo htmlspecialchars($dept['name']); ?></h2>
            <div>
                <a href="manage_departments.php" class="btn-primary" style="background:transparent; border:1px solid #38bdf8; color:#38bdf8; text-decoration:none; margin-right:10px;">&larr; Back to Departments</a>
                <button onclick="document.getElementById('add-supervisor-modal').style.display='block'" class="btn-primary"><i class="fa-solid fa-user-plus"></i> Add Supervisor Here</button>
            </div>
        </div>

        <?php if(isset($success_msg)): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if(isset($error_msg)): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

        <!-- Add Supervisor Modal -->
        <div id="add-supervisor-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:25px; width:90%; max-width:400px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 4px 15px rgba(0,0,0,0.3);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <h3 style="color:var(--text-heading); margin:0;">Add Supervisor</h3>
                    <button onclick="document.getElementById('add-supervisor-modal').style.display='none'" style="background:none; border:none; color:var(--text-main); font-size:18px; cursor:pointer;">&times;</button>
                </div>
                <form action="" method="POST">
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required placeholder="e.g. Dr. Jane Smith">
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Faculty ID / Employee Code</label>
                        <input type="text" name="university_id" required placeholder="e.g. FAC-001">
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="e.g. jane@faculty.edu">
                    </div>
                    <p style="font-size:12px; color:#94a3b8; margin-bottom:15px;"><i class="fa-solid fa-info-circle"></i> Default password: <b>supervisor123</b></p>
                    <button type="submit" name="add_supervisor" class="btn-primary" style="width:100%;">Create Supervisor</button>
                </form>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-bottom: 30px;">
            <!-- Supervisors List -->
            <div class="data-table-card">
                <div class="card-header"><h3><i class="fa-solid fa-user-tie"></i> Supervisors (<?php echo $supervisors->num_rows; ?>)</h3></div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($supervisors->num_rows > 0): ?>
                                <?php while($row = $supervisors->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center;">No supervisors found in this department.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Students List -->
            <div class="data-table-card">
                <div class="card-header"><h3><i class="fa-solid fa-graduation-cap"></i> Students (<?php echo $students->num_rows; ?>)</h3></div>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($students->num_rows > 0): ?>
                                <?php while($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align:center;">No students found in this department.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Classes List -->
        <div class="data-table-card">
            <div class="card-header"><h3><i class="fa-solid fa-chalkboard"></i> Classes in this Department</h3></div>
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Session & Section</th>
                            <th>Managed By (Supervisor)</th>
                            <th>Total Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($classes && $classes->num_rows > 0): ?>
                            <?php while($c = $classes->fetch_assoc()): 
                                $c_id = $c['id'];
                                $s_count = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE class_id='$c_id'")->fetch_assoc()['cnt'];
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['class_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['session_name'] . ' - ' . $c['section_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['supervisor_name']); ?></td>
                                <td><span class="status-badge" style="background:rgba(34,197,94,0.1); color:#22c55e;"><?php echo $s_count; ?> Students</span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No classes have been created in this department yet.</td></tr>
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
