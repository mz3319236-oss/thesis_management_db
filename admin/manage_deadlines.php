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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_deadline'])) {
    if ($_SESSION['role'] !== 'student') {
        $title = $conn->real_escape_string($_POST['title']);
        $deadline_date = $conn->real_escape_string($_POST['deadline_date']);
        $description = $conn->real_escape_string($_POST['description']);
        
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
        
        if ($conn->query("INSERT INTO deadlines (title, deadline_date, description, department_id, class_id) VALUES ('$title', '$deadline_date', '$description', $dept_id, $class_id)")) {
            $success = "Deadline added successfully!";
            // Push notification logic omitted here for simplicity, or we can just send to those who match.
            // Simplified:
            $conn->query("INSERT INTO notifications (user_id, message, link) SELECT id, CONCAT('New Deadline: ', '$title'), '../auth/deadlines.php' FROM users WHERE role IN ('student', 'supervisor')");
        } else {
            $error = "Error adding deadline: " . $conn->error;
        }
    } else {
        $error = "You do not have permission to add deadlines.";
    }
}

if (isset($_GET['delete_id'])) {
    if ($_SESSION['role'] !== 'student') {
        $id = intval($_GET['delete_id']);
        $conn->query("DELETE FROM deadlines WHERE id=$id");
        $success = "Deadline removed.";
    } else {
        $error = "You do not have permission to delete deadlines.";
    }
}

$dead_sql = "SELECT d.*, dept.name as dept_name, c.class_name, c.section_name 
             FROM deadlines d 
             LEFT JOIN departments dept ON d.department_id = dept.id 
             LEFT JOIN classes c ON d.class_id = c.id ";

if ($role === 'student') {
    $stu_dept = $_SESSION['department_id'] ?? 0;
    $stu_class = $_SESSION['class_id'] ?? 0;
    
    if (!$stu_dept) {
        $st_q = $conn->query("SELECT department_id, class_id FROM users WHERE id='$user_id'");
        if ($st_q && $st_q->num_rows > 0) {
            $st = $st_q->fetch_assoc();
            $stu_dept = $st['department_id'];
            $stu_class = $st['class_id'];
        }
    }
    
    $dead_sql .= " WHERE (d.department_id IS NULL OR d.department_id = '$stu_dept')
                   AND (d.class_id IS NULL OR d.class_id = '$stu_class') ";
} else if ($role === 'supervisor') {
    // Supervisor sees everything like admin
}

$dead_sql .= " ORDER BY d.deadline_date ASC";
$deadlines = $conn->query($dead_sql);
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Manage Deadlines & Key Dates</h2>

        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: <?php echo ($_SESSION['role'] === 'student') ? '1fr' : '1.2fr 2fr'; ?>; gap:30px;">
            <?php if ($_SESSION['role'] !== 'student'): ?>
            <div class="data-table-card">
                <div class="card-header"><h3>Add New Date</h3></div>
                <form action="" method="POST" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                    <div class="form-group">
                        <label>Event / Submission Name</label>
                        <input type="text" name="title" required placeholder="e.g. Final Thesis Submission">
                    </div>
                    <div class="form-group">
                        <label>Deadline Date</label>
                        <input type="date" name="deadline_date" required>
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
                            $classes = $conn->query("SELECT c.id, c.class_name, c.section_name, dept.name as dept_name FROM classes c JOIN departments dept ON c.department_id=dept.id");
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
                        <label>Description (Optional)</label>
                        <textarea name="description" placeholder="Brief instructions..."></textarea>
                    </div>
                    <button type="submit" name="add_deadline" class="btn-primary" style="width:100%;">Create Deadline</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="data-table-card">
                <div class="card-header"><h3>Upcoming Deadlines</h3></div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead><tr><th>Date</th><th>Event</th><?php if ($_SESSION['role'] !== 'student'): ?><th>Action</th><?php endif; ?></tr></thead>
                        <tbody>
                            <?php while($d = $deadlines->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $date = strtotime($d['deadline_date']); 
                                    $is_past = ($date < time());
                                    $color = $is_past ? '#ef4444' : '#38bdf8';
                                    ?>
                                    <b style="color:<?php echo $color; ?>;"><?php echo date('d M Y', $date); ?></b>
                                </td>
                                <td>
                                    <b style="color:var(--text-heading);"><?php echo htmlspecialchars($d['title']); ?></b>
                                    <p style="font-size:11px; margin-top:3px; color:#94a3b8;"><?php echo htmlspecialchars($d['description']); ?></p>
                                    <div style="margin-top: 5px;">
                                        <?php if($d['dept_name']): ?>
                                            <small style="color:#a855f7; font-size:11px;">Dept: <?php echo htmlspecialchars($d['dept_name']); ?></small>
                                        <?php endif; ?>
                                        <?php if($d['class_name']): ?>
                                            <br><small style="color:#a855f7; font-size:11px;">Class: <?php echo htmlspecialchars($d['class_name'] . ' - ' . $d['section_name']); ?></small>
                                        <?php endif; ?>
                                        <?php if(!$d['dept_name'] && !$d['class_name']): ?>
                                            <small style="color:#a855f7; font-size:11px;">Global (All)</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php if ($_SESSION['role'] !== 'student'): ?>
                                <td>
                                    <a href="?delete_id=<?php echo $d['id']; ?>" style="color:#ef4444;" onclick="return confirm('Delete this deadline?');"><i class="fa-solid fa-trash"></i></a>
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
