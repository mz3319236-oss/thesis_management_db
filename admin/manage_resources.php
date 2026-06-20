<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';



$success = ''; $error = '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch user's department if supervisor
$user_dept_id = null;
if ($role === 'supervisor') {
    $q = $conn->query("SELECT department_id FROM users WHERE id='$user_id'");
    if ($q && $q->num_rows > 0) {
        $user_dept_id = $q->fetch_assoc()['department_id'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_res'])) {
    if ($_SESSION['role'] !== 'student') {
        $title = $conn->real_escape_string($_POST['title']);
        $target_role = $conn->real_escape_string($_POST['target_role']);
        
        $dept_id = "NULL";
        $class_id = "NULL";
        
        if ($role === 'supervisor' || $role === 'admin') {
            if (!empty($_POST['department_id'])) {
                $d_id = intval($_POST['department_id']);
                $dept_id = "'$d_id'";
            }
            if (!empty($_POST['class_id'])) {
                $c_id = intval($_POST['class_id']);
                $class_id = "'$c_id'";
            }
        }
        
        if(isset($_FILES['res_file']) && $_FILES['res_file']['error'] == 0) {
            $file_name = $_FILES['res_file']['name'];
            $file_tmp = $_FILES['res_file']['tmp_name'];
            $new_file_name = "RES_" . time() . "_" . $file_name;
            $upload_dir = '../uploads/resources/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $conn->query("INSERT INTO resources (title, file_path, user_role, department_id, class_id) VALUES ('$title', '$new_file_name', '$target_role', $dept_id, $class_id)");
                $success = "Resource uploaded successfully!";
            } else {
                $error = "File upload failed.";
            }
        }
    } else {
        $error = "You do not have permission to upload resources.";
    }
}

if (isset($_GET['delete_id'])) {
    if ($_SESSION['role'] !== 'student') {
        $id = intval($_GET['delete_id']);
        $conn->query("DELETE FROM resources WHERE id=$id");
        $success = "Resource removed.";
    } else {
        $error = "You do not have permission to delete resources.";
    }
}

$res_sql = "SELECT r.*, d.name as dept_name, c.class_name, c.section_name 
            FROM resources r 
            LEFT JOIN departments d ON r.department_id = d.id 
            LEFT JOIN classes c ON r.class_id = c.id ";

if ($role === 'student') {
    $stu_dept = $_SESSION['department_id'] ?? 0;
    $stu_class = $_SESSION['class_id'] ?? 0;
    
    // Fallback query to get class/dept if not in session
    if (!$stu_dept) {
        $st_q = $conn->query("SELECT department_id, class_id FROM users WHERE id='$user_id'");
        if ($st_q && $st_q->num_rows > 0) {
            $st = $st_q->fetch_assoc();
            $stu_dept = $st['department_id'];
            $stu_class = $st['class_id'];
        }
    }
    
    $res_sql .= " WHERE (r.user_role = 'all' OR r.user_role = 'student')
                  AND (r.department_id IS NULL OR r.department_id = '$stu_dept')
                  AND (r.class_id IS NULL OR r.class_id = '$stu_class') ";
} else if ($role === 'supervisor') {
    $res_sql .= " WHERE (r.user_role = 'all' OR r.user_role = 'supervisor') ";
}

$res_sql .= " ORDER BY r.created_at DESC";
$resources = $conn->query($res_sql);
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Manage Guidelines & Resources</h2>

        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: <?php echo ($_SESSION['role'] === 'student') ? '1fr' : '1fr 2fr'; ?>; gap:30px;">
            <?php if ($_SESSION['role'] !== 'student'): ?>
            <div class="data-table-card">
                <div class="card-header"><h3>Upload New</h3></div>
                <form action="" method="POST" enctype="multipart/form-data" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                    <div class="form-group">
                        <label>Document Title</label>
                        <input type="text" name="title" required placeholder="e.g. Thesis Formatting Guide">
                    </div>
                    <div class="form-group">
                        <label>Visible To Role</label>
                        <select name="target_role">
                            <option value="all">Everyone (Students & Supervisors)</option>
                            <option value="student">Students Only</option>
                            <option value="supervisor">Supervisors Only</option>
                        </select>
                    </div>

                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'supervisor'): ?>
                    <div class="form-group">
                        <label>Target Department (Optional)</label>
                        <select name="department_id">
                            <option value="">All Departments</option>
                            <?php 
                            $depts = $conn->query("SELECT id, name FROM departments");
                            while($d = $depts->fetch_assoc()): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Target Class (Optional)</label>
                        <select name="class_id">
                            <option value="">All Classes in Department</option>
                            <?php 
                            $classes = $conn->query("SELECT c.id, c.class_name, c.section_name, d.name as dept_name FROM classes c JOIN departments d ON c.department_id=d.id");
                            if ($classes && $classes->num_rows > 0):
                                while($c = $classes->fetch_assoc()): 
                                    $label = htmlspecialchars($c['class_name'] . ' - ' . $c['section_name']);
                                    if(isset($c['dept_name'])) $label = htmlspecialchars($c['dept_name']) . ': ' . $label;
                                ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $label; ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select File (PDF, DOCX, ZIP)</label>
                        <input type="file" name="res_file" required>
                    </div>
                    <button type="submit" name="upload_res" class="btn-primary" style="width:100%;">Upload Resource</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="data-table-card">
                <div class="card-header"><h3>Current Resources</h3></div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead><tr><th>Title</th><th>Targeting</th><?php if ($_SESSION['role'] !== 'student'): ?><th>Action</th><?php endif; ?></tr></thead>
                        <tbody>
                            <?php while($r = $resources->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <b style="color:var(--text-heading);"><?php echo htmlspecialchars($r['title']); ?></b><br>
                                    <a href="../uploads/resources/<?php echo $r['file_path']; ?>" target="_blank" style="font-size:12px; color:#38bdf8; text-decoration:none;"><i class="fa-solid fa-download"></i> Download</a>
                                </td>
                                <td>
                                    <span class="status-badge" style="background:rgba(168,85,247,0.1); color:#a855f7; margin-bottom: 4px; display:inline-block;"><?php echo strtoupper($r['user_role']); ?></span>
                                    <?php if($r['dept_name']): ?>
                                        <br><small style="color:#64748b; font-size:11px;">Dept: <?php echo htmlspecialchars($r['dept_name']); ?></small>
                                    <?php endif; ?>
                                    <?php if($r['class_name']): ?>
                                        <br><small style="color:#64748b; font-size:11px;">Class: <?php echo htmlspecialchars($r['class_name'] . ' - ' . $r['section_name']); ?></small>
                                    <?php endif; ?>
                                    <?php if(!$r['dept_name'] && !$r['class_name']): ?>
                                        <br><small style="color:#64748b; font-size:11px;">Global (All)</small>
                                    <?php endif; ?>
                                </td>
                                <?php if ($_SESSION['role'] !== 'student'): ?>
                                <td>
                                    <a href="manage_resources.php?delete_id=<?php echo $r['id']; ?>" style="color:#ef4444; text-decoration:none;" onclick="return confirm('Delete this resource?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
